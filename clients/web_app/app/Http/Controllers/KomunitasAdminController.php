<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KomunitasImport;
use App\Exports\KomunitasExport;

class KomunitasAdminController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
    }

    private function api()
    {
        return Http::withHeaders(['Connection' => 'close'])->withToken(session('token'))->acceptJson()->withoutVerifying()->timeout(15);
    }

    private function errorMessage($response, string $fallback): string
    {
        $errors = $response->json('errors');

        if (is_array($errors)) {
            $messages = [];
            foreach ($errors as $fieldErrors) {
                foreach ((array) $fieldErrors as $message) {
                    $messages[] = $message;
                }
            }
            if (!empty($messages)) {
                return implode(' ', $messages);
            }
        }
        return $response->json('message') ?? $response->json('error') ?? $fallback;
    }

    public function store(Request $request)
    {
        $response = $this->api()->post($this->gatewayUrl() . '/api/komunitas', $request->all());

        if ($response->successful()) {
            return redirect('/dashboard-admin')->with('success', 'Komunitas berhasil ditambahkan.');
        }

        $error = $this->errorMessage($response, 'Gagal menambahkan komunitas (Cek validasi).');
        return back()->with('error', $error)->withInput();
    }

    public function update(Request $request, $id)
    {
        $response = $this->api()->put($this->gatewayUrl() . '/api/komunitas/' . $id, $request->all());

        if ($response->successful()) {
            return redirect('/dashboard-admin')->with('success', 'Data komunitas berhasil diperbarui.');
        }
        
        $error = $this->errorMessage($response, 'Gagal memperbarui data komunitas.');
        return back()->with('error', $error)->withInput();
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->gatewayUrl() . '/api/komunitas/' . $id);
        
        if ($response->successful()) {
            return redirect('/dashboard-admin')->with('success', 'Komunitas berhasil dihapus.');
        }
        
        $error = $this->errorMessage($response, 'Gagal menghapus data komunitas.');
        return back()->with('error', $error);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            $import = new KomunitasImport($this->gatewayUrl(), session('token'));
            Excel::import($import, $request->file('file'));
            
            $msg = 'Berhasil mengimpor ' . $import->getSuccessCount() . ' baris data komunitas.';
            if ($import->getFailureCount() > 0) {
                $msg .= ' Terdapat ' . $import->getFailureCount() . ' baris gagal (cek format).';
            }
            return redirect('/dashboard-admin')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $response = $this->api()->get($this->gatewayUrl() . '/api/komunitas');
        $komunitas = $response->successful() ? ($response->json('data') ?? []) : [];
        
        return Excel::download(new KomunitasExport($komunitas), 'Data_Komunitas_'.date('Ymd_His').'.xlsx');
    }
}
