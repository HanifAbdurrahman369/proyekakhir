@extends('layouts.public')

@section('content')
<div class="relative w-full h-screen">
    <img src="{{ asset('storage/bg.png') }}"
         class="absolute inset-0 w-full h-full object-cover"
         alt="bg">

    <div class="absolute inset-0 bg-black/50"></div>

    <div class="relative z-10 flex h-full flex-col items-center justify-center text-center px-6">
        <p class="text-white font-regular text-2xl">SELAMAT DATANG DI</p>
        <p class="text-white font-regular text-4xl font-bold">SIG-PALA</p>
        <p class="text-white font-regular text-lg max-w-xl">
            SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS PADA LAHAN RAWA BATOLA
        </p>
    </div>
</div>

<div class="flex w-full py-8 px-2.5 flex-col items-center gap-10">
    <p class="text-slate-700 text-3xl text-center font-bold">MAP INTERAKTIF</p>

    <div class="relative w-full max-w-6xl h-[600px] rounded-2xl shadow-2xl overflow-hidden bg-white">
        <div id="map" class="w-full h-full z-0"></div>

        <div id="side-panel" class="absolute top-0 right-[-400px] w-[350px] h-full bg-white/95 backdrop-blur-md z-[1000] shadow-2xl transition-all duration-500 ease-in-out p-6 overflow-y-auto border-l border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-primary-700">Detail Lahan Sawah</h3>
                <button onclick="closeSidePanel()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="panel-content" class="space-y-4">
                <p class="text-gray-400 italic text-center py-10">Pilih "Lihat Detail" pada peta untuk melihat informasi...</p>
            </div>
        </div>
    </div>
</div>

<div class="flex w-full py-8 px-4 flex-col items-center gap-10">
    <p class="text-slate-700 text-3xl text-center">DATA STATISTIK</p>

    <div class="grid md:grid-cols-3 gap-6 w-full max-w-6xl">
        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Kecamatan</p>
            <p class="text-2xl font-bold text-primary-700" id="stat-kecamatan">...</p>
        </div>

        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Kelurahan</p>
            <p class="text-2xl font-bold text-primary-700" id="stat-kelurahan">...</p>
        </div>

        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Hasil Panen</p>
            <p class="text-2xl font-bold text-primary-700" id="stat-panen">...</p>
        </div>
    </div>

    <div class="w-full max-w-6xl bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-primary-500 text-white">
                <tr>
                    <th class="p-3">Kecamatan</th>
                    <th class="p-3">Kelurahan</th>
                    <th class="p-3">Hasil Panen (Ton)</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <tr class="border-b"><td class="p-3">Alalak</td><td class="p-3">Handil Bakti</td><td class="p-3">5.200</td></tr>
                <tr class="border-b"><td class="p-3">Anjir Muara</td><td class="p-3">Anjir Pasar</td><td class="p-3">6.100</td></tr>
                <tr class="border-b"><td class="p-3">Mandastana</td><td class="p-3">Tabing Rimbah</td><td class="p-3">4.850</td></tr>
                <tr class="border-b"><td class="p-3">Marabahan</td><td class="p-3">Ulu Benteng</td><td class="p-3">7.300</td></tr>
                <tr class="border-b"><td class="p-3">Tabukan</td><td class="p-3">Karya Maju</td><td class="p-3">3.950</td></tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Styling Profesional & Modern untuk Layer Control */
    .leaflet-control-layers {
        border-radius: 12px !important;
        border: none !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(5px);
        padding: 10px !important;
        font-family: 'Inter', sans-serif !important;
    }

    .leaflet-control-layers-toggle {
        width: 44px !important;
        height: 44px !important;
        background-size: 26px 26px !important;
    }

    .leaflet-control-layers-list label {
        margin: 8px 0;
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .leaflet-control-layers-separator {
        height: 1px;
        background-color: #f1f5f9;
        margin: 10px 0;
    }

    /* Indikator Judul di dalam List */
    .layer-group-title {
        font-size: 10px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 5px 0;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // 1. Inisialisasi Tile Layers
        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' });
        const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri Satellite' });
        const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: 'OpenTopoMap' });

        const map = L.map('map', {
            center: [-3.0000, 114.6000],
            zoom: 10,
            layers: [osm]
        });

        // 2. Layer Groups
        const kabGroup = L.layerGroup().addTo(map); // Default Aktif
        const kecGroup = L.layerGroup(); 
        const kelGroup = L.layerGroup(); 
        const lahanGroup = L.layerGroup(); // Default Tidak Aktif

        // 3. Konfigurasi Layer Control Profesional (Collapsed Default)
        const baseMaps = {
            "🗺️ Peta Standar": osm,
            "🛰️ Citra Satelit": satellite,
            "⛰️ Peta Topografi": terrain
        };

        const overlayMaps = {
            "🏙️ Kabupaten": kabGroup,
            "🏢 Kecamatan": kecGroup,
            "🏡 Kelurahan": kelGroup,
            "<span style='color: #16a34a; font-weight: bold;'>🌾 Lahan Sawah</span>": lahanGroup
        };

        // Tambahkan ke Peta (Tombol akan muncul tertutup secara default)
        L.control.layers(baseMaps, overlayMaps, { 
            collapsed: true, 
            position: 'topright' 
        }).addTo(map);

        // 4. Ambil Batas Kabupaten Barito Kuala
        fetch('http://127.0.0.1:8000/api/batas-wilayah')
            .then(res => res.json())
            .then(geojsonData => {
                const layerBatas = L.geoJSON(geojsonData, {
                    style: {
                        color: "#2563eb",
                        weight: 2,
                        fillColor: "#3b82f6",
                        fillOpacity: 0.08 
                    }
                }).addTo(kabGroup);
                
                if (layerBatas.getBounds().isValid()) {
                    map.fitBounds(layerBatas.getBounds());
                }
            })
            .catch(err => console.error("API Batas Wilayah bermasalah"));

        // 5. Ambil Lahan Sawah
        fetch('http://127.0.0.1:8000/api/map-lahan')
            .then(res => res.json())
            .then(data => {
                L.geoJSON(data, {
                    style: { color: "#16a34a", fillColor: "#22c55e", fillOpacity: 0.6 },
                    onEachFeature: function (feature, layer) {
                        const props = feature.properties;
                        const popupContent = `
                            <div class="text-center p-1">
                                <p class="font-bold text-gray-800 mb-2">${props.nama_lahan}</p>
                                <button onclick='showDetail(${JSON.stringify(props)})' 
                                        class="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-bold hover:bg-blue-700 transition">
                                    Detail Lahan 🔍
                                </button>
                            </div>
                        `;
                        layer.bindPopup(popupContent);
                    }
                }).addTo(lahanGroup);
            })
            .catch(err => console.error("API Lahan Sawah bermasalah"));

        // 6. Fetch Statistik (Original)
        fetch('http://127.0.0.1:8000/api/statistik')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const d = res.data;
                    document.getElementById('stat-kecamatan').innerText = d.total_kecamatan || 0;
                    document.getElementById('stat-kelurahan').innerText = d.total_kelurahan || 0;
                    document.getElementById('stat-panen').innerText = (d.total_panen_ton || 0) + " Ton";
                }
            });
    });

    function showDetail(props) {
        const panel = document.getElementById('side-panel');
        const content = document.getElementById('panel-content');
        panel.style.right = '0';
        content.innerHTML = `
            <div class="bg-blue-50 p-4 rounded-xl border-l-4 border-blue-500 mb-4 shadow-sm">
                <label class="text-[10px] uppercase text-blue-400 font-bold tracking-wider">Nama Lahan</label>
                <p class="text-lg font-bold text-slate-800">${props.nama_lahan}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <label class="text-[9px] uppercase text-slate-400 font-bold">Pemilik</label>
                    <p class="text-sm font-bold text-slate-700">${props.pemilik}</p>
                </div>
                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <label class="text-[9px] uppercase text-slate-400 font-bold">Luas Lahan</label>
                    <p class="text-sm font-bold text-emerald-600">${props.luas_ha} Ha</p>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                <label class="text-[10px] uppercase text-slate-400 font-bold mb-2 block tracking-widest">Karakteristik</label>
                <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-[11px] font-bold">
                    ${props.tipe_rawa}
                </span>
                <p class="mt-2 text-[11px] text-slate-500 font-medium">Kabupaten: Barito Kuala</p>
            </div>
        `;
    }

    function closeSidePanel() {
        document.getElementById('side-panel').style.right = '-400px';
    }
</script>
@endsection