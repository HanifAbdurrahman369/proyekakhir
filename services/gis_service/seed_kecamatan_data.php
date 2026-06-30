<?php
use Illuminate\Support\Facades\DB;

$data = [
    'Tabunganen' => ['produktivitas' => 35.84, 'produksi' => 40778],
    'Tamban' => ['produktivitas' => 35.80, 'produksi' => 29896],
    'Mekarsari' => ['produktivitas' => 35.81, 'produksi' => 30588],
    'Anjir Pasar' => ['produktivitas' => 35.83, 'produksi' => 32084],
    'Anjir Muara' => ['produktivitas' => 47.72, 'produksi' => 24186],
    'Alalak' => ['produktivitas' => 41.64, 'produksi' => 22345],
    'Mandastana' => ['produktivitas' => 40.28, 'produksi' => 30705],
    'Jejangkit' => ['produktivitas' => 47.65, 'produksi' => 11843.2],
    'Belawang' => ['produktivitas' => 44.72, 'produksi' => 43656],
    'Wanaraya' => ['produktivitas' => 53.55, 'produksi' => 10913],
    'Barambai' => ['produktivitas' => 40.89, 'produksi' => 27560],
    'Rantau Badauh' => ['produktivitas' => 41.82, 'produksi' => 38753],
    'Cerbon' => ['produktivitas' => 41.73, 'produksi' => 17746],
    'Bakumpai' => ['produktivitas' => 47.70, 'produksi' => 14426],
    'Marabahan' => ['produktivitas' => 35.72, 'produksi' => 7349],
    'Tabukan' => ['produktivitas' => 35.80, 'produksi' => 23913],
    'Kuripan' => ['produktivitas' => 35.65, 'produksi' => 21],
];

foreach ($data as $nama => $values) {
    DB::table('kecamatan')->where('nama_kecamatan', $nama)->update([
        'produktivitas' => $values['produktivitas'],
        'produksi' => $values['produksi']
    ]);
}

echo "Data updated successfully.\n";
