<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Mengenai laravel

Laravel adalah kerangka kerja aplikasi web dengan sintaks yang ekspresif dan elegan. Kami percaya bahwa pengembangan harus menjadi pengalaman yang menyenangkan dan kreatif agar benar-benar memuaskan. Laravel menghilangkan rasa sakit dari pengembangan dengan memudahkan tugas-tugas umum yang digunakan dalam banyak proyek web.

## Laravel Setup (Quick Guide)

Ini adalah panduan singkat untuk menjalankan proyek Laravel dari GitHub tanpa mengganggu dokumentasi utama.

### Clone Proyek dari GitHub

```bash
git clone https://github.com/Zaidizzah/digitalization-documents.git
cd digitalization-documents
```

### Install dependency via composer

```bash
composer install
```

### Salin dan konfigurasi file `.env`

```bash
cp .env.example .env
```

### Generate application key

```bash
php artisan key:generate
```

### Migrasi dan seeder

```bash
php artisan migrate
php artisan db:seed
```

### Menjalankan project laravel

```bash
php artisan serve
```

---

# Project Digitalisasi Dokumen

Proyek ini bertujuan untuk mengelola proses digitalisasi dokumen secara efisien, mulai dari upload, penyimpanan metadata, hingga pencarian berbasis skema dan atribut tertentu. Cocok digunakan untuk instansi, perusahaan, maupun kebutuhan personal.

## Fitur Utama

- ✅ Upload dokumen (PDF, DOCX, gambar, dll)
- 🗂️ Kategori dokumen berdasarkan skema
- 🧩 Skema dinamis (custom field)
- 🔍 Pencarian dan filter berdasarkan metadata
- 🔐 Otentikasi pengguna (admin & user)
- 🧾 Export hasil dalam format Excel, CSV, dan PDF
- 🔄 Tracking revisi dokumen
- 🗑️ Soft delete & restore dokumen

## Alur Kerja Sistem

```mermaid
graph TD
    A[Login Admin/User]
    B[Upload Dokumen]
    C[Tentukan Skema dan Metadata]
    D[Simpan ke Database dan Storage]
    E[Tampilkan di Dashboard]
    F[Pencarian & Filter]
    G[Unduh / Preview / Export]

    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
```

## Kontributor:
- [Ahmad Zaid](https://github.com/Ahmad0126/) sebagai Sistem analist, debugger, dan fullstack web developer
- [Zaid Izzah Nurbaain](https://github.com/Zaidizzah/) sebagai Sistem analist, debugger, dan fullstack web developer

## Link cepat
- [Mengenai laravel](#mengenai-laravel)
- [Pengenalan digitalisasi dokumen](#project-digitalisasi-dokumen)
- [Fitur aplikasi digitalisasi dokumen](#fitur-utama)
- [Alur kerja sistem digitalisasi dokumen](#alur-kerja-sistem)
- [Kontibutor project digitalisasi dokumen](#kontributor)
