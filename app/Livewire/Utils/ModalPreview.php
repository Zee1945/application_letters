<?php

namespace App\Livewire\Utils;

use Livewire\Attributes\On;
use Livewire\Component;

class ModalPreview extends Component
{
    public $files_preview = [];
    public $modal_id = null;
    public function mount()
    {
        $this->files_preview = [];
        $this->modal_id = 'modalPreview_' . uniqid(); // Buat unique ID
    }

    public function render()
    {
        return view('livewire.utils.modal-preview');
    }

    #[On('open-modal-preview')]
   public function openModal($files) {
        $this->files_preview = $files;
        $this->dispatch('open-modal-preview-js', modalId: $this->modal_id);
    }


    public function closeModal() {
        $this->files_preview = [];
        $this->dispatch('close-modal-preview-js',modalId: $this->modal_id);
    }







}
