<?php
$resp = json_decode(file_get_contents('http://127.0.0.1:8003/api/spasial-lahan/referensi'), true);
print_r(array_keys($resp['data'] ?? []));
if (isset($resp['data']['petani'])) {
    echo "Petani count: " . count($resp['data']['petani']) . "\n";
    print_r(array_slice($resp['data']['petani'], 0, 2));
} else {
    echo "Petani NOT set\n";
}
