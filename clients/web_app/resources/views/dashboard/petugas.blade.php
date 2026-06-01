@extends('layouts.app')

@section('title', 'Portal Petugas Lapangan')

@section('content')

{{-- ALERT PESAN SUKSES/ERROR DARI CONTROLLER --}}
@if(session('success'))
    <div class="mb-4 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl">
        <span class="font-bold">Berhasil!</span> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">
        <span class="font-bold">Gagal!</span> {{ session('error') }}
    </div>
@endif

@if($page == 'dashboard')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-primary-900">Beranda Petugas</h1>
            <p class="text-xs text-gray-400 mt-0.5">Ringkasan kinerja dan pintasan tugas Anda</p>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-6">
        <a href="/peta-lahan" class="bg-white p-6 rounded-2xl shadow-sm border border-primary-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">🗺️</div>
            <h3 class="font-bold text-gray-800 text-lg">Mapping Wilayah</h3>
            <p class="text-xs text-gray-500 mt-1">Buat poligon batas sawah dan titik koordinat.</p>
        </a>
        <a href="/verifikasi-panen" class="bg-white p-6 rounded-2xl shadow-sm border border-primary-100 hover:shadow-md transition group">
            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition">📋</div>
            <h3 class="font-bold text-gray-800 text-lg">Validasi Data Petani</h3>
            <p class="text-xs text-gray-500 mt-1">Tinjau laporan panen yang masuk ke sistem.</p>
        </a>
    </div>

@elseif($page == 'peta')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-primary-900">Mapping Wilayah</h1>
            <p class="text-xs text-gray-400 mt-0.5">Gambar batas lahan sawah ke dalam sistem database.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-primary-100 p-4">
            <div id="map" class="w-full h-[500px] rounded-xl border z-10"></div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-primary-100 p-5">
            <h3 class="font-bold text-primary-900 mb-4 text-sm">Informasi Detail Lahan</h3>
            
            <form action="/petugas/spasial/simpan" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" id="geojson_data" name="geojson">
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <input type="hidden" id="pemilik_lahan" name="pemilik_lahan">

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Lahan</label>
                    <input type="text" name="nama_lahan" class="w-full text-sm rounded-lg border-gray-300" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Pemilik / Petani</label>
                    <select id="user_id" name="user_id" class="w-full text-sm rounded-lg border-gray-300" required onchange="document.getElementById('pemilik_lahan').value = this.options[this.selectedIndex].text;">
                        <option value="">Pilih Petani...</option>
                        @foreach($referensi['petani'] as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Luas (Ha)</label>
                        <input type="number" step="0.01" id="luas_lahan_hektar" name="luas_lahan_hektar" class="w-full text-sm rounded-lg bg-gray-100 border-gray-200" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Tipe Rawa</label>
                        <select name="tipe_lahan_id" class="w-full text-sm rounded-lg border-gray-300" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['tipe_lahan'] as $t)
                                <option value="{{ $t['id'] }}">{{ $t['nama_tipe'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Kecamatan</label>
                        <select name="kecamatan_id" class="w-full text-sm rounded-lg border-gray-300" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['kecamatan'] as $k)
                                <option value="{{ $k['id'] }}">{{ $k['nama_kecamatan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Kelurahan</label>
                        <select name="kelurahan_id" class="w-full text-sm rounded-lg border-gray-300" required>
                            <option value="">Pilih...</option>
                            @foreach($referensi['kelurahan'] as $kel)
                                <option value="{{ $kel['id'] }}">{{ $kel['nama_kelurahan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-3">
                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg transition">Simpan Lahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const map = L.map('map').setView([-3.0560, 114.6046], 11);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}').addTo(map);

            const drawnItems = new L.FeatureGroup(); map.addTo(drawnItems);
            const drawControl = new L.Control.Draw({
                edit: { featureGroup: drawnItems },
                draw: { polygon: true, marker: true, polyline: false, rectangle: false, circle: false, circlemarker: false }
            });
            map.addControl(drawControl);

            // Render Lahan yang sudah ada dari Database
            const koleksi = @json($koleksiLahan);
            if(koleksi && koleksi.features) {
                L.geoJSON(koleksi, {
                    style: { color: '#059669', fillColor: '#34d399', fillOpacity: 0.4 },
                    onEachFeature: function(feature, layer) { layer.bindPopup("<b>" + feature.properties.nama_lahan + "</b><br>Luas: " + feature.properties.luas_lahan_hektar + " Ha"); }
                }).addTo(map);
            }

            map.on(L.Draw.Event.CREATED, function (event) {
                const layer = event.layer; drawnItems.clearLayers(); drawnItems.addLayer(layer);
                const geojsonData = layer.toGeoJSON();
                document.getElementById('geojson_data').value = JSON.stringify(geojsonData.geometry);

                if (event.layerType === 'polygon') {
                    document.getElementById('luas_lahan_hektar').value = (turf.area(geojsonData) / 10000).toFixed(2);
                    const centroid = turf.centroid(geojsonData);
                    document.getElementById('longitude').value = centroid.geometry.coordinates[0];
                    document.getElementById('latitude').value = centroid.geometry.coordinates[1];
                } else if (event.layerType === 'marker') {
                    const latlng = layer.getLatLng();
                    document.getElementById('latitude').value = latlng.lat; document.getElementById('longitude').value = latlng.lng;
                    document.getElementById('luas_lahan_hektar').value = 0;
                }
            });
        });
    </script>

@elseif($page == 'verifikasi')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-primary-900">Verifikasi Siklus Tanam</h1>
            <p class="text-xs text-gray-400 mt-0.5">Tinjau dan validasi laporan panen dari petani.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-primary-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Lahan & Petani</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Bibit</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Panen</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi Validasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($antrean as $row)
                <tr class="hover:bg-primary-50/50">
                    <td class="px-6 py-4">
                        <p class="text-sm font-bold text-primary-900">{{ $row['lahan']['nama_lahan'] ?? '-' }}</p>
                        <p class="text-[10px] text-gray-500">{{ $row['lahan']['pemilik_lahan'] ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row['bibit']['nama_bibit'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-emerald-600">{{ $row['hasil_panen_ton'] ?? 0 }} Ton</td>
                    <td class="px-6 py-4"><span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">Pending</span></td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                        <form action="/petugas/verifikasi/{{ $row['id'] }}/approve" method="POST">
                            @csrf <button type="submit" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">Setujui</button>
                        </form>
                        <form action="/petugas/verifikasi/{{ $row['id'] }}/reject" method="POST">
                            @csrf <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">Tolak</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-10 text-xs text-gray-400">Belum ada data laporan yang masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@endsection