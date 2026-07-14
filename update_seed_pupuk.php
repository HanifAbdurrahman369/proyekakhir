<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [
    "UPDATE jenis_pupuk SET nama_bibit = 'Pupuk Urea', varietas = 'Subsidi', masa_tanam_hari = 0 WHERE id = 1",
    "UPDATE jenis_pupuk SET nama_bibit = 'Pupuk NPK Phonska', varietas = 'Subsidi', masa_tanam_hari = 0 WHERE id = 2",
    "UPDATE jenis_pupuk SET nama_bibit = 'Pupuk SP-36', varietas = 'Non-Subsidi', masa_tanam_hari = 0 WHERE id = 3",
    "UPDATE jenis_pupuk SET nama_bibit = 'Pupuk Kandang / Organik', varietas = 'Organik', masa_tanam_hari = 0 WHERE id = 4"
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
echo "Seeding update complete.\n";
