<?php

namespace App\Livewire\Utils;

use Livewire\Attributes\On;
use Livewire\Component;

class ModalPreview extends Component
{
    public $files_preview = [];
    public function mount($files=[])
    {
        $this->files_preview = $files;
    }

    public function render()
    {
        return view('livewire.utils.modal-preview');
    }

    #[On('open-modal-preview')]
    public function openModal($files) {
        $this->files_preview = $files;
        $this->dispatch('open-modal-preview');
    }


    public function closeModal() {
        $this->files_preview = [];
        $this->dispatch('close-modal-preview');
    }







}
