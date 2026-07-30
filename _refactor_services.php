<?php

/**
 * Batch refactor simple CRUD services to extend BaseService.
 *
 * Run: php _refactor_services.php
 *
 * This script:
 * 1. Finds all services that have only the 5 standard CRUD methods
 * 2. Checks their interface for return types
 * 3. Rewrites them to extend BaseService
 */

// Services that should NOT be refactored even if they match the simple pattern
// (because they have complex store/update logic despite standard method names)
$skip_list = [
    'AppModuleFeatureService.php',
    'AppNotificationService.php',
    'CompanyService.php',
    'CustomerService.php',
    'DistributorService.php',
    'EmployeeService.php',
    'GodownService.php',
    'SupplierService.php',
    'VendorService.php',
    'TransporterService.php',
    'AccountGroupService.php',
    'PaymentService.php',
    'PurchaseService.php',
    'ReceiptService.php',
    'StockJournalService.php',
    'StockJournalEntryService.php',
    'StockJournalGodownEntryService.php',
    'StockSummaryService.php',
    'UserService.php',
    'UserFiscalYearService.php',
    'VoucherNoService.php',
    'VoucherReferenceService.php',
    'VoucherService.php',
    'FreightService.php',
    'DayBookService.php',
    'FiscalYearCloseService.php',
    'FiscalYearOpenService.php',
    'OpeningBalanceService.php',
    'PhysicalStockCountService.php',
    'ReceiptNoteReportService.php',
    'ReceiptVoucherService.php',
    'MenuService.php',
];

$files = glob('app/Modules/*/Services/*Service.php');
$count = 0;
$skipped = 0;
$errors = [];

foreach ($files as $f) {
    $basename = basename($f);

    // Skip listed complex services
    if (in_array($basename, $skip_list)) {
        $skipped++;

        continue;
    }

    // Skip already-refactored
    $content = file_get_contents($f);
    if (strpos($content, 'extends BaseService') !== false) {
        $skipped++;

        continue;
    }

    // Parse module name from path
    preg_match('#app/Modules/(\w+)/Services/#', $f, $moduleMatch);
    $moduleName = $moduleMatch[1];

    // Parse service class name
    preg_match('/class (\w+)Service implements (\w+)/', $content, $classMatch);
    if (empty($classMatch)) {
        $errors[] = "$basename: Cannot parse class definition";

        continue;
    }
    $shortName = $classMatch[1]; // e.g., "AccountNature"
    $interfaceName = $classMatch[2]; // e.g., "AccountNatureServiceInterface"

    // Parse model class
    preg_match('/use\s+(Modules\\\\\w+\\\\Models\\\\(\w+));/', $content, $modelMatch);
    if (empty($modelMatch)) {
        // Try alternate pattern
        preg_match('/use\s+Modules\\\\(\w+)\\\Models\\\\(\w+);/', $content, $modelMatch2);
        if (! empty($modelMatch2)) {
            $modelClass = $modelMatch2[0]; // Full use statement
            $modelShortName = $modelMatch2[2];
        } else {
            $errors[] = "$basename: Cannot parse model class";

            continue;
        }
    } else {
        $modelClass = $modelMatch[1]; // Full qualified model class
        $modelShortName = $modelMatch[2]; // Short model name
    }

    // Clean the model class path (remove leading "use " and trailing ";")
    // The modelClass from preg_match already has the full namespace

    // Parse $resource array
    preg_match('/protected\s+\$resource\s*=\s*\[(.*?)\];/s', $content, $resourceMatch);
    $resourceLines = '';
    if (! empty($resourceMatch)) {
        // Preserve the original resource lines
        $indent = '    ';
        $resourceLines = $resourceMatch[1];
        // Clean up whitespace
        $resourceLines = trim($resourceLines);
        if (! empty($resourceLines)) {
            // Reformat with proper indentation
            $parts = explode(',', $resourceLines);
            $formatted = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if (! empty($p)) {
                    $formatted[] = "\n            '$p',";
                }
            }
            // If original had single quotes wrapping each item
            $resourceLines = implode('', $formatted)."\n        ";
        }
    }

    // Check the interface for return types
    $interfaceFile = str_replace('/Services/', '/Contracts/', $f);
    $interfaceFile = str_replace('Service.php', 'Interface.php', $interfaceFile);
    if (file_exists($interfaceFile)) {
        $interfaceContent = file_get_contents($interfaceFile);
    } else {
        $interfaceContent = '';
    }

    // Determine if getById returns nullable
    $isNullable = true; // default
    if (preg_match('/function getById\(int \$id\): \?/', $interfaceContent)) {
        $isNullable = true;
    } elseif (preg_match('/function getById\(int \$id\): (\w+)/', $interfaceContent, $typeMatch)) {
        // Non-nullable, check if it matches the model
        $returnType = $typeMatch[1];
        $isNullable = ! ($returnType === $modelShortName || strpos($returnType, $modelShortName) !== false);
    }

    // Determine if getAll returns Collection (it usually does)
    // Determine if store returns the model
    $storeReturnsNullable = false;
    if (preg_match('/function store\(array \$data\): \?/', $interfaceContent)) {
        $storeReturnsNullable = true;
    }

    $updateReturnsNullable = false;
    if (preg_match('/function update\(array \$data, int \$id\): \?/', $interfaceContent)) {
        $updateReturnsNullable = true;
    }

    // Build the refactored file content
    $nullable = $isNullable ? '?' : '';
    $storeNullable = $storeReturnsNullable ? '?' : '';
    $updateNullable = $updateReturnsNullable ? '?' : '';

    // Get the model's full use statement
    $modelUseLine = "use $modelClass;";

    // Build resource property
    $resourceProp = '';
    if (! empty($resourceLines) && trim($resourceLines) !== '') {
        $resourceProp = <<<RESOURCE

    protected array \$defaultResource = [$resourceLines];
RESOURCE;
    }

    $refactored = <<<PHP
<?php

namespace Modules\\{$moduleName}\\Services;

use App\\Support\\Services\\BaseService;
use Modules\\{$moduleName}\\Contracts\\{$interfaceName};
use {$modelClass};
use Illuminate\\Database\\Eloquent\\Collection;

class {$shortName}Service extends BaseService implements {$interfaceName}
{
    protected string \$modelClass = {$modelShortName}::class;
{$resourceProp}

    public function getAll(): Collection
    {
        return \$this->getAllRecords();
    }

    public function getById(int \$id): {$nullable}{$modelShortName}
    {
        return \$this->findOrFail(\$id);
    }

    public function store(array \$data): {$storeNullable}{$modelShortName}
    {
        return \$this->createRecord(\$data);
    }

    public function update(array \$data, int \$id): {$updateNullable}{$modelShortName}
    {
        return \$this->updateRecord(\$id, \$data);
    }

    public function delete(int \$id): bool
    {
        return \$this->deleteRecord(\$id);
    }
}
PHP;

    $result = file_put_contents($f, $refactored);
    if ($result === false) {
        $errors[] = "$basename: Failed to write";
    } else {
        $count++;
        echo "  ✓ $basename\n";
    }
}

echo "\n============================\n";
echo "Refactored: $count services\n";
echo "Skipped: $skipped services\n";
echo 'Errors: '.count($errors)."\n";
if (! empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $e) {
        echo "  ✗ $e\n";
    }
}
echo "============================\n";
