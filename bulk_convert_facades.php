<?php

/**
 * Bulk Facade Conversion Script
 *
 * Scans all modules in app/Modules/ and:
 * 1. Detects modules with service interfaces but missing facades
 * 2. Detects controllers still using constructor injection instead of facades
 * 3. Generates Facade class files
 * 4. Updates controllers to use facades (replaces $this->service-> with Facade::)
 * 5. Updates ServiceProviders to register missing interface-to-service bindings
 *
 * This is a development/transition tool — it handles the common patterns found
 * across ~107 converted modules. After all modules are converted, keep this
 * script for onboarding new modules.
 *
 * Usage:
 *   php bulk_convert_facades.php                # Dry-run (shows what would change)
 *   php bulk_convert_facades.php --apply         # Actually apply changes
 *   php bulk_convert_facades.php --module=Xxx    # Target a specific module only
 *   php bulk_convert_facades.php --force         # Overwrite existing facade files
 *   php bulk_convert_facades.php --no-backup     # Skip .bak file creation
 */

// --- Configuration ---
$modulesDir = __DIR__ . '/app/Modules';
$dryRun = true;
$targetModule = null;
$force = false;
$createBackup = true;

// Parse CLI args
foreach ($argv as $arg) {
    if ($arg === '--apply') {
        $dryRun = false;
    }
    if (str_starts_with($arg, '--module=')) {
        $targetModule = substr($arg, 9);
    }
    if ($arg === '--force') {
        $force = true;
    }
    if ($arg === '--no-backup') {
        $createBackup = false;
    }
}

// --- Stats ---
$stats = [
    'modules_scanned' => 0,
    'facades_generated' => 0,
    'controllers_updated' => 0,
    'providers_updated' => 0,
    'skipped_facade_exists' => 0,
    'skipped_no_interface' => 0,
    'skipped_no_controller' => 0,
    'skipped_controller_clean' => 0,
    'complex_constructors' => 0,
    'backups_created' => 0,
    'errors' => [],
];

// --- ANSI / Output Helpers ---

function info(string $msg): void
{
    echo "  \033[36mℹ\033[0m {$msg}\n";
}

function success(string $msg): void
{
    echo "  \033[32m✓\033[0m {$msg}\n";
}

function warning(string $msg): void
{
    echo "  \033[33m⚠\033[0m {$msg}\n";
}

function error(string $msg): void
{
    echo "  \033[31m✗\033[0m {$msg}\n";
}

function sectionHeader(string $msg): void
{
    $dashLen = mb_strlen($msg);
    echo "\n\033[1;34m{$msg}\033[0m\n";
    echo str_repeat('─', $dashLen) . "\n";
}

// --- File / Module Discovery Helpers ---

function getModuleName(string $moduleDir): string
{
    return basename($moduleDir);
}

function findServiceInterfaceFile(string $moduleDir): ?string
{
    $contractsDir = $moduleDir . '/Contracts';
    if (!is_dir($contractsDir)) {
        return null;
    }

    $files = glob($contractsDir . '/*ServiceInterface.php');
    return $files[0] ?? null;
}

function findServiceInterfaceClass(string $interfaceFile): ?string
{
    if (!$interfaceFile) {
        return null;
    }

    $content = file_get_contents($interfaceFile);
    if (preg_match('/^interface\s+(\w+ServiceInterface)/m', $content, $m)) {
        return $m[1];
    }

    return null;
}

function findFacadeFile(string $moduleDir): ?string
{
    $facadesDir = $moduleDir . '/Facades';
    if (!is_dir($facadesDir)) {
        return null;
    }

    $files = glob($facadesDir . '/*Facade.php');
    // Filter out RepositoryFacade files
    $files = array_filter($files, fn($f) => !str_contains($f, 'RepositoryFacade'));
    return !empty($files) ? reset($files) : null;
}

function findControllerFiles(string $moduleDir): array
{
    $controllersDir = $moduleDir . '/Controllers/Api';
    if (!is_dir($controllersDir)) {
        return [];
    }

    return glob($controllersDir . '/*Controller.php');
}

function findServiceProviderFile(string $moduleDir): ?string
{
    $providersDir = $moduleDir . '/Providers';
    if (!is_dir($providersDir)) {
        return null;
    }

    $files = glob($providersDir . '/*ServiceProvider.php');
    return $files[0] ?? null;
}

function getNamespaceFromFile(string $file): ?string
{
    $content = file_get_contents($file);
    if (preg_match('/^namespace\s+([^;]+);/m', $content, $m)) {
        return $m[1];
    }
    return null;
}

function getShortName(string $fqcn): string
{
    $parts = explode('\\', $fqcn);
    return end($parts);
}

function getFacadeClassName(string $interfaceShortName): string
{
    return str_replace('ServiceInterface', 'Facade', $interfaceShortName);
}

// --- Injection Detection ---

/**
 * Check if a controller uses constructor injection with a ServiceInterface.
 *
 * Returns: [varName, interfaceShortName, interfaceFqcn] or null.
 */
function detectConstructorInjection(string $file): ?array
{
    $content = file_get_contents($file);

    // Match the full constructor signature
    if (!preg_match('/function\s+__construct\s*\(([^)]*)\)/s', $content, $constructMatch)) {
        return null;
    }

    $params = $constructMatch[1];

    // Look for: protected ?SomeServiceInterface $varName (or without ?)
    if (!preg_match(
        '/protected\s+(\??)\s*(\w+ServiceInterface)\s+\$(\w+)/',
        $params,
        $paramMatch
    )) {
        return null;
    }

    $interfaceShortName = $paramMatch[2];
    $varName = $paramMatch[3];

    // Resolve the full FQCN from use statements
    $fqcn = resolveFqcn($content, $interfaceShortName);

    return [
        'varName' => $varName,
        'interfaceShortName' => $interfaceShortName,
        'interfaceFqcn' => $fqcn,
    ];
}

/**
 * Check if a controller has a __construct but no injection pattern matched.
 * Used to warn about complex constructors that may need manual review.
 */
function hasComplexConstructor(string $file): bool
{
    $content = file_get_contents($file);
    return (bool) preg_match('/function\s+__construct\s*\(/', $content);
}

/**
 * Resolve a short class name to its FQCN from use statements.
 */
function resolveFqcn(string $content, string $shortName): string
{
    if (preg_match('/^use\s+([^;]+\\\\' . preg_quote($shortName, '/') . ');/m', $content, $m)) {
        return $m[1];
    }
    // Fallback: assume Modules\{Module}\Contracts\{ShortName}
    if (preg_match('/^namespace\s+Modules\\(\w+)/m', $content, $nsMatch)) {
        return "Modules\\{$nsMatch[1]}\\Contracts\\{$shortName}";
    }
    return $shortName;
}

// --- Facade Generation ---

function generateFacadeContent(string $moduleFqcn, string $facadeClassName, string $interfaceFqcn): string
{
    $interfaceShort = getShortName($interfaceFqcn);

    return "<?php\n\n"
        . "namespace {$moduleFqcn}\\Facades;\n\n"
        . "use Illuminate\\Support\\Facades\\Facade;\n"
        . "use {$interfaceFqcn};\n\n"
        . "class {$facadeClassName} extends Facade\n"
        . "{\n"
        . "    protected static function getFacadeAccessor(): string\n"
        . "    {\n"
        . "        return {$interfaceShort}::class;\n"
        . "    }\n"
        . "}\n";
}

// --- Controller Transformation ---

/**
 * Update a controller file to use facade instead of constructor injection.
 *
 * Returns ['old' => string, 'new' => string] or null if nothing changed.
 */
function convertControllerToFacade(string $file, array $injection, string $facadeImportFqcn): ?array
{
    $content = file_get_contents($file);
    $oldContent = $content;

    $varName = $injection['varName'];
    $interfaceShortName = $injection['interfaceShortName'];
    $facadeShortName = getFacadeClassName($interfaceShortName);
    $escapedInterface = preg_quote($interfaceShortName, '/');
    $escapedVar = preg_quote($varName, '/');

    // 1. Remove the constructor injection parameter.
    //    Handle multi-line signatures by removing only the protected ... $var part.
    //    Pattern: the specific param: `protected ?InterfaceName $varName = null` or `protected InterfaceName $varName`
    $paramPattern = '/protected\s+\??\s*' . $escapedInterface . '\s+\$' . $escapedVar . '\s*(?:=\s*null)?\s*,?\s*/s';
    $content = preg_replace($paramPattern, '', $content, 1);

    // 2. If the constructor is now empty (no params left), remove it entirely.
    $content = preg_replace(
        '/public\s+function\s+__construct\s*\(\s*\)\s*\{\s*\}/s',
        '',
        $content
    );

    // 3. Remove the interface use statement
    $content = preg_replace(
        '/^use\s+[^;]*\\\\' . $escapedInterface . ';\s*$/m',
        '',
        $content
    );

    // 4. Add the facade use statement (after the LAST remaining use statement)
    $facadeImportLine = "use {$facadeImportFqcn};";
    if (!str_contains($content, $facadeImportLine)) {
        // Find the position of the last use statement and insert after it
        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $useMatches, PREG_SET_ORDER)) {
            $lastUse = end($useMatches);
            $lastUseLine = $lastUse[0];
            $content = str_replace($lastUseLine, $lastUseLine . "\n" . $facadeImportLine, $content);
        }
    }

    // 5. Replace $this->{varName}-> with {FacadeShortName}::
    $content = str_replace(
        '$this->' . $varName . '->',
        $facadeShortName . '::',
        $content
    );

    // 6. Remove any leftover empty constructor
    $content = preg_replace('/\s*public\s+function\s+__construct\s*\(\s*\)\s*\{\s*\}/s', '', $content);

    // Clean up triple+ blank lines
    $content = preg_replace("/\n{3,}/", "\n\n", $content);

    if ($content === $oldContent) {
        return null;
    }

    return ['old' => $oldContent, 'new' => $content];
}

// --- ServiceProvider Helpers ---

/**
 * Check if a ServiceProvider already has the binding for a service interface.
 */
function providerHasBinding(string $providerFile, string $interfaceFqcn): bool
{
    $content = file_get_contents($providerFile);
    $interfaceShort = getShortName($interfaceFqcn);

    // Look specifically for $this->app->bind() or $this->app->singleton() calls
    return (bool) preg_match(
        '/\$this->app->(?:bind|singleton)\s*\(\s*' . preg_quote($interfaceShort, '/') . '|' . preg_quote($interfaceFqcn, '/') . '/',
        $content
    );
}

/**
 * Add a missing service binding to the ServiceProvider.
 * Returns the new content, or null if unchanged / uncapable.
 */
function addBindingToProvider(string $providerFile, string $moduleDir, string $interfaceFqcn): ?string
{
    $content = file_get_contents($providerFile);
    $oldContent = $content;

    $interfaceShort = getShortName($interfaceFqcn);
    $serviceShort = str_replace('ServiceInterface', 'Service', $interfaceShort);
    $moduleName = getModuleName($moduleDir);
    $serviceFqcn = "Modules\\{$moduleName}\\Services\\{$serviceShort}";

    // Add missing use statements (at the end of the use block)
    $interfaceUseLine = "use {$interfaceFqcn};";
    $serviceUseLine = "use {$serviceFqcn};";

    $uses = [];
    if (preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $useMatches)) {
        $uses = $useMatches[0];
    }

    $lastUseLine = !empty($uses) ? end($uses) : null;

    if (!str_contains($content, $interfaceUseLine) && $lastUseLine) {
        $content = str_replace($lastUseLine, $lastUseLine . "\n" . $interfaceUseLine, $content);
    }
    if (!str_contains($content, $serviceUseLine) && $lastUseLine) {
        $content = str_replace($lastUseLine, $lastUseLine . "\n" . $serviceUseLine, $content);
    }

    // Add binding inside register() method, after the last existing binding
    $bindLine = "\$this->app->bind({$interfaceShort}::class, {$serviceShort}::class);";
    if (!str_contains($content, $bindLine)) {
        $content = preg_replace(
            '/(\s+)(\$this->app->(?:bind|singleton)\s*\([^;]+;\s*)$/m',
            '$0' . "\n" . '$1' . $bindLine,
            $content,
            1
        );
    }

    $content = preg_replace("/\n{3,}/", "\n\n", $content);

    return $content !== $oldContent ? $content : null;
}

// ===========================================================================
//  MAIN SCRIPT
// ===========================================================================

echo "\n";
sectionHeader('Bulk Facade Conversion Tool');
echo "  \033[90mMode:\033[0m " . ($dryRun ? 'DRY-RUN (use --apply to make changes)' : 'APPLY') . "\n";
if ($targetModule) {
    echo "  \033[90mTarget module:\033[0m {$targetModule}\n";
}
if ($force) {
    echo "  \033[90mForce overwrite:\033[0m yes\n";
}
echo "\n";

// Get all module directories
$moduleDirs = glob($modulesDir . '/*', GLOB_ONLYDIR);
sort($moduleDirs);

foreach ($moduleDirs as $moduleDir) {
    $moduleName = getModuleName($moduleDir);

    // Filter by target module if specified
    if ($targetModule && $moduleName !== $targetModule) {
        continue;
    }

    $stats['modules_scanned']++;

    // 1. Check for service interface
    $interfaceFile = findServiceInterfaceFile($moduleDir);
    if (!$interfaceFile) {
        $stats['skipped_no_interface']++;
        continue;
    }

    $interfaceShortName = findServiceInterfaceClass($interfaceFile);
    $interfaceNamespace = getNamespaceFromFile($interfaceFile);
    $interfaceFqcn = $interfaceNamespace . '\\' . $interfaceShortName;

    // 2. Check for existing facade
    $facadeFile = findFacadeFile($moduleDir);
    $facadeClassName = getFacadeClassName($interfaceShortName);
    $moduleNamespace = 'Modules\\' . $moduleName;

    // 3. Find and scan ALL controllers (even if facade exists — may need conversion)
    $controllerFiles = findControllerFiles($moduleDir);
    $controllersNeedingUpdate = [];

    foreach ($controllerFiles as $controllerFile) {
        $injection = detectConstructorInjection($controllerFile);
        if ($injection) {
            $controllersNeedingUpdate[] = [
                'file' => $controllerFile,
                'injection' => $injection,
            ];
        } elseif (hasComplexConstructor($controllerFile)) {
            // Has a __construct but no injection pattern matched — flag for manual review
            $stats['complex_constructors']++;
            if ($targetModule || !$facadeFile) {
                warning(basename($controllerFile) . ' has __construct() but no matching injection pattern — may need manual review');
            }
        }
    }

    // If no interface and no controllers need changes, and facade exists — skip
    if (empty($controllersNeedingUpdate) && $facadeFile) {
        $stats['skipped_controller_clean']++;
        continue;
    }

    // If no controllers at all and facade exists — skip
    if (empty($controllerFiles) && $facadeFile) {
        $stats['skipped_no_controller']++;
        continue;
    }

    // --- Report what we found ---
    echo "\n\033[1m{$moduleName}\033[0m";
    echo "  [Facade: \033[3" . ($facadeFile ? '2mEXISTS' : '1mMISSING') . "\033[0m]";
    echo "  [Controllers: " . count($controllerFiles) . "]";

    if (!empty($controllersNeedingUpdate)) {
        echo "  \033[33m" . count($controllersNeedingUpdate) . " need update\033[0m";
    }
    echo "\n";

    // --- 4. Facade Generation ---
    if (!$facadeFile || $force) {
        $facadesDir = $moduleDir . '/Facades';
        $facadeFilePath = $facadesDir . '/' . $facadeClassName . '.php';

        if (!is_dir($facadesDir)) {
            if (!$dryRun) {
                if (!mkdir($facadesDir, 0755, true) && !is_dir($facadesDir)) {
                    $stats['errors'][] = "Failed to create {$facadesDir}";
                    error("Failed to create Facades directory");
                    continue;
                }
            } else {
                info("Would create directory: Facades/");
            }
        }

        $facadeContent = generateFacadeContent($moduleNamespace, $facadeClassName, $interfaceFqcn);

        if ($dryRun) {
            info("Would create Facade: {$facadeClassName}.php");
            // Still count as pending action
            $stats['facades_generated']++;
        } else {
            file_put_contents($facadeFilePath, $facadeContent);
            success("Created Facade: {$facadeClassName}.php");
            $stats['facades_generated']++;
        }
    }

    // --- 5. Controller Updates ---
    foreach ($controllersNeedingUpdate as $item) {
        $controllerFile = $item['file'];
        $injection = $item['injection'];
        $controllerBasename = basename($controllerFile);

        $facadeImportFqcn = $moduleNamespace . '\\Facades\\' . $facadeClassName;

        $result = convertControllerToFacade($controllerFile, $injection, $facadeImportFqcn);

        if ($result === null) {
            warning("Could not convert {$controllerBasename} (pattern not matched)");
            continue;
        }

        if ($dryRun) {
            info("Would update {$controllerBasename}: replace \${$injection['varName']} with {$facadeClassName}::");
            $stats['controllers_updated']++;
        } else {
            // Backup original file
            if ($createBackup) {
                $backupFile = $controllerFile . '.bak';
                file_put_contents($backupFile, $result['old']);
                $stats['backups_created']++;
            }
            file_put_contents($controllerFile, $result['new']);
            success("Updated {$controllerBasename}");
            $stats['controllers_updated']++;
        }
    }

    // --- 6. ServiceProvider Update ---
    $providerFile = findServiceProviderFile($moduleDir);
    if ($providerFile && (!$facadeFile || $force)) {
        $hasBinding = providerHasBinding($providerFile, $interfaceFqcn);

        if (!$hasBinding) {
            $newContent = addBindingToProvider($providerFile, $moduleDir, $interfaceFqcn);
            if ($newContent !== null) {
                if ($dryRun) {
                    info("Would add binding in " . basename($providerFile));
                    $stats['providers_updated']++;
                } else {
                    file_put_contents($providerFile, $newContent);
                    success("Updated " . basename($providerFile));
                    $stats['providers_updated']++;
                }
            } else {
                warning("Could not add binding to " . basename($providerFile));
            }
        }
    }
}

// --- Summary ---
echo "\n";
sectionHeader('Summary');

echo "  \033[90mModules scanned:\033[0m         {$stats['modules_scanned']}\n";
echo "  \033[90mFacades generated:\033[0m       {$stats['facades_generated']}\n";
echo "  \033[90mControllers updated:\033[0m     {$stats['controllers_updated']}\n";
echo "  \033[90mProviders updated:\033[0m       {$stats['providers_updated']}\n";
echo "  \033[90mBackup .bak files:\033[0m       {$stats['backups_created']}\n";
echo "  \033[90mComplex constructors (flag):\033[0m {$stats['complex_constructors']}\n";
echo "  \033[90mSkipped (has facade):\033[0m    {$stats['skipped_facade_exists']}\n";
echo "  \033[90mSkipped (no interface):\033[0m  {$stats['skipped_no_interface']}\n";
echo "  \033[90mSkipped (no controller):\033[0m {$stats['skipped_no_controller']}\n";
echo "  \033[90mSkipped (controllers clean):\033[0m {$stats['skipped_controller_clean']}\n";

if (!empty($stats['errors'])) {
    echo "\n  \033[31mErrors (" . count($stats['errors']) . "):\033[0m";
    foreach ($stats['errors'] as $err) {
        echo "\n    - {$err}";
    }
    echo "\n";
}

if ($dryRun) {
    $pending = $stats['facades_generated'] + $stats['controllers_updated'] + $stats['providers_updated'];
    if ($pending > 0) {
        echo "\n  \033[33mRun with --apply to apply {$pending} pending change(s)\033[0m\n";
    } else {
        echo "\n  \033[32mAll modules are up to date! No changes needed.\033[0m\n";
    }
}

echo "\n";
