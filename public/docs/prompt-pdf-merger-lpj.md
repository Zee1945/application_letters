Saya memiliki aplikasi Laravel untuk manajemen surat/dokumen kegiatan yang sudah production.
Saya ingin menambahkan fitur **PDF merge untuk dokumen LPJ (Laporan Pertanggungjawaban)**
tanpa merusak data dan flow yang sudah ada.

---

### STACK & INFRASTRUKTUR

- Laravel (PHP), Livewire, MinIO (file storage)
- OnlyOffice untuk konversi DOCX → PDF (sudah berjalan)
- Library PDF merger: `webklex/laravel-pdfmerger` sudah terinstall tapi belum dipakai
- Document generation: PhpOffice\PhpWord\TemplateProcessor

---

### STRUKTUR DATA YANG RELEVAN

**Models:**
- `ApplicationReport` (table: `application_reports`) — data utama LPJ
  - relasi: `attachments()` → `ReportAttachment`
  - `application_id` sebagai foreign key ke `Application`
- `ReportAttachment` (table: `report_attachments`)
  - kolom: `file_id`, `application_report_id`, `type`, `reference_id`
  - type values: `spj-file`, `minutes-file`, `document-photos`, `attendence-files`
- `ApplicationDraftCostBudget` — data realisasi anggaran
  - relasi: `files()` many-to-many via `draft_cost_budget_files` (kuitansi/SPBY)
- `ApplicationParticipant` — data personal narasumber/moderator
  - kolom: `cv_file_id`, `idcard_file_id`, `npwp_file_id`, `material_file_id`
- `ApplicationFile` — menyimpan referensi dokumen yang sudah di-generate

**Services yang sudah ada:**
- `TemplateProcessorService` — generate dokumen DOCX & konversi ke PDF
- `FileManagementService` — upload/download MinIO, konversi OnlyOffice
- `ApplicationService` — business logic termasuk `storeAttachmentToDetails()`

**Jobs yang sudah ada:**
- `GenerateReportJob` — generate LPJ DOCX → PDF
- `GenerateSubmitReportJob` — handle submit workflow

---

### REQUIREMENT FITUR BARU

**Tujuan:** Setelah LPJ di-generate, merge semua dokumen pendukung menjadi 1 PDF lampiran
terlampir. Proses ini hanya berjalan jika ada **flag tertentu bernilai true**.

**Urutan merge (sequence matters):**

1. **LPJ utama** — hasil generate DOCX→PDF (sudah ada, jadi base document)
2. **Form Realisasi** (jika ada):
   - Ambil cover PDF "Form Realisasi"
   - Merge dengan file SPBY/kuitansi (`draft_cost_budget_files`)
   - Append ke LPJ
3. **Dokumentasi Kegiatan** (jika ada file `document-photos`):
   - Masukkan dulu foto-foto ke template Word → konversi ke PDF via OnlyOffice
   - Ambil cover PDF "Dokumentasi Kegiatan"
   - Merge cover + PDF dokumentasi
   - Append ke LPJ
4. **File Pendukung** (jika `attendence-files` atau file lain > 1):
   - Ambil cover PDF "File Pendukung"
   - Merge dengan semua file pendukung
   - Append ke LPJ
5. **Data Personal Narasumber/Moderator** (jika ada):
   - Ambil cover PDF "Data Personal"
   - Merge dengan CV, ID card, NPWP dari `ApplicationParticipant`
   - Append ke LPJ

**Catatan penting:**
- File pendukung, kuitansi, data personal **bisa sudah berupa PDF** siap merge
- `parent_id` untuk hasil merge adalah `id` dari `ApplicationReport` (LPJ)
- Jangan ubah data/relasi yang sudah ada, hanya tambah kolom/tabel baru jika perlu
- Cover PDF untuk setiap section sudah tersedia sebagai template statis

---

### PERTANYAAN / YANG SAYA BUTUHKAN

1. **Rekomendasi library PDF merger** terbaik untuk Laravel di 2024 selain
   `webklex/laravel-pdfmerger` — pertimbangkan: stabilitas, dukungan PDF kompleks,
   lisensi, performa untuk file besar. Apakah perlu binary eksternal (pdftk, ghostscript)?

2. **Desain flag** — sebaiknya flag "aktifkan merge" disimpan di mana?
   Opsi: kolom di `application_reports`, kolom di `applications`, config app, atau
   flag di `ApplicationFile`? Mana yang paling clean dan tidak breaking?

3. **Desain tabel/kolom baru** — apa yang perlu ditambahkan ke database untuk menyimpan:
   - Status proses merge (pending/processing/done/failed)
   - Referensi ke file hasil merge
   - Urutan lampiran (sequence)
   - Parent ID (LPJ id)

4. **Arsitektur service** — apakah sebaiknya buat `PdfMergeService` baru atau
   extend `FileManagementService`? Bagaimana integrasi dengan Job queue yang sudah ada?

5. **Implementasi step-by-step** — berikan kode untuk:
   - Migration baru (tanpa ubah tabel existing)
   - `PdfMergeService` dengan method untuk tiap section
   - Job `MergeLpjDocumentJob` yang memanggil service sesuai urutan
   - Cara trigger job ini dari `GenerateReportJob` yang sudah ada
   - Error handling jika salah satu file tidak ditemukan

6. **Edge cases** yang perlu dihandle:
   - File di MinIO tidak ditemukan saat proses merge
   - OnlyOffice timeout saat konversi foto dokumentasi
   - Rollback jika proses merge gagal di tengah jalan
   - File sudah dalam format non-PDF (gambar, Word)
