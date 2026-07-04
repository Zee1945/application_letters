<?php

namespace Database\Seeders;

use App\Models\FileType;
use App\Models\GroupFileType;
use Illuminate\Database\Seeder;

class GroupFileTypeSeeder extends Seeder
{
    /**
     * Buat grup file type lalu petakan file_types ke grup berdasarkan code.
     *
     * Idempoten: grup dibuat dengan firstOrCreate (by name), dan pemetaan
     * file_types di-update ulang setiap kali seeder jalan, sehingga aman
     * dijalankan berkali-kali maupun setelah FileTypeSeeder.
     */
    public function run(): void
    {
        // name => order tampilan grup
        $groups = [
            'Dokumen Utama'    => 1,
            'Surat Undangan'   => 2,
            'Surat Permohonan' => 3,
            'Surat Tugas'      => 4,
            'Daftar Kehadiran' => 5,
            'Lampiran Laporan' => 6,
        ];

        $groupIds = [];
        foreach ($groups as $name => $order) {
            $group = GroupFileType::firstOrCreate(['name' => $name], ['order' => $order]);
            // Pastikan order ikut ter-update bila grup sudah ada sebelumnya.
            if ($group->order !== $order) {
                $group->order = $order;
                $group->save();
            }
            $groupIds[$name] = $group->id;
        }

        // Pemetaan code => nama grup. Prefix (startsWith) menangani varian
        // narasumber/moderator/panitia/peserta sekaligus.
        $prefixMap = [
            'surat_undangan_'   => 'Surat Undangan',
            'surat_permohonan_' => 'Surat Permohonan',
            'surat_tugas_'      => 'Surat Tugas',
            'daftar_kehadiran_' => 'Daftar Kehadiran',
        ];

        // Code eksak yang tidak mengikuti pola prefix di atas.
        $exactMap = [
            'draft_tor'        => 'Dokumen Utama',
            'tor'              => 'Dokumen Utama',
            'sk'               => 'Dokumen Utama',
            'jadwal_kegiatan'  => 'Dokumen Utama',
            'laporan_kegiatan' => 'Lampiran Laporan',
            'notulensi'        => 'Lampiran Laporan',
            'file_spj'         => 'Lampiran Laporan',
            'absensi_kehadiran'=> 'Lampiran Laporan',
            'materi_narasumber'=> 'Lampiran Laporan',
        ];

        foreach (FileType::all() as $fileType) {
            $code = (string) $fileType->code;
            $groupName = null;

            foreach ($prefixMap as $prefix => $name) {
                if (str_starts_with($code, $prefix)) {
                    $groupName = $name;
                    break;
                }
            }

            if ($groupName === null && isset($exactMap[$code])) {
                $groupName = $exactMap[$code];
            }

            if ($groupName !== null) {
                $fileType->group_file_type_id = $groupIds[$groupName];
                $fileType->save();
            }
        }
    }
}
