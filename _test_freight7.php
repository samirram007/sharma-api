<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

$u = User::where('email','admin@admin.com')->first();
Auth::guard('api')->setUser($u); // try api guard

$controller = app(\Modules\Freight\Controllers\Api\FreightController::class);
$request = Illuminate\Http\Request::create('/api/freights/delivery_note','GET',['per_page'=>10,'page'=>1,'freight_status'=>'all']);
$request->setUserResolver(function() use ($u) { return $u; });

try {
  $resp = $controller->delivery_note($request);
  var_dump(get_class($resp));
  if (method_exists($resp, 'response')) {
    $r = $resp->response();
    echo "STATUS:".$r->getStatusCode().PHP_EOL;
    echo substr($r->getContent(),0,300).PHP_EOL;
  }
} catch (Throwable $e) {
  echo "ERR:".$e->getMessage().PHP_EOL;
}
