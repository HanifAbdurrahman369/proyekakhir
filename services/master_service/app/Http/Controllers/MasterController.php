<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MasterController extends Controller
{
    private array $hiddenTables = [];

    private function databaseName(): string
    {
        return DB::getDatabaseName();
    }

    private function tableExists(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    private function getPrimaryKey(string $tableName): string
    {
        $database = $this->databaseName();

        $row = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', 'PRIMARY')
            ->orderBy('ORDINAL_POSITION')
            ->first();

        return $row->COLUMN_NAME ?? 'id';
    }

    private function getColumnMeta(string $tableName): array
    {
        $database = $this->databaseName();

        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->orderBy('ORDINAL_POSITION')
            ->get()
            ->map(function ($col) {
                return [
                    'name' => $col->COLUMN_NAME,
                    'type' => $col->COLUMN_TYPE,
                    'data_type' => strtolower($col->DATA_TYPE),
                    'nullable' => $col->IS_NULLABLE === 'YES',
                    'default' => $col->COLUMN_DEFAULT,
                    'extra' => strtolower($col->EXTRA ?? ''),
                    'key' => $col->COLUMN_KEY,
                ];
            })
            ->toArray();
    }

    private function isGeometryColumn(array $meta): bool
    {
        return in_array(strtolower($meta['data_type']), [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection',
        ]);
    }

    private function safeTableName(string $tableName): string
    {
        return str_replace('`', '', $tableName);
    }

    private function safeColumnName(string $columnName): string
    {
        return str_replace('`', '', $columnName);
    }

    private function quotedIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function prepareDataForWrite(string $tableName, Request $request, bool $isUpdate = false): array
    {
        $metas = $this->getColumnMeta($tableName);
        $input = $request->all();

        unset($input['_token'], $input['_method']);

        $normalData = [];
        $geometryData = [];

        foreach ($metas as $meta) {
            $column = $meta['name'];

            if (!array_key_exists($column, $input)) {
                continue;
            }

            if (str_contains($meta['extra'], 'auto_increment')) {
                continue;
            }

            $value = $input[$column];

            if ($value === '' || $value === '[NULL]') {
                $value = null;
            }

            if ($isUpdate && $value === '[Data Geometri/Spasial]') {
                continue;
            }

            if ($this->isGeometryColumn($meta)) {
                if ($value === null || $value === '[Data Geometri/Spasial]') {
                    continue;
                }

                $geometryData[$column] = $value;
                continue;
            }

            $normalData[$column] = $value;
        }

        return [
            'normal' => $normalData,
            'geometry' => $geometryData,
        ];
    }

    private function geometrySqlValue(string $value): string
    {
        $trimmed = trim($value);
        $quoted = DB::getPdo()->quote($trimmed);

        if (Str::startsWith($trimmed, ['{', '['])) {
            return "ST_GeomFromGeoJSON($quoted)";
        }

        return "ST_GeomFromText($quoted)";
    }

    public function getTables()
    {
        $tables = DB::select('SHOW TABLES');
        $result = [];

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            if (in_array($tableName, $this->hiddenTables)) {
                continue;
            }

            $result[] = [
                'table_name' => $tableName,
                'primary_key' => $this->getPrimaryKey($tableName),
                'columns' => Schema::getColumnListing($tableName),
                'column_meta' => $this->getColumnMeta($tableName),
            ];
        }

        return response()->json($result);
    }

    public function getTableData($tableName)
    {
        $tableName = $this->safeTableName($tableName);

        if (!$this->tableExists($tableName)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak ditemukan: ' . $tableName
            ], 404);
        }

        try {
            $primaryKey = $this->getPrimaryKey($tableName);
            $metas = $this->getColumnMeta($tableName);

            $selects = [];

            foreach ($metas as $meta) {
                $column = $this->safeColumnName($meta['name']);

                if ($this->isGeometryColumn($meta)) {
                    $selects[] = DB::raw("ST_AsText(" . $this->quotedIdentifier($column) . ") as " . $this->quotedIdentifier($column));
                } else {
                    // Penting: jangan pakai backtick manual di string biasa.
                    // Laravel Query Builder akan mengamankan nama kolom secara otomatis.
                    $selects[] = $column;
                }
            }

            $rows = DB::table($tableName)
                ->select($selects)
                ->limit(500)
                ->get()
                ->map(function ($row) {
                    $array = (array) $row;

                    foreach ($array as $key => $value) {
                        if (is_resource($value)) {
                            $array[$key] = '[Data Binary/Resource]';
                        }

                        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                            $array[$key] = '[Data Binary/Spasial]';
                        }
                    }

                    return $array;
                });

            return response()->json([
                'success' => true,
                'table_name' => $tableName,
                'primary_key' => $primaryKey,
                'columns' => Schema::getColumnListing($tableName),
                'column_meta' => $metas,
                'rows' => $rows,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tabel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeData(Request $request, $tableName)
    {
        $tableName = $this->safeTableName($tableName);

        if (!$this->tableExists($tableName)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak ditemukan: ' . $tableName
            ], 404);
        }

        $prepared = $this->prepareDataForWrite($tableName, $request, false);

        try {
            if (empty($prepared['normal']) && empty($prepared['geometry'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang dikirim.'
                ], 422);
            }

            if (empty($prepared['geometry'])) {
                DB::table($tableName)->insert($prepared['normal']);
            } else {
                $columns = [];
                $values = [];

                foreach ($prepared['normal'] as $column => $value) {
                    $columns[] = $this->quotedIdentifier($column);

                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = DB::getPdo()->quote($value);
                    }
                }

                foreach ($prepared['geometry'] as $column => $value) {
                    $columns[] = $this->quotedIdentifier($column);
                    $values[] = $this->geometrySqlValue($value);
                }

                $sql = "INSERT INTO " . $this->quotedIdentifier($tableName) .
                    " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";

                DB::statement($sql);
            }

            return response()->json([
                'success' => true,
                'message' => "Data berhasil ditambahkan ke tabel $tableName"
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateData(Request $request, $tableName, $id)
    {
        $tableName = $this->safeTableName($tableName);

        if (!$this->tableExists($tableName)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak ditemukan: ' . $tableName
            ], 404);
        }

        $primaryKey = $this->getPrimaryKey($tableName);
        $prepared = $this->prepareDataForWrite($tableName, $request, true);

        unset($prepared['normal'][$primaryKey]);

        try {
            if (!empty($prepared['normal'])) {
                DB::table($tableName)
                    ->where($primaryKey, $id)
                    ->update($prepared['normal']);
            }

            foreach ($prepared['geometry'] as $column => $geometryValue) {
                $sql = "UPDATE " . $this->quotedIdentifier($tableName) .
                    " SET " . $this->quotedIdentifier($column) . " = " . $this->geometrySqlValue($geometryValue) .
                    " WHERE " . $this->quotedIdentifier($primaryKey) . " = " . DB::getPdo()->quote($id);

                DB::statement($sql);
            }

            return response()->json([
                'success' => true,
                'message' => "Data tabel $tableName berhasil diperbarui."
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteData($tableName, $id)
    {
        $tableName = $this->safeTableName($tableName);

        if (!$this->tableExists($tableName)) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel tidak ditemukan: ' . $tableName
            ], 404);
        }

        $primaryKey = $this->getPrimaryKey($tableName);

        try {
            DB::table($tableName)
                ->where($primaryKey, $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Data di tabel $tableName berhasil dihapus."
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function executeRawSql(Request $request)
    {
        $request->validate([
            'sql' => 'required|string'
        ]);

        try {
            DB::unprepared($request->sql);

            return response()->json([
                'success' => true,
                'message' => 'Query SQL berhasil dieksekusi.'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportSql($tableName = null)
    {
        try {
            $tables = $tableName
                ? [$this->safeTableName($tableName)]
                : collect(DB::select('SHOW TABLES'))
                    ->map(fn ($t) => array_values((array) $t)[0])
                    ->values()
                    ->toArray();

            $output = "-- SiPetani Database Export\n";
            $output .= "-- Database: " . $this->databaseName() . "\n";
            $output .= "-- Generated: " . now() . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                if (!$this->tableExists($table)) {
                    continue;
                }

                $output .= "DROP TABLE IF EXISTS " . $this->quotedIdentifier($table) . ";\n";

                $create = DB::select("SHOW CREATE TABLE " . $this->quotedIdentifier($table))[0];
                $output .= $create->{'Create Table'} . ";\n\n";

                $metas = $this->getColumnMeta($table);
                $selects = [];
                $geometryColumns = [];

                foreach ($metas as $meta) {
                    $column = $this->safeColumnName($meta['name']);

                    if ($this->isGeometryColumn($meta)) {
                        $geometryColumns[] = $column;
                        $selects[] = DB::raw("ST_AsText(" . $this->quotedIdentifier($column) . ") as " . $this->quotedIdentifier($column));
                    } else {
                        // Penting: jangan pakai "`$column`"
                        $selects[] = $column;
                    }
                }

                $rows = DB::table($table)->select($selects)->get();

                foreach ($rows as $row) {
                    $rowArray = (array) $row;

                    $columns = [];
                    $values = [];

                    foreach ($rowArray as $column => $value) {
                        $columns[] = $this->quotedIdentifier($column);

                        if ($value === null) {
                            $values[] = 'NULL';
                            continue;
                        }

                        if (in_array($column, $geometryColumns)) {
                            $values[] = "ST_GeomFromText(" . DB::getPdo()->quote($value) . ")";
                            continue;
                        }

                        $values[] = DB::getPdo()->quote($value);
                    }

                    $output .= "INSERT INTO " . $this->quotedIdentifier($table) .
                        " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                }

                $output .= "\n";
            }

            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = ($tableName ?? 'full_database') . '_' . date('Ymd_His') . '.sql';

            return response($output)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export SQL: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel($tableName = null)
    {
        try {
            $tables = $tableName
                ? [$this->safeTableName($tableName)]
                : collect(DB::select('SHOW TABLES'))
                    ->map(fn ($t) => array_values((array) $t)[0])
                    ->values()
                    ->toArray();

            $html = '<html>';
            $html .= '<head>';
            $html .= '<meta charset="UTF-8">';
            $html .= '<style>';
            $html .= 'body{font-family:Arial,sans-serif;font-size:12px;}';
            $html .= 'table{border-collapse:collapse;margin-bottom:30px;width:100%;}';
            $html .= 'th{background:#3E7D00;color:#ffffff;font-weight:bold;}';
            $html .= 'th,td{border:1px solid #999;padding:6px;mso-number-format:"\@";}';
            $html .= 'h2{color:#3E7D00;}';
            $html .= '</style>';
            $html .= '</head>';
            $html .= '<body>';

            $html .= '<h1>Export Data Master SiPetani</h1>';
            $html .= '<p>Database: ' . e($this->databaseName()) . '</p>';
            $html .= '<p>Generated: ' . e((string) now()) . '</p>';

            foreach ($tables as $table) {
                if (!$this->tableExists($table)) {
                    continue;
                }

                $metas = $this->getColumnMeta($table);
                $selects = [];

                foreach ($metas as $meta) {
                    $column = $this->safeColumnName($meta['name']);

                    if ($this->isGeometryColumn($meta)) {
                        $selects[] = DB::raw("ST_AsText(" . $this->quotedIdentifier($column) . ") as " . $this->quotedIdentifier($column));
                    } else {
                        $selects[] = $column;
                    }
                }

                $rows = DB::table($table)->select($selects)->get();

                $html .= '<h2>Tabel: ' . e($table) . '</h2>';
                $html .= '<table>';

                $html .= '<tr>';
                foreach ($metas as $meta) {
                    $html .= '<th>' . e($meta['name']) . '</th>';
                }
                $html .= '</tr>';

                foreach ($rows as $row) {
                    $rowArray = (array) $row;

                    $html .= '<tr>';
                    foreach ($metas as $meta) {
                        $column = $meta['name'];
                        $value = $rowArray[$column] ?? '';

                        if (is_resource($value)) {
                            $value = '[Data Binary/Resource]';
                        }

                        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                            $value = '[Data Binary/Spasial]';
                        }

                        $html .= '<td>' . e((string) $value) . '</td>';
                    }
                    $html .= '</tr>';
                }

                $html .= '</table>';
            }

            $html .= '</body></html>';

            $filename = ($tableName ?? 'semua_tabel') . '_' . date('Ymd_His') . '.xls';

            return response($html)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal export Excel: ' . $e->getMessage()
            ], 500);
        }
    }
}
