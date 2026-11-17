# Backend CI4 - UMKM Lokal API

API CRUD dengan CodeIgniter 4. Untuk demo lokal, model menyimpan data di `writable/data/umkm.json`, sehingga tidak memerlukan server database. File dibuat otomatis saat data pertama kali ditulis. Tiga contoh data awal muncul saat penyimpanan masih kosong.

## Menjalankan

1. Pastikan PHP 8.2+ dan Composer terpasang.
2. Salin konfigurasi: `cp .env.example .env`.
3. Pasang dependency: `composer install`.
4. Jalankan: `php spark serve --host 0.0.0.0 --port 8080`.
5. Cek `http://localhost:8080/api/umkm`.

## Endpoint

| Method | Path | Hasil |
|---|---|---|
| GET | `/api/umkm?page=1&per_page=10` | Daftar data dan metadata pagination |
| GET | `/api/umkm/{id}` | Detail data |
| POST | `/api/umkm` | Membuat data, status 201 |
| PUT | `/api/umkm/{id}` | Mengganti seluruh data |
| DELETE | `/api/umkm/{id}` | Menghapus data |

Contoh body POST/PUT:

```json
{
  "nama_umkm": "Warung Banua",
  "pemilik": "Andi",
  "alamat": "Jl. A. Yani, Banjarmasin",
  "kategori": "Kuliner",
  "kontak": "081234567890"
}
```

Semua respons memakai `success`, `message`, dan `data`. Daftar juga menyertakan `meta`. Validasi menghasilkan HTTP 422 dengan objek `errors`; body JSON tidak valid menghasilkan 400 dan id yang tidak ditemukan menghasilkan 404.

## Penyimpanan

Model menggunakan `flock` dan penulisan file atomik supaya request tulis tidak saling menimpa pada demo lokal. Untuk deployment atau banyak pengguna, ganti model dengan `CodeIgniter\\Model` dan database MySQL/PostgreSQL. Penyimpanan berkas ini adalah pilihan agar praktik dapat dijalankan tanpa layanan tambahan.
