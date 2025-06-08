<?php

namespace App\Livewire\Forms;

use Livewire\Component;
class TinymceEditor extends Component
{
    public $content = ''; // Default textarea content
    public $editorId; // Variable to hold editor ID

    // Method to update the content
    public function updatedContent($value)
    {
        $this->content = $value;
    }

    public function mount($editorId)
    {
        $this->editorId = $editorId; // Set the editor ID when mounting
    }

    public function render()
    {
        return view('livewire.forms.tinymce-editor');
    }
}
