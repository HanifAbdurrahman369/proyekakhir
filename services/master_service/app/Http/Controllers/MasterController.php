<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class MasterController extends Controller
{
    // 1. Ambil semua daftar tabel di database
    public function getTables() {
        $tables = DB::select('SHOW TABLES');
        return response()->json($tables);
    }

    // 2. Ambil semua kolom dan data dari tabel tertentu
    public function getTableData($tableName) {
        if (!Schema::hasTable($tableName)) {
            return response()->json(['message' => 'Tabel tidak ditemukan'], 404);
        }
        $columns = Schema::getColumnListing($tableName);
        $data = DB::table($tableName)->get();
        return response()->json(['columns' => $columns, 'rows' => $data]);
    }

    // 3. Generic CRUD: Insert Data
    public function storeData(Request $request, $tableName) {
        DB::table($tableName)->insert($request->all());
        return response()->json(['message' => "Data berhasil ditambah ke $tableName"]);
    }

    // 4. Generic CRUD: Update Data
    public function updateData(Request $request, $tableName, $id) {
        DB::table($tableName)->where('id', $id)->update($request->all());
        return response()->json(['message' => "Data di $tableName berhasil diupdate"]);
    }

    // 5. Generic CRUD: Delete Data
    public function deleteData($tableName, $id) {
        DB::table($tableName)->where('id', $id)->delete();
        return response()->json(['message' => "Data di $tableName berhasil dihapus"]);
    }

    // 6. Manipulasi Skema: Eksekusi Kode SQL Mentah (Import SQL)
    public function executeRawSql(Request $request) {
        $request->validate(['sql' => 'required']);
        try {
            DB::unprepared($request->sql);
            return response()->json(['message' => 'Query SQL berhasil dieksekusi']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    // 7. EXPORT SQL (phpMyAdmin Compatible)
    public function exportSql($tableName = null)
    {
        // Jika tableName null, ambil semua tabel, jika ada ambil tabel spesifik
        $tables = $tableName ? [$tableName] : DB::connection()->getDoctrineSchemaManager()->listTableNames();
        $output = "-- SIG-PALA Database Export\n-- Generated: " . now() . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop table if exists
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Create table structure
            $createTable = DB::select("SHOW CREATE TABLE `$table`")[0];
            $output .= $createTable->{'Create Table'} . ";\n\n";

            // Get Data
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArray = (array)$row;
                $columns = "`" . implode("`, `", array_keys($rowArray)) . "`";
                $values = array_map(function($value) {
                    if (is_null($value)) return "NULL";
                    return "'" . addslashes($value) . "'";
                }, array_values($rowArray));
                
                $output .= "INSERT INTO `$table` ($columns) VALUES (" . implode(", ", $values) . ");\n";
            }
            $output .= "\n";
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;";
        $filename = ($tableName ?? 'full_database') . "_" . date('Ymd_His') . ".sql";

        return response($output)
            ->header('Content-Type', 'application/sql')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    // 8. EXPORT EXCEL (Dynamic Table)
    public function exportExcel($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            return response()->json(['message' => 'Tabel tidak ditemukan'], 404);
        }

        $data = DB::table($tableName)->get();
        $filename = $tableName . "_" . date('Ymd_His') . ".xlsx";

        // Menggunakan library Excel untuk membuat file secara dinamis dari Collection
        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function collection() { return $this->data; }
            public function headings(): array {
                return count($this->data) > 0 ? array_keys((array)$this->data[0]) : [];
            }
        }, $filename);
    }

}