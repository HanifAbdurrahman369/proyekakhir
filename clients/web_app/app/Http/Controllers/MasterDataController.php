<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MasterDataController extends Controller
{
    protected function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    protected function apiUrl(): string
    {
        return $this->gatewayUrl() . '/api/master';
    }

    private function api()
    {
        return Http::withHeaders(['Connection' => 'close'])
            ->withToken(session('token'))
            ->acceptJson()
            ->withoutVerifying()
            ->timeout(15)
            ->connectTimeout(5);
    }

    private function errorMessage($response, string $fallback): string
    {
        return $response->json('message')
            ?? $response->json('error')
            ?? $fallback;
    }

    private function normalizeTableList($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $tables = $payload['data'] ?? $payload['tables'] ?? $payload;

        if (!is_array($tables)) {
            return [];
        }

        return collect($tables)
            ->filter(fn ($table) => is_array($table) && !empty($table['table_name']))
            ->map(function ($table) {
                $columns = $table['columns'] ?? [];

                if (!is_array($columns)) {
                    $columns = [];
                }

                $table['columns'] = $columns;
                $table['primary_key'] = $table['primary_key'] ?? 'id';

                return $table;
            })
            ->values()
            ->all();
    }

    private function normalizeTableData($payload): array
    {
        if (!is_array($payload)) {
            return [
                'columns' => [],
                'rows' => [],
                'primary_key' => 'id',
            ];
        }

        $data = $payload['data'] ?? $payload;

        return [
            'columns' => is_array($data['columns'] ?? null) ? $data['columns'] : [],
            'rows' => is_array($data['rows'] ?? null) ? $data['rows'] : [],
            'primary_key' => $data['primary_key'] ?? 'id',
        ];
    }

    public function index(Request $request)
    {
        $tableName = $request->query('table');

        $tableNames = [];
        $allTablesWithColumns = [];
        $tableMeta = [];
        $columns = [];
        $rows = [];
        $primaryKey = 'id';

        try {
            $resTables = $this->api()->get($this->apiUrl() . '/tables');
        } catch (\Throwable $e) {
            report($e);

            return view('dashboard.admin', compact(
                'tableNames',
                'tableName',
                'columns',
                'rows',
                'allTablesWithColumns',
                'primaryKey',
                'tableMeta'
            ))->with('error', 'Master service belum dapat dihubungi. Pastikan service port 8004 berjalan.');
        }

        if ($resTables->successful()) {
            foreach ($this->normalizeTableList($resTables->json()) as $table) {
                $name = $table['table_name'];

                $tableNames[] = $name;
                $allTablesWithColumns[$name] = $table['columns'] ?? [];
                $tableMeta[$name] = $table;
            }
        } else {
            return view('dashboard.admin', compact(
                'tableNames',
                'tableName',
                'columns',
                'rows',
                'allTablesWithColumns',
                'primaryKey',
                'tableMeta'
            ))->with('error', 'Gagal mengambil daftar tabel dari master_service: ' . $this->errorMessage($resTables, $resTables->body()));
        }

        if ($tableName && in_array($tableName, $tableNames)) {
            try {
                $resData = $this->api()->get($this->apiUrl() . '/tables/' . urlencode($tableName));
            } catch (\Throwable $e) {
                report($e);
                return back()->with('error', 'Master service terputus saat mengambil data tabel.');
            }

            if ($resData->successful()) {
                $data = $this->normalizeTableData($resData->json());

                $columns = $data['columns'] ?? [];
                $rows = $data['rows'] ?? [];
                $primaryKey = $data['primary_key'] ?? 'id';
            } else {
                return back()->with('error', 'Gagal mengambil data tabel: ' . $this->errorMessage($resData, $resData->body()));
            }
        }

        return view('dashboard.admin', compact(
            'tableNames',
            'tableName',
            'columns',
            'rows',
            'allTablesWithColumns',
            'primaryKey',
            'tableMeta'
        ));
    }

    public function store(Request $request, $tableName)
    {
        $data = $request->except(['_token']);

        try {
            $res = $this->api()->post($this->apiUrl() . '/tables/' . urlencode($tableName), $data);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Master service belum dapat dihubungi. Data belum tersimpan.')->withInput();
        }

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data berhasil ditambahkan ke tabel $tableName.");
        }

        return back()->with('error', 'Gagal menambahkan data: ' . ($res->json('message') ?? $res->body()))->withInput();
    }

    public function update(Request $request, $tableName, $id)
    {
        $data = $request->except(['_token', '_method']);

        try {
            $res = $this->api()->put($this->apiUrl() . '/tables/' . urlencode($tableName) . '/' . urlencode($id), $data);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Master service belum dapat dihubungi. Data belum diperbarui.')->withInput();
        }

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data di tabel $tableName berhasil diperbarui.");
        }

        return back()->with('error', 'Gagal memperbarui data: ' . ($res->json('message') ?? $res->body()))->withInput();
    }

    public function destroy($tableName, $id)
    {
        try {
            $res = $this->api()->delete($this->apiUrl() . '/tables/' . urlencode($tableName) . '/' . urlencode($id));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Master service belum dapat dihubungi. Data belum dihapus.');
        }

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data berhasil dihapus dari tabel $tableName.");
        }

        return back()->with('error', 'Gagal menghapus data: ' . ($res->json('message') ?? $res->body()));
    }

    public function executeSql(Request $request)
    {
        try {
            $res = $this->api()->post($this->apiUrl() . '/execute-sql', [
                'sql' => $request->sql
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Master service belum dapat dihubungi. Query belum dieksekusi.');
        }

        if ($res->successful()) {
            return back()->with('success', 'Query SQL berhasil dieksekusi.');
        }

        return back()->with('error', 'Gagal mengeksekusi SQL: ' . ($res->json('message') ?? $res->body()));
    }

    public function exportSql($tableName = null)
    {
        $url = $this->apiUrl() . '/export/sql' . ($tableName ? '/' . urlencode($tableName) : '');

        return redirect($url);
    }

    public function exportExcel($tableName = null)
    {
        $url = $this->apiUrl() . '/export/excel' . ($tableName ? '/' . urlencode($tableName) : '');

        return redirect($url);
    }
}
