<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [
    "INSERT INTO jenis_pupuk (id, nama_bibit, varietas, masa_tanam_hari) VALUES (1, 'Urea', 'Pupuk Kimia', 0) ON DUPLICATE KEY UPDATE nama_bibit=VALUES(nama_bibit)",
    "INSERT INTO jenis_pupuk (id, nama_bibit, varietas, masa_tanam_hari) VALUES (2, 'NPK Phonska', 'Pupuk Majemuk', 0) ON DUPLICATE KEY UPDATE nama_bibit=VALUES(nama_bibit)",
    "INSERT INTO jenis_pupuk (id, nama_bibit, varietas, masa_tanam_hari) VALUES (3, 'SP-36', 'Pupuk Fosfat', 0) ON DUPLICATE KEY UPDATE nama_bibit=VALUES(nama_bibit)",
    "INSERT INTO jenis_pupuk (id, nama_bibit, varietas, masa_tanam_hari) VALUES (4, 'Kompos', 'Pupuk Organik', 0) ON DUPLICATE KEY UPDATE nama_bibit=VALUES(nama_bibit)"
];

foreach ($sqls as $sql) {
    echo "Executing: $sql\n";
    try {
        $pdo->exec($sql);
        echo "OK\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Seeding complete.\n";
