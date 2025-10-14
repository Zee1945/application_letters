<?php

namespace App\Livewire\FormLists\Master;

use App\Models\Department;
use App\Models\FileType;
use App\Models\Position;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ManageTemplateList extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $department = 0;
    public $template_file;
    public $edit_item = null;

    protected $updatesQueryString = ['search', 'page'];

    public function render()
    {
        $templates = FileType::where('is_upload',0)
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            })
            ->get();

        return view('livewire.form-lists.master.manage-template-list', [
            'templates' => $templates,
        ])->extends('layouts.main');
    }

  public function openModalEdit($item)
    {
        $this->edit_item = $item;
        $this->reset('template_file');
        $this->dispatch('show-edit-modal');
    }

    public function updateTemplateFile()
    {
        $this->validate([
            'template_file' => 'required|file|mimes:doc,docx',
        ]);

        $template = FileType::findOrFail($this->edit_item->id);

        // Simpan file ke storage (misal: storage/app/templates)
        $path = $this->template_file->store('templates');

        // Update path file di database (pastikan ada field untuk path file)
        $template->file_path = $path;
        $template->save();

        $this->dispatch('closeEditModal');
        session()->flash('success', 'Template berhasil diupdate.');
    }
}
