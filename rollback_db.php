<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [
    "ALTER TABLE jenis_pupuk 
        DROP COLUMN nama_pupuk, 
        DROP COLUMN jenis, 
        ADD COLUMN nama_bibit VARCHAR(100) NULL AFTER id, 
        ADD COLUMN varietas VARCHAR(100) NULL AFTER nama_bibit,
        ADD COLUMN masa_tanam_hari INT NULL AFTER varietas"
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
echo "Rollback complete.\n";
