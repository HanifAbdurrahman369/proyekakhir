<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelurahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hapus truncate karena ada foreign key constraint dengan lahan_sawah
        // DB::table('kelurahan')->truncate();

        $data = [
            // 1. Alalak
            1 => [
                'Berangas', 'Berangas Barat', 'Handil Bakti', // Kelurahan
                'Berangas Timur', 'Beringin', 'Pulau Sugara', 'Sungai Lumbah', 'Tatah Mesjid', 'Panca Karya', 'Pulau Alalak', 'Pulau Sewangi', 'Sungai Pitung', 'Belandean', 'Belandean Muara', 'Tanjung Harapan', 'Semangat Dalam', 'Semangat Bakti', 'Semangat Karya' // Desa
            ],
            // 2. Anjir Muara
            2 => [
                'Anjir Muara Kota', 'Anjir Muara Kota Tengah', 'Anjir Muara Lama', 'Anjir Serapat Baru', 'Anjir Serapat Baru I', 'Anjir Serapat Lama', 'Anjir Serapat Muara', 'Anjir Serapat Muara I', 'Beringin Jaya', 'Marabahan Baru', 'Patih Muhur', 'Patih Muhur Baru', 'Sepakat Bersama', 'Sungai Punggu', 'Sungai Punggu Baru'
            ],
            // 3. Anjir Pasar
            3 => [
                'Andaman', 'Andaman II', 'Anjir Pasar Kota', 'Anjir Pasar Kota II', 'Anjir Pasar Lama', 'Anjir Seberang Pasar', 'Anjir Seberang Pasar II', 'Banyiur', 'Barunai Baru', 'Danau Karya', 'Gandaraya', 'Gandaria', 'Hilir Mesjid', 'Mentaren', 'Pandan Sari'
            ],
            // 4. Bakumpai
            4 => [
                'Lepasan', // Kelurahan
                'Bahalayung', 'Balukung', 'Banitan', 'Batik', 'Benua Anyar', 'Murung Raya', 'Palingkau', 'Sungai Lirik' // Desa
            ],
            // 5. Barambai
            5 => [
                'Bagagap', 'Barambai', 'Handil Barabai', 'Karya Baru', 'Karya Tani', 'Kolam Kanan', 'Kolam Kiri', 'Kolam Kiri Dalam', 'Pendalaman', 'Pendalaman Baru', 'Sungai Kali'
            ],
            // 6. Belawang
            6 => [
                'Bambangin', 'Belawang', 'Binaan Baru', 'Karang Buah', 'Karang Dukuh', 'Murung Keramat', 'Parimata', 'Patih Selera', 'Rangga Surya', 'Samuda', 'Sukaramai', 'Sungai Seluang', 'Sungai Seluang Pasar'
            ],
            // 7. Cerbon
            7 => [
                'Badandan', 'Bantuil', 'Sawahan', 'Simpang Nungki', 'Sungai Kambat', 'Sungai Rasau', 'Sungai Raya', 'Sungai Tunjang'
            ],
            // 8. Jejangkit
            8 => [
                'Bahandang', 'Cahaya Baru', 'Jejangkit Barat', 'Jejangkit Muara', 'Jejangkit Pasar', 'Jejangkit Timur', 'Sampurna'
            ],
            // 9. Kuripan
            9 => [
                'Asia Baru', 'Batik', 'Jambu', 'Jambu Baru', 'Jarenang', 'Kabuau', 'Kuripan', 'Rimbung Tulang', 'Tabatan'
            ],
            // 10. Mandastana
            10 => [
                'Antasan Segara', 'Bangkit Baru', 'Karang Bunga', 'Karang Indah', 'Lok Rawa', 'Pantai Hambawang', 'Puntik Dalam', 'Puntik Luar', 'Puntik Tengah', 'Sungai Ramania', 'Tabing Rimbah', 'Tanipah', 'Tatah Alayung', 'Terantang'
            ],
            // 11. Marabahan
            11 => [
                'Marabahan Kota', 'Ulu Benteng', // Kelurahan
                'Antar Baru', 'Antar Jaya', 'Antar Raya', 'Bagus', 'Baliuk', 'Karya Maju', 'Penghulu', 'Sido Makmur' // Desa
            ],
            // 12. Mekarsari
            12 => [
                'Indah Sari', 'Jelapat II', 'Karang Mekar', 'Mekarsari', 'Tamban Raya', 'Tamban Raya Baru', 'Tinggiran Baru', 'Tinggiran Darat', 'Tinggiran Tengah'
            ],
            // 13. Rantau Badauh
            13 => [
                'Danda Jaya', 'Pindahan Baru', 'Simpang Arja', 'Sinar Baru', 'Sungai Bamban', 'Sungai Gampa', 'Sungai Gampa Asahi', 'Sungai Pantai', 'Sungai Sahurai'
            ],
            // 14. Tabukan
            14 => [
                'Bandar Karya', 'Karya Indah', 'Karya Jadi', 'Karya Makmur', 'Muara Pulau', 'Pantang Baru', 'Pantang Raya', 'Rantau Bamban', 'Tabukan Raya', 'Tamba Jaya', 'Teluk Tamba'
            ],
            // 15. Tabunganen
            15 => [
                'Beringin Kencana', 'Karya Baru', 'Kuala Lupak', 'Sungai Jingah Besar', 'Sungai Telan Besar', 'Sungai Telan Kecil', 'Sungai Telan Muara', 'Sungai Teras Dalam', 'Sungai Teras Luar', 'Tabunganen Kecil', 'Tabunganen Muara', 'Tabunganen Pemurus', 'Tabunganen Tengah', 'Tanggul Rejo'
            ],
            // 16. Tamban
            16 => [
                'Damsari', 'Jelapat Baru', 'Jelapat I', 'Koanda', 'Purwosari Baru', 'Purwosari I', 'Purwosari II', 'Sekata Baru', 'Sidorejo', 'Tamban Bangun', 'Tamban Bangun Baru', 'Tamban Kecil', 'Tamban Muara', 'Tamban Muara Baru', 'Tamban Sari Baru', 'Tinggiran II Luar'
            ],
            // 17. Wanaraya
            17 => [
                'Babat Raya', 'Dwipa Sari', 'Kolam Kanan', 'Kolam Kiri', 'Kolam Makmur', 'Pinang Habang', 'Roham Raya', 'Sidomulyo', 'Simpang Jaya', 'Sumber Rahayu', 'Surya Kanta', 'Tumih', 'Waringin Kencana'
            ]
        ];

        foreach ($data as $kecamatanId => $kelurahans) {
            foreach ($kelurahans as $kelurahan) {
                DB::table('kelurahan')->updateOrInsert(
                    ['kecamatan_id' => $kecamatanId, 'nama_kelurahan' => $kelurahan],
                    ['nama_kelurahan' => $kelurahan] // Kolom tambahan jika ada (saat ini tidak ada)
                );
            }
        }
    }
}
