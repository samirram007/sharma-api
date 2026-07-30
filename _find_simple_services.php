<?php

/**
 * This script identifies simple CRUD services (only 5 standard methods)
 * that can be refactored to extend BaseService without losing functionality.
 *
 * Run: php _find_simple_services.php
 */
$files = glob('app/Modules/*/Services/*Service.php');
$total = count($files);
echo "Total service files: $total\n\n";

$complex = [];
$simple = [];
$already_done = [];
$not_found = [];

foreach ($files as $f) {
    $content = file_get_contents($f);
    $name = basename($f, '.php');

    // Skip already-refactored services
    if (strpos($content, 'extends BaseService') !== false) {
        $already_done[] = $name;

        continue;
    }

    // Count method definitions
    preg_match_all('/public function (\w+)/', $content, $methods);
    $methodNames = $methods[1] ?? [];

    // Check for constructor
    $hasConstructor = in_array('__construct', $methodNames);

    // Standard CRUD methods
    $standardMethods = ['getAll', 'getById', 'store', 'update', 'delete'];
    $hasStandard = array_intersect($standardMethods, $methodNames);

    // Extra methods beyond standard CRUD
    $extraMethods = array_diff($methodNames, $standardMethods);
    $extraMethods = array_values(array_filter($extraMethods, fn ($m) => ! in_array($m, ['__construct', '__destruct', '__call'])));

    // Extract model class from the file
    preg_match('/use.*?Models\\\\(\w+);/', $content, $modelMatch);
    $modelClass = $modelMatch[1] ?? 'UNKNOWN';

    // Extract $resource
    preg_match('/protected\s+\$resource\s*=\s*\[(.*?)\];/s', $content, $resourceMatch);

    $info = [
        'name' => $name,
        'file' => $f,
        'model' => $modelClass,
        'hasConstructor' => $hasConstructor,
        'hasStandard' => count($hasStandard),
        'extraMethods' => $extraMethods,
        'hasResource' => ! empty($resourceMatch),
    ];

    if (count($hasStandard) >= 5 && ! $hasConstructor && empty($extraMethods)) {
        $simple[] = $info;
    } else {
        $complex[] = $info;
    }
}

echo '=== SIMPLE CRUD SERVICES ('.count($simple).") ===\n\n";
foreach ($simple as $s) {
    echo "  {$s['name']} (Model: {$s['model']}, Resource: ".($s['hasResource'] ? 'yes' : 'no').")\n";
}

echo "\n=== ALREADY REFACTORED (".count($already_done).") ===\n\n";
foreach ($already_done as $a) {
    echo "  $a\n";
}

echo "\n=== COMPLEX SERVICES (".count($complex).") ===\n\n";
foreach ($complex as $c) {
    $extra = ! empty($c['extraMethods']) ? ', Extra: '.implode(', ', $c['extraMethods']) : '';
    $ctor = $c['hasConstructor'] ? ', hasConstructor' : '';
    $std = $c['hasStandard'] < 5 ? ', missingStandard('.(5 - $c['hasStandard']).')' : '';
    echo "  {$c['name']} (Model: {$c['model']}$ctor$std$extra)\n";
}
