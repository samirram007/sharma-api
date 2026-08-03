<?php

/**
 * Find custom methods in BaseService children that use direct model queries
 * instead of going through the repository facade.
 *
 * Reports methods with patterns like:
 * - Model::with(...)->where(...)->get()
 * - Model::where(...)->orderBy(...)->get()
 * - Model::with(...)->get()
 */
$serviceFiles = glob(__DIR__.'/app/Modules/*/Services/*.php');

$directQueryPatterns = [
    // Model::with(...)->where(...)->get()
    '/\w+::with\(.*?\)\s*->\s*where\(.*?\)\s*->\s*get\(\)/s',
    // Model::where(...)->orderBy(...)->get()
    '/\w+::where\(.*?\)\s*->\s*orderBy\(.*?\)\s*->\s*get\(\)/s',
    // Model::with(...)->orderBy(...)->get()
    '/\w+::with\(.*?\)\s*->\s*orderBy\(.*?\)\s*->\s*get\(\)/s',
    // Model::with(...)->get()
    '/\w+::with\(.*?\)\s*->\s*get\(\)/s',
    // Model::where(...)->get()
    '/\w+::where\(.*?\)\s*->\s*get\(\)/s',
    // Model::orderBy(...)->get()
    '/\w+::orderBy\(.*?\)\s*->\s*get\(\)/s',
    // Model::all()
    '/\w+::all\(\)/s',
    // ->fresh() patterns
    '/->fresh\(/s',
];

echo "Scanning for custom methods with direct model queries...\n\n";

foreach ($serviceFiles as $file) {
    $content = file_get_contents($file);
    $relativePath = str_replace(__DIR__.'/', '', $file);
    $lines = file($file);

    // Check if this service extends BaseService
    if (! preg_match('/extends\s+BaseService\b/', $content)) {
        continue;
    }

    // Check if it has a repositoryFacadeClass
    if (! preg_match('/repositoryFacadeClass\s*=\s*\w+RepositoryFacade/', $content)) {
        continue;
    }

    // Extract module name
    if (! preg_match('/namespace\s+Modules\\\\(\w+)\\\\Services/', $content, $m)) {
        continue;
    }
    $moduleName = $m[1];

    // Get the facade class name
    preg_match('/repositoryFacadeClass\s*=\s*(\w+RepositoryFacade)::class/', $content, $f);
    $facadeClass = $f[1] ?? 'Unknown';

    $linesWithIssues = 0;

    // Check each line for direct model queries
    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;
        $trimmed = trim($line);

        // Skip comments, blank lines, property declarations, and CRUD overrides
        if (empty($trimmed) || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
            continue;
        }

        foreach ($directQueryPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                // Only report if it's in a method body (not a property or return type declaration)
                if ($linesWithIssues === 0) {
                    echo "{$moduleName}Service ({$relativePath})\n";
                    echo "  Facade: {$facadeClass}\n";
                }
                echo "  Line {$lineNum}: ".trim(substr(trim($line), 0, 120))."\n";
                $linesWithIssues++;
                break;
            }
        }
    }

    if ($linesWithIssues > 0) {
        echo "\n";
    }
}

echo "Done.\n";
