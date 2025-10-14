{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
@push('styles')
<style>

</style>
@endpush

<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Manage Template</div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <!-- Search Section -->
            <div class="d-flex gap-2 mb-4">
                <form wire:submit.prevent="search" class="d-flex flex-grow-1 gap-2">
                    <input type="text" wire:model="search" class="form-control rounded-pill" placeholder="Cari nama template...">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Cari</button>
                </form>
            </div>

            <div class="row g-4">
                @forelse ($templates as $key => $template)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow template-card position-relative">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                                <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:60px; height:60px;">
                                    <i class="fa-solid fa-file-word fa-2x"></i>
                                </span>
                                <div class="fw-bold text-center mb-2" style="font-size:1.1rem;">{{ $template->name }}</div>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                               
                                    <button type="button"
                                        class="btn btn-outline-warning btn-sm rounded-pill px-3 template-action-btn"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editTemplateModal"
                                        wire:click="openModalEdit({{ $template }})">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-secondary text-center mb-0 rounded-pill">
                            Tidak ada Template ditemukan.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

   {{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
<div wire:ignore.self class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('manage-templates.update', $editTemplate?->id ?? 0) }}" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="editTemplateModalLabel">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="fa-solid fa-file-word fa-lg"></i>
                        </span>
                        Edit File Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <div class="fw-bold mb-1" style="font-size:1.1rem;">
                            @if ($this->edit_id)
                                {{ $this->edit_id?->name ?? '-' }}
                            @endif
                        </div>
                            <a href="#"
                            {{-- <a href="{{ route('manage-templates.download', $editTemplate->id) }}" --}}
                               class="btn btn-outline-primary btn-sm rounded-pill"
                               title="Download Dokumen Sebelumnya" target="_blank">
                                <i class="fa fa-download"></i> Download File Lama
                            </a>
                    </div>
                    <div class="mb-3">
                        <label for="templateFile" class="form-label">File Template (Word, .doc/.docx)</label>
                        <input type="file" id="templateFile" class="form-control"
                               wire:model="template_file"
                               accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               required>
                        @error('template_file') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <small class="text-muted">File wajib berekstensi <b>.doc</b> atau <b>.docx</b>.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-edit-modal', () => {
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    backdrop.remove();
                });
            const modal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
            modal.show();
        });
        Livewire.on('closeEditModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editTemplateModal'));
            if(modal) modal.hide();
        });
    });
</script>
@endpush