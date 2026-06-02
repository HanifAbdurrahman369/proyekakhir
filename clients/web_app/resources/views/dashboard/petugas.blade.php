@extends('layouts.app')

@section('title', 'Portal Petugas Lapangan')

@section('content')

{{-- PESAN SISTEM (BERHASIL / GAGAL) --}}
@if(session('success'))
    <div class="mb-4 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl shadow-sm text-sm">
        <span class="font-bold">Berhasil!</span> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm text-sm">
        <span class="font-bold">Gagal!</span> {{ session('error') }}
    </div>
@endif

@if($page == 'dashboard')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary-900">Beranda Petugas</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan operasional dan tugas lapangan Anda.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/manajemen-data-spasial" class="bg-white p-6 rounded-2xl shadow-sm border border-primary-100 hover:shadow-md transition group">
            <h3 class="font-bold text-gray-800 text-lg">Manajemen Data Spasial</h3>
            <p class="text-xs text-gray-500 mt-2">Kelola pemetaan digital wilayah lahan.</p>
        </a>
        <a href="/input-parameter-lingkungan" class="bg-white p-6 rounded-2xl shadow-sm border border-primary-100 hover:shadow-md transition group">
            <h3 class="font-bold text-gray-800 text-lg">Input Parameter Lingkungan</h3>
            <p class="text-xs text-gray-500 mt-2">Catat kondisi pH dan fisik lahan sawah.</p>
        </a>
        <a href="/verifikasi-data-petani" class="bg-white p-6 rounded-2xl shadow-sm border border-primary-100 hover:shadow-md transition group">
            <h3 class="font-bold text-gray-800 text-lg">Verifikasi Data Petani</h3>
            <p class="text-xs text-gray-500 mt-2">Validasi pelaporan hasil siklus tanam.</p>
        </a>
    </div>

@elseif($page == 'manajemen-data-spasial')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary-900">Manajemen Data Spasial</h1>
        <p class="text-sm text-gray-500 mt-1">Pemetaan batas poligon dan penetapan titik koordinat lahan.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-primary-100 p-4 relative">
            <div id="map" class="w-full h-[650px] rounded-xl z-10 border border-gray-200"></div>
            <div class="absolute bottom-6 left-6 z-[400] bg-white/90 backdrop-blur-sm p-3 rounded-lg shadow-lg border border-gray-100 text-xs text-gray-700">
                <span class="font-bold text-primary-900">Peralatan Peta:</span><br>
                <span class="inline-block mt-1">⬟ <b>Polygon:</b> Tarik garis batas luas lahan</span><br>
                <span class="inline-block mt-1">📍 <b>Marker:</b> Tandai titik koordinat lahan</span><br>
                <span class="inline-block mt-1 italic text-gray-500">*Klik area lahan (hijau) di peta untuk Edit/Hapus.</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-primary-100 p-6 overflow-y-auto max-h-[680px]">
            <h3 id="formTitle" class="font-bold text-primary-900 mb-4 text-sm border-b pb-2">Informasi Detail Lahan Sawah</h3>
            
            <form id="formLahanSpasial" action="/petugas/spasial/simpan" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" id="methodField" value="POST">
                
                <input type="hidden" id="geojson_data" name="geojson">
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <input type="hidden" id="pemilik_lahan" name="pemilik_lahan">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Lahan</label>
                    <input type="text" name="nama_lahan" class="w-full text-sm p-2.5 rounded-lg border-gray-300 focus:ring-primary-500" placeholder="Misal: Sawah Blok A" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Pilih Petani</label>
                    <select id="user_id" name="user_id" class="w-full text-sm p-2.5 rounded-lg border-gray-300 focus:ring-primary-500" required onchange="document.getElementById('pemilik_lahan').value = this.options[this.selectedIndex].text;">
                        <option value="">Pilih Data Petani...</option>
                        @foreach($referensi['petani'] ?? [] as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Luas (Hektar)</label>
                        <input type="number" step="0.01" id="luas_lahan_hektar" name="luas_lahan_hektar" class="w-full text-sm p-2.5 rounded-lg bg-gray-100 border-gray-200 focus:outline-none" readonly placeholder="Otomatis">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Tipe Rawa</label>
                        <select id="tipe_lahan_id" name="tipe_lahan_id" class="w-full text-sm p-2.5 rounded-lg border-gray-300 focus:ring-primary-500" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['tipe_lahan'] ?? [] as $t)
                                <option value="{{ $t['id'] }}">{{ $t['nama_tipe'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Kecamatan</label>
                        <select id="kecamatan_id" name="kecamatan_id" class="w-full text-sm p-2.5 rounded-lg border-gray-300 focus:ring-primary-500" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['kecamatan'] ?? [] as $k)
                                <option value="{{ $k['id'] }}">{{ $k['nama_kecamatan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Kelurahan</label>
                        <select id="kelurahan_id" name="kelurahan_id" class="w-full text-sm p-2.5 rounded-lg border-gray-300 focus:ring-primary-500" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['kelurahan'] ?? [] as $kel)
                                <option value="{{ $kel['id'] }}" data-kecamatan="{{ $kel['kecamatan_id'] }}">{{ $kel['nama_kelurahan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-3 flex flex-col gap-2">
                    <button type="submit" id="btnSubmitForm" class="w-full py-2.5 bg-primary-800 hover:bg-primary-900 text-white text-sm font-bold rounded-lg transition shadow-md">💾 Simpan Manajemen Data Spasial</button>
                    <button type="button" id="btnResetForm" onclick="resetFormulirKeDefault()" class="hidden w-full py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-bold rounded-lg transition">❌ Batal Edit / Buat Baru</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let map, drawnItems;

        document.addEventListener('DOMContentLoaded', () => {
            // 1. Inisialisasi Peta Satelit
            map = L.map('map').setView([-3.0560, 114.6046], 11);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            }).addTo(map);

            drawnItems = new L.FeatureGroup(); 
            map.addTo(drawnItems);

            const drawControl = new L.Control.Draw({
                edit: { featureGroup: drawnItems },
                draw: { polygon: true, marker: true, polyline: false, rectangle: false, circle: false, circlemarker: false }
            });
            map.addControl(drawControl);

            // 2. Render Peta Dari Database dan Pasang Tombol Aksi di Popup
            const koleksi = @json($koleksiLahan ?? null);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if(koleksi && koleksi.features) {
                L.geoJSON(koleksi, {
                    style: { color: '#059669', fillColor: '#34d399', fillOpacity: 0.4 },
                    onEachFeature: function(feature, layer) { 
                        let props = feature.properties;
                        let popupHtml = `
                            <div class="p-1 min-w-[220px]">
                                <h4 class="font-bold text-primary-900 border-b border-gray-200 pb-1 mb-2">${props.nama_lahan}</h4>
                                <div class="text-xs space-y-1 mb-3">
                                    <p><span class="text-gray-500">Petani:</span> <span class="font-semibold">${props.pemilik_lahan || '-'}</span></p>
                                    <p><span class="text-gray-500">Luas:</span> <span class="font-bold text-emerald-600">${props.luas_lahan_hektar} Ha</span></p>
                                    <p><span class="text-gray-500">Tipe:</span> <span class="font-semibold">${props.nama_tipe || '-'}</span></p>
                                    <p><span class="text-gray-500">Wilayah:</span> <span class="font-semibold">${props.nama_kelurahan || '-'}, ${props.nama_kecamatan || '-'}</span></p>
                                </div>
                                <div class="flex gap-2 pt-2 border-t border-gray-100">
                                    <button onclick='editLahanSpasial(${JSON.stringify(feature).replace(/'/g, "&#39;")})' class="flex-1 bg-amber-100 text-amber-700 hover:bg-amber-200 py-1.5 rounded text-xs font-bold transition">✏️ Edit</button>
                                    <form action="/petugas/spasial/destroy/${props.id}" method="POST" class="flex-1" onsubmit="return confirm('Hapus permanen poligon lahan ini?');">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="w-full bg-red-100 text-red-700 hover:bg-red-200 py-1.5 rounded text-xs font-bold transition">🗑️ Hapus</button>
                                    </form>
                                </div>
                            </div>
                        `;
                        layer.bindPopup(popupHtml); 
                    }
                }).addTo(map);
            }

            // 3. Tangkap Event Leaflet Draw
            map.on(L.Draw.Event.CREATED, function (e) {
                const layer = e.layer; 
                drawnItems.clearLayers(); 
                drawnItems.addLayer(layer);
                
                const geojson = layer.toGeoJSON();
                document.getElementById('geojson_data').value = JSON.stringify(geojson.geometry);

                // Otomatisasi Perhitungan Hektar & Koordinat Menggunakan Turf.js
                if (e.layerType === 'polygon') {
                    document.getElementById('luas_lahan_hektar').value = (turf.area(geojson) / 10000).toFixed(2);
                    const centroid = turf.centroid(geojson);
                    document.getElementById('longitude').value = centroid.geometry.coordinates[0];
                    document.getElementById('latitude').value = centroid.geometry.coordinates[1];
                } else if (e.layerType === 'marker') {
                    const latlng = layer.getLatLng();
                    document.getElementById('latitude').value = latlng.lat; 
                    document.getElementById('longitude').value = latlng.lng;
                    document.getElementById('luas_lahan_hektar').value = 0;
                }
            });

            // 4. Logika Asli Dropdown Filter: Kecamatan ➔ Kelurahan
            document.getElementById('kecamatan_id').addEventListener('change', function() {
                const kecId = this.value;
                const kelSelect = document.getElementById('kelurahan_id');
                kelSelect.value = ""; 
                
                Array.from(kelSelect.options).forEach(option => {
                    if(option.value === "") return;
                    if(option.getAttribute('data-kecamatan') == kecId) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
        });

        // 5. Fungsi Global: Memasukkan Data Peta ke Formulir Edit
        window.editLahanSpasial = function(feature) {
            const props = feature.properties;
            
            // Ubah Mode Formulir
            document.getElementById('formTitle').innerText = "✏️ Edit Informasi Lahan Sawah";
            document.getElementById('formLahanSpasial').action = `/petugas/spasial/update/${props.id}`;
            document.getElementById('methodField').value = "PUT";
            document.getElementById('btnSubmitForm').innerText = "💾 Update Data Spasial Lahan";
            document.getElementById('btnResetForm').classList.remove('hidden');

            // Isi Input Teks
            document.querySelector('[name="nama_lahan"]').value = props.nama_lahan;
            document.getElementById('luas_lahan_hektar').value = props.luas_lahan_hektar;
            document.getElementById('geojson_data').value = JSON.stringify(feature.geometry);

            // Set Dropdown Selects berdasarkan ID
            document.getElementById('user_id').value = props.pemilik_lahan_id || ''; 
            document.getElementById('tipe_lahan_id').value = props.tipe_lahan_id || '';
            document.getElementById('kecamatan_id').value = props.kecamatan_id || '';
            
            // Trigger perubahan kecamatan agar opsi kelurahan terbuka, lalu set kelurahan
            document.getElementById('kecamatan_id').dispatchEvent(new Event('change'));
            setTimeout(() => { document.getElementById('kelurahan_id').value = props.kelurahan_id || ''; }, 50);

            // Gambar ulang poligon merah di Leaflet Draw agar siap di-edit
            drawnItems.clearLayers();
            const polyLayer = L.geoJSON(feature, {
                style: { color: '#dc2626', fillColor: '#ef4444', fillOpacity: 0.4 }
            }).getLayers()[0];
            drawnItems.addLayer(polyLayer);
            map.fitBounds(polyLayer.getBounds());
            
            document.getElementById('formTitle').scrollIntoView({ behavior: 'smooth' });
        }

        // 6. Fungsi Global: Batal Edit
        window.resetFormulirKeDefault = function() {
            document.getElementById('formTitle').innerText = "Informasi Detail Lahan Sawah";
            document.getElementById('formLahanSpasial').action = "/petugas/spasial/simpan";
            document.getElementById('methodField').value = "POST";
            document.getElementById('btnSubmitForm').innerText = "💾 Simpan Manajemen Data Spasial";
            document.getElementById('btnResetForm').classList.add('hidden');
            document.getElementById('formLahanSpasial').reset();
            drawnItems.clearLayers(); // Bersihkan kanvas gambar
        }
    </script>

@elseif($page == 'input-parameter-lingkungan')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary-900">Input Parameter Lingkungan</h1>
        <p class="text-sm text-gray-500 mt-1">Pencatatan data kualitas fisik lingkungan lahan secara berkala.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-primary-100 p-8 max-w-3xl mx-auto">
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl text-sm mb-6 font-medium">
            ⚠️ Modul ini sedang dalam tahap pengembangan struktur Backend. Fungsi penyimpanan sementara dinonaktifkan.
        </div>
        
        <form action="#" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Pilih Lahan Sawah Target</label>
                <select class="w-full text-sm p-3 rounded-lg border-gray-300 focus:ring-primary-500" disabled>
                    <option>Sistem sedang dimuat...</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Ketinggian Muka Air (cm)</label>
                    <input type="number" step="0.1" class="w-full text-sm p-3 rounded-lg border-gray-300 focus:ring-primary-500" placeholder="Misal: 15.5">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Derajat Keasaman (pH Air)</label>
                    <input type="number" step="0.1" max="14" class="w-full text-sm p-3 rounded-lg border-gray-300 focus:ring-primary-500" placeholder="Misal: 6.5">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Status Kekeruhan Air</label>
                <select class="w-full text-sm p-3 rounded-lg border-gray-300 focus:ring-primary-500">
                    <option>Jernih</option>
                    <option>Sedikit Keruh</option>
                    <option>Sangat Keruh</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Catatan Tambahan Lapangan</label>
                <textarea rows="3" class="w-full text-sm p-3 rounded-lg border-gray-300 focus:ring-primary-500" placeholder="Kondisi cuaca atau temuan hama..."></textarea>
            </div>
            <div class="pt-2">
                <button type="button" class="w-full py-3 bg-gray-400 text-white text-sm font-bold rounded-lg cursor-not-allowed">Simpan Parameter Lingkungan (Ditunda)</button>
            </div>
        </form>
    </div>

@elseif($page == 'verifikasi-data-petani')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary-900">Verifikasi Data Petani</h1>
        <p class="text-sm text-gray-500 mt-1">Tinjau dan validasi laporan operasional hasil panen dari petani.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-primary-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Lahan & Petani</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Bibit</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Hasil Panen</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi Validasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($antrean ?? [] as $row)
                <tr class="hover:bg-primary-50/50 transition">
                    <td class="px-6 py-4">
                        <p class="text-sm font-bold text-primary-900">{{ $row['lahan']['nama_lahan'] ?? '-' }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $row['lahan']['pemilik_lahan'] ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-700">{{ $row['bibit']['nama_bibit'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-emerald-600">{{ $row['hasil_panen_ton'] ?? 0 }} Ton</td>
                    <td class="px-6 py-4"><span class="bg-yellow-100 text-yellow-700 border border-yellow-200 px-3 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase">Pending</span></td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                        <form action="/petugas/verifikasi/{{ $row['id'] }}/approve" method="POST">
                            @csrf <button type="submit" class="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-lg text-xs font-bold transition">Setujui</button>
                        </form>
                        <form action="/petugas/verifikasi/{{ $row['id'] }}/reject" method="POST">
                            @csrf <button type="submit" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white px-4 py-2 rounded-lg text-xs font-bold transition">Tolak</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-10 text-sm text-gray-400 italic">Sistem tidak menemukan data laporan yang perlu divalidasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@endsection~