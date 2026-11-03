# Panduan Database LPM UNIKA

Direktori ini memuat berkas-berkas skema database SQL dan catatan migrasi untuk Website Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.

---

## Daftar Berkas Database

1. **`lpm_scu.sql`**
   - Dump basis data utama sistem LPM UNIKA (skema tabel lengkap dan data awal/master).
   - Digunakan saat inisialisasi awal instalasi sistem pada environment lokal atau server produksi baru.

2. **`query_update_database.sql`**
   - Kumpulan query pembaruan skema:
     - Penambahan kolom `role`, `akses_halaman`, dan `status` pada tabel `users`.
     - Konfigurasi proteksi dokumen terbatas (`is_restricted`, `max_preview_pages`, `allowed_domains`) untuk `dokumen`, `buletin`, `spmi_kemendikti`, dan kegiatan/dokumen AMI.

3. **`update_struktur_organisasi.sql`**
   - Skrip pembaruan data dan struktur hierarki bagan organisasi LPM UNIKA.

---

## Cara Import Database

### Melalui phpMyAdmin
1. Buka phpMyAdmin (misal: `http://localhost/phpmyadmin`).
2. Buat database baru bernama `lpm_scu` dengan collation `utf8mb4_unicode_ci`.
3. Klik tab **Import**, pilih berkas `database/lpm_scu.sql`, lalu klik **Import**.
4. Lakukan hal yang sama untuk `query_update_database.sql` jika diperlukan penyesuaian lanjutan.

### Melalui CLI (Terminal / CMD)
```bash
# 1. Buat database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS lpm_scu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import skema utama
mysql -u root -p lpm_scu < database/lpm_scu.sql

# 3. Jalankan pembaruan skema
mysql -u root -p lpm_scu < database/query_update_database.sql
```

---

## Migrasi Otomatis Melalui Skrip PHP

Sistem telah dilengkapi dengan skrip migrasi otomatis di folder `scripts/`:

```bash
# Menjalankan seluruh migrasi skema tabel terbaru
php scripts/run_migrations.php

# Menyiapkan tabel Evaluasi Kinerja 360 LPM (jika belum ada)
php scripts/setup_evaluasi_kinerja_tables.php
```
