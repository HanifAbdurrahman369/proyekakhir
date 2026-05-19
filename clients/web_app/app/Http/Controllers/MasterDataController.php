<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MasterDataController extends Controller
{
    // Mengarah ke master_service di Port 8004
    private $apiUrl = 'http://127.0.0.1:8004/api';

    private function api()
    {
        return Http::withToken(session('token'))->acceptJson()->withoutVerifying();
    }

    // 1. Menampilkan Halaman Utama & Data Dinamis
    public function index(Request $request)
    {
        $tableName = $request->query('table');
        $tableNames = [];
        $allTablesWithColumns = []; // Penampung baru untuk ringkasan All Tables
        $columns = [];
        $rows = [];

        $resTables = $this->api()->get($this->apiUrl . '/tables');
        if ($resTables->successful()) {
            foreach ($resTables->json() as $table) {
                $name = $table['table_name'];
                $tableNames[] = $name;
                $allTablesWithColumns[$name] = $table['columns']; // Map tabel ke kolom
            }
        }

        if ($tableName && in_array($tableName, $tableNames)) {
            $resData = $this->api()->get($this->apiUrl . '/tables/' . $tableName);
            if ($resData->successful()) {
                $data = $resData->json();
                $columns = $data['columns'] ?? [];
                $rows = $data['rows'] ?? [];
            }
        }

        return view('dashboard.admin', compact('tableNames', 'tableName', 'columns', 'rows', 'allTablesWithColumns'));
    }

    // 2. Insert Data Dinamis
    public function store(Request $request, $tableName)
    {
        $data = $request->except(['_token']);
        $res = $this->api()->post($this->apiUrl . '/tables/' . $tableName, $data);
        
        if ($res->successful()) return redirect("/admin/master?table=$tableName")->with('success', "Data berhasil ditambahkan ke tabel $tableName.");
        return back()->with('error', 'Gagal menambahkan data.');
    }

    // 3. Update Data Dinamis
    public function update(Request $request, $tableName, $id)
    {
        $data = $request->except(['_token', '_method']);
        $res = $this->api()->put($this->apiUrl . "/tables/$tableName/$id", $data);
        
        if ($res->successful()) {
            return redirect("/admin/master?table=$tableName")->with('success', "Data di tabel $tableName berhasil diperbarui.");
        }
        
        // Menampilkan pesan error spesifik yang dikirim dari master_service
        $error = $res->json('message') ?? 'Gagal memperbarui data pengguna.';
        return back()->with('error', $error)->withInput();
    }

    // 4. Delete Data Dinamis
    public function destroy($tableName, $id)
    {
        $res = $this->api()->delete($this->apiUrl . "/tables/$tableName/$id");
        
        if ($res->successful()) return redirect("/admin/master?table=$tableName")->with('success', 'Data berhasil dihapus.');
        return back()->with('error', 'Gagal menghapus data.');
    }

    // 5. Eksekusi Raw SQL (Tambah Kolom/Tabel/Import)
    public function executeSql(Request $request)
    {
        $res = $this->api()->post($this->apiUrl . '/execute-sql', ['sql' => $request->sql]);
        
        if ($res->successful()) return back()->with('success', 'Query SQL berhasil dieksekusi.');
        return back()->with('error', 'Gagal mengeksekusi SQL: ' . ($res->json('message') ?? 'Periksa sintaks Anda.'));
    }

    // 6. Proxy Export (Meneruskan ke master_service)
    public function exportSql($tableName = null)
    {
        $url = "http://127.0.0.1:8004/api/export/sql" . ($tableName ? "/$tableName" : "");
        return redirect($url);
    }

public function exportExcel($tableName = null)
    {
        // Meneruskan request ke master_service, jika $tableName null maka mengarah ke full database excel
        $url = "http://127.0.0.1:8004/api/export/excel" . ($tableName ? "/$tableName" : "");
        return redirect($url);
    }
}