@extends('layouts.app')

@section('title', 'Lapor Hasil Panen')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6">

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-2xl bg-green-100 text-green-700 border border-green-200 text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="mb-5 p-3.5 rounded-2xl bg-red-100 text-red-700 border border-red-200 text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION ERROR --}}
    @if($errors->any())
        <div class="mb-5 p-3.5 rounded-2xl bg-red-100 text-red-700 border border-red-200 text-xs">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-[#e7efd8]">
            {{-- HEADER --}}
            <div class="bg-primary-700 text-white px-5 py-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary-100 mb-1">
                    Laporan Hasil Panen
                </p>
                <h1 class="text-base font-bold sm:text-lg">
                    Form Lapor Panen Petani
                </h1>
                <p class="text-xs text-primary-100 mt-0.5">
                    Silakan pilih lahan dan tanggal tanam, kemudian masukkan data hasil panen riil Anda.
                </p>
            </div>

            {{-- FORM BODY --}}
            <div class="p-5">
                <form action="{{ route('lapor.panen.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- PILIH LAHAN --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Pilih Lahan <span class="text-red-500">*</span>
                            </label>
                            <select name="lahan_id" id="lahan_id"
                                    class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    required>
                                <option value="">-- Pilih Lahan Sawah --</option>
                                @forelse($lahan as $item)
                                    <option value="{{ $item['id'] }}" @selected(old('lahan_id') == $item['id'])>
                                        {{ $item['nama_lahan'] }} | {{ $item['kecamatan'] ?? '-' }} | {{ $item['luas_lahan_hektar'] ?? 0 }} Ha
                                    </option>
                                @empty
                                    <option disabled>Belum memiliki lahan terdaftar</option>
                                @endforelse
                            </select>
                        </div>

                        {{-- TANGGAL TANAM (DROPDOWN) --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Tanggal Tanam <span class="text-red-500">*</span>
                            </label>
                            <select name="siklus_tanam_id" id="siklus_tanam_id"
                                    class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    required>
                                <option value="">-- Pilih Lahan Terlebih Dahulu --</option>
                            </select>
                        </div>

                        {{-- JENIS BIBIT --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Jenis Bibit
                            </label>
                            <select name="bibit_id" id="bibit_id"
                                    class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs bg-gray-50 focus:outline-none"
                                    disabled>
                                <option value="">-- Bibit Terdeteksi --</option>
                                @foreach($bibit as $item)
                                    <option value="{{ $item['id'] }}">
                                        {{ $item['nama_bibit'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ESTIMASI PANEN --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Estimasi Panen (Hari)
                            </label>
                            <input type="number"
                                   name="estimasi_panen"
                                   id="estimasi_panen"
                                   class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>

                        {{-- TANGGAL PANEN --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Tanggal Panen <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   name="tanggal_panen"
                                   id="tanggal_panen"
                                   value="{{ old('tanggal_panen') }}"
                                   class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   required>
                        </div>

                        {{-- HASIL PANEN --}}
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-700">
                                Hasil Panen (Ton) <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   name="hasil_panen"
                                   id="hasil_panen"
                                   value="{{ old('hasil_panen') }}"
                                   placeholder="Contoh: 4.5"
                                   class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                   required>
                        </div>
                    </div>

                    {{-- STATUS SISTEM --}}
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                        <h4 class="font-semibold text-xs text-blue-800 mb-2">
                            Informasi Pelaporan
                        </h4>
                        <div class="space-y-1.5 text-xs text-blue-700">
                            <p>
                                <strong>Status Verifikasi Laporan:</strong> PENDING
                            </p>
                            <p class="text-[10px] text-blue-600 pt-1 leading-relaxed">
                                Laporan panen ini akan disimpan secara mandiri di database tanpa menimpa data awal siklus tanam Anda. Laporan akan diverifikasi oleh petugas.
                            </p>
                        </div>
                    </div>

                    {{-- BUTTONS --}}
                    <div class="flex flex-col sm:flex-row justify-end gap-2.5 mt-5">
                        <a href="{{ route('riwayat.panen') }}"
                           class="w-full sm:w-auto text-center px-8 py-2 rounded-xl border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </a>
                        <button type="submit"
                                class="w-full sm:w-auto px-5 py-2 rounded-xl bg-primary-700 text-xs font-semibold text-white hover:bg-primary-800 transition">
                            Kirim Laporan Panen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lahanSelect = document.getElementById('lahan_id');
        const siklusSelect = document.getElementById('siklus_tanam_id');
        const bibitSelect = document.getElementById('bibit_id');
        const estimasiInput = document.getElementById('estimasi_panen');
        const tanggalPanenInput = document.getElementById('tanggal_panen');
        const hasilPanenInput = document.getElementById('hasil_panen');

        // Data semua siklus tanam dari PHP
        const rawSiklus = @json($siklusTanam);
        
        // Filter siklus tanam yang bisa dilaporkan panen (sudah DITERIMA oleh petugas)
        const siklusTanam = rawSiklus.filter(item => item.status_verifikasi === 'DITERIMA');

        lahanSelect.addEventListener('change', function() {
            const selectedLahanId = this.value;
            
            resetFormFields();

            if (!selectedLahanId) {
                siklusSelect.innerHTML = '<option value="">-- Pilih Lahan Terlebih Dahulu --</option>';
                return;
            }

            // Filter siklus berdasarkan lahan_id
            const filtered = siklusTanam.filter(item => String(item.lahan_id) === String(selectedLahanId));

            if (filtered.length === 0) {
                siklusSelect.innerHTML = '<option value="">Tidak ada siklus aktif di lahan ini</option>';
                return;
            }

            siklusSelect.innerHTML = '<option value="">-- Pilih Tanggal Tanam --</option>';
            filtered.forEach(item => {
                const dateFormatted = formatDate(item.tanggal_tanam);
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${dateFormatted} (${item.nama_bibit})`;
                siklusSelect.appendChild(option);
            });
        });

        siklusSelect.addEventListener('change', function() {
            const selectedSiklusId = this.value;
            resetFormFields();

            if (!selectedSiklusId) return;

            // Cari detail siklus terpilih
            const detail = siklusTanam.find(item => String(item.id) === String(selectedSiklusId));

            if (detail) {
                // Set bibit
                bibitSelect.value = detail.bibit_id || '';
                
                // Set estimasi panen
                estimasiInput.value = detail.estimasi_panen || '';

                // Set tanggal panen (jika ada)
                if (detail.tanggal_panen) {
                    tanggalPanenInput.value = formatDateYmd(detail.tanggal_panen);
                }

                // Set hasil panen (jika ada)
                if (detail.hasil_panen !== null && detail.hasil_panen !== undefined) {
                    hasilPanenInput.value = detail.hasil_panen;
                }
            }
        });

        function resetFormFields() {
            bibitSelect.value = '';
            estimasiInput.value = '';
            tanggalPanenInput.value = '';
            hasilPanenInput.value = '';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch (e) {
                return dateString;
            }
        }

        function formatDateYmd(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            } catch (e) {
                return '';
            }
        }
    });
</script>
@endsection
