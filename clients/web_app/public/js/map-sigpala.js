// ========================================================
// 0. INJEKSI CSS DINAMIS UNTUK MAP & CONTROL LAYER
// ========================================================
document.addEventListener("DOMContentLoaded", function () {
    const gatewayUrl = window.GATEWAY_URL || '';
    const apiBase = gatewayUrl ? `${gatewayUrl}/api` : '/api';
    const isFullMap = window.location.pathname.includes('/map');

    let baseStyles = `
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

        /* ============================================
           VARIABEL WARNA TEMA HIJAU
        ============================================ */
        :root {
            --green-50:  #f0fdf4;
            --green-100: #dcfce7;
            --green-200: #bbf7d0;
            --green-400: #4ade80;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            --green-800: #166534;
            --green-900: #14532d;
            --slate-50:  #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        /* ============================================
           MODIFIKASI TOMBOL ZOOM (+ / -) MODERN
        ============================================ */
        .leaflet-bar {
            border: none !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
            border-radius: 16px !important;
            overflow: hidden;
            margin-top: 24px !important;
            margin-left: 24px !important;
        }
        
        .leaflet-bar a {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            color: var(--slate-600) !important;
            border-bottom: 1px solid var(--slate-100) !important;
            transition: all 0.2s ease !important;
            width: 44px !important;
            height: 44px !important;
            line-height: 44px !important;
            font-weight: 500 !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 18px !important;
        }

        .leaflet-bar a:last-child {
            border-bottom: none !important;
        }

        .leaflet-bar a:hover {
            background-color: var(--green-600) !important;
            color: #ffffff !important;
        }

        /* ============================================
           KONTROL LAYER — CLEAN MODERN REDESIGN
        ============================================ */
        /* Tombol Utama (Wadah Icon Default) */
        .leaflet-control-layers-toggle {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
            border: none !important;
            width: 44px !important;
            height: 44px !important;
            transition: all 0.2s ease !important;
        }
        
        .leaflet-control-layers-toggle:hover {
            background-color: var(--slate-50) !important;
        }

        /* Wadah Menu Terbuka */
        .leaflet-control-layers {
            border-radius: 16px !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            padding: 0 !important;
            border: 1px solid var(--slate-100) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important;
            font-family: 'Poppins', sans-serif !important;
            min-width: 210px;
            overflow: hidden;
            margin-top: 24px !important;
            margin-right: 24px !important;
        }

        .leaflet-control-layers-list {
            padding: 14px 16px 16px !important;
        }

        .leaflet-control-layers-list::before {
            content: "LAYER PETA";
            display: block;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 1.5px;
            color: var(--slate-400);
            text-align: center;
            padding: 8px 0;
            margin: -14px -16px 12px -16px;
            border-bottom: 1px solid var(--slate-100);
        }

        .leaflet-control-layers-separator {
            height: 1px;
            background: var(--slate-100);
            margin: 8px 0 !important;
            border: none !important;
        }

        .leaflet-control-layers-list label {
            font-family: 'Poppins', sans-serif !important;
            font-size: 14px !important;
            font-weight: 500;
            color: var(--slate-700);
            margin: 2px 0 !important;
            display: flex !important;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 7px 10px !important;
            border-radius: 8px;
            transition: background 0.18s, color 0.18s;
        }

        .leaflet-control-layers-list label:hover {
            background: var(--slate-50);
            color: var(--green-600);
        }

        /* RADIO BUTTON TETAP TIDAK DIUBAH DARI FILE ANDA */
        .leaflet-control-layers-selector {
            appearance: none !important;
            -webkit-appearance: none !important;
            width: 18px !important;
            height: 18px !important;
            border: 2px solid var(--slate-200) !important;
            border-radius: 50% !important;
            margin: 0 !important;
            position: relative;
            cursor: pointer;
            outline: none !important;
            transition: border-color 0.2s, box-shadow 0.2s;
            flex-shrink: 0;
            background: white;
        }

        .leaflet-control-layers-selector:checked {
            border-color: var(--green-600) !important;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .leaflet-control-layers-selector:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: var(--green-600);
            border-radius: 50%;
        }

        /* ============================================
           POPUP LAHAN SAWAH — TIDAK DISENTUH
        ============================================ */
        .leaflet-popup-content-wrapper {
            font-family: 'Poppins', sans-serif !important;
            border-radius: 16px !important;
            border: 1px solid var(--green-200) !important;
            box-shadow: 0 12px 40px rgba(22, 163, 74, 0.18), 0 2px 10px rgba(0,0,0,0.07) !important;
            padding: 0 !important;
            overflow: hidden !important;
            min-width: 200px;
        }

        .leaflet-popup-content {
            margin: 0 !important;
        }

        .leaflet-popup-tip-container {
            margin-top: -1px !important;
        }

        .leaflet-popup-tip {
            background: white !important;
            box-shadow: none !important;
        }

        .leaflet-popup-close-button {
            color: var(--slate-400) !important;
            font-size: 18px !important;
            top: 8px !important;
            right: 10px !important;
            width: 24px !important;
            height: 24px !important;
            line-height: 24px !important;
            border-radius: 50% !important;
            transition: color 0.15s, background 0.15s !important;
            z-index: 10;
        }

        .leaflet-popup-close-button:hover {
            color: var(--green-700) !important;
            background: var(--green-50) !important;
        }

        /* ============================================
           SIDE PANEL
        ============================================ */
        #side-panel {
            font-family: 'Poppins', sans-serif !important;
        }

        @media (max-width: 640px) {
            #side-panel {
                width: 100% !important;
            }
        }
    `;

    if (isFullMap) {
        baseStyles = `body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }\n` + baseStyles;
    }

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
    fetch(`${apiBase}/batas-wilayah`)
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
  // 5. Ambil Lahan Sawah
    fetch(`${apiBase}/map-lahan`)
        .then(res => res.json())
        .then(data => {
            L.geoJSON(data, {
                style: { color: "#16a34a", fillColor: "#22c55e", fillOpacity: 0.6 },
                onEachFeature: function (feature, layer) {
                    const props = feature.properties;
                    // ============================================
                    // POPUP REDESIGN — CLEAN GREEN CARD
                    // ============================================
                    const popupContent = `
                        <div style="font-family:'Poppins',sans-serif; min-width:220px; overflow:hidden;">
                            
                            <!-- HEADER POPUP -->
                            <div style="background:#16a34a; padding:14px 16px 12px;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:2px;">
                                    <div style="width:28px; height:28px; background:rgba(255,255,255,0.2); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">🌾</div>
                                    <div>
                                        <p style="margin:0; font-size:13px; font-weight:700; color:#ffffff; line-height:1.3;">${props.nama_lahan}</p>
                                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.75); font-weight:400;">Lahan Sawah</p>
                                    </div>
                                </div>
                            </div>

                            <!-- BODY POPUP -->
                            <div style="background:#ffffff; padding:12px 16px;">
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:10px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <p style="margin:0; font-size:12px; color:#64748b; font-weight:400;">Pemilik: <span style="font-weight:600; color:#334155;">${props.pemilik}</span></p>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px;">
                                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:8px 10px; text-align:center;">
                                        <p style="margin:0; font-size:10px; color:#16a34a; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Luas</p>
                                        <p style="margin:0; font-size:14px; font-weight:700; color:#166534;">${props.luas_ha} <span style="font-size:10px; font-weight:500;">Ha</span></p>
                                    </div>
                                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:8px 10px; text-align:center;">
                                        <p style="margin:0; font-size:10px; color:#16a34a; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Panen</p>
                                        <!-- MENGUBAH PROP MENJADI hasil_panen -->
                                        <p style="margin:0; font-size:14px; font-weight:700; color:#166534;">${props.hasil_panen} <span style="font-size:10px; font-weight:500;">Ton</span></p>
                                    </div>
                                </div>

                                <button onclick='showDetail(${JSON.stringify(props).replace(/'/g, "&#39;")})' 
                                    style="
                                        width:100%; background:#16a34a; color:white;
                                        border:none; padding:9px 0; border-radius:8px;
                                        font-family:'Poppins',sans-serif; font-size:12px;
                                        font-weight:600; cursor:pointer; letter-spacing:0.3px;
                                        display:flex; align-items:center; justify-content:center; gap:6px;
                                        transition:background 0.2s;
                                    "
                                    onmouseover="this.style.background='#15803d'"
                                    onmouseout="this.style.background='#16a34a'"
                                >
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    Lihat Detail Lahan
                                </button>
                            </div>
                        </div>
                    `;
                    layer.bindPopup(popupContent, { maxWidth: 280 });
                }
            }).addTo(lahanGroup);
        })
        .catch(err => console.error("API Lahan Sawah bermasalah"));

    // // 6. Fetch Statistik
    // fetch('http://127.0.0.1:8000/api/statistik')
    //     .then(res => res.json())
    //     .then(res => {
    //         if (res.status === 'success') {
    //             const d = res.data;
    //             const statKecamatan = document.getElementById('stat-kecamatan');
    //             const statKelurahan = document.getElementById('stat-kelurahan');
    //             const statTotalLahan = document.getElementById('stat-total-lahan');
    //             const statTotalLuas = document.getElementById('stat-total-luas');

    //             if (statKecamatan) statKecamatan.innerText = d.total_kecamatan || 0;
    //             if (statKelurahan) statKelurahan.innerText = d.total_kelurahan || 0;
    //             if (statTotalLahan) statTotalLahan.innerText = d.total_lahan_sawah || 0;
    //             if (statTotalLuas) {
    //                 const luasHa = parseFloat(d.total_luas_ha || 0).toFixed(2);
    //                 statTotalLuas.innerText = luasHa + " Ha";
    //             }
    //         }
    //     })
    //     .catch(err => console.error("Gagal memuat statistik"));
});

// ============================================================
// FUNGSI GLOBAL: showDetail — SIDE PANEL REDESIGN
// ============================================================
function showDetail(props) {
    const panel = document.getElementById('side-panel');
    const content = document.getElementById('panel-content');
    if (!panel || !content) return;

    panel.style.right = '0';

    content.innerHTML = `
        <div style="font-family:'Poppins',sans-serif;">

            <!-- ── HEADER PANEL ── -->
            <div style="
                background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
                padding: 22px 20px 48px;
                position: relative;
                overflow: hidden;
            ">
                <!-- Dekoratif lingkaran -->
                <div style="position:absolute; top:-20px; right:-20px; width:110px; height:110px; background:rgba(255,255,255,0.07); border-radius:50%;"></div>
                <div style="position:absolute; bottom:-30px; left:-10px; width:80px; height:80px; background:rgba(255,255,255,0.05); border-radius:50%;"></div>
                
                <div style="position:relative; z-index:1;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <div style="
                            width:42px; height:42px;
                            background:rgba(255,255,255,0.18);
                            border-radius:12px;
                            display:flex; align-items:center; justify-content:center;
                            font-size:20px; flex-shrink:0;
                        ">🌾</div>
                        <div>
                            <p style="margin:0; font-size:10px; color:rgba(255,255,255,0.65); font-weight:500; text-transform:uppercase; letter-spacing:1px;">Informasi Lahan</p>
                            <p style="margin:0; font-size:16px; font-weight:700; color:#ffffff; line-height:1.3;">${props.nama_lahan}</p>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.13); border-radius:8px; padding:6px 10px; width:fit-content;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <p style="margin:0; font-size:12px; color:rgba(255,255,255,0.9); font-weight:500;">${props.pemilik}</p>
                    </div>
                </div>
            </div>

            <!-- ── STATS ROW (overlap ke header) ── -->
            <div style="
                display:grid; grid-template-columns:1fr 1fr;
                gap:10px; padding:0 16px;
                margin-top:-28px; position:relative; z-index:2;
            ">
                <div style="
                    background:white; border-radius:12px;
                    border:1px solid #e2e8f0;
                    box-shadow:0 4px 16px rgba(0,0,0,0.08);
                    padding:12px 14px; text-align:center;
                ">
                    <p style="margin:0 0 2px; font-size:10px; font-weight:600; color:#16a34a; text-transform:uppercase; letter-spacing:0.8px;">Luas Lahan</p>
                    <p style="margin:0; font-size:20px; font-weight:800; color:#166534; line-height:1.1;">${props.luas_ha}</p>
                    <p style="margin:0; font-size:10px; font-weight:500; color:#94a3b8;">Hektar</p>
                </div>
                <div style="
                    background:white; border-radius:12px;
                    border:1px solid #e2e8f0;
                    box-shadow:0 4px 16px rgba(0,0,0,0.08);
                    padding:12px 14px; text-align:center;
                ">
                    <!-- UBAH PRODUKTIVITAS MENJADI HASIL PANEN -->
                    <p style="margin:0 0 2px; font-size:10px; font-weight:600; color:#16a34a; text-transform:uppercase; letter-spacing:0.8px;">Hasil Panen</p>
                    <p style="margin:0; font-size:20px; font-weight:800; color:#166534; line-height:1.1;">${props.hasil_panen}</p>
                    <p style="margin:0; font-size:10px; font-weight:500; color:#94a3b8;">Ton</p>
                </div>
            </div>

            <!-- ── BODY PANEL ── -->
            <div style="padding:14px 16px 24px;">

                <!-- Informasi Lokasi -->
                <div style="margin-bottom:12px;">
                    <p style="
                        margin:0 0 10px;
                        font-size:10px; font-weight:700; color:#94a3b8;
                        text-transform:uppercase; letter-spacing:1.2px;
                        display:flex; align-items:center; gap:6px;
                    ">
                        <span style="display:inline-block; width:20px; height:2px; background:#bbf7d0; border-radius:2px;"></span>
                        Informasi Lokasi
                        <span style="display:inline-block; flex:1; height:2px; background:#f1f5f9; border-radius:2px;"></span>
                    </p>

                    <div style="
                        background:#f8fafc; border:1px solid #e2e8f0;
                        border-radius:12px; overflow:hidden;
                    ">
                        <!-- Kecamatan & Kelurahan -->
                        <div style="padding:12px 14px; display:flex; align-items:flex-start; gap:10px;">
                            <div style="
                                width:32px; height:32px; flex-shrink:0;
                                background:#dcfce7; border-radius:8px;
                                display:flex; align-items:center; justify-content:center;
                            ">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                            <div>
                                <p style="margin:0; font-size:10px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">Kecamatan / Kelurahan</p>
                                <p style="margin:2px 0 0; font-size:13px; font-weight:600; color:#1e293b;">${props.kecamatan} <span style="color:#94a3b8; font-weight:400;">/</span> ${props.kelurahan}</p>
                            </div>
                        </div>

                        <div style="height:1px; background:#e2e8f0; margin:0 14px;"></div>

                        <!-- Alamat -->
                        <div style="padding:12px 14px; display:flex; align-items:flex-start; gap:10px;">
                            <div style="
                                width:32px; height:32px; flex-shrink:0;
                                background:#dcfce7; border-radius:8px;
                                display:flex; align-items:center; justify-content:center;
                            ">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <p style="margin:0; font-size:10px; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">Alamat Detail</p>
                                <p style="margin:2px 0 0; font-size:13px; font-weight:500; color:#1e293b; line-height:1.5;">${props.alamat_detail}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produktivitas Lahan -->
                <div style="margin-bottom:4px;">
                    <p style="
                        margin:0 0 10px;
                        font-size:10px; font-weight:700; color:#94a3b8;
                        text-transform:uppercase; letter-spacing:1.2px;
                        display:flex; align-items:center; gap:6px;
                    ">
                        <span style="display:inline-block; width:20px; height:2px; background:#bbf7d0; border-radius:2px;"></span>
                        Informasi Tambahan
                        <span style="display:inline-block; flex:1; height:2px; background:#f1f5f9; border-radius:2px;"></span>
                    </p>

                    <div style="
                        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                        border: 1.5px solid #86efac;
                        border-radius: 14px;
                        padding: 18px 20px;
                        display: flex; align-items: center; justify-content: space-between;
                    ">
                        <div>
                            <!-- UBAH ESTIMASI MENJADI PRODUKTIVITAS LAHAN -->
                            <p style="margin:0 0 4px; font-size:11px; font-weight:600; color:#16a34a; text-transform:uppercase; letter-spacing:0.8px;">Produktivitas Lahan</p>
                            <div style="display:flex; align-items:baseline; gap:5px;">
                                <span style="font-size:38px; font-weight:800; color:#15803d; line-height:1;">${props.produktivitas}</span>
                                <span style="font-size:15px; font-weight:600; color:#16a34a;">Ton / Ha</span>
                            </div>
                            <p style="margin:4px 0 0; font-size:11px; color:#4ade80; font-weight:500;">Tingkat Produktivitas Lahan Sawah Rawa</p>
                        </div>
                    
                </div>

            </div>
        </div>
    `;
}

// ============================================================
// FUNGSI GLOBAL: closeSidePanel — TIDAK DIUBAH
// ============================================================
function closeSidePanel() {
    const panel = document.getElementById('side-panel');
    if (panel) {
        if (window.innerWidth <= 640) {
            panel.style.right = '-110%';
        } else {
            panel.style.right = '-450px';
        }
    }
}