<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqls = [
    "DROP TABLE IF EXISTS hama",
    
    "ALTER TABLE lahan_sawah DROP COLUMN foto_lahan, DROP COLUMN butuh_bantuan_pemetaan",
    
    "ALTER TABLE jenis_pupuk 
        DROP COLUMN nama_bibit, 
        DROP COLUMN varietas, 
        DROP COLUMN masa_tanam_hari, 
        ADD COLUMN nama_pupuk VARCHAR(150) NULL AFTER id, 
        ADD COLUMN jenis VARCHAR(50) NULL AFTER nama_pupuk",
        
    "ALTER TABLE kecamatan MODIFY COLUMN sumber_data_padi VARCHAR(100)",
    "ALTER TABLE komunitas MODIFY COLUMN alamat VARCHAR(255)",
    "ALTER TABLE users MODIFY COLUMN alamat VARCHAR(255)",
    "ALTER TABLE lahan_huma MODIFY COLUMN device_id VARCHAR(100)",
    "ALTER TABLE lahan_huma MODIFY COLUMN external_id VARCHAR(100)",
    "ALTER TABLE lahan_huma MODIFY COLUMN nama_lahan VARCHAR(150)",
    "ALTER TABLE lahan_huma MODIFY COLUMN nama_pemilik VARCHAR(150)",
    "ALTER TABLE lahan_huma MODIFY COLUMN district_name VARCHAR(100)",
    "ALTER TABLE lahan_huma MODIFY COLUMN tipe_tanah VARCHAR(50)",
    "ALTER TABLE notifikasi MODIFY COLUMN judul VARCHAR(150)",
    "ALTER TABLE rekomendasi_huma MODIFY COLUMN water_status VARCHAR(50)",
    "ALTER TABLE rekomendasi_huma MODIFY COLUMN status_tindakan VARCHAR(100)",
    "ALTER TABLE rekomendasi_huma MODIFY COLUMN nama_pupuk VARCHAR(100)",
    "ALTER TABLE rekomendasi_huma MODIFY COLUMN satuan VARCHAR(30)"
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
echo "Migration complete.\n";
