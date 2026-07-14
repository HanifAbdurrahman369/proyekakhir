<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$output = "";
foreach ($tables as $table) {
    $output .= "\nTable: $table\n";
    $stmt2 = $pdo->query("DESCRIBE $table");
    $cols = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        $output .= " - {$col['Field']} ({$col['Type']})\n";
    }
}
file_put_contents('schema_dump.txt', $output);
echo "Done";
