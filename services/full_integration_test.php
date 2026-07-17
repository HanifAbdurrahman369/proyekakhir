<?php

declare(strict_types=1);

require __DIR__ . '/auth_service/vendor/autoload.php';

use Firebase\JWT\JWT;

$root = dirname(__DIR__);
$env = parse_ini_file(__DIR__ . '/auth_service/.env', false, INI_SCANNER_RAW);
$db = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'], $env['DB_PORT'], $env['DB_DATABASE']),
    $env['DB_USERNAME'],
    $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$secret = $env['JWT_SECRET'] ?? 'secret-key-sementara-untuk-lokal';
$gateway = 'http://127.0.0.1:8003/api';
$results = [];
$created = ['lahan' => null, 'tanam' => null, 'panen' => null, 'monitoring' => null, 'komunitas' => null, 'profile_komunitas' => null];

function tokenForRole(PDO $db, string $secret, int $roleId): string
{
    $stmt = $db->prepare('SELECT id,email,nik,nip,role_id,komunitas_id FROM users WHERE role_id=? ORDER BY id LIMIT 1');
    $stmt->execute([$roleId]);
    $user = $stmt->fetch();
    if (!$user) {
        throw new RuntimeException("Akun role {$roleId} tidak ditemukan.");
    }

    return JWT::encode([
        'iss' => 'auth-service',
        'sub' => (int) $user['id'],
        'email' => $user['email'],
        'nik' => $user['nik'],
        'nip' => $user['nip'],
        'role_id' => (int) $user['role_id'],
        'komunitas_id' => $user['komunitas_id'] ? (int) $user['komunitas_id'] : null,
        'iat' => time(),
        'exp' => time() + 3600,
    ], $secret, 'HS256');
}

function api(string $label, string $method, string $url, ?string $token = null, ?array $payload = null, array $expected = [200]): array
{
    global $results;
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    $ok = !$error && in_array($status, $expected, true);
    $results[] = ['label' => $label, 'status' => $status, 'ok' => $ok, 'message' => $json['message'] ?? $error ?: null];
    if (!$ok) {
        throw new RuntimeException("{$label} gagal (HTTP {$status}): " . ($json['message'] ?? $error ?: substr((string) $raw, 0, 250)));
    }
    return is_array($json) ? $json : [];
}

function assertDb(PDO $db, string $label, string $sql, array $bindings, $expected): void
{
    global $results;
    $stmt = $db->prepare($sql);
    $stmt->execute($bindings);
    $actual = $stmt->fetchColumn();
    $ok = (string) $actual === (string) $expected;
    $results[] = ['label' => $label, 'status' => 'DB', 'ok' => $ok, 'message' => $ok ? null : "expected={$expected}, actual={$actual}"];
    if (!$ok) {
        throw new RuntimeException("{$label} gagal: expected={$expected}, actual={$actual}");
    }
}

try {
    $farmer = $db->query('SELECT * FROM users WHERE role_id=1 ORDER BY id LIMIT 1')->fetch();
    $officer = $db->query('SELECT * FROM users WHERE role_id=2 ORDER BY id LIMIT 1')->fetch();
    $officerOriginalKomunitasId = !empty($officer['komunitas_id']) ? (int) $officer['komunitas_id'] : null;
    $officerOriginalCommunity = $officerOriginalKomunitasId
        ? $db->query('SELECT * FROM komunitas WHERE id=' . $officerOriginalKomunitasId)->fetch()
        : null;
    $reference = $db->query('SELECT k.id kecamatan_id, d.id kelurahan_id FROM kecamatan k JOIN kelurahan d ON d.kecamatan_id=k.id ORDER BY k.id,d.id LIMIT 1')->fetch();
    $tipeId = (int) $db->query('SELECT id FROM tipe_lahan ORDER BY id LIMIT 1')->fetchColumn();
    $bibitId = (int) $db->query('SELECT id FROM jenis_bibit ORDER BY id LIMIT 1')->fetchColumn();
    $pupukId = (int) $db->query('SELECT id FROM jenis_pupuk ORDER BY id LIMIT 1')->fetchColumn();
    if (!$farmer || !$officer || !$reference || !$tipeId || !$bibitId || !$pupukId) {
        throw new RuntimeException('Data referensi minimum untuk pengujian tidak lengkap.');
    }

    $tokens = [
        1 => tokenForRole($db, $secret, 1),
        2 => tokenForRole($db, $secret, 2),
        3 => tokenForRole($db, $secret, 3),
        4 => tokenForRole($db, $secret, 4),
        5 => tokenForRole($db, $secret, 5),
    ];

    foreach (['health', 'kecamatan', 'kelurahan', 'tipe-lahan', 'map-lahan', 'map-lahan-termonitor', 'statistik', 'batas-wilayah', 'batas-kecamatan'] as $endpoint) {
        api("GET publik {$endpoint}", 'GET', "{$gateway}/{$endpoint}");
    }

    foreach ([1, 2, 3, 4, 5] as $role) {
        api("Profil role {$role}", 'GET', "{$gateway}/auth/profile", $tokens[$role]);
    }

    foreach (['lahan', 'lahan/dropdown', 'activities', 'my-siklus-tanam', 'riwayat-panen', 'siklus-pupuk', 'total-produksi', 'bibit', 'jenis-pupuk', 'notifikasi'] as $endpoint) {
        api("Petani {$endpoint}", 'GET', "{$gateway}/{$endpoint}", $tokens[1]);
    }
    foreach (['lahan', 'lahan/dropdown', 'activities', 'my-siklus-tanam', 'riwayat-panen', 'siklus-pupuk', 'total-produksi'] as $endpoint) {
        api("Brigade {$endpoint}", 'GET', "{$gateway}/{$endpoint}", $tokens[5]);
    }
    foreach (['lahan/pending', 'panen/pending', 'lahan/accepted', 'monitoring', 'spasial-lahan/referensi', 'spasial-lahan', 'lahan-termonitor', 'lahan-termonitor/monitoring', 'lahan-termonitor/preview', 'notifikasi'] as $endpoint) {
        api("Petugas {$endpoint}", 'GET', "{$gateway}/{$endpoint}", $tokens[2]);
    }
    foreach (['produksi-pejabat', 'total-lahan', 'produksi-kecamatan', 'produksi-kelurahan', 'lahan-kecamatan', 'produksi-bulanan', 'top-kecamatan'] as $endpoint) {
        api("Pejabat {$endpoint}", 'GET', "{$gateway}/{$endpoint}", $tokens[3]);
    }
    api('Admin daftar user', 'GET', "{$gateway}/users", $tokens[4]);
    api('Admin tabel master', 'GET', "{$gateway}/master/tables", $tokens[4]);

    api('Update profil petani', 'PUT', "{$gateway}/auth/profile", $tokens[1], [
        'nama_lengkap' => $farmer['nama_lengkap'], 'email' => $farmer['email'],
        'no_hp' => $farmer['no_hp'], 'alamat' => $farmer['alamat'],
    ]);

    $suffix = date('YmdHis');
    $officerKelurahan = json_decode((string) ($officer['wilayah_kelurahan_ids'] ?? '[]'), true);
    $officerKelurahanId = is_array($officerKelurahan) && $officerKelurahan
        ? (int) $officerKelurahan[0]
        : (int) $reference['kelurahan_id'];
    api('Update profil wilayah petugas', 'PUT', "{$gateway}/auth/profile", $tokens[2], [
        'nama_lengkap' => $officer['nama_lengkap'], 'email' => $officer['email'],
        'no_hp' => $officer['no_hp'], 'alamat' => $officer['alamat'],
        'wilayah_kecamatan_id' => (int) (($officer['wilayah_kecamatan_id'] ?? null) ?: $reference['kecamatan_id']),
        'wilayah_kelurahan_id' => $officerKelurahanId,
    ]);
    $profileCommunityId = (int) $db->query('SELECT komunitas_id FROM users WHERE id=' . (int) $officer['id'])->fetchColumn();
    if (!$officerOriginalKomunitasId) {
        $created['profile_komunitas'] = $profileCommunityId;
    }
    assertDb($db, 'DB profil kecamatan petugas tersimpan', 'SELECT wilayah_kecamatan_id FROM komunitas WHERE id=?', [$profileCommunityId], (int) (($officer['wilayah_kecamatan_id'] ?? null) ?: $reference['kecamatan_id']));
    assertDb($db, 'DB profil kelurahan petugas tersimpan', 'SELECT JSON_UNQUOTE(JSON_EXTRACT(wilayah_kelurahan_ids, "$[0]")) FROM komunitas WHERE id=?', [$profileCommunityId], $officerKelurahanId);

    $community = api('Tambah komunitas petugas', 'POST', "{$gateway}/komunitas", $tokens[2], [
        'jenis_komunitas' => 'kelompok_tani', 'nama' => "E2E Komunitas {$suffix}",
        'nama_komunitas' => "E2E Komunitas {$suffix}",
        'nik' => "99{$suffix}", 'nomor_hp' => '081234567890',
        'wilayah_kecamatan_id' => (int) $reference['kecamatan_id'],
        'wilayah_kelurahan_ids' => [(int) $reference['kelurahan_id']],
        'nama_bpp' => 'BPP Pengujian Integrasi',
        'alamat' => 'Alamat sekretariat pengujian integrasi',
    ], [201]);
    $created['komunitas'] = (int) $community['data']['id'];
    assertDb($db, 'DB komunitas dan wilayah tersimpan', 'SELECT COUNT(*) FROM komunitas WHERE id=? AND nama=? AND nik=? AND wilayah_kecamatan_id=?', [$created['komunitas'], "E2E Komunitas {$suffix}", "99{$suffix}", (int) $reference['kecamatan_id']], 1);
    api('Ubah komunitas petugas', 'PUT', "{$gateway}/komunitas/{$created['komunitas']}", $tokens[2], [
        'nama' => "E2E Komunitas Update {$suffix}",
        'nama_komunitas' => "E2E Komunitas Update {$suffix}",
        'status_keanggotaan' => 'TIDAK_AKTIF',
    ]);
    assertDb($db, 'DB status komunitas diperbarui', 'SELECT status_keanggotaan FROM komunitas WHERE id=?', [$created['komunitas']], 'TIDAK_AKTIF');

    $landPayload = [
        'nama_lahan' => "E2E Lahan {$suffix}",
        'kecamatan_id' => (int) $reference['kecamatan_id'],
        'kelurahan_id' => (int) $reference['kelurahan_id'],
        'tipe_lahan_id' => $tipeId,
        'alamat_detail' => 'Lokasi pengujian integrasi otomatis',
        'luas_lahan_hektar' => 1.25,
        'luas_tanam_hektar' => 1.00,
        'tahun_lbs' => '2024',
    ];
    $land = api('Petani tambah lahan', 'POST', "{$gateway}/lahan", $tokens[1], $landPayload, [201]);
    $created['lahan'] = (int) $land['data']['id'];
    assertDb($db, 'DB lahan pending tersimpan', 'SELECT status_verifikasi FROM lahan_sawah WHERE id=?', [$created['lahan']], 'PENDING');
    api('Petugas tolak lahan', 'POST', "{$gateway}/lahan/{$created['lahan']}/reject", $tokens[2], ['alasan_penolakan' => 'Pengujian alur perbaikan data']);
    assertDb($db, 'DB lahan ditolak', 'SELECT status_verifikasi FROM lahan_sawah WHERE id=?', [$created['lahan']], 'DITOLAK');
    api('Petani ajukan ulang lahan', 'PUT', "{$gateway}/lahan/{$created['lahan']}/resubmit", $tokens[1], $landPayload);
    api('Petugas setujui lahan', 'POST', "{$gateway}/lahan/{$created['lahan']}/approve", $tokens[2], []);
    assertDb($db, 'DB lahan diterima', 'SELECT status_verifikasi FROM lahan_sawah WHERE id=?', [$created['lahan']], 'DITERIMA');

    $polygon = json_encode(['type' => 'Polygon', 'coordinates' => [[[114.65, -3.20], [114.651, -3.20], [114.651, -3.201], [114.65, -3.20]]]]);
    api('Petugas simpan spasial', 'PUT', "{$gateway}/spasial-lahan/S-{$created['lahan']}", $tokens[2], array_merge($landPayload, [
        'latitude' => -3.2005, 'longitude' => 114.6505, 'polygon_geojson' => $polygon,
    ]));
    assertDb($db, 'DB spasial lahan tersimpan', 'SELECT status_spasial FROM lahan_sawah WHERE id=?', [$created['lahan']], 'SUDAH_DIPETAKAN');

    $monitoring = api('Petugas input monitoring', 'POST', "{$gateway}/monitoring", $tokens[2], [
        'lahan_id' => $created['lahan'], 'tanggal_cek' => date('Y-m-d'), 'ph_air' => 6.5,
        'tinggi_muka_air' => 12, 'status_air' => 'Normal', 'kekeruhan_air' => 'Jernih',
        'catatan_petugas' => 'Pengujian integrasi otomatis', 'latitude' => -3.2005, 'longitude' => 114.6505,
    ], [201]);
    $created['monitoring'] = (int) $monitoring['data']['id'];
    assertDb($db, 'DB monitoring tersimpan', 'SELECT COUNT(*) FROM monitoring_kondisi WHERE id=? AND lahan_id=?', [$created['monitoring'], $created['lahan']], 1);
    api('Petugas lihat monitoring', 'GET', "{$gateway}/monitoring/{$created['monitoring']}", $tokens[2]);

    $plantPayload = [
        'lahan_id' => $created['lahan'], 'luas_tanam_hektar' => 1.00, 'bibit_id' => $bibitId,
        'tanggal_tanam' => date('Y-m-d', strtotime('-10 days')), 'estimasi_hari_tanam' => 120,
        'pupuk_id' => $pupukId, 'tanggal_pemupukan' => date('Y-m-d', strtotime('-5 days')), 'takaran' => 25,
    ];
    $plant = api('Petani input laporan tanam', 'POST', "{$gateway}/activities", $tokens[1], $plantPayload, [201]);
    $created['tanam'] = (int) $plant['data']['id'];
    assertDb($db, 'DB tanam tersimpan', 'SELECT status_aktif FROM tanam_padi WHERE id=? AND lahan_id=?', [$created['tanam'], $created['lahan']], 'AKTIF');
    $plantPayload['takaran'] = 26;
    api('Petani ubah laporan tanam', 'PUT', "{$gateway}/activities/{$created['tanam']}", $tokens[1], $plantPayload);
    api('Petani input pemupukan', 'POST', "{$gateway}/siklus-pupuk", $tokens[1], [
        'siklus_tanam_id' => $created['tanam'], 'pupuk_id' => $pupukId,
        'tanggal_pemupukan' => date('Y-m-d'), 'takaran' => 30,
    ]);
    assertDb($db, 'DB pemupukan diperbarui', 'SELECT takaran_pupuk_kg FROM tanam_padi WHERE id=?', [$created['tanam']], '30.00');

    $harvest = api('Petani input laporan panen', 'POST', "{$gateway}/lapor-panen", $tokens[1], [
        'siklus_tanam_id' => $created['tanam'], 'tanggal_panen' => date('Y-m-d'), 'hasil_panen' => 5.5,
    ], [201]);
    $created['panen'] = (int) $harvest['data']['id'];
    assertDb($db, 'DB panen pending tersimpan', 'SELECT status_verifikasi FROM panen_padi WHERE id=?', [$created['panen']], 'PENDING');
    api('Petugas tolak laporan panen', 'POST', "{$gateway}/panen/{$created['panen']}/verifikasi", $tokens[2], ['aksi' => 'DITOLAK', 'alasan_penolakan' => 'Pengujian perbaikan panen']);
    api('Petani perbaiki laporan panen', 'PUT', "{$gateway}/lapor-panen/{$created['panen']}", $tokens[1], ['tanggal_panen' => date('Y-m-d'), 'hasil_panen' => 5.75]);
    api('Petugas setujui laporan panen', 'POST', "{$gateway}/panen/{$created['panen']}/verifikasi", $tokens[2], ['aksi' => 'DITERIMA']);
    assertDb($db, 'DB panen diterima', 'SELECT status_verifikasi FROM panen_padi WHERE id=?', [$created['panen']], 'DITERIMA');
    assertDb($db, 'DB siklus selesai', 'SELECT status_aktif FROM tanam_padi WHERE id=?', [$created['tanam']], 'NONAKTIF');

    api('Petugas hapus spasial', 'DELETE', "{$gateway}/spasial-lahan/S-{$created['lahan']}", $tokens[2]);
    assertDb($db, 'DB spasial dikosongkan', 'SELECT status_spasial FROM lahan_sawah WHERE id=?', [$created['lahan']], 'BELUM_DIPETAKAN');
    api('Hapus komunitas petugas', 'DELETE', "{$gateway}/komunitas/{$created['komunitas']}", $tokens[2]);
    $created['komunitas'] = null;
} catch (Throwable $e) {
    $results[] = ['label' => 'FATAL', 'status' => 'ERROR', 'ok' => false, 'message' => $e->getMessage()];
} finally {
    try {
        if ($created['monitoring']) {
            $db->prepare('DELETE FROM monitoring_kondisi WHERE id=?')->execute([$created['monitoring']]);
        }
        if ($created['panen']) {
            $db->prepare('DELETE FROM notifikasi WHERE ref_type="panen_padi" AND ref_id=?')->execute([$created['panen']]);
            $db->prepare('DELETE FROM panen_padi WHERE id=?')->execute([$created['panen']]);
        }
        if ($created['tanam']) {
            $db->prepare('DELETE FROM tanam_padi WHERE id=?')->execute([$created['tanam']]);
        }
        if ($created['lahan']) {
            $db->prepare('DELETE FROM notifikasi WHERE ref_type="lahan_sawah" AND ref_id=?')->execute([$created['lahan']]);
            $db->prepare('DELETE FROM lahan_sawah WHERE id=?')->execute([$created['lahan']]);
        }
        if ($created['komunitas']) {
            $db->prepare('DELETE FROM komunitas WHERE id=?')->execute([$created['komunitas']]);
        }
        if ($created['profile_komunitas']) {
            $db->prepare('UPDATE users SET komunitas_id=? WHERE id=?')->execute([$officerOriginalKomunitasId, $officer['id']]);
            $db->prepare('DELETE FROM komunitas WHERE id=?')->execute([$created['profile_komunitas']]);
        } elseif ($officerOriginalCommunity) {
            $db->prepare('UPDATE komunitas SET wilayah_kecamatan_id=?, wilayah_kelurahan_ids=?, nama=?, nomor_hp=?, alamat=?, updated_at=? WHERE id=?')->execute([
                $officerOriginalCommunity['wilayah_kecamatan_id'],
                $officerOriginalCommunity['wilayah_kelurahan_ids'],
                $officerOriginalCommunity['nama'],
                $officerOriginalCommunity['nomor_hp'],
                $officerOriginalCommunity['alamat'],
                $officerOriginalCommunity['updated_at'],
                $officerOriginalKomunitasId,
            ]);
        }
    } catch (Throwable $cleanupError) {
        $results[] = ['label' => 'Cleanup data uji', 'status' => 'ERROR', 'ok' => false, 'message' => $cleanupError->getMessage()];
    }
}

$failed = array_values(array_filter($results, fn (array $row) => !$row['ok']));
echo json_encode([
    'success' => count($failed) === 0,
    'total' => count($results),
    'passed' => count($results) - count($failed),
    'failed' => count($failed),
    'failures' => $failed,
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
