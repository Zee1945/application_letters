{{-- filepath:
c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
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
                    <input type="text" wire:model.live.debounce.1000ms="search" class="form-control rounded-pill"
                        placeholder="Cari nama template...">
                </form>
            </div>

            <div class="row g-4">
                @forelse ($templates as $key => $template)
                    {{-- filepath:
                    c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php
                    --}}
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow template-card position-relative" {{-- data-bs-toggle="modal"
                            --}} {{-- data-bs-target="#editTemplateModal" --}} wire:click="openModalEdit({{ $template }})">
                            <!-- Tombol edit, hanya muncul saat hover -->
                            <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
                                <span
                                    class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3 icon-file"
                                    style="width:60px; height:60px;">
                                    <i class="fa-solid fa-file-word fa-2x"></i>
                                </span>
                                <span
                                    class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mb-3 icon-edit"
                                    style="width:60px; height:60px; margin-top: -75px;">
                                    {{-- <i class="fa-solid fa-file-word fa-2x"></i> --}}
                                    <i class="fa fa-edit fa-2x"></i>
                                </span>
                                <div class="fw-bold text-center mb-2" style="font-size:1.1rem;">{{ $template->name }}</div>
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

    {{-- filepath:
    c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
    <div wire:self.ignore class="modal fade" id="editTemplateModal" tabindex="-1"
        aria-labelledby="editTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('manage-templates.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title d-flex align-items-center gap-2" id="editTemplateModalLabel">
                            Edit File Template
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if (!empty($this->edit_item))
                            <input type="hidden" name="code" value="{{ $this->edit_item['code'] }}">
                            <div class="mb-3">
                                <div class="fw-bold mb-1" style="font-size:1.1rem;">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <span
                                                class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center mb-3 mx-auto"
                                                style="width:60px; height:60px;">
                                                <i class="fa-solid fa-file-word fa-2x"></i>
                                            </span>
                                        </div>
                                        <div class="">
                                            <div class="">
                                                {{ $this->edit_item['name'] ?? '-' }}
                                            </div>
                                            <div class="d-flex">
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-primary btn-sm dropdown-toggle"
                                                        type="button" id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                        <i class="fa fa-download"></i> Download
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('manage-templates.download-template', ['code' => $this->edit_item['code']]) }}"
                                                                target="_blank" title="Download Dokumen Sebelumnya">
                                                                <i class="fa fa-file"></i> File Saat ini
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('manage-templates.download-backup-template', ['code' => $this->edit_item['code']]) }}"
                                                                target="_blank" title="Download Dokumen Backup">
                                                                <i class="fa fa-file-archive"></i> File Backup
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                        </div>

                                    </div>
                                </div>



                        @endif

                        </div>

                        <div class="mb-1">
                            <label for="templateFile" class="form-label fw-bold">Upload Template Baru</label>
                            <input type="file" id="templateFile" class="form-control" name="new_file_template"
                                accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                required>
                            @error('template_file') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <small class="text-muted"><span class="text-danger">*</span> </span> File wajib berekstensi
                            <b>.docx</b>.</small>

                        <!-- Tambahkan Catatan -->
                        <div class="alert alert-info d-flex align-items-start gap-3 mt-3" role="alert">
                            <i class="fa-solid fa-circle-info text-info fa-2x"></i>
                            <div>
                                <strong>Catatan Penting:</strong>
                                <p class="mb-0">
                                    Saat mengedit template, <b>jangan mengubah variabel</b> seperti
                                    <code>${variabel_name}</code> yang ada pada file. Variabel tersebut dibutuhkan oleh
                                    sistem.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- filepath:
c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\manage-template-list.blade.php --}}
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
                if (modal) modal.hide();
            });
        });
    </script>
@endpush