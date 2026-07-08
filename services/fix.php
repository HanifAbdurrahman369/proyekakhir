<?php
$c = file_get_contents('clients/web_app/app/Http/Controllers/PejabatController.php'); 
$c = str_replace('Http::withoutVerifying()->withToken(', 'Http::withToken(', $c); // normalize first
$c = str_replace('Http::withToken(', 'Http::withHeaders([\'Connection\' => \'close\'])->withoutVerifying()->withToken(', $c); 
$c = str_replace('/api/statistik', '/api/produksi-kelurahan', $c); 
$c = str_replace('json(\'data.tabel_rekap\')', 'json(\'data\')', $c); 
$c = str_replace('json(\'data.lahan_all\')', 'json(\'data\')', $c); 
file_put_contents('clients/web_app/app/Http/Controllers/PejabatController.php', $c);
echo "OK\n";
