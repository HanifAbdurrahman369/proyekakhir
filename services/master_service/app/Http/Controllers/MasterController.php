<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class MasterController extends Controller
{
    // 1. Ambil semua daftar tabel (Kecuali migrations & password_reset_tokens) + Kolomnya
    public function getTables() {
        $tables = DB::select('SHOW TABLES');
        $result = [];
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            // REVISI 2 & 3: Saring agar tidak menampilkan tabel sistem internal
            if (!in_array($tableName, ['migrations', 'password_reset_tokens'])) {
                $result[] = [
                    'table_name' => $tableName,
                    'columns' => Schema::getColumnListing($tableName)
                ];
            }
        }
        return response()->json($result);
    }

    // 2. Ambil semua kolom dan data dari tabel tertentu (Guest-Safe & Spatial-Safe)
    public function getTableData($tableName) {
        if (!Schema::hasTable($tableName)) {
            return response()->json(['message' => 'Tabel tidak ditemukan'], 404);
        }
        
        $columns = Schema::getColumnListing($tableName);
        
        // REVISI 1: Proteksi data spasial/binary agar tidak merusak encoding JSON
        $data = DB::table($tableName)->get()->map(function($row) {
            $rowArray = (array)$row;
            foreach ($rowArray as $key => $value) {
                if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                    $rowArray[$key] = '[Data Geometri/Spasial]';
                }
            }
            return $rowArray;
        });

        return response()->json(['columns' => $columns, 'rows' => $data]);
    }

    // 3. Generic CRUD: Insert Data
    public function storeData(Request $request, $tableName) {
        // REVISI 4: Filter hanya kolom yang sah di database & ubah string kosong ke null
        $columns = Schema::getColumnListing($tableName);
        $data = array_intersect_key($request->all(), array_flip($columns));
        
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        DB::table($tableName)->insert($data);
        return response()->json(['message' => "Data berhasil ditambah ke $tableName"]);
    }

    // 4. Generic CRUD: Update Data (Spatial & ID Protected)
    public function updateData(Request $request, $tableName, $id) {
        $columns = Schema::getColumnListing($tableName);
        $data = array_intersect_key($request->all(), array_flip($columns));
        
        // Proteksi 1: Keluarkan ID dari data yang akan di-update agar tidak bentrok dengan Primary Key
        unset($data['id']);

        foreach ($data as $key => $value) {
            // Mengubah string kosong menjadi null
            if ($value === '') {
                $data[$key] = null;
            }
            
            // Proteksi 2: Jika kolom berisi penanda geometri bawaan frontend, 
            // keluarkan dari antrean update agar data spasial asli di database tidak rusak/corrupt
            if ($value === '[Data Geometri/Spasial]') {
                unset($data[$key]);
            }
        }

        try {
            // Eksekusi update data ke database secara otomatis
            DB::table($tableName)->where('id', $id)->update($data);
            return response()->json(['message' => "Data di tabel $tableName berhasil diperbarui secara otomatis."]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menyimpan perubahan ke database: ' . $e->getMessage()], 500);
        }
    }

    // 5. Generic CRUD: Delete Data
    public function deleteData($tableName, $id) {
        DB::table($tableName)->where('id', $id)->delete();
        return response()->json(['message' => "Data di $tableName berhasil dihapus"]);
    }

    // 6. Manipulasi Skema: Eksekusi Kode SQL Mentah
    public function executeRawSql(Request $request) {
        $request->validate(['sql' => 'required']);
        try {
            DB::unprepared($request->sql);
            return response()->json(['message' => 'Query SQL berhasil dieksekusi']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // 7. EXPORT SQL
    public function exportSql($tableName = null)
    {
        $tables = $tableName ? [$tableName] : array_map(function($t) {
            return array_values((array)$t)[0];
        }, DB::select('SHOW TABLES'));

        $output = "-- SIG-PALA Database Export\n-- Generated: " . now() . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'password_reset_tokens'])) continue;

            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $createTable = DB::select("SHOW CREATE TABLE `$table`")[0];
            $output .= $createTable->{'Create Table'} . ";\n\n";

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $rowArray = (array)$row;
                $columns = "`" . implode("`, `", array_keys($rowArray)) . "`";
                $values = array_map(function($value) {
                    if (is_null($value)) return "NULL";
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) return "geomfromtext('POINT(0 0)')"; // Fallback data spasial
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

   // 8. EXPORT EXCEL (Mendukung Single Table & Full Database Multi-Sheets)
    public function exportExcel($tableName = null)
    {
        // Jika ada nama tabel, export satu tabel saja
        if ($tableName) {
            if (!Schema::hasTable($tableName)) {
                return response()->json(['message' => 'Tabel tidak ditemukan'], 404);
            }

            $columns = Schema::getColumnListing($tableName);
            $data = DB::table($tableName)->get()->map(function($item) {
                $array = (array)$item;
                foreach ($array as $key => $value) {
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $array[$key] = '[Data Geometri/Spasial]';
                    }
                }
                return $array;
            });

            $filename = $tableName . "_" . date('Ymd_His') . ".xlsx";
            return Excel::download(new class($data, $columns) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $data; private $columns;
                public function __construct($data, $columns) { $this->data = $data; $this->columns = $columns; }
                public function collection() { return $this->data; }
                public function headings(): array { return $this->columns; }
            }, $filename);
        }

        // JIKA TABEL KOSONG: Export semua tabel ke dalam 1 File Excel (Multi-Sheets)
        $tables = array_map(function($t) {
            return array_values((array)$t)[0];
        }, DB::select('SHOW TABLES'));

        $filename = "Full_Database_" . date('Ymd_His') . ".xlsx";

        return Excel::download(new class($tables) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
            private $tables;
            public function __construct($tables) { $this->tables = $tables; }
            
            public function sheets(): array {
                $sheets = [];
                foreach ($this->tables as $table) {
                    if (in_array($table, ['migrations', 'password_reset_tokens'])) continue;

                    $columns = Schema::getColumnListing($table);
                    $data = DB::table($table)->get()->map(function($item) {
                        $array = (array)$item;
                        foreach ($array as $key => $value) {
                            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                                $array[$key] = '[Data Geometri/Spasial]';
                            }
                        }
                        return $array;
                    });

                    // Class anonim untuk menghandle sheet per tabel
                    $sheets[] = new class($data, $columns, $table) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle {
                        private $data; private $columns; private $title;
                        public function __construct($data, $columns, $title) { $this->data = $data; $this->columns = $columns; $this->title = $title; }
                        public function collection() { return $this->data; }
                        public function headings(): array { return $this->columns; }
                        public function title(): string { return substr($this->title, 0, 31); } // Batasan limit nama sheet excel 31 karakter
                    };
                }
                return $sheets;
            }
        }, $filename);
    }
}