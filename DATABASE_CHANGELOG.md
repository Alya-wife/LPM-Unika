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
  - Terintegrasi dengan form unggah multi-format di Admin CMS (`admin/layanan-form-setting.php?tab=brosur`).
  - Halaman publik (`pelatihan.php` / `pelatihan-eksternal.php`) menampilkan brosur secara langsung (embedded viewer PDF, foto poster, atau kartu dokumen Word) dengan tombol aksi unduh langsung.

### G. Kunci Pengaturan Narasi Pelatihan (`pengaturan`)
- Penambahan / standarisasi kunci pada tabel `pengaturan`:
  - `pelatihan_hero_title`: Judul Banner Hero pada halaman info pelatihan.
  - `pelatihan_hero_desc`: Ikhtisar singkat pada banner hero.
  - `pelatihan_narasi_lengkap`: Teks narasi komprehensif profil pelatihan, kompetensi narasumber asesor BAN-PT/LAM, ruang lingkup materi (Auditor AMI, Bimtek SPMI PPEPP, Klinik Borang Akreditasi, RTM), serta kemitraan yang dapat disunting langsung oleh Administrator.

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
