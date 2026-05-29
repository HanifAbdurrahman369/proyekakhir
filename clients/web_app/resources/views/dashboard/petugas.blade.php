<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Kerja Petugas - Sistem Informasi Dinas Pertanian</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.net/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://cdnjs.net/leaflet.draw/1.0.4/leaflet.draw.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-800 antialiased">

    <header class="bg-emerald-700 text-white shadow-md px-6 py-3 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-800 rounded-lg">🌿</div>
            <div>
                <h1 class="text-sm font-bold tracking-wide uppercase">Sistem Informasi Dinas Pertanian</h1>
                <p class="text-[10px] text-emerald-200 mt-0.5">Kabupaten Barito Kuala &bull; Basis Microservices</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 bg-emerald-800 px-3 py-1.5 rounded-lg text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span>Petugas Lapangan</span>
            </div>
            <a href="/logout" class="text-xs bg-red-600 hover:bg-red-700 font-bold uppercase px-3 py-1.5 rounded-lg transition">Keluar</a>
        </div>
    </header>

    <div class="min-h-[calc(100vh-62px)] flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-white border-r border-primary-100 flex flex-col p-4 gap-1">
            <p class="text-[10px] font-bold text-gray-400 uppercase px-3 mb-2 tracking-wider">Menu Manajemen Utama</p>
            
            <button onclick="bukaTabKerja('tab-spasial')" id="nav-tab-spasial" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition uppercase bg-primary-50 text-primary-700 border border-primary-100">
                🗺️ Data Spasial Lahan
            </button>
            
            <button class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition uppercase text-gray-400 hover:bg-gray-50 opacity-60 cursor-not-allowed" disabled>
                🌾 Verifikasi Tanam (Soon)
            </button>
            
            <button class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition uppercase text-gray-400 hover:bg-gray-50 opacity-60 cursor-not-allowed" disabled>
                🔔 Notifikasi Sistem (Soon)
            </button>
        </aside>

        <main class="flex-1 p-6 space-y-6">
            
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl text-xs font-bold shadow-sm">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-200 text-red-700 rounded-xl text-xs font-bold shadow-sm">
                    ❌ {{ session('error') }}
                </div>
            @endif

            {{-- TAB: MANAJEMEN DATA SPASIAL GIS --}}
            <div id="tab-spasial" class="tab-konten-blok block space-y-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-primary-900">Digitalisasi Pemetaan Poligon Lahan Sawah</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Petakan batas wilayah spasial lahan rawa Barito Kuala menggunakan perangkat instrumen Leaflet.draw</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-xl border border-primary-100 p-2 shadow-sm">
                        <div id="mapPetugas" class="w-full h-[480px] rounded-lg z-10"></div>
                    </div>

                    <div class="bg-white rounded-xl border border-primary-100 p-5 shadow-sm flex flex-col justify-between h-fit">
                        <form id="formLahanSpasial" action="/petugas/spasial/simpan" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" id="methodField" name="_method" value="POST">
                            <input type="hidden" id="inputGeojson" name="geojson">
                            <input type="hidden" id="inputLat" name="latitude">
                            <input type="hidden" id="inputLng" name="longitude">

                            <div class="border-b pb-2 flex items-center justify-between">
                                <h3 id="formTitle" class="font-bold text-primary-900 text-xs uppercase tracking-wider border-l-4 border-emerald-600 pl-2">Formulir Lahan Baru</h3>
                                <button type="button" id="btnResetForm" onclick="resetFormulirKeDefault()" class="hidden text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded border hover:bg-gray-200">Batal Edit</button>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Nama Kelompok / Lahan Sawah</label>
                                <input type="text" id="nama_lahan" name="nama_lahan" required class="w-full text-xs p-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Nama Pemilik Lahan (Sesuai Sertifikat)</label>
                                <input type="text" id="pemilik_lahan" name="pemilik_lahan" required class="w-full text-xs p-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Tautkan Akun Petani</label>
                                    <select id="user_id" name="user_id" required class="w-full text-xs p-2 border border-gray-300 rounded-lg bg-white">
                                        <option value="">-- Pilih --</option>
                                        @foreach($referensi['petani'] ?? [] as $p)
                                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Tipe Lahan Pertanian</label>
                                    <select id="tipe_lahan_id" name="tipe_lahan_id" required class="w-full text-xs p-2 border border-gray-300 rounded-lg bg-white">
                                        <option value="">-- Pilih --</option>
                                        @foreach($referensi['tipe_lahan'] ?? [] as $t)
                                            <option value="{{ $t['id'] }}">{{ $t['nama_tipe'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Kecamatan</label>
                                    <select id="kecamatan_id" name="kecamatan_id" required class="w-full text-xs p-2 border border-gray-300 rounded-lg bg-white">
                                        <option value="">-- Pilih --</option>
                                        @foreach($referensi['kecamatan'] ?? [] as $kec)
                                            <option value="{{ $kec['id'] }}">{{ $kec['nama_kecamatan'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Desa / Kelurahan</label>
                                    <select id="kelurahan_id" name="kelurahan_id" required class="w-full text-xs p-2 border border-gray-300 rounded-lg bg-white">
                                        <option value="">-- Pilih --</option>
                                        @foreach($referensi['kelurahan'] ?? [] as $kel)
                                            <option value="{{ $kel['id'] }}" data-kecamatan="{{ $kel['kecamatan_id'] }}">{{ $kel['nama_kelurahan'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Tipologi Rawa</label>
                                    <select id="tipe_rawa" name="tipe_rawa" required class="w-full text-xs p-2 border border-gray-300 rounded-lg bg-white">
                                        <option value="">-- Pilih --</option>
                                        <option value="Rawa Pasang Surut">Rawa Pasang Surut</option>
                                        <option value="Rawa Lebak">Rawa Lebak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Luas Digital (Hektar)</label>
                                    <input type="number" step="0.01" id="luas_lahan_hektar" name="luas_lahan_hektar" required class="w-full text-xs p-2 border border-primary-100 font-bold text-primary-700 bg-primary-50 rounded-lg focus:ring-1 focus:ring-primary-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Alamat Deskriptif Lapangan</label>
                                <textarea id="alamat_detail" name="alamat_detail" rows="2" class="w-full text-xs p-2 border border-gray-300 rounded-lg"></textarea>
                            </div>

                            <button type="submit" id="btnSubmitForm" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 rounded-lg text-xs uppercase tracking-wide transition shadow-sm">
                                💾 Simpan Poligon Lahan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-primary-100 overflow-x-auto shadow-sm">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-primary-50 text-primary-900 text-xs uppercase font-semibold border-b">
                            <tr>
                                <th class="px-6 py-4">Nama Lahan Sawah</th>
                                <th class="px-6 py-4">Fisik Pemilik</th>
                                <th class="px-4 py-4">Kecamatan</th>
                                <th class="px-4 py-4">Desa/Kelurahan</th>
                                <th class="px-4 py-4">Luas Hektar</th>
                                <th class="px-4 py-4">Tipologi Rawa</th>
                                <th class="px-6 py-4 text-center sticky right-0 bg-primary-50">Aksi Spasial</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 divide-y divide-primary-50">
                            @forelse($koleksiLahan['features'] ?? [] as $f)
                            @php $props = $f['properties']; @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 font-semibold text-gray-900">{{ $props['nama_lahan'] }}</td>
                                <td class="px-6 py-3">{{ $props['pemilik_lahan'] }}</td>
                                <td class="px-4 py-3">{{ $props['kecamatan'] }}</td>
                                <td class="px-4 py-3">{{ $props['kelurahan'] }}</td>
                                <td class="px-4 py-3 font-bold text-primary-700 font-mono">{{ $props['luas_lahan_hektar'] }} Ha</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 border rounded text-[10px] font-medium">{{ $props['tipe_rawa'] }}</span></td>
                                <td class="px-6 py-3 text-center flex justify-center gap-2 sticky right-0 bg-white shadow-[-5px_0_10px_rgba(0,0,0,0.02)]">
                                    <button onclick="pemicuEditDataSpasial({{ json_encode($f) }})" class="text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-md text-xs font-semibold border border-blue-200">Edit Peta</button>
                                    <form action="/petugas/spasial/hapus/{{ $props['id'] }}" method="POST" onsubmit="return confirm('Apakah anda yakin ingin menghapus data spasial ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-md text-xs font-semibold border border-red-200">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400 text-xs italic">Belum ada data spasial lahan rawa yang dipetakan oleh petugas lapangan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 1. INITIALIZATION INTERAKTIF MAPS
        const map = L.map('mapPetugas').setView([-3.15, 114.60], 10);

        // Satelit Layer Base
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri'
        }).addTo(map);

        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        const drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    shapeOptions: { color: '#059669', fillColor: '#10b981', fillOpacity: 0.4 }
                },
                polyline: false, circle: false, rectangle: false, marker: false, circlemarker: false
            },
            edit: { featureGroup: drawnItems, remove: true }
        });
        map.addControl(drawControl);

        // 2. KATCH EVENT DRAW CREATED
        map.on(L.Draw::Event.CREATED, function (e) {
            const layer = e.layer;
            drawnItems.clearLayers();
            drawnItems.addLayer(layer);

            const geojsonObj = layer.toGeoJSON();
            document.getElementById('inputGeojson').value = JSON.stringify(geojsonObj.geometry);

            // Hitung Hektar
            const areaMeter = L.GeometryUtil.geodesicArea(layer.getLatLngs()[0]);
            document.getElementById('luas_lahan_hektar').value = (areaMeter / 10000).toFixed(2);

            const center = layer.getBounds().getCenter();
            document.getElementById('inputLat').value = center.lat;
            document.getElementById('inputLng').value = center.lng;
        });

        map.on(L.Draw::Event.EDITED, function (e) {
            e.layers.eachLayer(function (layer) {
                const geojsonObj = layer.toGeoJSON();
                document.getElementById('inputGeojson').value = JSON.stringify(geojsonObj.geometry);
                const areaMeter = L.GeometryUtil.geodesicArea(layer.getLatLngs()[0]);
                document.getElementById('luas_lahan_hektar').value = (areaMeter / 10000).toFixed(2);
            });
        });

        // 3. RENDER DATA SPASIAL KE MAPS UTAMA
        const dataSpasialLahan = @json($koleksiLahan);
        if(dataSpasialLahan && dataSpasialLahan.features) {
            L.geoJSON(dataSpasialLahan, {
                style: function(feature) {
                    return { color: '#1e3a8a', fillColor: '#3b82f6', fillOpacity: 0.2, weight: 2 };
                },
                onEachFeature: function(feature, layer) {
                    const p = feature.properties;
                    layer.bindPopup(`
                        <div class="p-1 font-sans text-xs">
                            <h4 class="font-bold text-gray-900 border-b pb-1 uppercase">${p.nama_lahan}</h4>
                            <p class="mt-1"><b>Pemilik:</b> ${p.pemilik_lahan}</p>
                            <p class="text-blue-700 font-bold"><b>Luas:</b> ${p.luas_lahan_hektar} Ha</p>
                            <p><b>Wilayah:</b> Desa ${p.kelurahan}</p>
                        </div>
                    `);
                }
            }).addTo(map);
        }

        // 4. TRIGGER EDIT MODE FORM DATA
        function pemicuEditDataSpasial(feature) {
            resetFormulirKeDefault();
            const p = feature.properties;

            document.getElementById('formTitle').innerText = "Modifikasi Lahan: " + p.nama_lahan;
            document.getElementById('formLahanSpasial').action = "/petugas/spasial/ubah/" + p.id;
            document.getElementById('methodField').value = "PUT";
            document.getElementById('btnSubmitForm').innerText = "⚡ Simpan Perubahan Data";
            document.getElementById('btnResetForm').classList.remove('hidden');

            document.getElementById('nama_lahan').value = p.nama_lahan;
            document.getElementById('pemilik_lahan').value = p.pemilik_lahan;
            document.getElementById('luas_lahan_hektar').value = p.luas_lahan_hektar;
            document.getElementById('tipe_rawa').value = p.tipe_rawa;
            document.getElementById('alamat_detail').value = p.alamat_detail;
            document.getElementById('inputLat').value = p.center.latitude;
            document.getElementById('inputLng').value = p.center.longitude;

            drawnItems.clearLayers();
            const polyLayer = L.geoJSON(feature, {
                style: { color: '#dc2626', fillColor: '#ef4444', fillOpacity: 0.4 }
            }).getLayers()[0];
            drawnItems.addLayer(polyLayer);
            map.fitBounds(polyLayer.getBounds());
        }

        function resetFormulirKeDefault() {
            document.getElementById('formTitle').innerText = "Formulir Lahan Baru";
            document.getElementById('formLahanSpasial').action = "/petugas/spasial/simpan";
            document.getElementById('methodField').value = "POST";
            document.getElementById('btnSubmitForm').innerText = "💾 Simpan Poligon Lahan";
            document.getElementById('btnResetForm').classList.add('hidden');
            document.getElementById('formLahanSpasial').reset();
            drawnItems.clearLayers();
        }

        // 5. DINAMIS DROPDOWN FILTERING
        document.getElementById('kecamatan_id').addEventListener('change', function() {
            const kecId = this.value;
            const kelSelect = document.getElementById('kelurahan_id');
            kelSelect.value = "";
            
            Array.from(kelSelect.options).forEach(option => {
                if(option.value === "") return;
                if(option.getAttribute('data-kecamatan') === kecId) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>