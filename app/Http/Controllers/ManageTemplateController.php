<?php

namespace App\Http\Controllers;

use App\Models\FileType;
use App\Services\MasterManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
        // Validasi input
        $request->validate([
            'new_file_template' => 'required|file|mimes:doc,docx|max:2048', // Maksimal 2MB
            'code' => 'required|string',
        ]);

        // Path untuk file lama dan baru
        $oldFilePath = public_path('templates/' . $request->code . '.docx');
        $newFilePath = public_path('templates/');

        // Hapus file lama jika ada
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }

        // Upload file baru
        if ($request->hasFile('new_file_template')) {
            $newFile = $request->file('new_file_template');

            // Simpan file baru dengan nama sesuai kode
            $newFile->move($newFilePath, $request->code . '.docx');
        }

        $fileType = FileType::whereCode($request->code)->first();

        // Tambahkan logika lain jika diperlukan, misalnya menyimpan metadata ke database
        MasterManagementService::storeLogActivity('update-manage-template',$fileType->id,$fileType->name);
        return redirect()->route('manage-templates.index')->with('success', 'Template berhasil diperbarui.');
    } catch (\Exception $e) {
        // Tangani error dan log pesan error
        Log::error('Error saat memperbarui template: ' . $e->getMessage());

        // Redirect kembali dengan pesan error
        return redirect()->route('manage-templates.index')->with('error', 'Terjadi kesalahan saat memperbarui template. Silakan coba lagi.');
    }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, string $id)
{

}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function downloadTemplate($code)
    {
        $filePath = public_path('templates/'.$code.'.docx');
        return response()->download($filePath);
    }
    public function downloadTemplateBackup($code)
    {
        $filePath = public_path('backup-templates/'.$code.'.docx');
        return response()->download($filePath);
    }



}
