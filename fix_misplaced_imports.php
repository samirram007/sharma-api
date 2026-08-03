<?php

/**
 * Bulk Fix: Move misplaced facade imports from class body to file-level,
 * and remove unused ServiceInterface imports from controllers.
 *
 * Usage:
 *   php fix_misplaced_imports.php            # Dry-run (shows what would change)
 *   php fix_misplaced_imports.php --apply    # Actually make the changes
 *   php fix_misplaced_imports.php --module=Xxx  # Target a specific module
 */

$modulesDir = __DIR__ . '/app/Modules';
$dryRun = true;
$targetModule = null;

foreach ($argv as $arg) {
    if ($arg === '--apply') {
        $dryRun = false;
    }
    if (str_starts_with($arg, '--module=')) {
        $targetModule = substr($arg, 9);
    }
}

$stats = [
    'scanned' => 0,
    'fixed' => 0,
    'skipped_clean' => 0,
    'errors' => [],
];

$controllerDirs = glob($modulesDir . '/*/Controllers/Api', GLOB_ONLYDIR);

foreach ($controllerDirs as $dir) {
    $moduleName = basename(dirname(dirname(dirname($dir))));
    if ($targetModule && $moduleName !== $targetModule) {
        continue;
    }

    $files = glob($dir . '/*Controller.php');
    foreach ($files as $file) {
        $stats['scanned']++;
        $content = file_get_contents($file);
        $original = $content;
        $basename = basename($file);

        // Find class declaration position
        if (!preg_match('/^class\s+\w+Controller\s+extends\s+Controller\s*$/m', $content, $classMatch, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $classPos = $classMatch[0][1];

        // Collect all use statements with their positions
        preg_match_all('/^(use\s+[^;]+;\s*)$/m', $content, $useMatches, PREG_SET_ORDER);

        $misplacedFacades = [];   // facade imports inside class body
        $unusedServiceIfaces = []; // service interface imports (anywhere) that are unused
        $lastFileLevelUse = null;  // the last use statement before class declaration
        $needsFix = false;

        // Track which files have had their use statements modified
        // We need to process by position since string positions shift after edits

        // First pass: identify what needs to change
        $fileLevelUseLines = [];
        $classBodyUseLines = [];

        foreach ($useMatches as $match) {
            $useLine = $match[0];
            $useTarget = $match[1];
            $usePos = strpos($content, $useLine);

            if ($usePos === false) {
                continue;
            }

            if ($usePos < $classPos) {
                // File-level use statement
                $fileLevelUseLines[] = $useLine;
                $lastFileLevelUse = $useLine;
            } else {
                // Inside class body
                $classBodyUseLines[] = ['line' => $useLine, 'target' => $useTarget];
            }
        }

        // Check each class-body use statement
        foreach ($classBodyUseLines as $item) {
            $useLine = $item['line'];
            $useTarget = $item['target'];

            // Misplaced facade import
            if (str_ends_with($useTarget, 'Facade') && !str_contains($useTarget, 'RepositoryFacade')) {
                $misplacedFacades[] = trim($useLine);
            }

            // Unused service interface (inside class body = definitely unused since injection was removed)
            if (str_ends_with($useTarget, 'ServiceInterface')) {
                $unusedServiceIfaces[] = trim($useLine);
            }
        }

        // Check file-level use statements for unused ServiceInterfaces
        $properUseLines = [];
        foreach ($fileLevelUseLines as $useLine) {
            preg_match('/^use\s+([^;]+);/', $useLine, $m);
            $target = $m[1] ?? '';

            if (str_ends_with($target, 'ServiceInterface')) {
                $shortName = substr($target, strrpos($target, '\\') + 1);
                // Check if it's actually used in code (not just in the use statement itself)
                $count = substr_count($content, $shortName);
                if ($count <= 1 && !str_contains($target, 'Repository')) {
                    $unusedServiceIfaces[] = trim($useLine);
                    continue; // Don't keep it
                }
            }
            $properUseLines[] = $useLine;
        }

        // Add misplaced facades to proper use lines
        foreach ($misplacedFacades as $facadeImport) {
            $shortName = substr($facadeImport, 4, -1); // "use Xxx;" → "Xxx"
            $shortName = substr($shortName, strrpos($shortName, '\\') + 1);
            $alreadyExists = false;
            foreach ($properUseLines as $existing) {
                if (str_contains($existing, $shortName)) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (!$alreadyExists) {
                $properUseLines[] = $facadeImport;
            }
        }

        // Only proceed if there are changes
        $hasMisplacedOrUnused = !empty($misplacedFacades) || !empty($unusedServiceIfaces);
        if (!$hasMisplacedOrUnused) {
            $stats['skipped_clean']++;
            continue;
        }

        // --- Report what we found ---
        echo "\n\033[1m{$moduleName}/{$basename}\033[0m\n";

        if ($dryRun) {
            foreach ($misplacedFacades as $imp) {
                echo "  \033[33m⚠ Would move to file-level:\033[0m {$imp}\n";
            }
            foreach ($unusedServiceIfaces as $imp) {
                echo "  \033[31m✗ Would remove (unused):\033[0m {$imp}\n";
            }
            $stats['fixed']++;
            continue;
        }

        // --- Actually apply the fix ---
        // Remove all class-body use statements (facades and service interfaces)
        foreach ($classBodyUseLines as $item) {
            $content = str_replace("\n" . $item['line'], '', $content);
            $content = str_replace($item['line'] . "\n", '', $content);
            $content = str_replace($item['line'], '', $content);
        }

        // Remove file-level unused service interface imports
        foreach ($unusedServiceIfaces as $unusedLine) {
            // Check if it's file-level (not already removed as class-body)
            if (str_contains($content, $unusedLine)) {
                $content = str_replace("\n" . $unusedLine, '', $content);
                $content = str_replace($unusedLine . "\n", '', $content);
                $content = str_replace($unusedLine, '', $content);
            }
        }

        // Re-find the last file-level use statement after removals
        preg_match_all('/^(use\s+[^;]+;\s*)$/m', $content, $remainingUses, PREG_SET_ORDER);
        $lastRemainingUse = null;
        foreach ($remainingUses as $rm) {
            $lastRemainingUse = $rm[0];
        }

        // Add the facade imports after the last remaining file-level use
        if ($lastRemainingUse && !empty($misplacedFacades)) {
            $insertStr = "\n";
            foreach ($misplacedFacades as $facadeImport) {
                $shortName = substr($facadeImport, 4, -1);
                $shortName = substr($shortName, strrpos($shortName, '\\') + 1);
                if (!str_contains($content, $shortName)) {
                    $insertStr .= $facadeImport . "\n";
                }
            }
            if (trim($insertStr) !== '') {
                $content = str_replace($lastRemainingUse, $lastRemainingUse . $insertStr, $content);
            }
        }

        // Clean up triple+ blank lines
        $content = preg_replace("/\n{3,}/", "\n\n", $content);

        if ($content !== $original) {
            file_put_contents($file, $content);
            $successCount = count($misplacedFacades) + count($unusedServiceIfaces);
            echo "  \033[32m✓ Fixed {$successCount} import issue(s)\033[0m\n";
            $stats['fixed']++;
        } else {
            echo "  \033[33m⚠ No changes needed (pattern not matched)\033[0m\n";
            $stats['skipped_clean']++;
        }
    }
}

// --- Summary ---
echo "\n=== Summary ===\n";
echo "  Scanned: {$stats['scanned']} controller(s)\n";
echo "  Fixed:   {$stats['fixed']} controller(s)\n";
echo "  Clean:   {$stats['skipped_clean']} controller(s)\n";

if (!empty($stats['errors'])) {
    echo "  Errors:  " . count($stats['errors']) . "\n";
    foreach ($stats['errors'] as $err) {
        echo "    - {$err}\n";
    }
}

if ($dryRun) {
    echo "\n  \033[33mRun with --apply to apply fixes to {$stats['fixed']} controller(s)\033[0m\n";
}

echo "\n";
