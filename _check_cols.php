<?php
require 'D:/Project/AIPT/sharma-api/vendor/autoload.php';
$app = require 'D:/Project/AIPT/sharma-api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cols = Illuminate\Support\Facades\Schema::getColumnListing('vouchers');
foreach($cols as $c){
  if(strpos($c,'deliver')!==false || strpos($c,'lock')!==false){
    echo $c.PHP_EOL;
  }
}
