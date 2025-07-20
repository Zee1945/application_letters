{{-- Modal Preview --}}
    <div class="modal fade" id="{{ $modal_id }}" tabindex="-1" aria-labelledby="{{ $modal_id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-transparent">
            <div class="modal-header">
                <div class="d-flex justify-content-end w-100">
                    <button type="button" class="btn btn-md btn-outline-light text-white rounded-circle border-2" wire:click="closeModal" aria-label="Close"><i class="fa-solid fa-xmark me-0"></i></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="row">
                    <div class="card">
                        <div class="card-body">

                            tessss
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-center">
                        {{-- {{count($this->files_preview)}} --}}
                        @if (count($this->files_preview) > 0)
                            @foreach ($this->files_preview as $file)
                                @if ($file['mimetype'] == 'pdf' || $file['mimetype'] == 'application/pdf')
                                    <iframe
                                        src="{{ Storage::disk($file['storage_type'])->temporaryUrl($file['path'], now()->addHours(1)) }}"
                                        width="100%" height="100%" style="min-height: calc(100vh - (2rem));">
                                    </iframe>
                                @else
                                    <img src="{{ Storage::disk($file['storage_type'])->temporaryUrl($file['path'], now()->addHours(1)) }}"
                                         alt="My Image" style="max-width: 100%; height: auto;">
                                @endif
                            @endforeach
                        @else
                            <p>No preview available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<script type="module">
    document.addEventListener('livewire:init', () => {
        const modalId = '{{ $modal_id }}';
        let modalInstance = null;
        
        Livewire.on('open-modal-preview-js', (event) => {
            if (event.modalId === modalId) {
                // Hapus backdrop yang ada sebelumnya
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    backdrop.remove();
                });
                
                modalInstance = bootstrap.Modal.getOrCreateInstance('#' + modalId);
                modalInstance.show();
                console.log(modalInstance);
                
            }
        });
        
        Livewire.on('close-modal-preview-js', (event) => {
            if (event.modalId === modalId && modalInstance) {
                modalInstance.hide();
                
            }
        });
    });
</script>
