<?php

namespace Database\Seeders;

use App\Models\FileType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mengambil role ID berdasarkan nama role
        $dean_id = Role::where('name', 'dekan')->first()->id;  // Mengambil ID role 'dekan'
        $user_id = Role::where('name', 'user')->first()->id;    // Mengambil ID role 'user'

        // Data parent (kolom kiri gambar) yang akan diinsert pertama kali
        $parent_files = [
            ['name' => 'TOR', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Draft TOR', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'SK', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Undangan Peserta dan Panitia', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Jadwal Kegiatan', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Laporan Kegiatan', 'trans_type' => 2, 'signed_role_id' => $user_id],
            ['name' => 'Surat Permohonan Narasumber', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Permohonan Moderator', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Narasumber', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Moderator', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Panitia', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Peserta', 'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Daftar Kehadiran Narasumber', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Moderator', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Panitia', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Peserta', 'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Notulensi', 'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
            ['name' => 'File SPJ', 'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
            ['name' => 'Materi Narasumber', 'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
        ];

        // $parent_ids = [];

        // Loop untuk insert data parent ke dalam tabel FileType
        foreach ($parent_files as $file) {
             FileType::create([
                'name'    => $file['name'],
                'code'         => strtolower(str_replace(' ', '_', $file['name'])), // Membuat 'code' dari nama file
                'trans_type'   => $file['trans_type'],  // trans_type sesuai dengan yang didefinisikan
                'signed_role_id' => $file['signed_role_id'],  // signed_role_id sesuai dengan role yang terhubung
                'is_upload' => isset($file['is_upload'])?$file['is_upload']:0 
            ]);
        }

    }
}
