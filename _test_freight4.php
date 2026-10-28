<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $service = app(\Modules\Freight\Contracts\FreightServiceInterface::class);
    // This will fail if no user is authenticated (getUserFiscalYearPeriod uses auth())
    $data = $service->getDeliveryNote(1, 10, ['freight_status'=>'all']);
    var_dump(get_class($data));
} catch (Throwable $e) {
    echo 'ERR:'.$e->getMessage().PHP_EOL;
}
