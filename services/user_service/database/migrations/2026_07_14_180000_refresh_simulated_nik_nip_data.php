<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Hapus identitas simulasi lama sebelum membuat data pengganti.
            DB::table('users')->update(['nik' => null, 'nip' => null]);
            DB::table('komunitas')->update(['nik' => null, 'nip' => null]);

            $komunitasIdentities = [];

            foreach (DB::table('komunitas')->orderBy('id')->get() as $komunitas) {
                $identity = $this->simulatedIdentity((int) $komunitas->id, 100);

                DB::table('komunitas')->where('id', $komunitas->id)->update([
                    'nik' => $identity['nik'],
                    'nip' => $identity['nip'],
                    'updated_at' => now(),
                ]);

                $komunitasIdentities[(int) $komunitas->id] = [
                    ...$identity,
                    'nama' => (string) $komunitas->nama,
                ];
            }

            foreach (DB::table('users')->orderBy('id')->get() as $user) {
                $roleId = (int) $user->role_id;
                $identity = $this->simulatedIdentity((int) $user->id, 500);

                // Petani dan brigade pangan menggunakan NIK. Jika data komunitas
                // mewakili orang yang sama, gunakan NIK yang sama pada kedua tabel.
                if (in_array($roleId, [1, 5], true)) {
                    $komunitas = $komunitasIdentities[(int) ($user->komunitas_id ?? 0)] ?? null;
                    if ($komunitas && $this->samePerson($user->nama_lengkap, $komunitas['nama'])) {
                        $identity['nik'] = $komunitas['nik'];
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'nik' => $identity['nik'],
                        'nip' => null,
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                // Petugas, pejabat, dan admin menggunakan NIP 18 digit.
                DB::table('users')->where('id', $user->id)->update([
                    'nik' => null,
                    'nip' => $identity['nip'],
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('users')->update(['nik' => null, 'nip' => null]);
            DB::table('komunitas')->update(['nik' => null, 'nip' => null]);
        });
    }

    /**
     * Membuat identitas sintetis yang mengikuti struktur NIK dan NIP Indonesia.
     * Angka ini hanya untuk simulasi dan tidak merujuk pada identitas nyata.
     *
     * @return array{nik: string, nip: string}
     */
    private function simulatedIdentity(int $id, int $offset): array
    {
        $seed = $id + $offset;
        $isMale = $seed % 2 === 1;
        $year = 1974 + ($seed % 20);
        $month = 1 + (($seed * 5) % 12);
        $day = 1 + (($seed * 7) % 27);
        $appointmentYear = min($year + 22 + ($seed % 7), 2024);
        $appointmentMonth = 1 + (($seed * 3) % 12);

        // 63.04.xx menyerupai kode wilayah Kabupaten Barito Kuala.
        $districtCode = sprintf('6304%02d', 1 + ($seed % 17));
        $nikDay = $isMale ? $day : $day + 40;
        $serial = 1000 + (($seed * 37) % 8999);

        $nik = sprintf(
            '%s%02d%02d%02d%04d',
            $districtCode,
            $nikDay,
            $month,
            $year % 100,
            $serial,
        );

        $nip = sprintf(
            '%04d%02d%02d%04d%02d%d%03d',
            $year,
            $month,
            $day,
            $appointmentYear,
            $appointmentMonth,
            $isMale ? 1 : 2,
            1 + ($seed % 999),
        );

        return ['nik' => $nik, 'nip' => $nip];
    }

    private function samePerson(string $userName, string $komunitasName): bool
    {
        $firstName = static fn (string $name): string => mb_strtolower(
            explode(' ', trim($name))[0] ?? ''
        );

        return $firstName($userName) !== ''
            && $firstName($userName) === $firstName($komunitasName);
    }
};
