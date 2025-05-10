# Project Digitalisasi Dokumen

Proyek ini bertujuan untuk mengelola proses digitalisasi dokumen secara efisien, mulai dari upload, penyimpanan metadata, hingga pencarian berbasis skema dan atribut tertentu. Cocok digunakan untuk instansi, perusahaan, maupun kebutuhan personal.

---

## Fitur Utama

- ✅ Upload dokumen (PDF, DOCX, gambar, dll)
- 🗂️ Kategori dokumen berdasarkan skema
- 🧩 Skema dinamis (custom field)
- 🔍 Pencarian dan filter berdasarkan metadata
- 🔐 Otentikasi pengguna (admin & user)
- 🧾 Export hasil dalam format Excel, CSV, dan PDF
- 🔄 Tracking revisi dokumen
- 🗑️ Soft delete & restore dokumen

---

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
