<?php
$c = file_get_contents('clients/web_app/app/Http/Controllers/AuthController.php');
$c = preg_replace('/Http::withoutVerifying\(\)/', 'Http::withHeaders([\'Connection\' => \'close\'])->withoutVerifying()', $c);
$c = preg_replace('/Http::withToken\(\$token\)/', 'Http::withHeaders([\'Connection\' => \'close\'])->withToken($token)', $c);
// If we accidentally duplicated it (like the ones we manually replaced)
$c = str_replace('Http::withHeaders([\'Connection\' => \'close\'])->withHeaders([\'Connection\' => \'close\'])', 'Http::withHeaders([\'Connection\' => \'close\'])', $c);
file_put_contents('clients/web_app/app/Http/Controllers/AuthController.php', $c);
echo "OK\n";
