<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\On;
use Livewire\Component;
class TinymceEditor extends Component
{
    public $content = ''; // Default textarea content
    public $editorId; // Variable to hold editor ID
    public $is_disabled = false; // Variable to hold editor ID

    // Method to update the content


    public function mount($editorId,$isDisabled = '')
    {
        $this->editorId = $editorId;
        $this->is_disabled = $isDisabled == 'disabled'? true:false;

    }

    public function render()
    {
        return view('livewire.forms.tinymce-editor');
    }


    #[On('update-content-value')]
    public function updatedContent($value)
    {
        // dd($value);
        $this->content = $value;
    }

    // public function update($){

    // }
}
