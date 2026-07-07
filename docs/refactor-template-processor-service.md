# Rencana Refactoring `TemplateProcessorService.php`

> **Tujuan:** Membuat file lebih mudah di-maintain, menghilangkan kode redundan, dan reusable — **tanpa mengubah perilaku output** (hasil dokumen harus identik).
>
> **Kondisi saat ini:** 1655 baris, 26 method, estimasi 300–350 baris (18–21%) adalah kode duplikat.
> **Target:** ~1200–1300 baris (turun 25–27%), perilaku tidak berubah.

---

## 1. Prinsip & Aturan Main

1. **Refactor, bukan rewrite.** Output dokumen (DOCX/PDF) harus **persis sama** sebelum dan sesudah.
2. **Incremental.** Kerjakan per-langkah, commit per-langkah, test setiap langkah.
3. **Backward compatible.** Semua method publik yang dipanggil dari luar (`generateApplicationDocument`, `generateDocumentToPDF`, `downloadDocxGenerated`, dll) **tidak diubah signature-nya**.
4. **Test dulu sebelum sentuh.** Sebelum refactor, generate dokumen dari 1 aplikasi sample untuk **tiap jenis** (TOR, SK, LPJ, surat tugas, dll), simpan sebagai baseline. Bandingkan setelah tiap langkah.

---

## 2. Ringkasan Masalah (Code Smells)

| Smell | Severity | Jumlah | Dampak |
|---|:---:|:---:|---|
| Loop `application->getAttributes()` + switch berulang | TINGGI | 9x | ~150 baris duplikat |
| Loop `detail->getAttributes()` (parsing `activity_dates`) | TINGGI | 6x | 2 varian, inkonsisten |
| Injeksi metadata signer (`setValue` berulang) | TINGGI | 9x | ~60 baris duplikat |
| Setup QR code (`setImageValue`) | TINGGI | 9x | ~50 baris duplikat |
| Lookup tahun dari SK (`year_sk`) | SEDANG | 9x | ~40 baris duplikat |
| Injeksi `department_name` + uppercase | SEDANG | 15x | ~30 baris duplikat |
| Magic string: file type code, letter name, participant type | SEDANG | 30+ | Rawan typo |
| Magic number: `funding_source == 1 ? 'BLU' : 'BOPTN'` | RENDAH | 8x | Rawan inkonsisten |
| Method panjang (>100 baris) | SEDANG | 6 | Sulit dibaca |
| Variabel `$temp` tak terpakai | RENDAH | beberapa | Noise |

---

## 3. Strategi Refactoring (Bertahap)

Diurutkan dari **risiko terendah → tertinggi**. Setiap fase bisa di-commit terpisah.

### FASE 0 — Persiapan (wajib sebelum mulai)
- [ ] Buat baseline output: generate dokumen tiap jenis dari aplikasi sample, simpan PDF/DOCX-nya.
- [ ] Pastikan ada cara cepat regenerate (mis. lewat tinker atau route test) untuk membandingkan.

---

### FASE 1 — Konstanta & Magic Values (Risiko: RENDAH)

Tambahkan konstanta di awal class. Tidak mengubah logika, hanya mengganti literal.

```php
class TemplateProcessorService
{
    /** Sumber dana: kode → label */
    private const FUNDING_SOURCE = [
        1 => 'BLU',
        2 => 'BOPTN', // default untuk selain 1
    ];

    /** Nama letter number yang sering dilookup */
    private const LETTER_NOMOR_SK = 'nomor_sk';
    private const LETTER_NOMOR_SURAT_TUGAS = 'nomor_surat_tugas';
    private const LETTER_NOMOR_SURAT_PERMOHONAN = 'nomor_surat_permohonan';
    // ... dst sesuai temuan

    /** Tipe peserta */
    private const PARTICIPANT_TYPES = ['commitee', 'speaker', 'moderator', 'participant'];

    /** Format tanggal */
    private const FMT_INPUT_DATE  = 'd-m-Y';
    private const FMT_DISPLAY_DAY = 'l';
}
```

**Helper kecil:**
```php
private static function fundingSourceLabel($value): string
{
    return self::FUNDING_SOURCE[$value] ?? self::FUNDING_SOURCE[2];
}
```

**Aksi:** ganti semua `$value == 1 ? 'BLU' : 'BOPTN'` (8 lokasi) dengan `self::fundingSourceLabel($value)`.

---

### FASE 2 — Extract Helper "Injeksi" (Risiko: RENDAH–SEDANG)

Helper-helper ini hanya membungkus rangkaian `setValue` yang sudah ada. Logika identik, hanya dipindah.

#### 2.1 `injectSignerMetadata()` — hilangkan 9x duplikasi
```php
private static function injectSignerMetadata(TemplateProcessor $tp, array $meta): void
{
    $tp->setValue('signed_location', self::sanitizeForXml($meta['Lokasi'] ?? ''));
    $tp->setValue('signed_date',     self::sanitizeForXml($meta['Tgl_cetak'] ?? ''));
    $tp->setValue('signer_position', self::sanitizeForXml($meta['Jabatan'] ?? ''));
    $tp->setValue('signer_name',     self::sanitizeForXml($meta['Nama'] ?? ''));
    $tp->setValue('signed_status',   self::sanitizeForXml($meta['status_surat'] ?? ''));
}
```
> ⚠️ Cek dulu: tiap method punya set field yang sedikit berbeda? Jika ada method yang inject field tambahan (mis. SK punya `NIP`), buat parameter opsional atau biarkan method itu memanggil `setValue` ekstra setelah helper.

#### 2.2 `setupQrCode()` — hilangkan 9x duplikasi
```php
private static function setupQrCode(TemplateProcessor $tp, array $meta, int $size = 100): void
{
    $qrPath = self::generateQrCode($meta);
    $tp->setImageValue('signed_barcode', [
        'path'   => $qrPath,
        'width'  => $size,
        'height' => $size,
        'ratio'  => true,
    ]);
}
```
> Ukuran QR bervariasi (100/75/70) — jadikan parameter `$size`.

#### 2.3 `injectDepartmentName()` — hilangkan 15x duplikasi
```php
private static function injectDepartmentName(TemplateProcessor $tp, $application): void
{
    $name = $application->department->approvalDepartment()->first()?->name;
    $tp->setValue('department_name', self::sanitizeForXml($name));
    $tp->setValue('department_name_uppercase', self::sanitizeForXml(strtoupper((string) $name)));
}
```
> ⚠️ `approvalDepartment()->first()` dipanggil 2x — di helper ini dijadikan 1x query (sekaligus perbaikan kecil performa).

#### 2.4 `getYearFromSK()` — hilangkan 9x duplikasi
```php
private static function getYearFromSK($application): string
{
    $letterSk = $application->letterNumbers()
        ->where('letter_name', self::LETTER_NOMOR_SK)
        ->first();

    return ($letterSk && !empty($letterSk->letter_date))
        ? Carbon::parse($letterSk->letter_date)->format('Y')
        : date('Y');
}
```

---

### FASE 3 — Extract Pemrosesan Atribut (Risiko: SEDANG)

Bagian paling banyak duplikasinya. **Hati-hati** karena ada 2 varian parsing `activity_dates`.

#### 3.1 `injectApplicationAttributes()` — hilangkan 9x loop
Membungkus loop `foreach ($application->getAttributes() ...)` dengan switch `activity_name` (uppercase) + `funding_source`.

```php
private static function injectApplicationAttributes(TemplateProcessor $tp, $application): void
{
    foreach ($application->getAttributes() as $key => $value) {
        switch ($key) {
            case 'activity_name':
                $tp->setValue($key, self::sanitizeForXml($value));
                $tp->setValue($key.'_uppercase', self::sanitizeForXml(strtoupper((string) $value)));
                break;
            case 'funding_source':
                $tp->setValue($key, self::sanitizeForXml(self::fundingSourceLabel($value)));
                break;
            default:
                $tp->setValue($key, self::sanitizeForXml($value));
                break;
        }
    }
}
```
> ⚠️ Verifikasi: beberapa method punya urutan case berbeda (activity_name dulu vs funding_source dulu) — hasilnya sama, aman digabung. Tapi pastikan tidak ada method yang punya case khusus tambahan.

#### 3.2 `injectDetailAttributes()` — hilangkan 6x loop + samakan 2 varian
Ada **2 varian** parsing `activity_dates`:
- **Varian A** (SK, Report): `humanReadableDate` → `implode('; ', ...)` ke key `activity_dates`.
- **Varian B** (DaftarKehadiran, SuratPermohonan, Notulensi): set `*_formatted` (`d F Y`) + `*_days` (`l`).

**Rekomendasi:** buat **satu** helper parsing yang menghasilkan SEMUA bentuk, lalu set semua key. Template yang tidak memakai key tertentu akan mengabaikannya — aman.

```php
private static function injectDetailAttributes(TemplateProcessor $tp, $application): void
{
    foreach ($application->detail->getAttributes() as $key => $value) {
        if ($key === 'activity_dates') {
            self::injectActivityDates($tp, $key, $value);
            continue;
        }
        $tp->setValue($key, self::sanitizeForXml($value));
    }
}

private static function injectActivityDates(TemplateProcessor $tp, string $key, $value): void
{
    $dates = array_filter(array_map('trim', explode(',', (string) $value)));

    $human = [];     // "Selasa, 16 Juni 2026"
    $formatted = []; // "16 Juni 2026"
    $days = [];      // "Selasa"

    Carbon::setLocale('id');
    foreach ($dates as $d) {
        $c = Carbon::createFromFormat(self::FMT_INPUT_DATE, $d);
        $human[]     = ViewHelper::humanReadableDate($c->format('Y-m-d'));
        $formatted[] = $c->translatedFormat('d F Y');
        $days[]      = $c->translatedFormat('l');
    }

    // Varian A
    $tp->setValue($key, self::sanitizeForXml(implode('; ', $human)));
    // Varian B
    $tp->setValue($key.'_formatted', self::sanitizeForXml(implode('; ', $formatted)));
    $tp->setValue($key.'_days', self::sanitizeForXml(implode('; ', $days)));
}
```
> ⚠️ **WAJIB CEK:** pastikan separator tiap varian sesuai template aslinya. Varian B di kode lama mungkin pakai separator/format berbeda — samakan persis dengan aslinya, JANGAN asal `'; '`. Bandingkan dengan baseline FASE 0.

#### 3.3 `getAllParticipantsByType()` — hilangkan 5x blok
```php
/** @return array{commitee:mixed,speaker:mixed,moderator:mixed,participant:mixed} */
private static function getAllParticipantsByType($participants): array
{
    $result = [];
    foreach (self::PARTICIPANT_TYPES as $type) {
        $result[$type] = self::filterParticipantByType($type, $participants);
    }
    return $result;
}
```

---

### FASE 4 — Sederhanakan Router `generateDocumentToPDF()` (Risiko: SEDANG)

Ganti switch 18-case (55 baris) dengan **mapping array**.

```php
private const GENERATOR_MAP = [
    'tor'        => ['method' => 'generateTor'],
    'draft_tor'  => ['method' => 'generateTor'],
    'sk'         => ['method' => 'generateSK'],
    'laporan_kegiatan' => ['method' => 'generateReport'],
    'jadwal_kegiatan'  => ['method' => 'generateRundown'],

    'surat_tugas_moderator'  => ['method' => 'generateSuratTugas', 'param' => 'moderator'],
    'surat_tugas_narasumber' => ['method' => 'generateSuratTugas', 'param' => 'speaker'],
    'surat_tugas_panitia'    => ['method' => 'generateSuratTugas', 'param' => 'commitee'],
    'surat_tugas_peserta'    => ['method' => 'generateSuratTugas', 'param' => 'participant'],

    'surat_undangan_panitia' => ['method' => 'generateSuratUndangan'],
    'surat_undangan_peserta' => ['method' => 'generateSuratUndangan'],

    'surat_permohonan_narasumber' => ['method' => 'generateSuratPermohonan'],
    'surat_permohonan_moderator'  => ['method' => 'generateSuratPermohonan'],

    'daftar_kehadiran_panitia'    => ['method' => 'generateDaftarKehadiran', 'param' => 'commitee'],
    'daftar_kehadiran_peserta'    => ['method' => 'generateDaftarKehadiran', 'param' => 'participant'],
    'daftar_kehadiran_moderator'  => ['method' => 'generateDaftarKehadiran', 'param' => 'moderator'],
    'daftar_kehadiran_narasumber' => ['method' => 'generateDaftarKehadiran', 'param' => 'speaker'],
];
```

Lalu di `generateDocumentToPDF`:
```php
$config = self::GENERATOR_MAP[$code] ?? null;
if (!$config) {
    // perilaku default lama (kosong / log)
    return;
}
$method = $config['method'];
isset($config['param'])
    ? self::{$method}(/* args umum */, $config['param'])
    : self::{$method}(/* args umum */);
```
> ⚠️ Pastikan urutan & jenis argumen tiap generator konsisten. Jika signature berbeda-beda, normalisasi dulu atau pertahankan switch untuk yang menyimpang.

---

### FASE 5 — Pecah Method Panjang (Risiko: SEDANG–TINGGI)

Setelah FASE 1–4, method generator akan otomatis menyusut karena memanggil helper. Untuk yang masih >100 baris, pecah lagi:

- **`generateReport()` (169 baris)** → ekstrak `attachDocumentationPhotos()` (logika foto + temp dir + max 5 foto).
- **`generateSuratTugas()` (151 baris)** → ekstrak logika cloneRow custom (baris ~850–892) ke helper sendiri.
- **`generateSuratPermohonan()` (145 baris)** → ekstrak logika session/date parsing.

> Lakukan **satu method per commit**, bandingkan output tiap kali.

---

## 4. Estimasi Dampak

| Fase | Baris dihemat | Risiko | Bisa dilewati? |
|---|:---:|:---:|:---:|
| 1. Konstanta | ~15 | Rendah | Tidak (fondasi) |
| 2. Helper injeksi | ~140 | Rendah–Sedang | Tidak |
| 3. Pemrosesan atribut | ~150 | Sedang | Tidak |
| 4. Router mapping | ~40 | Sedang | Ya (opsional) |
| 5. Pecah method panjang | ~0 (readability) | Sedang–Tinggi | Ya (opsional) |
| **Total** | **~345 baris** | | |

---

## 5. Urutan Eksekusi yang Disarankan

```
[ ] FASE 0: Buat baseline output semua jenis dokumen
[ ] FASE 1: Konstanta + fundingSourceLabel()        → commit → test
[ ] FASE 2.1: injectSignerMetadata()                → commit → test
[ ] FASE 2.2: setupQrCode()                         → commit → test
[ ] FASE 2.3: injectDepartmentName()                → commit → test
[ ] FASE 2.4: getYearFromSK()                       → commit → test
[ ] FASE 3.1: injectApplicationAttributes()         → commit → test
[ ] FASE 3.2: injectDetailAttributes() + dates      → commit → test (PALING RAWAN)
[ ] FASE 3.3: getAllParticipantsByType()            → commit → test
[ ] FASE 4: GENERATOR_MAP router                    → commit → test
[ ] FASE 5: Pecah generateReport/SuratTugas/dll     → commit → test (per method)
```

---

## 6. Catatan Penting / Risiko

1. **`activity_dates` adalah titik paling rawan.** Ada 2 varian dengan format & separator berbeda. JANGAN gabung sebelum memverifikasi format asli tiap template. Bandingkan byte-per-byte dengan baseline.
2. **Signature generator tidak seragam.** Sebagian terima `$participant_type`, sebagian tidak. Cek sebelum membuat router mapping.
3. **Field signer bisa berbeda antar dokumen.** SK mungkin punya field tambahan (NIP, dll). Helper `injectSignerMetadata` harus mengakomodasi atau biarkan method tambah field ekstra sendiri.
4. **Jangan ubah `sanitizeForXml`, `generateQrCode`, `getSignerMetadata`** — itu sudah dipakai luas dan stabil. Hanya bungkus pemanggilannya.
5. **Property statis `$application` dan `$dump`** — cek apakah masih dipakai. Jika `$dump` sisa debugging, hapus (FASE 1).
6. **Test = bandingkan output, bukan hanya "tidak error".** Dokumen bisa ter-generate tanpa error tapi isinya beda (separator hilang, field kosong). Wajib diff visual/teks dengan baseline.

---

## 7. Yang TIDAK Dilakukan (Out of Scope)

- Tidak memindah logika ke class lain (mis. pisah jadi `DocumentGeneratorService` per jenis) — itu rewrite besar, di luar lingkup ini.
- Tidak mengubah template DOCX.
- Tidak mengubah cara file disimpan/di-convert (itu ranah `FileManagementService`).
- Tidak menambah fitur baru.

Refactoring ini murni **internal cleanup** dengan output yang dijaga identik.
