<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TableRealization extends Component
{
    use WithFileUploads; // Tambahkan trait ini agar bisa meng-handle file upload

    public $draft_costs = [];
    public $realizations = [];
    public $img_nota = []; // Array untuk menampung file yang diupload

    public function mount($draftCost=null)
    {
        $this->draft_costs = $draftCost;
        $this->realizations = $this->getDistinctDataByCodeAndItem($draftCost);
        $this->realizations = array_map(function ($item) {
            if (count($item['children']) > 0) {
                foreach ($item['children'] as $key => &$value) {
                    $value['realization'] = null;
                    $value['file_id'] = null;
                }
            }
            return $item;
        }, $this->realizations);
    }
    public function render()
    {
        $normalize_realization = $this->normalizeRealization($this->realizations);
        $this->dispatch('transfer-realization',[...$normalize_realization]);
        return view('livewire.forms.table-realization');
    }

    // Fungsi untuk handle multiple file upload ke MinIO
    public function save()
    {
       dd($this->normalizeRealization($this->realizations));
    }
    public function normalizeRealization($realizations)
    {
        return array_map(function($item) use ($realizations){
            $item['file_id'] = null;
            foreach ($realizations as $key => $value) {
                if (count($value['children']) > 0) {
                    foreach ($value['children'] as $key_child => $child) {
                       if ($item['id'] == $child['id']) {
                           $path = 'temp/report/realization/' . $child['id'];

                            if (Storage::disk('minio')->exists($path)) {
                                $files = Storage::disk('minio')->files($path); // Mendapatkan semua file dalam folder
                                foreach ($files as $file) {
                                    Storage::disk('minio')->delete($file); // Hapus setiap file
                                }
                            }
                            $item['realization'] = $child['realization'];
                            if (array_key_exists('file_id', $child) && $child['file_id'] instanceof TemporaryUploadedFile) {
                                $file_path = $child['file_id']->store($path, 'minio');
                                $item['file_id'] = $file_path;
                            }
                       }
                    }
                }
            }
            return $item;
        },$this->draft_costs);
    }

    // Menyaring data berdasarkan code dan item yang unik
    public function getDistinctDataByCodeAndItem($data)
    {
        $raw_data = $data;
        $uniqueData = array_values(
            array_unique(
                array_map(function ($value) {
                    return $value['code'] . '|' . $value['item'];
                }, $data)
            )
        );

        return array_map(function ($code_item) use ($raw_data) {
            list($code, $item) = explode('|', $code_item);
            $new_item = ['key' => $item, 'code' => $code, 'item' => $item, 'is_parent' => true, 'children_total' => 0, 'children' => []];
            $new_item['children'] = array_filter($raw_data, function ($child) use ($code, $item, &$new_item) {
                if ($child['code'] == $code && $child['item'] == $item && !empty($child['sub_item'])) {
                    $new_item['children_total']++;
                    return $child;
                }
            });
            return $new_item;
        }, $uniqueData);
    }
}
