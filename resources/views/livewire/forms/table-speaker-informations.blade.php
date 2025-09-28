{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\forms\table-speaker-informations.blade.php --}}
<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <div class="row g-4">
        @foreach ($speakers as $index => $row)
            <div class="col-12">
                <div class="card shadow border-0 h-100">
                    <div class="card-header">
                              <div class="mb-3 d-flex">
                            <span class="fw-bold fs-6 me-1">{{$index+1}}.</span>
                            <div class="">
                                <span class="fw-bold fs-6">{{ $row->name }}</span>
                                <br>
                                <small class="fw-bold text-secondary">({{ $row->institution }})</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pb-4">
                  
                        <div class="row g-2">
                            {{-- CV --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">CV</label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['cv_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                        {{-- value="{{ !empty($this->rows[$index]['cv_file_id']) ? ($this->rows[$index]['cv_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                        disabled>
                                    <input type="file"
                                        wire:model="rows.{{ $index }}.cv_file_id"
                                        id="cv_file_id_{{ $index }}"
                                        class="d-none"
                                        accept=".pdf">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="document.getElementById('cv_file_id_{{ $index }}').click()"
                                        title="Upload / Pilih File">
                                        <i class="fa-regular fa-folder-open"></i>
                                    </button>
                                    @if(!empty($this->rows[$index]['cv_file_id']))
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['cv_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        @endif
                                </div>
                            </div>
                            {{-- KTP --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">KTP</label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['idcard_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                        {{-- value="{{ !empty($this->rows[$index]['idcard_file_id']) ? ($this->rows[$index]['idcard_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                        disabled>
                                    <input type="file"
                                        wire:model="rows.{{ $index }}.idcard_file_id"
                                        id="file_idcard_{{ $index }}"
                                        class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="document.getElementById('file_idcard_{{ $index }}').click()"
                                        title="Upload / Pilih File">
                                        <i class="fa-regular fa-folder-open"></i>
                                    </button>
                                    @if(!empty($this->rows[$index]['idcard_file_id']))
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['idcard_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        @endif
                                </div>
                            </div>
                            {{-- NPWP --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">NPWP</label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['npwp_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                        disabled>
                                    <input type="file"
                                        wire:model="rows.{{ $index }}.npwp_file_id"
                                        id="file_npwp_{{ $index }}"
                                        class="d-none"
                                        accept=".jpg,.jpeg,.png">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="document.getElementById('file_npwp_{{ $index }}').click()"
                                        title="Upload / Pilih File">
                                        <i class="fa-regular fa-folder-open"></i>
                                    </button>
                                    @if(!empty($this->rows[$index]['npwp_file_id']))
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['npwp_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        @endif
                                </div>
                            </div>
                            {{-- Materi --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">Materi</label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['material_file_id']) ?  'File terupload' : 'Belum ada file' }}"
                                        disabled>
                                    <input type="file"
                                        wire:model="rows.{{ $index }}.material_file_id"
                                        id="file_material_{{ $index }}"
                                        class="d-none"
                                        accept=".pdf">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="document.getElementById('file_material_{{ $index }}').click()"
                                        title="Upload / Pilih File">
                                        <i class="fa-regular fa-folder-open"></i>
                                    </button>
                                    @if(!empty($this->rows[$index]['material_file_id']))
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['material_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>