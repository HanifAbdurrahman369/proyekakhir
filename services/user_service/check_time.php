<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "PHP timezone: " . date_default_timezone_get() . "\n";
echo "PHP current time: " . date('Y-m-d H:i:s') . "\n";

$dbTime = DB::select("SELECT NOW() as now")[0]->now;
echo "DB current time (NOW()): " . $dbTime . "\n";

$dbUtcTime = DB::select("SELECT UTC_TIMESTAMP() as utc")[0]->utc;
echo "DB UTC time (UTC_TIMESTAMP()): " . $dbUtcTime . "\n";
