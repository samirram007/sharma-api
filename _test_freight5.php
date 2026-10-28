<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

$u = User::where('email','admin@admin.com')->first();
Auth::guard()->setUser($u);

try {
    $service = app(\Modules\Freight\Contracts\FreightServiceInterface::class);
    $data = $service->getDeliveryNote(1, 10, ['freight_status'=>'all']);
    var_dump(get_class($data));
    echo "count:".($data->count()).PHP_EOL;
} catch (Throwable $e) {
    echo "ERR:".$e->getMessage().PHP_EOL;
    echo $e->getTraceAsString();
}
