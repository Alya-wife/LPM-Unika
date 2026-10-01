# Catatan Perubahan & Pembaruan Basis Data (Database Changelog)
**Lembaga Penjaminan Mutu (LPM) – Universitas Katolik Soegijapranata (UNIKA)**

Dokumen ini mencatat seluruh perubahan, penambahan tabel, migrasi skema, dan pemulihan data pada basis data `lpm_scu`. Seluruh pembaruan telah diintegrasikan ke dalam berkas dump utama [`lpm_scu.sql`](./lpm_scu.sql) dan skrip migrasi otomatis [`scripts/run_migrations.php`](./scripts/run_migrations.php).

---

## 1. Pembaruan Skema & Tabel Baru

### A. Tabel Autentikasi Pengguna (`users`)
- **Penambahan Kolom `email`**:
  - Kolom `email VARCHAR(191) NULL DEFAULT NULL AFTER username` ditambahkan ke tabel `users`.
  - Mencegah error fatal SQL `1054 Unknown column 'email'` pada skrip integrasi Google OAuth (`admin/google-login.php`).
  - Akun administrator utama diperbarui dengan email resmi: `tu.lpm@unika.ac.id`.
  - Kata sandi admin terverifikasi hash `admin123` via `password_verify()`.

### B. Tabel SPMI Kemendiktisaintek (`spmi_kemendikti`)
- **Tabel Baru Ditambahkan**:
  ```sql
  CREATE TABLE IF NOT EXISTS `spmi_kemendikti` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `judul` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `file_pdf` VARCHAR(255) NOT NULL,
    `tahun` INT NOT NULL DEFAULT 2025,
    `urutan` INT NOT NULL DEFAULT 1,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ```
- **Seeding Dokumen Awal**:
  1. *Laporan Pelaksanaan SPMI Terintegrasi Kemendikti Saintek* (`laporan_spmi_kemendiktisaintek_2025.pdf`, Tahun 2025).
  2. *Hasil Evaluasi dan Pengukuran Mutu Nasional SPMI* (`hasil_pelaporan_spmi_nasional_2024.pdf`, Tahun 2024).

### C. Tabel Lembaga Akreditasi (`lembaga_akreditasi`)
- **Penambahan Kolom `link_website`**:
  - Kolom `link_website VARCHAR(255) NULL AFTER logo` ditambahkan.
  - Logo dan tautan resmi lembaga diperbarui untuk 8 badan akreditasi:
    - **BAN-PT**: `logo_banpt_1789003596.webp` & `https://www.banpt.or.id/`
    - **LAMEMBA**: `logo_lamemba_1789004015.webp` & `https://lamemba.or.id/`
    - **LAM INFOKOM**: `logo_laminfokom_1789004030.webp` & `https://laminfokom.or.id/`
    - **LAM TEKNIK**: `logo_lamteknik_1789004163.webp` & `https://lamteknik.or.id/`
    - **LAM-PTKes**: `logo_lamptkes_1789004441.webp` & `https://lamptkes.org/`
    - **LAMSPAK**: `logo_lamspak_1789004216.webp` & `https://lamspak.or.id/`
    - **LAMDEPILAR**: `logo_lamdepilar_1789004235.webp` & `https://lamdepilar.or.id/`
    - **LAMPTIP**: `logo_lamptip_1789004285.png` & `https://lamptip.or.id/`

### D. Tabel Siklus AMI (Audit Mutu Internal)
Untuk mencegah error saat mengakses halaman [`siklus-ami.php`](./siklus-ami.php), 6 tabel siklus AMI dibuat dan disinkronkan:
1. `ami_periode` (Daftar siklus dan periode audit, status aktif).
2. `ami_siklus1_kegiatan` (Jadwal dan rincian agenda Siklus 1 - Perencanaan).
3. `ami_siklus1_opening` (Data pertemuan pembukaan AMI).
4. `ami_siklus4_dokumen` (Dokumen berita acara, jadwal, dan instrumen visitasi audit lapangan).
5. `ami_siklus4_dokumentasi` (Galeri dokumentasi pelaksanaan audit mutu lapangan).
6. `ami_siklus5_rtm` (Notulensi, presensi, dan hasil Rapat Tinjauan Manajemen).

### E. Tabel Survei Kepuasan Layanan LPM (`kunjungan_feedback_respon` & `kunjungan_kuesioner_pertanyaan`)
- **Penyesuaian Kolom `kunjungan_feedback_respon`**:
  - Kolom `token_id`, `nama_institusi`, dan `tanggal_kunjungan` diubah menjadi `NULLABLE` sehingga formulir terbuka langsung untuk umum (sivitas akademika & masyarakat) tanpa wajib menggunakan kode token.
  - Kolom baru untuk menangkap profil demografi responden:
    - `jenis_kelamin VARCHAR(20) NULL` (Laki-laki / Perempuan).
    - `umur VARCHAR(20) NULL` (Angka umur responden).
    - `pendidikan_terakhir VARCHAR(50) NULL` (Diploma 1-3, S1, S2, S3).
    - `kategori_layanan VARCHAR(100) DEFAULT 'Pelayanan LPM'` ('Kunjungan Studi Banding' atau 'Pelayanan LPM').
    - `status_responden VARCHAR(100) NULL` (Dosen, Tendik, Mahasiswa, Alumni, Mitra, Masyarakat Umum).
    - `saran_masukan TEXT NULL` (Kotak isian masukan & kritik kualitatif).
- **Standarisasi 8 Butir Pertanyaan Kuesioner**:
  Tabel `kunjungan_kuesioner_pertanyaan` diperbarui langsung menjadi 8 butir standar kuesioner berskala likert 1–5 dengan ikon emotikon:
  1. *Kesesuaian persyaratan pelayanan dengan jenis pelayanannya*
  2. *Kemudahan prosedur pelayanan*
  3. *Kecepatan waktu pelayanan*
  4. *Kesesuaian produk layanan dengan hasil yang diberikan*
  5. *Kompetensi atau kemampuan petugas dalam memberikan pelayanan*
  6. *Perilaku atau sikap petugas dalam pelayanan terkait keramahan*
  7. *Kualitas sarana dan prasarana*
  8. *Penanganan pengaduan, saran dan masukan*

### F. Tabel Brosur Info Pelatihan (`layanan_brosur`)
- **Tabel Baru Ditambahkan**:
  ```sql
  CREATE TABLE IF NOT EXISTS `layanan_brosur` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `judul_brosur` VARCHAR(255) NOT NULL,
    `tahun` INT NOT NULL DEFAULT 2026,
    `deskripsi` TEXT NULL,
    `file_brosur` VARCHAR(255) NOT NULL,
    `tipe_file` VARCHAR(20) NOT NULL DEFAULT 'pdf',
    `ukuran_file` VARCHAR(50) NULL,
    `link_pendaftaran` VARCHAR(255) NULL,
    `urutan` INT NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ```
- **Fitur & Format File yang Didukung**:
  - Format Berkas: **PDF, Word (DOCX/DOC), serta Foto / Gambar (PNG, JPG, JPEG, WEBP)**.
  - Nilai kolom `tipe_file`: `'pdf'`, `'docx'`, atau `'image'`.
  - Terintegrasi dengan form unggah di Admin CMS (`admin/layanan-form-setting.php?tab=pelatihan-umum`).
  - **Penyederhanaan Upload (Terbaru)**: Admin kini hanya perlu mengunggah berkas dokumen atau foto saja. Kolom `judul_brosur` terisi otomatis dari nama berkas asli, `tahun` otomatis tahun berjalan, dan kolom link pendaftaran/deskripsi ditiadakan dari form.
  - **Brosur Sampel Resmi LPM**: Dokumen aktif diperbarui ke berkas contoh brosur grafis resmi LPM UNIKA 2026 (`brosur_contoh_pelatihan_lpm_2026.pdf` & `brosur_contoh_pelatihan_lpm_2026.jpg`) menggantikan naskah akademik sebelumnya.
  - Halaman publik (`pelatihan.php` / `pelatihan-eksternal.php`) menampilkan brosur secara langsung (embedded viewer PDF, foto poster, atau kartu dokumen Word) dengan tombol aksi unduh langsung.

### G. Kunci Pengaturan Narasi Pelatihan & Kontak (`pengaturan`)
- Penambahan / standarisasi kunci pada tabel `pengaturan`:
  - `pelatihan_hero_title`: Judul Banner Hero pada halaman info pelatihan.
  - `pelatihan_hero_desc`: Ikhtisar singkat pada banner hero.
  - `pelatihan_narasi_lengkap`: Teks narasi komprehensif profil pelatihan, kompetensi narasumber asesor BAN-PT/LAM, serta kemitraan yang dapat disunting langsung oleh Administrator.
  - `pelatihan_email_kontak`: Alamat email tujuan tombol konsultasi pelatihan, terhubung langsung ke `lpm@unika.ac.id`.
  - Formulir survei kepuasan publik (`survei-kepuasan.php`) tidak lagi mensyaratkan `umur` dan `token`, sehingga kolom `umur` dan `token_id` pada `kunjungan_feedback_respon` terisi `NULL` secara aman.

### H. Kolom Alur Publikasi Berita (`berita.status`)
- Penambahan kolom `status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER tampil_di_ami`.
- Memungkinkan implementasi sistem pratinjau dan persetujuan publikasi (Draft sebelum Terbit).

### I. Tabel Tanya Jawab FAQ (`faqs`)
- **Tabel Baru Ditambahkan**:
  ```sql
  CREATE TABLE IF NOT EXISTS `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pertanyaan` VARCHAR(255) NOT NULL,
    `jawaban` TEXT NOT NULL,
    `kategori` VARCHAR(100) DEFAULT 'Umum',
    `urutan` INT DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ```
- Dikelola melalui CMS Administrator (`admin/faq-setting.php`) dan tampil dinamis di `faq.php`.

### J. Tabel Glosarium Istilah Mutu (`glosarium`)
- **Tabel Baru Ditambahkan**:
  ```sql
  CREATE TABLE IF NOT EXISTS `glosarium` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `istilah` VARCHAR(100) NOT NULL,
    `istilah_lengkap` VARCHAR(255) DEFAULT NULL,
    `definisi` TEXT NOT NULL,
    `sumber` VARCHAR(150) DEFAULT 'Kemendikbudristek / SPMI',
    `urutan` INT DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ```
- Dikelola melalui CMS Administrator (`admin/glosarium-setting.php`) dan tampil dinamis dengan pencarian interaktif di `glosarium.php`.

### K. Kunci Pengaturan Tautan Portal SPMI & UI GreenMetric (`pengaturan`)
- Penambahan kunci pengaturan status portal SPMI (Aktif vs Coming Soon) beserta URL dan narasi:
  - `portal_sista_status`, `portal_sista_url`, `portal_sista_cs_title`, `portal_sista_cs_desc`
  - `portal_spmi_status`, `portal_spmi_url`, `portal_spmi_cs_title`, `portal_spmi_cs_desc`
  - `portal_eppepp_status`, `portal_eppepp_url`, `portal_eppepp_cs_title`, `portal_eppepp_cs_desc`
- Kunci pengaturan Judul Chart PPEPP & Teks Portals SPMI (`admin/spmi-portal-setting.php` & `includes/spmi-sections.php`):
  - `spmi_chart_title`, `spmi_chart_badge`, `spmi_chart_desc`
  - `portal_sista_name`, `portal_sista_badge`, `portal_sista_desc`
  - `portal_spmi_name`, `portal_spmi_badge`, `portal_spmi_desc`
  - `portal_eppepp_name`, `portal_eppepp_badge`, `portal_eppepp_desc`
- Kunci pengaturan UI GreenMetric (`admin/pemeringkatan-setting.php` & `pemeringkatan.php`):
  - `greenmetric_rank`, `greenmetric_scope`, `greenmetric_badge`, `greenmetric_title`, `greenmetric_desc`, `greenmetric_sertifikat`

### H. Seeding Data Dummy Siklus AMI (Audit Mutu Internal) 1 s.d. 5
Seluruh 5 tahapan siklus AMI pada halaman [`siklus-ami.php`](./siklus-ami.php) telah dilengkapi dengan data dummy akademis yang realistis dan lengkap:
1. **Pengaturan 5 Tahapan Siklus (Tabel `pengaturan`)**:
   - `ami_stage1_title` s.d. `ami_stage5_title` dan `ami_stage1_desc` s.d. `ami_stage5_desc`.
   - `ami_siklus2_judul`, `ami_siklus2_url`, `ami_siklus2_status` ('active'), `ami_siklus2_desc` (Portal Pengisian DED).
   - `ami_siklus3_judul`, `ami_siklus3_url`, `ami_siklus3_status` ('active'), `ami_siklus3_desc` (Portal Desk Evaluation).
2. **Siklus 1: Rangkaian Kegiatan & Opening Meeting**:
   - `ami_siklus1_kegiatan`: 4 entri dokumen (Jadwal Siklus XIX, Surat Tugas Auditor, Instrumen Audit 9 Kriteria, SOP Audit).
   - `ami_siklus1_opening`: 2 entri dokumentasi kegiatan pembukaan dan sosialisasi dengan foto serta tanggal resmi.
3. **Siklus 4: Audit Lapangan (Visitasi)**:
   - `ami_siklus4_dokumen`: 4 dokumen (SOP Visitasi, Panduan KTS, Jadwal Visitasi Fakultas & Prodi, Jadwal Unit Non-Akademik).
   - `ami_siklus4_dokumentasi`: 3 entri berita acara dan presensi (Fakultas Ilmu Komputer, S1 Teknik Informatika, S1 Manajemen).
4. **Siklus 5: Rapat Tinjauan Manajemen (RTM)**:
   - `ami_siklus5_rtm`: 3 entri notulensi, berkas presensi, dan galeri foto (Tingkat Universitas Pleno, Tingkat Fakultas FIK, Tingkat Prodi S1 Akuntansi).
5. **Skrip Otomatisasi Seeding**:
   - Skrip tersimpan di [`scripts/seed_ami_dummy_data.php`](./scripts/seed_ami_dummy_data.php) untuk memudahkan re-seeding kapan pun dibutuhkan.

### I. Tabel Manajemen Kategori Dinamis Admin CMS
Untuk memungkinkan administrator menambah, mengubah (edit/rename dengan cascading sync), dan menghapus kategori secara aman tanpa merusak integritas relasi data:
1. **Kategori Dokumen Mutu SPMI (`kategori_dokumen`)**:
   - Ditingkatkan dengan fitur CRUD penuh (`admin/kategori-dokumen.php`), sinkronisasi otomatis kolom `dokumen.kategori` saat nama kategori diedit, dan modal konfirmasi hapus dengan opsi pengalihan (*reassign*) dokumen agar tidak menjadi yatim (*orphan*).
   - Filter tab kategori publik pada `spmi.php#dokumen-spmi` terhubung dinamis ke tabel ini melalui `includes/spmi-sections.php`.
2. **Kategori Berita & Kegiatan (`kategori_berita`)**:
   - Tabel baru ditambahkan dan di-seed dengan 5 kategori awal: `Berita`, `Kegiatan LPM`, `Artikel Mutu`, `Sosialisasi`, `Penghargaan`.
   - Modul CRUD penuh di `admin/kategori-berita.php` terintegrasi dengan `admin/berita-form.php` dan `admin/berita-list.php`.
3. **Kategori Tanya Jawab FAQ (`kategori_faq`)**:
   - Tabel baru ditambahkan dan di-seed dengan kategori awal: `SPMI`, `AMI`, `Layanan`, `Akreditasi`, `Umum`.
   - Modul CRUD penuh di `admin/kategori-faq.php` terintegrasi dengan filter kategori dan form tambah/edit di `admin/faq-setting.php`.
4. **Kategori Halaman Website (`kategori_page`)**:
   - Tabel baru ditambahkan dan di-seed dengan kategori: `Profil`, `SPMI`, `AMI`, `Akreditasi`, `Mutu & Data`, `Dokumen`, `Knowledge Center`, `Layanan`, `Kontak`, `Umum`.
   - Modul CRUD penuh di `admin/kategori-page.php` terintegrasi dengan form `admin/page-form.php` dan filter `admin/page-list.php`.

### J. Pengaturan Teks Banner Hero Beranda & Formulir Gambar Slider Simpel
Pembaruan struktur tampilan banner slider beranda (`includes/beranda-sections.php` & `admin/slider-list.php`):
1. **Teks Tetap di Atas Slider**:
   - Susunan teks utama tetap (*stationary/persistent*) di depan layar banner, sedangkan foto background berganti secara halus (*cross-fade*).
   - Kunci pengaturan baru pada tabel `pengaturan`:
     - `hero_welcome_text`: Teks pembuka baris pertama (*default*: `Selamat datang di`).
     - `hero_title`: Judul utama banner baris kedua (*default*: `Lembaga Penjaminan Mutu Unika`).
     - `hero_tagline`: Slogan/tagline mutu berbingkai emas (*default*: `GROW WITH QUALITY, SERVE WITH HEART`).
     - `hero_desc`: Kalimat penjelasan komitmen mutu universitas.
     - `hero_btn_text` & `hero_btn_link`: Tombol aksi utama dengan dropdown halaman website & opsi URL eksternal.
     - `hero_btn2_text` & `hero_btn2_link`: Tombol aksi sekunder dengan dropdown halaman website & opsi URL eksternal.
2. **Formulir Input Gambar Slide Simpel (`admin/slider-form.php`)**:
   - Disederhanakan khusus mengelola gambar background slide dengan fitur **Crop** (16:9, 21:9, Bebas), **Rotate** (90° kiri/kanan), dan **Flip** (Horizontal & Vertical).
   - Otomatis terkonversi ke format WebP berkualitas tinggi di folder `uploads/slides/`.

---

## 2. Pemulihan & Sinkronisasi Data Konten

### A. Buletin Penjaminan Mutu
- Total data buletin dipulihkan dari 2 edisi menjadi **14 edisi lengkap** (Edisi 01 hingga Edisi 14).
- Berkas PDF untuk seluruh 14 edisi telah tersedia pada folder `uploads/buletin/`.

### B. Berita & Kegiatan LPM
- Total data berita dipulihkan dari 2 entri menjadi **54 entri berita & kegiatan**.
- Kategori mencakup 36 'Kegiatan LPM', serta kategori Berita Kampus, Pengumuman, dan Mutu Akademik.
- Berkas gambar cover dan galeri dipetakan ke format WebP di folder `uploads/berita/`.

### C. Visual Page Builder (`pages`)
- Pada baris `slug = 'akreditasi'`, blok `akreditasi_dokumen` ("Unduh Dokumen & Sertifikat Akreditasi Institusi") telah dihapus dari `blocks_json` agar tidak terjadi duplikasi komponen unduhan dengan kartu institusi di bagian atas.

---

## 3. Cara Menjalankan Migrasi di Lingkungan Lain

Jika melakukan klon proyek ke lingkungan baru:
1. Buat basis data MySQL bernama `lpm_scu`.
2. Impor berkas dump utama:
   ```bash
   mysql -u root lpm_scu < lpm_scu.sql
   ```
3. Atau jalankan skrip migrasi bawaan via CLI:
   ```bash
   php scripts/run_migrations.php
   ```
