<?php

/**
 * Audit: Detect misplaced facade imports in controllers.
 *
 * The batch conversion script sometimes inserted `use XxxFacade;` inside the
 * class body (after `use ApiResponseTrait;`) instead of at the file-level
 * import section. This script detects those cases and also checks for any
 * leftover unused ServiceInterface imports.
 *
 * Usage:
 *   php audit_misplaced_imports.php
 */

$modulesDir = __DIR__ . '/app/Modules';
$issues = [];

$controllerDirs = glob($modulesDir . '/*/Controllers/Api', GLOB_ONLYDIR);

foreach ($controllerDirs as $dir) {
    $files = glob($dir . '/*Controller.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $basename = basename($file);
        $moduleName = basename(dirname(dirname(dirname($dir))));

        // Find the class declaration line
        if (!preg_match('/^class\s+\w+Controller\s+extends\s+Controller\s*$/m', $content, $classMatch, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $classPos = $classMatch[0][1];

        // Find all use statements
        preg_match_all('/^use\s+([^;]+);\s*$/m', $content, $useMatches, PREG_SET_ORDER);

        $misplacedFacadeImports = [];
        $remainingServiceInterfaceImports = [];
        $unusedServiceImports = [];

        foreach ($useMatches as $match) {
            $useLine = $match[0];
            $useTarget = $match[1];
            $usePos = strpos($content, $useLine);

            // Check if the use statement is inside the class body (after class declaration)
            if ($usePos > $classPos) {
                // Check if this is a Facade import (misplaced)
                if (str_ends_with($useTarget, 'Facade') && !str_contains($useTarget, 'RepositoryFacade')) {
                    $misplacedFacadeImports[] = trim($useLine);
                }
            }

            // Check for unused ServiceInterface imports (file-level)
            if ($usePos < $classPos && str_ends_with($useTarget, 'ServiceInterface')) {
                $shortName = substr($useTarget, strrpos($useTarget, '\\') + 1);
                // Check if the interface is actually used anywhere in the file
                // (excluding the use statement itself)
                $pattern = '/\b' . preg_quote($shortName, '/') . '\b/';
                preg_match_all($pattern, $content, $refMatches);
                $refCount = count($refMatches[0]);
                // The use statement itself contains the name once
                if ($refCount <= 1) {
                    $unusedServiceImports[] = trim($useLine);
                }
            }

            // Check for unused ServiceInterface imports (inside class body - definitely unused since injection was removed)
            if ($usePos > $classPos && str_ends_with($useTarget, 'ServiceInterface')) {
                $remainingServiceInterfaceImports[] = trim($useLine);
            }
        }

        if (!empty($misplacedFacadeImports) || !empty($remainingServiceInterfaceImports) || !empty($unusedServiceImports)) {
            $issues[] = [
                'module' => $moduleName,
                'file' => $basename,
                'path' => $file,
                'misplaced_facade_imports' => $misplacedFacadeImports,
                'unused_service_interface_imports' => array_merge($remainingServiceInterfaceImports, $unusedServiceImports),
            ];
        }
    }
}

// Print results
echo "\n=== Misplaced Import Audit ===\n\n";

if (empty($issues)) {
    echo "  \033[32m✓ No misplaced imports found! All controllers are clean.\033[0m\n\n";
    exit(0);
}

echo "  \033[33mFound " . count($issues) . " controller(s) with import issues:\033[0m\n\n";

foreach ($issues as $issue) {
    echo "  \033[1m{$issue['module']}/{$issue['file']}\033[0m\n";

    if (!empty($issue['misplaced_facade_imports'])) {
        echo "    \033[31m✗ Misplaced facade import(s) (inside class body):\033[0m\n";
        foreach ($issue['misplaced_facade_imports'] as $imp) {
            echo "      - {$imp}\n";
        }
    }

    if (!empty($issue['unused_service_interface_imports'])) {
        echo "    \033[31m✗ Unused ServiceInterface import(s):\033[0m\n";
        foreach ($issue['unused_service_interface_imports'] as $imp) {
            echo "      - {$imp}\n";
        }
    }

    echo "\n";
}

echo "  Total: " . count($issues) . " controller(s) affected\n\n";
