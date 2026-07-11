<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

try {
    Schema::table('monitoring_kondisi', function ($t) {
        $t->dropForeign('fk_monitoring_huma');
    });
} catch (\Exception $e) {}

try {
    Schema::table('monitoring_kondisi', function ($t) {
        $t->renameColumn('lahan_huma_id', 'lahan_id');
    });
} catch (\Exception $e) {}

// Add back the fk_monitoring_lahan so the migration can drop it
try {
    Schema::table('monitoring_kondisi', function ($t) {
        $t->integer('lahan_id')->change();
        $t->foreign('lahan_id', 'fk_monitoring_lahan')
          ->references('id')
          ->on('lahan_sawah')
          ->onDelete('cascade');
    });
} catch (\Exception $e) {}

echo "Reverted!\n";
