<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
// Create a request as if authenticated? Easier to just call controller directly
$controller = app(\Modules\Freight\Controllers\Api\FreightController::class);
$request = Illuminate\Http\Request::create('/api/freights/delivery_note','GET',['per_page'=>10,'page'=>1,'freight_status'=>'all']);
try {
  // The controller uses FreightFacade which calls service; service calls auth()->guard()->user()->user_fiscal_year()
  // This will fail without auth, but let us see
  $resp = $controller->delivery_note($request);
  var_dump(get_class($resp));
  $content = method_exists($resp,'response') ? $resp->response()->getData(true) : (is_object($resp)&&method_exists($resp,'getData') ? $resp->getData(true) : null);
  print_r(array_slice($content,0,5));
} catch (Throwable $e) {
  echo 'ERR:'.$e->getMessage().PHP_EOL;
}
