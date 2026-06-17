<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MasterDataController extends Controller
{
    protected function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', 'http://127.0.0.1:8003'), '/');
    }

    protected function apiUrl(): string
    {
        return $this->gatewayUrl() . '/api/master';
    }

    private function api()
    {
        return Http::withToken(session('token'))
            ->acceptJson()
            ->withoutVerifying()
            ->timeout(10);
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

        $resTables = $this->api()->get($this->apiUrl() . '/tables');

        if ($resTables->successful()) {
            foreach ($resTables->json() as $table) {
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
            ))->with('error', 'Gagal mengambil daftar tabel dari master_service: ' . ($resTables->json('message') ?? $resTables->body()));
        }

        if ($tableName && in_array($tableName, $tableNames)) {
            $resData = $this->api()->get($this->apiUrl() . '/tables/' . urlencode($tableName));

            if ($resData->successful()) {
                $data = $resData->json();

                $columns = $data['columns'] ?? [];
                $rows = $data['rows'] ?? [];
                $primaryKey = $data['primary_key'] ?? 'id';
            } else {
                return back()->with('error', 'Gagal mengambil data tabel: ' . ($resData->json('message') ?? $resData->body()));
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

        $res = $this->api()->post($this->apiUrl() . '/tables/' . urlencode($tableName), $data);

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data berhasil ditambahkan ke tabel $tableName.");
        }

        return back()->with('error', 'Gagal menambahkan data: ' . ($res->json('message') ?? $res->body()))->withInput();
    }

    public function update(Request $request, $tableName, $id)
    {
        $data = $request->except(['_token', '_method']);

        $res = $this->api()->put($this->apiUrl() . '/tables/' . urlencode($tableName) . '/' . urlencode($id), $data);

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data di tabel $tableName berhasil diperbarui.");
        }

        return back()->with('error', 'Gagal memperbarui data: ' . ($res->json('message') ?? $res->body()))->withInput();
    }

    public function destroy($tableName, $id)
    {
        $res = $this->api()->delete($this->apiUrl() . '/tables/' . urlencode($tableName) . '/' . urlencode($id));

        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")
                ->with('success', "Data berhasil dihapus dari tabel $tableName.");
        }

        return back()->with('error', 'Gagal menghapus data: ' . ($res->json('message') ?? $res->body()));
    }

    public function executeSql(Request $request)
    {
        $res = $this->api()->post($this->apiUrl() . '/execute-sql', [
            'sql' => $request->sql
        ]);

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