<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
// Try with a fake token header to see past JWT auth
$req = Illuminate\Http\Request::create('/api/freights/delivery_note?per_page=10&page=1&freight_status=all','GET');
$req->headers->set('Accept','application/json');
$req->headers->set('Authorization','Bearer fake');
try {
  $resp = $kernel->handle($req);
  echo 'STATUS:'.$resp->getStatusCode().PHP_EOL;
  echo substr($resp->getContent(),0,1200).PHP_EOL;
  $kernel->terminate($req,$resp);
} catch (Throwable $e) {
  echo 'ERR:'.$e->getMessage().PHP_EOL;
}
