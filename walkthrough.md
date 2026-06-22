# Panduan Hasil Penerapan (Walkthrough): Pembatasan Masa Tanam Berdasarkan Peran

Dokumen ini menjelaskan implementasi serta verifikasi aturan masa tanam bagi akun **Kelompok Tani** dan **Brigade Pangan**.

## Aturan Masa Tanam
- **Kelompok Tani (Peran/Role 1)**:
  - Diizinkan melakukan lapor tanam pada bulan **Januari - September** (Bulan 1 s.d 9).
  - Di luar periode tersebut, input data tanam akan dikunci.
- **Brigade Pangan (Peran/Role 5)**:
  - Diizinkan melakukan lapor tanam pada bulan **Oktober - Januari** (Bulan 10, 11, 12, dan 1).
  - Di luar periode tersebut, input data tanam akan dikunci.
- **Periode Irisan (Januari / Bulan 1)**:
  - Kedua peran diperbolehkan melakukan input data tanam.

---

## Perubahan Kode yang Dilakukan

### 1. Tampilan Dashboard Utama
#### [MODIFY] [petani.blade.php](file:///c:/laragon/www/PROYEKAKHIR/clients/web_app/resources/views/dashboard/petani.blade.php)
- Melakukan pengecekan variabel `$isAllowedToPlant` berdasarkan peran pengguna saat ini dan bulan berjalan (`now()->format('n')`).
- Jika diperbolehkan, tombol **Lapor Tanam** aktif seperti biasa.
- Jika terkunci, tombol berubah menjadi tidak aktif (`Lapor Tanam (Kunci)`) disertai tampilan spanduk (banner) peringatan berwarna kuning yang merincikan aturan masa tanam.

### 2. Menu Navigasi Samping (Sidebar)
#### [MODIFY] [menu-petani.blade.php](file:///c:/laragon/www/PROYEKAKHIR/clients/web_app/resources/views/partials/sidebar/petani/menu-petani.blade.php)
- Menu samping **Lapor Tanam** secara dinamis akan terkunci dan dinonaktifkan dengan ikon gembok/kunci ketika pengguna berada di luar jadwal tanam yang ditentukan.

### 3. Keamanan Tambahan di Sisi Controller (Web Client)
#### [MODIFY] [SiklusTanamController.php](file:///c:/laragon/www/PROYEKAKHIR/clients/web_app/app/Http/Controllers/SiklusTanamController.php)
- Menambahkan validasi masa tanam pada fungsi `create`, `store`, `editTanam`, `updateTanam`, dan `destroyTanam`.
- Hal ini mencegah akses langsung melalui URL (misal: mengetikkan langsung `/lapor-tanam` atau `/lapor-tanam/{id}/edit`) atau memicu fungsi kirim data di luar bulan jadwal tanam. Jika diakses secara tidak sah, pengguna otomatis dialihkan kembali ke dashboard dengan pesan error.

---

## Hasil Verifikasi & Uji Coba

Pengujian dilakukan pada waktu berjalan saat ini (Bulan Juni / Bulan 6):
1. **Kelompok Tani (`nrlhikmah554@gmail.com`)**:
   - Berhasil masuk ke dashboard.
   - Karena Juni masuk dalam rentang Januari - September, tombol dan menu **Lapor Tanam** aktif dan dapat diakses sepenuhnya.
2. **Brigade Pangan (`budi@gmail.com`)**:
   - Berhasil masuk ke dashboard.
   - Karena Juni berada di luar rentang Oktober - Januari, tombol dan menu **Lapor Tanam** otomatis terkunci/dinonaktifkan.
   - Banner peringatan *"Masa Tanam Sedang Terkunci"* tampil dengan jelas.
   - Akses manual langsung ke `/lapor-tanam` berhasil diblokir dan dialihkan kembali ke dashboard dengan pesan peringatan.

---

## Rekaman Demo Verifikasi
Berikut adalah hasil tangkapan simulasi pengujian kedua peran tersebut di peramban (browser):

![Rekaman Verifikasi](/C:/Users/nrlhi/.gemini/antigravity-ide/brain/02a4290c-d9a0-4483-9faf-c20f22b06826/tanam_verification_1782114457950.webp)
