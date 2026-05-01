// ========================================================
// 0. INJEKSI CSS DINAMIS UNTUK MAP & CONTROL LAYER
// ========================================================
document.addEventListener("DOMContentLoaded", function () {
    // Cek apakah ini halaman full map (mencari elemen yang tidak ada di welcome)
    // Jika body tidak memiliki scroll (seperti yang kita butuhkan di full map)
    const isFullMap = window.location.pathname.includes('/map'); // Sesuaikan dengan route Anda

    let baseStyles = `
        /* KONTROL LAYER MODERN REDESIGN */
        .leaflet-control-layers {
            border-radius: 16px !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px);
            padding: 16px 20px !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12) !important;
            font-family: 'Poppins', sans-serif !important;
            min-width: 220px;
        }

        .leaflet-control-layers-list::before {
            content: "CONTROL LAYER";
            display: block;
            font-weight: 800;
            color: #16a34a;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-align: center;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 2px dashed #f1f5f9;
        }

        .leaflet-control-layers-list label {
            font-size: 14.5px !important;
            font-weight: 500;
            color: #475569;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .leaflet-control-layers-list label:hover {
            background: #f0fdf4;
            color: #16a34a;
            transform: translateX(4px);
        }

        .leaflet-control-layers-selector {
            appearance: none;
            -webkit-appearance: none;
            width: 20px !important;
            height: 20px !important;
            border: 2px solid #cbd5e1;
            border-radius: 50% !important;
            margin: 0 !important;
            position: relative;
            cursor: pointer;
            outline: none;
            transition: 0.3s;
            flex-shrink: 0;
            background-color: white;
        }

        .leaflet-control-layers-selector:checked {
            border-color: #16a34a;
        }

        .leaflet-control-layers-selector:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background-color: #16a34a;
            border-radius: 50%;
        }

        .leaflet-control-layers-separator {
            height: 1px;
            background-color: #e2e8f0;
            margin: 10px 0;
        }

        .leaflet-popup-content-wrapper, .leaflet-popup-tip {
            font-family: 'Poppins', sans-serif !important;
        }

        @media (max-width: 640px) {
            #side-panel {
                width: 100% !important; 
            }
        }
    `;

    // Jika ini adalah halaman Full Map, tambahkan aturan hilangkan scroll body
    if (isFullMap) {
        baseStyles = `
            body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        ` + baseStyles;
    }

    // Membuat elemen <style> dan memasukkannya ke dalam <head> HTML
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = baseStyles;
    document.head.appendChild(styleSheet);


    // ========================================================
    // SISA KODE JAVASCRIPT ANDA
    // ========================================================
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
    const kabGroup = L.layerGroup().addTo(map); 
    const kecGroup = L.layerGroup(); 
    const kelGroup = L.layerGroup(); 
    const lahanGroup = L.layerGroup(); 

    // 3. Konfigurasi Layer Control
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

    L.control.layers(baseMaps, overlayMaps, { 
        collapsed: true, 
        position: 'topright' 
    }).addTo(map);

    // 4. Ambil Batas Kabupaten Barito Kuala
    fetch('http://127.0.0.1:8000/api/batas-wilayah')
        .then(res => res.json())
        .then(geojsonData => {
            const layerBatas = L.geoJSON(geojsonData, {
                style: { color: "#2563eb", weight: 2, fillColor: "#3b82f6", fillOpacity: 0.08 }
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
                        <div class="text-center p-1 min-w-[150px]">
                            <p class="font-bold text-gray-800 mb-1">${props.nama_lahan}</p>
                            <p class="text-xs text-gray-500 font-medium mb-3">Pemilik: <span class="text-gray-700">${props.pemilik}</span></p>
                            <button onclick='showDetail(${JSON.stringify(props).replace(/'/g, "&#39;")})' 
                                    class="bg-blue-600 text-white px-4 py-1.5 rounded-full text-xs font-bold hover:bg-blue-700 transition w-full shadow-sm">
                                Detail Lahan 🔍
                            </button>
                        </div>
                    `;
                    layer.bindPopup(popupContent);
                }
            }).addTo(lahanGroup);
        })
        .catch(err => console.error("API Lahan Sawah bermasalah"));

    // 6. Fetch Statistik
    fetch('http://127.0.0.1:8000/api/statistik')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const d = res.data;
                const statKecamatan = document.getElementById('stat-kecamatan');
                const statKelurahan = document.getElementById('stat-kelurahan');
                const statTotalLahan = document.getElementById('stat-total-lahan');
                const statTotalLuas = document.getElementById('stat-total-luas');

                // Update data statistik jika elemennya ada di halaman
                if (statKecamatan) statKecamatan.innerText = d.total_kecamatan || 0;
                if (statKelurahan) statKelurahan.innerText = d.total_kelurahan || 0;
                if (statTotalLahan) statTotalLahan.innerText = d.total_lahan_sawah || 0;
                if (statTotalLuas) {
                    const luasHa = parseFloat(d.total_luas_ha || 0).toFixed(2);
                    statTotalLuas.innerText = luasHa + " Ha";
                }
            }
        })
        .catch(err => console.error("Gagal memuat statistik"));
});

// Fungsi Global untuk UI Panel
function showDetail(props) {
    const panel = document.getElementById('side-panel');
    const content = document.getElementById('panel-content');
    if (!panel || !content) return; // Mencegah error jika elemen tidak ada
    
    panel.style.right = '0';
    content.innerHTML = `
        <div class="bg-blue-50 p-4 rounded-xl border-l-4 border-blue-500 mb-4 shadow-sm">
            <p class="text-lg font-bold text-slate-800 leading-tight">${props.nama_lahan}</p>
            <p class="text-sm text-blue-700 mt-1 font-medium flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Pemilik: ${props.pemilik}
            </p>
        </div>
        
        <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm mb-4">
            <label class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-3 block border-b border-gray-100 pb-1">
                Informasi Lokasi
            </label>
            <div class="space-y-2">
                <div class="flex items-start gap-2">
                    <span class="text-gray-400 mt-0.5">📍</span>
                    <div>
                        <p class="text-xs text-gray-500 font-medium leading-none">Kecamatan / Kelurahan</p>
                        <p class="text-sm text-slate-700 font-semibold mt-0.5">${props.kecamatan} / ${props.kelurahan}</p>
                    </div>
                </div>
                <div class="flex items-start gap-2 mt-2 pt-2 border-t border-gray-50">
                    <span class="text-gray-400 mt-0.5">🏠</span>
                    <div>
                        <p class="text-xs text-gray-500 font-medium leading-none">Alamat Detail</p>
                        <p class="text-sm text-slate-700 mt-0.5 leading-snug">${props.alamat_detail}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                <label class="text-[10px] text-gray-400 font-bold uppercase">Luas Lahan</label>
                <p class="font-bold text-slate-700">${props.luas_ha} Ha</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                <label class="text-[10px] text-gray-400 font-bold uppercase">Produktivitas</label>
                <p class="font-bold text-blue-600">${props.produktivitas} Ton/Ha</p>
            </div>
        </div>

        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 shadow-inner">
            <label class="text-[10px] text-emerald-600 font-bold uppercase tracking-widest">Estimasi Hasil Panen</label>
            <p class="text-3xl font-black text-emerald-700 mt-1">${props.total_panen} <span class="text-sm font-bold text-emerald-600">Ton</span></p>
        </div>
    `;
}

function closeSidePanel() {
    const panel = document.getElementById('side-panel');
    if (panel) {
        // Cek ukuran layar untuk menentukan nilai right saat ditutup
        if (window.innerWidth <= 640) {
            panel.style.right = '-110%';
        } else {
            panel.style.right = '-450px';
        }
    }
}