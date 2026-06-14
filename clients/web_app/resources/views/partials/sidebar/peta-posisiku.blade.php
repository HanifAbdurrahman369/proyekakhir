@extends('layouts.app')

@section('title', 'Peta Posisiku')

@section('content')

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<style>
    .sigpala-kecamatan-label {
        background: rgba(255,255,255,.88);
        border: 1px solid rgba(32,60,16,.12);
        border-radius: 999px;
        box-shadow: 0 8px 20px rgba(15,23,42,.14);
        color: #203c10;
        font-family: 'Poppins', sans-serif;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .03em;
        padding: 4px 8px;
        text-transform: uppercase;
    }

    .sigpala-kecamatan-label::before {
        display: none;
    }
</style>

<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow">

    {{-- HEADER --}}
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-primary-900">
            Peta Posisiku
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Menampilkan posisi lokasi Anda secara realtime berdasarkan koordinat GPS perangkat.
        </p>

    </div>

    {{-- ALERT --}}
    <div id="location-alert"
         class="hidden mb-4 p-4 rounded-lg border text-sm">
    </div>

    {{-- INFO CARD --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-green-50 border border-green-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-green-700 font-semibold">
                Latitude
            </p>

            <p id="latitude"
               class="text-lg font-bold text-green-900 mt-1">
                -
            </p>

        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-blue-700 font-semibold">
                Longitude
            </p>

            <p id="longitude"
               class="text-lg font-bold text-blue-900 mt-1">
                -
            </p>

        </div>

        <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-yellow-700 font-semibold">
                Akurasi GPS
            </p>

            <p id="accuracy"
               class="text-lg font-bold text-yellow-900 mt-1">
                -
            </p>

        </div>

    </div>

    {{-- MAP --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200">

        <div id="map"
             class="w-full h-[600px]">
        </div>

    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    let map;
    let marker;
    let circle;

    /*
    |--------------------------------------------------------------------------
    | INIT MAP
    |--------------------------------------------------------------------------
    */

    map = L.map('map').setView([-2.5489, 118.0149], 5);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap'
        }
    ).addTo(map);

    const gatewayBase = window.GATEWAY_URL || "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}";
    const batasKecamatanGroup = L.layerGroup().addTo(map);
    const kecamatanPalette = [
        '#15803d', '#0f766e', '#0369a1', '#7c3aed', '#c2410c',
        '#be123c', '#047857', '#b45309', '#4338ca', '#0e7490',
        '#65a30d', '#a21caf', '#1d4ed8', '#ca8a04', '#dc2626',
        '#0891b2', '#4d7c0f'
    ];

    function warnaKecamatan(feature) {
        const props = feature?.properties || {};
        const id = Number(props.kecamatan_id || props.id || 1);

        return props.warna_peta || props.fill_color || kecamatanPalette[(Math.max(id, 1) - 1) % kecamatanPalette.length];
    }

    L.control.layers({}, {
        'Batas Kecamatan': batasKecamatanGroup
    }, {
        position: 'topright',
        collapsed: true
    }).addTo(map);

    fetch(`${gatewayBase}/api/batas-kecamatan`)
        .then(response => response.json())
        .then(data => {
            const featureCollection = data.data || data;

            L.geoJSON(featureCollection, {
                interactive: false,
                style: function(feature) {
                    const color = warnaKecamatan(feature);

                    return {
                        color,
                        weight: 2,
                        opacity: 0.96,
                        fillColor: color,
                        fillOpacity: 0.07,
                        dashArray: '7 5'
                    };
                },
                onEachFeature: function(feature, layer) {
                    const props = feature?.properties || {};
                    const label = props.nama_kecamatan || props.kecamatan || props.label;
                    if (!label) return;

                    layer.bindTooltip(label, {
                        permanent: true,
                        direction: 'center',
                        className: 'sigpala-kecamatan-label'
                    });
                }
            }).addTo(batasKecamatanGroup);
        })
        .catch(() => {
            console.error('API Batas Kecamatan bermasalah');
        });

    /*
    |--------------------------------------------------------------------------
    | ALERT HELPER
    |--------------------------------------------------------------------------
    */

    function showAlert(message, type = 'success')
    {
        const alertBox = document.getElementById('location-alert');

        alertBox.classList.remove('hidden');

        if(type === 'success') {

            alertBox.className =
                'mb-4 p-4 rounded-lg border text-sm bg-green-100 text-green-700 border-green-200';

        } else {

            alertBox.className =
                'mb-4 p-4 rounded-lg border text-sm bg-red-100 text-red-700 border-red-200';
        }

        alertBox.innerHTML = message;
    }

    /*
    |--------------------------------------------------------------------------
    | GET USER LOCATION
    |--------------------------------------------------------------------------
    */

    if (navigator.geolocation) {

        navigator.geolocation.getCurrentPosition(

            function(position) {

                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                /*
                |--------------------------------------------------------------------------
                | UPDATE CARD
                |--------------------------------------------------------------------------
                */

                document.getElementById('latitude').innerText =
                    lat.toFixed(6);

                document.getElementById('longitude').innerText =
                    lng.toFixed(6);

                document.getElementById('accuracy').innerText =
                    accuracy.toFixed(2) + ' meter';

                /*
                |--------------------------------------------------------------------------
                | SET MAP VIEW
                |--------------------------------------------------------------------------
                */

                map.setView([lat, lng], 17);

                /*
                |--------------------------------------------------------------------------
                | MARKER
                |--------------------------------------------------------------------------
                */

                marker = L.marker([lat, lng]).addTo(map);

                marker.bindPopup(`
                    <div class="text-sm">
                        <strong>Posisi Anda</strong>
                        <br>
                        Latitude: ${lat.toFixed(6)}
                        <br>
                        Longitude: ${lng.toFixed(6)}
                    </div>
                `).openPopup();

                /*
                |--------------------------------------------------------------------------
                | ACCURACY CIRCLE
                |--------------------------------------------------------------------------
                */

                circle = L.circle([lat, lng], {

                    radius: accuracy,

                    color: '#16a34a',

                    fillColor: '#22c55e',

                    fillOpacity: 0.2

                }).addTo(map);

                /*
                |--------------------------------------------------------------------------
                | SUCCESS ALERT
                |--------------------------------------------------------------------------
                */

                showAlert(
                    'Lokasi berhasil ditemukan dan ditampilkan pada peta.'
                );

            },

            function(error) {

                let message = 'Gagal mendapatkan lokasi pengguna.';

                switch(error.code) {

                    case error.PERMISSION_DENIED:
                        message = 'Akses lokasi ditolak oleh pengguna.';
                        break;

                    case error.POSITION_UNAVAILABLE:
                        message = 'Informasi lokasi tidak tersedia.';
                        break;

                    case error.TIMEOUT:
                        message = 'Permintaan lokasi melebihi batas waktu.';
                        break;
                }

                showAlert(message, 'error');

            },

            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );

    } else {

        showAlert(
            'Browser tidak mendukung geolocation.',
            'error'
        );
    }

</script>

@endsection
