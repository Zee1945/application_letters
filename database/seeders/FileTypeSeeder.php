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
            ['name' => 'Draft TOR', 'order'=>1,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'TOR', 'order'=>2,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'SK', 'order'=>3,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Undangan Peserta dan Panitia', 'order'=>12,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Jadwal Kegiatan', 'order'=>13,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Laporan Kegiatan', 'order'=>16,'trans_type' => 2, 'signed_role_id' => $user_id],
            ['name' => 'Surat Permohonan Narasumber', 'order'=>14,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Permohonan Moderator', 'order'=>15,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Narasumber', 'order'=>4,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Moderator', 'order'=>5,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Panitia', 'order'=>6,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Surat Tugas Peserta', 'order'=>7,'trans_type' => 1, 'signed_role_id' => $dean_id],
            ['name' => 'Daftar Kehadiran Narasumber', 'order'=>8,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Moderator', 'order'=>9,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Panitia', 'order'=>10,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Daftar Kehadiran Peserta', 'order'=>11,'trans_type' => 1, 'signed_role_id' => $user_id],
            ['name' => 'Notulensi', 'order'=>17,'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
            ['name' => 'File SPJ', 'order'=>18,'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
            ['name' => 'Materi Narasumber', 'order'=>19,'trans_type' => 2, 'signed_role_id' => $user_id,'is_upload'=>1],
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
