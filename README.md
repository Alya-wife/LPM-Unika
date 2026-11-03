# Sistem Informasi & Portal Resmi Lembaga Penjaminan Mutu (LPM)
### Universitas Katolik Soegijapranata (SCU)

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-5.7%20%2F%208.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)]()

Portal terpadu penjaminan mutu akademik dan non-akademik di lingkungan **Universitas Katolik Soegijapranata**, memfasilitasi siklus SPMI, Audit Mutu Internal (AMI), akreditasi institusi & program studi, pemeringkatan, serta sistem evaluasi kinerja 360 personel LPM.

---

## 📁 Struktur Direktori Proyek

Proyek ini dibangun menggunakan arsitektur modular Native PHP yang terstruktur, aman, dan kompatibel penuh dengan web server Apache (Laragon / cPanel / VPS):

```text
LPM/
├── admin/                      # Modul Dashboard & Panel Kontrol Administrator
│   ├── includes/               # Header, sidebar, footer & autentikasi sesi admin
│   ├── *-list.php              # Tampilan manajemen data (tabel, filter, pencarian AJAX, pagination)
│   ├── *-form.php              # Formulir tambah / edit data (input validasi, unggah berkas, dsb.)
│   └── evaluasi-kinerja-*.php  # Manajemen kuesioner, hak akses Google & rekap evaluasi 360
├── api/                        # Endpoint REST / JSON handler untuk request internal
├── assets/                     # Berkas statis (CSS pihak ketiga, JS, icon fonts)
├── config/                     # Konfigurasi basis data & kredensial integrasi
│   ├── database.php            # Koneksi PDO MySQL & fungsi utilitas global
│   ├── oauth.php               # Konfigurasi kredensial Google OAuth 2.0 (GSI)
│   └── oauth.example.php       # Template contoh konfigurasi OAuth
├── database/                   # Skema SQL, dump database, dan catatan migrasi
│   ├── lpm_scu.sql             # Dump basis data utama sistem LPM
│   ├── query_update_database.sql # Pembaruan skema kolom & hak akses role
│   └── README.md               # Panduan teknis restorasi dan import database
├── includes/                   # Komponen tata letak & seksi modular halaman publik
│   ├── header.php              # Header global, meta tag SEO, & CSS variables tema
│   ├── navbar.php              # Menu navigasi responsif & whimsical dropdown layanan
│   ├── footer.php              # Footer institusi & tautan penting
│   └── *-sections.php          # Seksi render dinamis pendukung Visual Page Builder
├── scripts/                    # Skrip utilitas CLI & migrasi otomatis database
│   ├── run_migrations.php      # Eksekutor migrasi otomatis skema tabel
│   ├── setup_evaluasi_kinerja_tables.php # Inisialisasi skema modul evaluasi kinerja
│   └── convert_all_existing_images_to_webp.php # Optimasi format gambar WebP
├── uploads/                    # Media, dokumen PDF, foto buletin & avatar peserta
├── .htaccess                   # Pengaturan URL rewriting, kompresi, & proteksi direktori
├── index.php                   # Beranda utama website LPM
├── berita.php                  # Portal Berita & Dokumentasi Kegiatan
├── dokumen.php                 # Arsip Dokumen Mutu & Kebijakan
├── evaluasi-kinerja.php        # Portal Evaluasi Kinerja Personel LPM (Google Sign-In)
├── spmi.php                    # Sistem Penjaminan Mutu Internal (PPEPP)
├── siklus-ami.php              # Portal Pelaksanaan Siklus Audit Mutu Internal
└── README.md                   # Dokumentasi teknis proyek
```

---

## 🚀 Fitur Unggulan Sistem

1. **Siklus SPMI & Siklus PPEPP:**
   - Dokumentasi Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan (PPEPP) standar mutu.
   - Akses dokumen kebijakan, manual mutu, standar, dan formulir mutu universitas.

2. **Audit Mutu Internal (AMI):**
   - Pelaksanaan siklus AMI terpadu dari pengantar, audit dokumen, visitasi auditor, hingga Rapat Tinjauan Manajemen (RTM).

3. **Sistem Evaluasi Kinerja 360 LPM:**
   - Autentikasi Google SSO (Google Identity Services) khusus email civitas yang terdaftar.
   - Evaluasi Diri (Penilaian Pribadi) personel.
   - Penilaian Kinerja Antar Rekan Kerja (*Peer Review* 360).
   - Penilaian Kinerja Personel oleh Kepala LPM dengan skala skor 1–5 dan catatan bukti pendukung.
   - Rekapitulasi nilai per tahun akademik, lembar cetak surat resmi (*print view*), dan ekspor CSV.

4. **Layanan & Kemitraan Mutu:**
   - Pendaftaran Pelatihan Eksternal.
   - Pengajuan Permohonan Kunjungan Studi Banding.
   - Survei Kepuasan Layanan LPM berbasis instrumen mutu.

5. **Panel Admin Modern (CMS):**
   - Role-Based Access Control (Superadmin, Admin Konten, Pengelola Layanan).
   - Live AJAX search, filter tahun akademik, dan sliding pagination 3-tombol dinamis.
   - Visual Page Builder untuk kustomisasi seksi konten beranda.

---

## 🛠️ Persyaratan Sistem

- **Web Server:** Apache 2.4+ (dengan modul `mod_rewrite` aktif)
- **PHP:** Versi 8.1 atau lebih tinggi
  - Ekstensi PHP yang dibutuhkan: `pdo_mysql`, `gd` (atau `imagick`), `json`, `mbstring`, `curl`
- **Database:** MySQL 5.7+ / MariaDB 10.4+
- **Browser:** Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari modern

---

## ⚙️ Panduan Instalasi & Menjalankan Proyek

### 1. Clone Repository
```bash
git clone https://github.com/Alya-wife/LPM-Unika.git
cd LPM-Unika
```

### 2. Setup Basis Data
1. Buka phpMyAdmin atau MySQL CLI, buat database bernama `lpm_scu`.
2. Import file database utama:
   ```bash
   mysql -u root -p lpm_scu < database/lpm_scu.sql
   mysql -u root -p lpm_scu < database/query_update_database.sql
   ```
3. Atau jalankan skrip migrasi PHP dari terminal:
   ```bash
   php scripts/run_migrations.php
   php scripts/setup_evaluasi_kinerja_tables.php
   ```

### 3. Konfigurasi Sambungan
- Salin konfigurasi Google OAuth jika belum ada:
  ```bash
  cp config/oauth.example.php config/oauth.php
  ```
- Sesuaikan kredensial database di [`config/database.php`](config/database.php):
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'lpm_scu');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  ```

### 4. Hak Akses Folder Unggahan
Pastikan folder `uploads/` dapat ditulisi (*writable*):
```bash
chmod -R 775 uploads/
```

### 5. Akses Melalui Browser
- **Portal Publik:** `http://localhost/LPM/` (atau virtual host `http://lpm.local/`)
- **Panel Administrator:** `http://localhost/LPM/admin/`

---

## 🔐 Akun Default Administrator

| Akun | Username | Password Default | Role |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | *(sesuai database instalasi)* | Superadmin (Akses Penuh) |

---

## 📄 Lisensi & Hak Cipta
Hak Cipta &copy; Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata Semarang. Seluruh hak cipta dilindungi undang-undang.
