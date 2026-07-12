# Panduan Deployment aaPanel untuk SiPetani (Microservices & Web App)

Dokumen ini memuat panduan penting agar aplikasi SiPetani (Laravel Microservices) berjalan lancar saat di-deploy ke aaPanel dan mencegah error `404 Not Found`, `403 Forbidden`, maupun `CORS`.

## 1. Pengaturan Website & Document Root
Untuk setiap service Laravel (termasuk Web App dan API Gateway), **Site Directory** dan **Document Root** harus disetel dengan benar.

- **Site directory**: `/www/wwwroot/proyekakhir/clients/web_app` (Contoh untuk Web App)
- **Run directory**: `/public`
- **PENTING**: Centang pada `Anti-XSS attack (open_basedir)` **WAJIB DIHILANGKAN** agar Laravel dapat membaca file di luar folder `public`.

## 2. Pengaturan URL Rewrite (Nginx)
Agar routing Laravel berfungsi dan tidak menghasilkan `404 Not Found` pada endpoint API atau halaman selain `/`:

1. Buka pengaturan website di aaPanel.
2. Masuk ke menu **URL rewrite**.
3. Pilih template **Laravel5** dari dropdown.
4. Klik **Save**.

*Konfigurasi ini akan menambahkan kode Nginx berikut:*
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 3. Hak Akses (Permissions)
Setiap kali memindahkan, menghapus, atau mengunggah file baru via Terminal / SSH / File Manager, pastikan hak akses folder selalu dikembalikan ke `www` (user Nginx/PHP).
Jalankan di Terminal aaPanel:
```bash
sudo chown -R www:www /www/wwwroot/proyekakhir
```

## 4. Konfigurasi .env (API Gateway & Web App)
- Pada `clients/web_app/.env`, pastikan `GATEWAY_URL` menunjuk ke URL API Gateway **TANPA** garis miring di akhir (trailing slash).
  ```env
  GATEWAY_URL=https://api.sigpala.my.id
  ```
- Pada `services/api_gateway/.env`, pastikan URL semua microservice (`AUTH_SERVICE_URL`, `GIS_SERVICE_URL`, dll) menunjuk ke internal port yang benar (misal: `http://127.0.0.1:8001`) dan tidak ada yang ter-hardcode ke `localhost` jika berjalan di port terpisah.

## 5. SSL / HTTPS
Jika Frontend menggunakan HTTPS (`https://sigpala.my.id`), maka API Gateway **WAJIB** menggunakan HTTPS (`https://api.sigpala.my.id`). Jika API Gateway menggunakan HTTP (tanpa `s`), browser akan memblokir request tersebut dengan alasan `Mixed Content` atau `CORS`.

Pastikan SSL certificate terpasang untuk semua domain publik (Frontend dan API Gateway) melalui menu **SSL** di pengaturan website aaPanel.
