<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MigrateNikNip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-nik-nip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate NIK and NIP columns and simulate data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting NIK/NIP migration...');

        if (!Schema::hasColumn('users', 'nik')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nik', 20)->nullable()->after('id');
            });
            $this->info('Added nik to users table');
        }

        if (!Schema::hasColumn('users', 'nip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nip', 20)->nullable()->after('nik');
            });
            $this->info('Added nip to users table');
        }

        if (!Schema::hasColumn('komunitas', 'nip')) {
            Schema::table('komunitas', function (Blueprint $table) {
                $table->string('nip', 20)->nullable()->after('nik');
            });
            $this->info('Added nip to komunitas table');
        }

        // Generate NIK (16 digits)
        $generateNik = function () {
            $nik = '';
            for ($i = 0; $i < 16; $i++) {
                $nik .= mt_rand(0, 9);
            }
            return $nik;
        };

        // Generate NIP (18 digits)
        $generateNip = function () {
            $nip = '';
            for ($i = 0; $i < 18; $i++) {
                $nip .= mt_rand(0, 9);
            }
            return $nip;
        };

        // Update existing Komunitas (all get NIK if empty, but let's assume they all get a new random NIK for simulation if empty or just overwrite to be safe? "suntikkan data simulasi untuk NIK dan NIP di tabel komunitas dan tabel user dari setiap data akun yang sudah ada")
        $komunitas = DB::table('komunitas')->get();
        foreach ($komunitas as $k) {
            $updateData = [];
            if (empty($k->nik)) {
                $updateData['nik'] = $generateNik();
            }
            if (empty($k->nip)) {
                $updateData['nip'] = $generateNip();
            }
            if (!empty($updateData)) {
                DB::table('komunitas')->where('id', $k->id)->update($updateData);
            }
        }
        $this->info('Simulated NIK/NIP for komunitas');

        // Update existing Users
        // Role 1 & 5 -> NIK, Role 2,3,4 -> NIP
        $users = DB::table('users')->get();
        foreach ($users as $u) {
            $roleId = (int) $u->role_id;
            $updateData = [];

            if (in_array($roleId, [1, 5], true)) {
                if (empty($u->nik)) {
                    // Try to get NIK from their komunitas_id if any
                    $userKomunitas = DB::table('komunitas')->where('id', $u->komunitas_id)->first();
                    if ($userKomunitas && !empty($userKomunitas->nik)) {
                        $updateData['nik'] = $userKomunitas->nik;
                    } else {
                        $updateData['nik'] = $generateNik();
                    }
                }
            } else if (in_array($roleId, [2, 3, 4], true)) {
                if (empty($u->nip)) {
                    $updateData['nip'] = $generateNip();
                }
            }

            if (!empty($updateData)) {
                DB::table('users')->where('id', $u->id)->update($updateData);
            }
        }
        $this->info('Simulated NIK/NIP for users');

        $this->info('Migration and Data Simulation complete.');
    }
}
