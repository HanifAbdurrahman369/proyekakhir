// ========================================================
// SIG-PALA interactive map
// ========================================================
function sigpalaDisplay(value, fallback = '-') {
    return value === null || value === undefined || value === '' ? fallback : value;
}

function sigpalaNumber(value, fractionDigits = 2) {
    const number = Number(value);
    if (!Number.isFinite(number)) return '0';

    return number.toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: fractionDigits
    });
}

function sigpalaShortKecamatanName(name) {
    return sigpalaDisplay(name, '').replace(/^kecamatan\s+/i, '').trim();
}

function sigpalaEscapeHtml(value) {
    return String(sigpalaDisplay(value, ''))
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function sigpalaPriorityShortLabel(key) {
    return {
        tinggi: 'Tinggi',
        sedang: 'Sedang',
        rendah: 'Rendah',
        'belum-data': 'Belum data'
    }[key || 'belum-data'] || 'Belum data';
}

function sigpalaZoomToFeature(map, layer, feature) {
    if (layer && typeof layer.getBounds === 'function') {
        const bounds = layer.getBounds();
        if (bounds && bounds.isValid()) {
            map.fitBounds(bounds, { padding: [36, 36], maxZoom: 15 });
            return;
        }
    }

    if (feature?.geometry?.type === 'Point' && Array.isArray(feature.geometry.coordinates)) {
        const lng = Number(feature.geometry.coordinates[0]);
        const lat = Number(feature.geometry.coordinates[1]);
        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            map.setView([lat, lng], 17);
        }
    }
}

const SIGPALA_PRODUCTIVITY_CLASSES = {
    tinggi: {
        label: 'Produktivitas tinggi',
        color: '#15803d',
        fillColor: '#22c55e',
        textColor: '#166534'
    },
    sedang: {
        label: 'Produktivitas sedang',
        color: '#b45309',
        fillColor: '#f59e0b',
        textColor: '#92400e'
    },
    rendah: {
        label: 'Produktivitas rendah',
        color: '#b91c1c',
        fillColor: '#ef4444',
        textColor: '#991b1b'
    },
    'belum-data': {
        label: 'Belum ada data',
        color: '#64748b',
        fillColor: '#94a3b8',
        textColor: '#475569'
    }
};

function sigpalaProductivityClass(key) {
    return SIGPALA_PRODUCTIVITY_CLASSES[key || 'belum-data'] || SIGPALA_PRODUCTIVITY_CLASSES['belum-data'];
}

function sigpalaKecamatanStyle(feature) {
    const props = feature?.properties || {};
    const config = sigpalaProductivityClass(props.kategori_produktivitas || props.priority_class);
    const fillOpacity = Number(props.fill_opacity ?? 0);

    return {
        color: '#1f2937',
        weight: 0.85,
        opacity: 0.78,
        fillColor: props.fill_color || config.fillColor,
        fillOpacity: Number.isFinite(fillOpacity) && fillOpacity > 0 ? fillOpacity : 0.16
    };
}

function sigpalaKabupatenStyle(feature) {
    const props = feature?.properties || {};

    return {
        color: props.warna_peta || '#203c10',
        weight: 3.5,
        opacity: 1,
        fillColor: props.fill_color || 'transparent',
        fillOpacity: Number(props.fill_opacity ?? 0)
    };
}

function sigpalaBindKecamatanLabel(feature, layer) {
    const props = feature?.properties || {};
    const label = sigpalaShortKecamatanName(props.nama_kecamatan || props.kecamatan || props.label);
    if (!label) return;

    layer.bindTooltip(label, {
        permanent: true,
        direction: 'center',
        interactive: false,
        className: 'sigpala-kecamatan-label'
    });
}

function sigpalaBindWilayahLabel(feature, layer) {
    const props = feature?.properties || {};
    const label = props.nama_kabupaten || props.nama || props.label;
    if (!label) return;

    layer.bindTooltip(label, {
        permanent: false,
        direction: 'center',
        className: 'sigpala-wilayah-label'
    });
}

function sigpalaProductivityBadge(label, key) {
    const config = sigpalaProductivityClass(key);

    return `<span class="sigpala-priority-badge" style="background:${config.fillColor}22;color:${config.textColor};border-color:${config.fillColor}55">${sigpalaDisplay(label || config.label)}</span>`;
}

function sigpalaKecamatanPopup(props) {
    const key = props.kategori_produktivitas || 'belum-data';
    const label = props.kategori_produktivitas_label || sigpalaProductivityClass(key).label;

    return `
        <div class="sigpala-popup">
            <p class="sigpala-popup-eyebrow">Produktivitas Kecamatan</p>
            <h3>${sigpalaDisplay(props.nama_kecamatan)}</h3>
            <div class="sigpala-popup-badge-wrap">${sigpalaProductivityBadge(label, key)}</div>
            <dl>
                <div><dt>Produktivitas</dt><dd>${sigpalaNumber(props.produktivitas_ton_ha)} ton/ha</dd></div>
                <div><dt>Total panen</dt><dd>${sigpalaNumber(props.total_panen_ton)} ton</dd></div>
                <div><dt>Total luas</dt><dd>${sigpalaNumber(props.total_luas_ha)} ha</dd></div>
                <div><dt>Lahan terdata</dt><dd>${sigpalaNumber(props.jumlah_lahan, 0)}</dd></div>
            </dl>
        </div>
    `;
}

function sigpalaLahanPopup(props) {
    const id = sigpalaEscapeHtml(props.id || '');

    return `
        <div class="sigpala-popup">
            <p class="sigpala-popup-eyebrow">Lahan Sawah</p>
            <h3>${sigpalaEscapeHtml(sigpalaDisplay(props.nama_lahan))}</h3>
            <dl>
                <div><dt>Pemilik</dt><dd>${sigpalaEscapeHtml(sigpalaDisplay(props.pemilik || props.pemilik_lahan))}</dd></div>
                <div><dt>Kecamatan</dt><dd>${sigpalaEscapeHtml(sigpalaDisplay(props.nama_kecamatan || props.kecamatan))}</dd></div>
                <div><dt>Luas</dt><dd>${sigpalaNumber(props.luas_ha || props.luas_lahan_hektar)} ha</dd></div>
                <div><dt>Produktivitas</dt><dd>${sigpalaNumber(props.produktivitas || props.produktivitas_ton_ha)} t/ha</dd></div>
            </dl>
            <button type="button" class="sigpala-popup-detail-button" data-lahan-detail-id="${id}">Detail Informasi</button>
        </div>
    `;
}

function sigpalaFeatureCenter(feature) {
    const props = feature?.properties || {};
    const lat = Number(props.latitude);
    const lng = Number(props.longitude);

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        return [lat, lng];
    }

    const coords = [];
    const collect = (node) => {
        if (!Array.isArray(node)) return;
        if (typeof node[0] === 'number' && typeof node[1] === 'number') {
            coords.push(node);
            return;
        }
        node.forEach(collect);
    };

    collect(feature?.geometry?.coordinates);
    if (!coords.length) return null;

    const sums = coords.reduce((acc, item) => {
        acc.lng += Number(item[0]) || 0;
        acc.lat += Number(item[1]) || 0;
        return acc;
    }, { lat: 0, lng: 0 });

    return [sums.lat / coords.length, sums.lng / coords.length];
}

document.addEventListener('DOMContentLoaded', function () {
    const gatewayUrl = window.GATEWAY_URL || '';
    const apiBase = gatewayUrl ? `${gatewayUrl}/api` : '/api';
    const isFullMap = window.location.pathname === '/map';

    const baseStyles = `
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

        :root {
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-200: #bbf7d0;
            --green-600: #16a34a;
            --green-700: #15803d;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        body, html {
            ${isFullMap ? 'margin:0;padding:0;height:100%;overflow:hidden;' : ''}
        }

        body,
        button,
        input,
        #map,
        .leaflet-container,
        .leaflet-control,
        .leaflet-popup-content {
            font-family: 'Poppins', sans-serif !important;
        }

        .leaflet-bar {
            border: none !important;
            box-shadow: 0 4px 20px rgba(0,0,0,.08) !important;
            border-radius: 16px !important;
            overflow: hidden;
            margin-top: 24px !important;
            margin-left: 24px !important;
        }

        .leaflet-bar a {
            background-color: rgba(255,255,255,.95) !important;
            color: var(--slate-600) !important;
            border-bottom: 1px solid var(--slate-100) !important;
            width: 44px !important;
            height: 44px !important;
            line-height: 44px !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 18px !important;
            font-weight: 500 !important;
        }

        .leaflet-bar a:hover {
            background-color: var(--green-600) !important;
            color: #fff !important;
        }

        ${isFullMap ? `
        .leaflet-left .leaflet-control {
            margin-top: 104px !important;
        }
        ` : ''}

        .leaflet-control-layers {
            border: 1px solid var(--slate-100) !important;
            border-radius: 16px !important;
            background: rgba(255,255,255,.95) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,.08) !important;
            font-family: 'Poppins', sans-serif !important;
            margin-top: 24px !important;
            margin-right: 24px !important;
            overflow: hidden;
        }

        .leaflet-control-layers-toggle {
            background-color: rgba(255,255,255,.95) !important;
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,.08) !important;
            height: 44px !important;
            width: 44px !important;
        }

        .leaflet-control-layers-list {
            padding: 14px 16px 16px !important;
        }

        .leaflet-control-layers-list::before {
            content: "LAYER PETA";
            display: block;
            color: var(--slate-400);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            margin: -14px -16px 12px;
            padding: 10px 0;
            text-align: center;
            border-bottom: 1px solid var(--slate-100);
        }

        .leaflet-control-layers-list label {
            align-items: center;
            border-radius: 8px;
            color: var(--slate-700);
            cursor: pointer;
            display: flex !important;
            font-size: 13px !important;
            font-weight: 600;
            gap: 10px;
            margin: 2px 0 !important;
            padding: 7px 10px !important;
        }

        .leaflet-popup-content-wrapper {
            border: 1px solid var(--green-200) !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 40px rgba(22,163,74,.16), 0 2px 10px rgba(0,0,0,.07) !important;
            font-family: 'Poppins', sans-serif !important;
            overflow: hidden !important;
            padding: 0 !important;
        }

        .leaflet-popup-content {
            margin: 0 !important;
        }

        .leaflet-popup-tip {
            background: #fff !important;
            box-shadow: none !important;
        }

        .leaflet-popup-close-button {
            border-radius: 50% !important;
            color: var(--slate-400) !important;
            font-size: 18px !important;
            height: 24px !important;
            line-height: 24px !important;
            right: 10px !important;
            top: 8px !important;
            width: 24px !important;
            z-index: 10;
        }

        .sigpala-kecamatan-label {
            background: rgba(255,255,255,.76) !important;
            border: 1px solid rgba(15,23,42,.08) !important;
            border-radius: 8px !important;
            box-shadow: 0 6px 18px rgba(15,23,42,.10) !important;
            color: #1e293b !important;
            font-size: 9px !important;
            font-weight: 800 !important;
            letter-spacing: .02em !important;
            line-height: 1.12 !important;
            max-width: 88px !important;
            padding: 3px 6px !important;
            text-align: center !important;
            text-transform: uppercase !important;
            white-space: normal !important;
        }

        .sigpala-kecamatan-label.is-muted,
        .sigpala-kecamatan-label::before,
        .sigpala-wilayah-label::before {
            display: none !important;
        }

        .sigpala-wilayah-label {
            background: rgba(32,60,16,.92) !important;
            border: 0 !important;
            border-radius: 999px !important;
            color: #fff !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            padding: 5px 10px !important;
            text-transform: uppercase !important;
        }

        #side-panel,
        #map-insight-panel,
        #map-legend {
            font-family: 'Poppins', sans-serif !important;
            pointer-events: auto;
        }

        .sigpala-map-card {
            background: rgba(255,255,255,.94);
            border: 1px solid rgba(226,232,240,.95);
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(15,23,42,.14);
            backdrop-filter: blur(14px);
        }

        .sigpala-map-action-rail {
            bottom: 58px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: absolute;
            right: 24px;
            z-index: 8700;
        }

        .sigpala-map-action-button {
            align-items: center;
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(226,232,240,.95);
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(15,23,42,.13);
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 8px;
            height: 48px;
            justify-content: center;
            letter-spacing: .04em;
            padding: 0;
            transition: all .2s ease;
            width: 48px;
        }

        .sigpala-map-action-button:hover,
        .sigpala-map-action-button.is-active {
            background: #1f3b21;
            border-color: #1f3b21;
            color: #fff;
        }

        .sigpala-map-action-dot {
            background: #d4a43d;
            border-radius: 999px;
            box-shadow: 0 0 0 4px rgba(212,164,61,.14);
            height: 8px;
            width: 8px;
        }

        .sigpala-map-action-icon {
            height: 21px;
            width: 21px;
        }

        .sigpala-priority-panel {
            overflow: hidden;
        }

        .sigpala-priority-panel.is-hidden {
            display: none;
        }

        .sigpala-priority-panel--full {
            position: absolute;
            right: 24px;
            bottom: 112px;
            z-index: 9000;
            width: min(390px, calc(100vw - 48px));
            max-height: calc(100vh - 152px);
        }

        .sigpala-priority-panel--floating {
            position: absolute;
            right: 16px;
            top: 16px;
            z-index: 900;
            width: 360px;
            max-height: calc(100% - 32px);
        }

        .sigpala-priority-panel--embedded {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            height: 100%;
            min-height: 100%;
            position: relative;
            width: 100%;
        }

        .sigpala-priority-shell {
            color: #1f2933;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        .sigpala-priority-panel--full .sigpala-priority-shell,
        .sigpala-priority-panel--floating .sigpala-priority-shell {
            background: rgba(255,255,255,.97);
            border-radius: 18px;
        }

        .sigpala-priority-head {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 18px 20px 12px;
        }

        .sigpala-priority-eyebrow {
            color: #334155;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .24em;
            margin: 0;
            text-transform: uppercase;
        }

        .sigpala-priority-close {
            align-items: center;
            background: rgba(15,23,42,.06);
            border: 0;
            border-radius: 999px;
            color: #475569;
            cursor: pointer;
            display: none;
            flex: 0 0 auto;
            font-size: 18px;
            font-weight: 700;
            height: 32px;
            justify-content: center;
            line-height: 1;
            width: 32px;
        }

        .sigpala-priority-panel--full .sigpala-priority-close {
            display: inline-flex;
        }

        .sigpala-priority-stats {
            border-bottom: 1px solid rgba(148,163,184,.28);
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 0 20px;
            padding: 4px 0 16px;
        }

        .sigpala-priority-stat-label {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            white-space: nowrap;
        }

        .sigpala-priority-stat-value {
            color: #111827;
            font-family: 'Poppins', sans-serif;
            font-size: 38px;
            font-weight: 750;
            letter-spacing: 0;
            line-height: 1;
            margin-top: 6px;
        }

        .sigpala-priority-meta {
            color: #64748b;
            display: flex;
            flex-wrap: wrap;
            font-size: 11px;
            font-weight: 700;
            gap: 8px 14px;
            margin: 12px 20px 0;
        }

        .sigpala-priority-section {
            min-height: 0;
            padding: 20px;
        }

        .sigpala-priority-title {
            color: #334155;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .22em;
            margin: 0 0 14px;
            text-transform: uppercase;
        }

        .sigpala-insight-scroll {
            max-height: 360px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .sigpala-priority-panel--embedded .sigpala-insight-scroll {
            max-height: clamp(260px, 44vh, 430px);
        }

        .sigpala-priority-panel--full .sigpala-insight-scroll {
            max-height: calc(100vh - 430px);
        }

        .sigpala-empty-priority {
            color: #64748b;
            font-size: 13px;
            padding: 14px 6px;
        }

        .sigpala-legend {
            position: absolute;
            left: 24px;
            bottom: 24px;
            z-index: 9000;
            width: 270px;
        }

        .sigpala-legend.is-hidden,
        .sigpala-dashboard-legend.is-hidden {
            display: none;
        }

        .sigpala-dashboard-legend {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 900;
            width: 260px;
        }

        .sigpala-priority-badge {
            align-items: center;
            border: 1px solid;
            border-radius: 999px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            padding: 4px 8px;
            text-transform: uppercase;
        }

        .sigpala-popup {
            min-width: 230px;
            padding: 15px 16px 14px;
        }

        .sigpala-popup-detail-button {
            align-items: center;
            background: #1f3b21;
            border: 0;
            border-radius: 12px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            justify-content: center;
            margin-top: 13px;
            padding: 10px 12px;
            width: 100%;
        }

        .sigpala-popup-eyebrow {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            margin: 0 0 5px;
            text-transform: uppercase;
        }

        .sigpala-popup h3 {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
            margin: 0 24px 10px 0;
        }

        .sigpala-popup-badge-wrap {
            margin-bottom: 10px;
        }

        .sigpala-popup dl {
            display: grid;
            gap: 7px;
            margin: 0;
        }

        .sigpala-popup dl div,
        .sigpala-list-button {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }

        .sigpala-popup dt {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            margin: 0;
        }

        .sigpala-popup dd {
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            margin: 0;
            text-align: right;
        }

        .sigpala-list-button {
            background: transparent;
            border: 0;
            border-bottom: 1px solid rgba(148,163,184,.28);
            cursor: pointer;
            padding: 15px 6px;
            text-align: left;
            width: 100%;
        }

        .sigpala-list-button:hover {
            background: rgba(255,255,255,.64);
        }

        .sigpala-color-dot {
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 auto;
            height: 9px;
            width: 9px;
        }

        .sigpala-lahan-marker {
            background: #16a34a;
            border: 2px solid #ffffff;
            border-radius: 999px;
            box-shadow: 0 0 0 5px rgba(22,163,74,.22), 0 10px 24px rgba(15,23,42,.22);
            height: 18px;
            width: 18px;
        }

        @media (max-width: 640px) {
            #side-panel {
                width: 100% !important;
            }
        }

        @media (max-width: 1024px) {
            ${isFullMap ? `
            .leaflet-left .leaflet-control,
            .leaflet-right .leaflet-control {
                margin-top: 86px !important;
            }
            ` : ''}

            .sigpala-priority-panel--full,
            .sigpala-priority-panel--floating {
                left: 16px;
                right: 16px;
                top: auto;
                bottom: 86px;
                width: auto;
                max-height: 54vh;
            }

            .sigpala-priority-panel--full .sigpala-insight-scroll,
            .sigpala-priority-panel--floating .sigpala-insight-scroll {
                max-height: 24vh;
            }

            .sigpala-map-action-rail {
                bottom: 48px;
                right: 16px;
            }

            .sigpala-priority-stats {
                gap: 10px;
            }

            .sigpala-priority-stat-value {
                font-size: 30px;
            }

            .sigpala-legend,
            .sigpala-dashboard-legend {
                bottom: 86px;
                left: 16px;
                width: calc(100% - 32px);
                max-width: 360px;
            }

        }
    `;

    const styleSheet = document.createElement('style');
    styleSheet.type = 'text/css';
    styleSheet.innerText = baseStyles;
    document.head.appendChild(styleSheet);

    const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 20
    });
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' });
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri Satellite' });
    const terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: 'OpenTopoMap' });

    const map = L.map('map', {
        center: [-3.0000, 114.6000],
        zoom: 10,
        layers: [cartoLight]
    });

    map.createPane('sigpalaKecamatanPane');
    map.getPane('sigpalaKecamatanPane').style.zIndex = 410;
    map.createPane('sigpalaLahanPane');
    map.getPane('sigpalaLahanPane').style.zIndex = 430;
    map.createPane('sigpalaHumaPane');
    map.getPane('sigpalaHumaPane').style.zIndex = 440;

    const kabGroup = L.layerGroup().addTo(map);
    const kecGroup = L.layerGroup().addTo(map);
    const lahanGroup = L.layerGroup().addTo(map);
    const humaGroup = L.layerGroup().addTo(map);

    L.control.layers({
        'Peta Ringan': cartoLight,
        'Peta Standar': osm,
        'Citra Satelit': satellite,
        'Peta Topografi': terrain
    }, {
        'Batas Kabupaten': kabGroup,
        'Kecamatan': kecGroup,
        'Lahan Sawah': lahanGroup,
        'Lahan Termonitor (Huma)': humaGroup
    }, {
        collapsed: true,
        position: 'topright'
    }).addTo(map);

    let allKecamatanFeatures = [];
    let allLahanFeatures = [];
    let selectedKecamatanLayer = null;
    const kecamatanLayersById = new Map();
    const lahanLayersById = new Map();
    const lahanFeaturesById = new Map();
    const hasEmbeddedInsightPanel = document.getElementById('map-insight-panel')?.dataset.mapPriorityPanel === 'embedded';
    const insightPanel = isFullMap ? null : (hasEmbeddedInsightPanel ? ensureInsightPanel() : null);
    const legendPanel = ensureLegendPanel();
    ensureLegendToggle();

    renderMapLegend();
    if (insightPanel) renderKecamatanInsights();

    let uniqueKelurahans = [];
    let currentLahanLayer = null;

    Promise.allSettled([
        fetch(`${apiBase}/batas-wilayah`).then(res => res.json()),
        fetch(`${apiBase}/batas-kecamatan`).then(res => res.json()),
        fetch(`${apiBase}/map-lahan`).then(res => res.json()),
        fetch(`${apiBase}/map-lahan-termonitor`).then(res => res.json())
    ]).then(results => {
        // 1. Batas Wilayah (index 0)
        if (results[0].status === 'fulfilled') {
            const layerBatas = L.geoJSON(results[0].value, {
                interactive: false,
                style: sigpalaKabupatenStyle,
                onEachFeature: sigpalaBindWilayahLabel
            }).addTo(kabGroup);
            if (layerBatas.getBounds().isValid()) {
                map.fitBounds(layerBatas.getBounds());
            }
        } else {
            console.error('API Batas Wilayah bermasalah');
        }

        // 2. Batas Kecamatan (index 1)
        if (results[1].status === 'fulfilled') {
            const featureCollection = normalizeFeatureCollection(results[1].value);
            allKecamatanFeatures = featureCollection.features || [];
            renderKecamatanLayer(featureCollection);
            if (insightPanel) renderKecamatanInsights();
        } else {
            console.error('API Batas Kecamatan bermasalah');
        }

        // 3. Lahan Sawah (index 2)
        if (results[2].status === 'fulfilled') {
            const featureCollection = normalizeFeatureCollection(results[2].value);
            allLahanFeatures = featureCollection.features || [];
            
            const kelMap = {};
            allLahanFeatures.forEach(f => {
                const kel = f.properties.nama_kelurahan || f.properties.kelurahan;
                const kec = f.properties.nama_kecamatan || f.properties.kecamatan;
                if (kel) {
                    const key = `${kel.toLowerCase().trim()}|${(kec || '').toLowerCase().trim()}`;
                    if (!kelMap[key]) {
                        kelMap[key] = {
                            nama_kelurahan: kel,
                            nama_kecamatan: kec || '',
                            features: []
                        };
                    }
                    kelMap[key].features.push(f);
                }
            });
            uniqueKelurahans = Object.values(kelMap);
            
            renderLahanLayer(allLahanFeatures);
        } else {
            console.error('API Lahan Sawah bermasalah');
            const mapLoading = document.getElementById('map-loading');
            if (mapLoading) {
                mapLoading.innerHTML = '<h3 class="text-red-600 font-bold">Gagal Memuat Lahan Sawah</h3>';
            }
        }

        // 4. Lahan Termonitor (index 3)
        if (results[3].status === 'fulfilled') {
            const featureCollection = normalizeFeatureCollection(results[3].value);
            const humaFeatures = featureCollection.features || [];
            
            L.geoJSON(humaFeatures, {
                pane: 'sigpalaHumaPane',
                pointToLayer: function (feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 8,
                        fillColor: '#0ea5e9',
                        color: '#0284c7',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                },
                style: function(feature) {
                    return {
                        color: '#0ea5e9',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#38bdf8',
                        fillOpacity: 0.4
                    };
                },
                onEachFeature: function(feature, layer) {
                    const p = feature.properties;
                    const ph = p.ph_tanah || '-';
                    const tooltipContent = `<div class="font-bold text-sky-900">${p.nama_lahan}</div><div class="text-xs text-sky-700">IoT Huma (pH: ${ph})</div>`;
                    layer.bindTooltip(tooltipContent, {
                        direction: 'top',
                        className: 'bg-sky-50 border border-sky-200 shadow-sm'
                    });
                    
                    const popupContent = `
                        <div class="sigpala-popup border-t-4 border-sky-500 rounded-xl">
                            <p class="sigpala-popup-eyebrow text-sky-600">Lahan Termonitor (IoT)</p>
                            <h3 class="text-sky-900 mb-2">${sigpalaEscapeHtml(p.nama_lahan)}</h3>
                            <dl class="text-sm">
                                <div class="flex justify-between py-1 border-b border-sky-100"><dt class="text-slate-500">Device ID</dt><dd class="font-mono text-xs">${sigpalaEscapeHtml(p.device_id)}</dd></div>
                                <div class="flex justify-between py-1 border-b border-sky-100"><dt class="text-slate-500">pH Tanah</dt><dd class="font-bold ${parseFloat(ph) < 5.5 || parseFloat(ph) > 7.5 ? 'text-amber-600' : 'text-green-600'}">${sigpalaEscapeHtml(ph)}</dd></div>
                                <div class="flex justify-between py-1 border-b border-sky-100"><dt class="text-slate-500">Nitrogen (N)</dt><dd class="font-bold">${sigpalaEscapeHtml(p.n_level)}</dd></div>
                                <div class="flex justify-between py-1 border-b border-sky-100"><dt class="text-slate-500">Fosfor (P)</dt><dd class="font-bold">${sigpalaEscapeHtml(p.p_level)}</dd></div>
                                <div class="flex justify-between py-1 border-b border-sky-100"><dt class="text-slate-500">Kalium (K)</dt><dd class="font-bold">${sigpalaEscapeHtml(p.k_level)}</dd></div>
                                <div class="flex justify-between py-1 mt-1"><dt class="text-slate-500 text-xs">Pembaruan</dt><dd class="text-xs">${sigpalaEscapeHtml(p.waktu_rekam)}</dd></div>
                            </dl>
                        </div>
                    `;
                    layer.bindPopup(popupContent, { maxWidth: 300 });
                }
            }).addTo(humaGroup);
        } else {
            console.error('API Lahan Termonitor bermasalah');
        }

        // Hide map loading after all promises settled
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading && !mapLoading.innerHTML.includes('Gagal Memuat')) {
            mapLoading.style.opacity = '0';
            setTimeout(() => mapLoading.style.display = 'none', 500);
        }
    });

    function normalizeFeatureCollection(data) {
        if (data?.data?.type === 'FeatureCollection') return data.data;
        if (data?.type === 'FeatureCollection') return data;
        if (Array.isArray(data?.features)) return { type: 'FeatureCollection', features: data.features };
        if (Array.isArray(data)) return { type: 'FeatureCollection', features: data };
        return { type: 'FeatureCollection', features: [] };
    }

    function ensureInsightPanel() {
        let panel = document.getElementById('map-insight-panel');
        if (!panel) {
            panel = document.createElement('aside');
            panel.id = 'map-insight-panel';
            document.getElementById('map')?.parentElement?.appendChild(panel);
        }

        const mode = panel.dataset.mapPriorityPanel === 'embedded'
            ? 'embedded'
            : (isFullMap ? 'full' : 'floating');

        panel.dataset.priorityMode = mode;
        panel.className = `sigpala-priority-panel sigpala-priority-panel--${mode} sigpala-map-card${mode === 'full' ? ' is-hidden' : ''}`;
        panel.setAttribute('aria-hidden', mode === 'full' ? 'true' : 'false');
        return panel;
    }

    function ensureLegendToggle() {
        if (!legendPanel) return null;

        let button = document.getElementById('map-legend-toggle');
        if (!button) {
            button = document.createElement('button');
            button.id = 'map-legend-toggle';
            button.type = 'button';
            ensureMapActionRail()?.appendChild(button);
        }

        button.className = 'sigpala-map-action-button sigpala-legend-toggle';
        button.setAttribute('aria-label', 'Tampilkan legenda peta');
        button.setAttribute('aria-controls', 'map-legend');
        button.setAttribute('aria-expanded', 'false');
        button.innerHTML = `
            <svg class="sigpala-map-action-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6.75h11M9 12h11M9 17.25h11M4.5 6.75h.01M4.5 12h.01M4.5 17.25h.01"/>
            </svg>
        `;
        button.addEventListener('click', () => {
            setLegendPanelOpen(legendPanel.classList.contains('is-hidden'));
        });

        return button;
    }

    function ensureMapActionRail() {
        const mapParent = document.getElementById('map')?.parentElement;
        if (!mapParent) return null;

        let rail = document.getElementById('map-action-rail');
        if (!rail) {
            rail = document.createElement('div');
            rail.id = 'map-action-rail';
            rail.className = 'sigpala-map-action-rail';
            mapParent.appendChild(rail);
        }

        return rail;
    }

    function setLegendPanelOpen(open) {
        if (!legendPanel) return;

        legendPanel.classList.toggle('is-hidden', !open);
        legendPanel.classList.toggle('is-open', open);
        legendPanel.setAttribute('aria-hidden', open ? 'false' : 'true');

        const button = document.getElementById('map-legend-toggle');
        if (button) {
            button.classList.toggle('is-active', open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    function ensureLegendPanel() {
        let panel = document.getElementById('map-legend');
        if (!panel) {
            panel = document.createElement('aside');
            panel.id = 'map-legend';
            document.getElementById('map')?.parentElement?.appendChild(panel);
        }

        panel.className = `${isFullMap ? 'sigpala-legend' : 'sigpala-dashboard-legend'} sigpala-map-card is-hidden`;
        panel.setAttribute('aria-hidden', 'true');
        return panel;
    }

    function renderMapLegend() {
        if (!legendPanel) return;

        legendPanel.innerHTML = `
            <div style="padding:14px 15px 15px">
                <p style="margin:0 0 10px;font-size:11px;font-weight:800;letter-spacing:.12em;color:#64748b;text-transform:uppercase">Legenda Peta</p>
                <div style="display:grid;gap:9px;font-size:12px;color:#334155">
                    ${Object.entries(SIGPALA_PRODUCTIVITY_CLASSES).map(([key, config]) => `
                        <div style="display:flex;align-items:center;gap:9px">
                            <span style="width:18px;height:14px;border-radius:5px;background:${config.fillColor};opacity:${key === 'belum-data' ? '.55' : '.85'};border:1px solid ${config.color}"></span>
                            <span>${config.label}</span>
                        </div>
                    `).join('')}
                    <div style="height:1px;background:#e2e8f0;margin:2px 0"></div>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span style="width:18px;height:14px;border-radius:5px;background:rgba(34,197,94,.38);border:1px solid #16a34a"></span>
                        <span>Polygon lahan sawah</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span class="sigpala-lahan-marker" style="height:14px;width:14px;box-shadow:0 0 0 4px rgba(22,163,74,.18)"></span>
                        <span>Titik koordinat lahan</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span style="width:20px;height:2px;background:#1f2937;border-radius:99px"></span>
                        <span>Batas Kecamatan</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span style="width:20px;height:3px;background:#203c10;border-radius:99px"></span>
                        <span>Batas Kabupaten Barito Kuala</span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderKecamatanLayer(featureCollection) {
        kecGroup.clearLayers();
        kecamatanLayersById.clear();

        L.geoJSON(featureCollection, {
            pane: 'sigpalaKecamatanPane',
            interactive: true,
            style: sigpalaKecamatanStyle,
            onEachFeature: function (feature, layer) {
                const props = feature.properties || {};
                const id = String(props.kecamatan_id || props.id || '');
                if (id) kecamatanLayersById.set(id, layer);

                sigpalaBindKecamatanLabel(feature, layer);
                layer.bindPopup(sigpalaKecamatanPopup(props));

                layer.on('mouseover', function () {
                    if (layer !== selectedKecamatanLayer) {
                        layer.setStyle({ weight: 1.4, color: '#0f172a', fillOpacity: 0.42 });
                    }
                    layer.bringToFront();
                });

                layer.on('mouseout', function () {
                    if (layer !== selectedKecamatanLayer) {
                        layer.setStyle(sigpalaKecamatanStyle(feature));
                    }
                });

                layer.on('click', function () {
                    focusKecamatanFeature(feature, layer);
                });
            }
        }).addTo(kecGroup);

        updateKecamatanLabelVisibility();
    }

    function renderLahanLayer(features) {
        lahanGroup.clearLayers();
        lahanLayersById.clear();
        lahanFeaturesById.clear();

        L.geoJSON(features, {
            pane: 'sigpalaLahanPane',
            pointToLayer: function (feature, latlng) {
                const marker = L.circleMarker(latlng, {
                    radius: 6,
                    color: '#166534',
                    weight: 1.5,
                    fillColor: '#22c55e',
                    fillOpacity: 0.78,
                    className: 'sigpala-lahan-marker'
                });

                marker.sigpalaIsCoordinateMarker = true;
                return marker;
            },
            style: {
                color: '#166534',
                fillColor: '#22c55e',
                fillOpacity: 0.34,
                opacity: 0.95,
                weight: 1.25
            },
            onEachFeature: function (feature, layer) {
                const props = feature.properties || {};
                const id = String(props.id || '');
                if (id) {
                    lahanLayersById.set(id, layer);
                    lahanFeaturesById.set(id, feature);
                }

                bindLahanPopup(feature, layer);
            }
        }).addTo(lahanGroup);

        features.forEach(feature => {
            if (feature?.geometry?.type === 'Point') return;

            const props = feature.properties || {};
            const center = sigpalaFeatureCenter(feature);
            if (!center) return;

            const marker = L.circleMarker(center, {
                pane: 'sigpalaLahanPane',
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: '#16a34a',
                fillOpacity: 0.95,
                opacity: 1,
                className: 'sigpala-lahan-marker'
            }).addTo(lahanGroup);
            marker.sigpalaIsCoordinateMarker = true;

            const id = String(props.id || '');
            if (id && !lahanLayersById.has(id)) {
                lahanLayersById.set(id, marker);
                lahanFeaturesById.set(id, feature);
            }

            bindLahanPopup(feature, marker);
        });
    }

    function bindLahanPopup(feature, layer) {
        const props = feature.properties || {};
        const namaLahan = sigpalaEscapeHtml(sigpalaDisplay(props.nama_lahan));
        const pemilik = sigpalaEscapeHtml(sigpalaDisplay(props.pemilik || props.pemilik_lahan));

        layer.bindTooltip(`<b>${namaLahan}</b><br><span style="font-size:11px">Pemilik: ${pemilik}</span>`, {
            direction: 'top',
            className: 'sigpala-tooltip'
        });

        layer.bindPopup(sigpalaLahanPopup(props));
        layer.on('click', function () {
            focusLahanFeature(feature, layer);
        });
    }

    function focusKecamatanFeature(feature, layer) {
        const props = feature.properties || {};
        if (selectedKecamatanLayer && selectedKecamatanLayer !== layer) {
            selectedKecamatanLayer.setStyle(sigpalaKecamatanStyle(selectedKecamatanLayer.feature));
        }

        selectedKecamatanLayer = layer;
        sigpalaZoomToFeature(map, layer, feature);
        layer.setStyle({ weight: 1.8, color: '#0f172a', fillOpacity: 0.48 });
        layer.bringToFront();
        layer.openPopup();
        if (typeof closeSidePanel === 'function') {
            closeSidePanel();
        }
    }

    function focusLahanFeature(feature, layer) {
        const center = sigpalaFeatureCenter(feature);
        if (layer?.sigpalaIsCoordinateMarker && center) {
            map.flyTo(center, Math.max(map.getZoom(), 17), { duration: 0.65 });
        } else {
            sigpalaZoomToFeature(map, layer, feature);
        }
        layer.openPopup();
    }

    function renderKecamatanInsights() {
        if (!insightPanel) return;

        const rows = allKecamatanFeatures
            .map(feature => ({ feature, props: feature.properties || {} }))
            .sort((a, b) => Number(b.props.produktivitas_ton_ha || 0) - Number(a.props.produktivitas_ton_ha || 0));

        const statsRows = allKecamatanFeatures.map(feature => feature.properties || {});
        const totalKecamatan = statsRows.length;
        const totalLahan = statsRows.reduce((sum, row) => sum + Number(row.jumlah_lahan || 0), 0);
        const totalLuas = statsRows.reduce((sum, row) => sum + Number(row.total_luas_ha || 0), 0);
        const totalPanen = statsRows.reduce((sum, row) => sum + Number(row.total_panen_ton || 0), 0);
        const avgProductivity = totalLuas > 0 ? totalPanen / totalLuas : 0;
        const distribution = statsRows.reduce((acc, row) => {
            const key = row.kategori_produktivitas || 'belum-data';
            acc[key] = (acc[key] || 0) + 1;
            return acc;
        }, {});

        const priorityKeys = ['tinggi', 'sedang', 'rendah'];
        const list = rows.map(({ props }) => {
            const key = props.kategori_produktivitas || 'belum-data';
            const config = sigpalaProductivityClass(key);
            const id = props.kecamatan_id || props.id || '';
            const productivity = Number(props.produktivitas_ton_ha || 0);

            return `
                <button type="button" class="sigpala-list-button" data-kecamatan-id="${id}">
                    <span style="display:flex;align-items:center;gap:14px;min-width:0">
                        <span class="sigpala-color-dot" style="background:${config.fillColor}"></span>
                        <span style="min-width:0">
                            <span style="display:block;font-family:'Poppins',sans-serif;font-weight:800;color:#111827;font-size:17px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${sigpalaEscapeHtml(sigpalaShortKecamatanName(props.nama_kecamatan))}</span>
                            <span style="display:block;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.12em;margin-top:4px;text-transform:uppercase">${sigpalaEscapeHtml(sigpalaPriorityShortLabel(key))}</span>
                        </span>
                    </span>
                    <span style="font-family:'Poppins',sans-serif;font-weight:800;color:#334155;font-size:13px;white-space:nowrap">${productivity > 0 ? `${sigpalaNumber(productivity)} t/ha` : 'Belum data'}</span>
                </button>
            `;
        }).join('');

        insightPanel.innerHTML = `
            <div class="sigpala-priority-shell">
                <div class="sigpala-priority-head">
                    <p class="sigpala-priority-eyebrow">Distribusi Prioritas</p>
                </div>
                <div class="sigpala-priority-stats">
                    ${priorityKeys.map(key => {
                        const config = sigpalaProductivityClass(key);
                        return `
                            <div>
                                <div class="sigpala-priority-stat-label">
                                    <span class="sigpala-color-dot" style="background:${config.fillColor}"></span>${sigpalaPriorityShortLabel(key)}
                                </div>
                                <div class="sigpala-priority-stat-value">${distribution[key] || 0}</div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <div class="sigpala-priority-meta">
                    <span>${sigpalaNumber(totalKecamatan, 0)} kecamatan</span>
                    <span>${sigpalaNumber(totalLahan, 0)} lahan</span>
                    <span>Rata-rata ${sigpalaNumber(avgProductivity)} t/ha</span>
                    ${(distribution['belum-data'] || 0) ? `<span>${sigpalaNumber(distribution['belum-data'], 0)} belum data</span>` : ''}
                </div>
                <div class="sigpala-priority-section">
                    <p class="sigpala-priority-title">Sorotan Kecamatan</p>
                    <div class="sigpala-insight-scroll">${list || '<div class="sigpala-empty-priority">Data kecamatan belum tersedia.</div>'}</div>
                </div>
            </div>
        `;

        insightPanel.querySelectorAll('[data-kecamatan-id]').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-kecamatan-id');
                const layer = kecamatanLayersById.get(String(id));
                const row = allKecamatanFeatures.find(item => String(item.properties?.kecamatan_id || item.properties?.id || '') === String(id));
                if (layer && row) focusKecamatanFeature(row, layer);
            });
        });
    }

    function updateKecamatanLabelVisibility() {
        const show = map.getZoom() >= 10;
        document.querySelectorAll('.sigpala-kecamatan-label').forEach(label => {
            label.classList.toggle('is-muted', !show);
        });
    }

    map.on('zoomend', updateKecamatanLabelVisibility);

    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-lahan-detail-id]');
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const id = String(button.getAttribute('data-lahan-detail-id') || '');
        const feature = lahanFeaturesById.get(id);
        if (feature) {
            map.closePopup();
            showDetail(feature.properties || {});
        }
    });

    // Set up Search and Autocomplete
    const searchInput = document.getElementById('search-lahan');
    const searchResults = document.getElementById('search-results');

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', (e) => {
            const query = (e.target.value || '').trim().toLowerCase();
            searchResults.innerHTML = '';
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            // 1. Search Kecamatan matches
            const kecamatanMatches = allKecamatanFeatures.filter(f => {
                const name = (f.properties.nama_kecamatan || f.properties.kecamatan || f.properties.label || '').toLowerCase();
                return name.includes(query);
            });
            const uniqueKecMatches = [];
            const seenKec = new Set();
            kecamatanMatches.forEach(f => {
                const name = f.properties.nama_kecamatan || f.properties.kecamatan || f.properties.label;
                if (name && !seenKec.has(name.toLowerCase())) {
                    seenKec.add(name.toLowerCase());
                    uniqueKecMatches.push(f);
                }
            });

            // 2. Search Kelurahan matches
            const kelurahanMatches = uniqueKelurahans.filter(k => {
                return k.nama_kelurahan.toLowerCase().includes(query);
            });

            // 3. Search Lahan matches
            const lahanMatches = allLahanFeatures.filter(f => {
                const n = (f.properties.nama_lahan || '').toLowerCase();
                const p = (f.properties.pemilik || f.properties.pemilik_lahan || '').toLowerCase();
                return n.includes(query) || p.includes(query);
            });

            const matches = [];

            // Add Kecamatan (Max 3)
            uniqueKecMatches.slice(0, 3).forEach(k => {
                matches.push({
                    type: 'kecamatan',
                    title: k.properties.nama_kecamatan || k.properties.kecamatan || k.properties.label,
                    subtitle: 'Kecamatan di Barito Kuala',
                    feature: k
                });
            });

            // Add Kelurahan (Max 3)
            kelurahanMatches.slice(0, 3).forEach(k => {
                matches.push({
                    type: 'kelurahan',
                    title: k.nama_kelurahan,
                    subtitle: `Kelurahan di Kec. ${k.nama_kecamatan}`,
                    kelurahanData: k
                });
            });

            // Add Lahan (Max 6)
            lahanMatches.slice(0, 6).forEach(l => {
                matches.push({
                    type: 'lahan',
                    title: l.properties.nama_lahan,
                    subtitle: `Lahan | Pemilik: ${l.properties.pemilik || l.properties.pemilik_lahan || '-'} | Kec. ${l.properties.nama_kecamatan || '-'}`,
                    feature: l
                });
            });

            if (matches.length > 0) {
                matches.forEach(match => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-3 hover:bg-emerald-50 cursor-pointer border-b border-slate-100 last:border-0 transition-colors flex items-center gap-3';
                    
                    let iconHtml = '';
                    if (match.type === 'kecamatan') {
                        iconHtml = `
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        `;
                    } else if (match.type === 'kelurahan') {
                        iconHtml = `
                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                        `;
                    } else {
                        iconHtml = `
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-11.314l.707.707m12.022 12.022l-.707.707M9 9h6v6H9V9z" />
                                </svg>
                            </div>
                        `;
                    }

                    div.innerHTML = `
                        ${iconHtml}
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-slate-800 truncate">${sigpalaDisplay(match.title)}</p>
                            <p class="text-xs text-slate-500 truncate">${sigpalaDisplay(match.subtitle)}</p>
                        </div>
                    `;

                    div.addEventListener('click', () => {
                        searchResults.classList.add('hidden');
                        searchInput.value = sigpalaDisplay(match.title);
                        
                        if (match.type === 'kecamatan') {
                            const targetLayer = L.geoJSON(match.feature);
                            if (targetLayer.getBounds().isValid()) {
                                map.fitBounds(targetLayer.getBounds(), { padding: [36, 36] });
                            }
                            closeSidePanel();
                        } else if (match.type === 'kelurahan') {
                            const targetLayer = L.geoJSON(match.kelurahanData.features);
                            if (targetLayer.getBounds().isValid()) {
                                map.fitBounds(targetLayer.getBounds(), { padding: [36, 36] });
                            }
                            closeSidePanel();
                        } else if (match.type === 'lahan') {
                            const targetLayer = L.geoJSON(match.feature);
                            if (targetLayer.getBounds().isValid()) {
                                map.fitBounds(targetLayer.getBounds(), { maxZoom: 16 });
                            }
                            showDetail(match.feature.properties);
                        }
                    });
                    searchResults.appendChild(div);
                });
                searchResults.classList.remove('hidden');
            } else {
                searchResults.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Tidak ada hasil ditemukan</div>';
                searchResults.classList.remove('hidden');
            }
        });

        // Hide search results when clicking outside
        document.addEventListener('click', (e) => {
            if (searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }
});

function showDetail(props) {
    const panel = document.getElementById('side-panel');
    const content = document.getElementById('panel-content');
    if (!panel || !content) return;

    const panelTitle = panel.querySelector('h3');
    const panelSubtitle = panel.querySelector('h3 + p');
    if (panelTitle) panelTitle.textContent = 'Detail Lahan Sawah';
    if (panelSubtitle) panelSubtitle.textContent = 'Informasi lengkap lahan yang dipilih dari peta.';

    panel.style.right = '0';

    const namaLahan = sigpalaDisplay(props.nama_lahan);
    const pemilik = sigpalaDisplay(props.pemilik || props.pemilik_lahan);
    const tipeLahan = sigpalaDisplay(props.tipe_lahan || props.nama_tipe, 'Belum Ditentukan');
    const tahunLbs = sigpalaDisplay(props.tahun_lbs);
    const kecamatan = sigpalaDisplay(props.kecamatan || props.nama_kecamatan);
    const kelurahan = sigpalaDisplay(props.kelurahan || props.nama_kelurahan);
    const alamatDetail = sigpalaDisplay(props.alamat_detail);
    const luasHa = sigpalaNumber(props.luas_ha || props.luas_lahan_hektar);
    const hasilPanen = sigpalaNumber(props.hasil_panen || props.hasil_panen_ton);
    const produktivitas = sigpalaNumber(props.produktivitas || props.produktivitas_ton_ha);

    content.innerHTML = `
        <div style="font-family:'Poppins',sans-serif;">
            <div style="background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);padding:22px 20px 48px;position:relative;overflow:hidden;border-radius:18px 18px 0 0;">
                <div style="position:absolute;top:-20px;right:-20px;width:110px;height:110px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
                <p style="margin:0 0 6px;font-size:10px;color:rgba(255,255,255,.72);font-weight:700;text-transform:uppercase;letter-spacing:1px;">Informasi Lahan</p>
                <h2 style="margin:0;color:#fff;font-size:22px;font-weight:850;line-height:1.2;padding-right:20px;">${sigpalaEscapeHtml(namaLahan)}</h2>
                <div style="display:inline-flex;align-items:center;gap:6px;margin-top:12px;background:rgba(255,255,255,.13);border-radius:999px;padding:7px 11px;">
                    <span style="font-size:12px;color:rgba(255,255,255,.92);font-weight:700;">${sigpalaEscapeHtml(pemilik)}</span>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:0 16px;margin-top:-28px;position:relative;z-index:2;">
                <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:12px 14px;text-align:center;">
                    <p style="margin:0 0 2px;font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.8px;">Luas Lahan</p>
                    <p style="margin:0;font-size:22px;font-weight:850;color:#166534;line-height:1.1;">${luasHa}</p>
                    <p style="margin:0;font-size:10px;font-weight:600;color:#94a3b8;">Hektar</p>
                </div>
                <div style="background:white;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:12px 14px;text-align:center;">
                    <p style="margin:0 0 2px;font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.8px;">Hasil Panen</p>
                    <p style="margin:0;font-size:22px;font-weight:850;color:#166534;line-height:1.1;">${hasilPanen}</p>
                    <p style="margin:0;font-size:10px;font-weight:600;color:#94a3b8;">Ton</p>
                </div>
            </div>

            <div style="padding:14px 16px 24px;">
                ${sigpalaDetailSection('Informasi Lokasi', [
                    ['Kecamatan / Kelurahan', `${kecamatan} / ${kelurahan}`],
                    ['Tipe Lahan / Tahun Basis', `${tipeLahan} / ${tahunLbs}`],
                    ['Alamat Detail', alamatDetail]
                ])}
                <div style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1.5px solid #86efac;border-radius:14px;padding:18px 20px;margin-top:12px;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.8px;">Produktivitas Lahan</p>
                    <div style="display:flex;align-items:baseline;gap:5px;">
                        <span style="font-size:36px;font-weight:850;color:#15803d;line-height:1;">${produktivitas}</span>
                        <span style="font-size:15px;font-weight:700;color:#16a34a;">Ton / Ha</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function sigpalaDetailSection(title, rows) {
    return `
        <div>
            <p style="margin:0 0 10px;font-size:10px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:1.2px;display:flex;align-items:center;gap:6px;">
                <span style="display:inline-block;width:20px;height:2px;background:#bbf7d0;border-radius:2px;"></span>
                ${title}
                <span style="display:inline-block;flex:1;height:2px;background:#f1f5f9;border-radius:2px;"></span>
            </p>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                ${rows.map(([label, value]) => `
                    <div style="padding:12px 14px;border-bottom:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">${label}</p>
                        <p style="margin:2px 0 0;font-size:13px;font-weight:650;color:#1e293b;line-height:1.5;">${sigpalaEscapeHtml(value)}</p>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function closeSidePanel() {
    const panel = document.getElementById('side-panel');
    if (!panel) return;

    panel.style.right = window.innerWidth <= 640 ? '-110%' : '-450px';
}
