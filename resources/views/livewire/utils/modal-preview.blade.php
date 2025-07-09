{{-- Modal Preview  --}}
<div class="modal fade" id="modalPreview" tabindex="-1" aria-labelledby="modalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="modalPreviewLabel">Preview File</h1>
          <button type="button" class="btn-close" wire:click="closeModalPreview" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="">
                  <div class="row">
                      <div class="d-flex flex-column align-items-center">
                        @if (count($this->files)>0)
                        @forelse ($this->files as $file)
                        @if ($file['mimetype'] == 'pdf' || $file['mimetype']== 'application/pdf')
                            <iframe
                                src="{{ Storage::disk($file['storage_type'])->temporaryUrl($file['path'], now()->addHours(1)) }}"
                                width="100%" height="600px">
                            </iframe>
                        @else
                            <img src="{{ Storage::disk($file['storage_type'])->temporaryUrl($file['path'], now()->addHours(1)) }}" alt="My Image {{ Storage::disk($file['storage_type'])->temporaryUrl($file['path'], now()->addHours(1)) }}">
                        @endif
                    @empty
                    @endforelse
                    @endif
                      </div>
                  </div>
          </div>
        </div>
      </div>
    </div>
  </div>



  <script type="module">
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-modal-preview', (event) => {
           const modal = bootstrap.Modal.getOrCreateInstance('#modalPreview');
           modal.show();
       });
        Livewire.on('close-modal-preview', (event) => {
           const modal = bootstrap.Modal.getOrCreateInstance('#modalPreview');
           modal.hide();
       });
    });
</script>
