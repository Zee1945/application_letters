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
                        <div class="d-flex justify-content-between mb-3">
                        <div class=" d-flex">
                            <span class="fw-bold fs-6 me-1">{{$index+1}}.</span>
                            <div class="">
                                <span class="fw-bold fs-6">{{ $row->name }}</span>
                                <br>
                                <small class="fw-bold text-secondary">({{ $row->institution }})</small>
                            </div>
                        </div>
                        <div class="d-flex align-self-center">
                            @if ($this->selected_inf_id == 'part_'.$row->id.'_'.$row->participant_type_id)
                                <button type="button" class="btn btn-outline-secondary rounded-circle btn-xs" title="Tutup" wire:click="setSelectedInf('null','null')">
                                    <i class="fa-solid fa-xmark me-0" style="font-size: 1rem"></i>
                                </button>
                            @else
                                <button type="button" class="btn btn-success rounded-circle btn-xs" title="Buka" wire:click="setSelectedInf('{{ $row->id }}','{{ $row->participant_type_id }}')">
                                    <i class="fa-solid fa-plus me-0" style="font-size: 1rem"></i>
                                </button>
                            @endif
                                
                        </div>
                        </div>
                              
                    </div>
                    <div class="card-body pb-4 {{ $this->selected_inf_id == 'part_'.$row->id.'_'.$row->participant_type_id ?'':'d-none' }}" id="part_{{$row->id}}_{{$row->participant_type_id}}">
                    <form method="POST" action="{{ route('applications.submit-doc-speaker',['application_id'=>$this->application->id]) }}" enctype="multipart/form-data">
                     @csrf
                        <input type="hidden" name="participant_data" value="{{$row->id}}_{{$row->participant_type_id}}">
                        <div class="row g-2">
                            {{-- CV --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">CV</label>
                                 {{-- Start New component   --}}
                                <div class="input-group">
                                    @if (!empty($this->rows[$index]['cv_file_id']))
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['cv_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                        {{-- value="{{ !empty($this->rows[$index]['cv_file_id']) ? ($this->rows[$index]['cv_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                        disabled>
                                    
                                @if(viewHelper::handleFieldDisabled($this->application,false,true) != 'disabled')
                                    <button class="btn btn-outline-danger" type="button"
                                        wire:click="destroyAttachment('{{$this->rows[$index]['cv_file_id']}}','{{ $index }}','cv_file_id')"
                                        title="Hapus File">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                @endif
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['cv_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>

                                        @else
                                        <input type="file"
                                        name="cv_file_id"
                                        id="cv_file_id_{{ $index }}"
                                        class="form-control"
                                        accept=".pdf">
                                    @endif
                                </div>
                                {{-- End New Component --}}
                            </div>
                            {{-- KTP --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">KTP</label>
                                     {{-- Start New component   --}}
                                <div class="input-group">
                                    @if (!empty($this->rows[$index]['idcard_file_id']))
                                    <input type="text"
                                        class="form-control bg-light"
                                        value="{{ !empty($this->rows[$index]['idcard_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                        {{-- value="{{ !empty($this->rows[$index]['cv_file_id']) ? ($this->rows[$index]['cv_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                        disabled>
                                @if(viewHelper::handleFieldDisabled($this->application,false,true) != 'disabled')
                                    
                                    <button class="btn btn-outline-danger" type="button"
                                        wire:click="destroyAttachment('{{$this->rows[$index]['idcard_file_id']}}','{{ $index }}','idcard_file_id')"
                                        title="Hapus File">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-outline-success" type="button"
                                            wire:click="openModalPreview({{ $this->rows[$index]['idcard_file_id'] }})"
                                            title="Lihat File">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>

                                        @else
                                        <input type="file"
                                        name="idcard_file_id"
                                        id="idcard_file_id_{{ $index }}"
                                        class="form-control"
                                        accept=".pdf">
                                    @endif
                                </div>
                                {{-- End New Component --}}
                            </div>
                            {{-- NPWP --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">NPWP</label>
                            {{-- Start New component   --}}
                                    <div class="input-group">
                                        @if (!empty($this->rows[$index]['npwp_file_id']))
                                        <input type="text"
                                            class="form-control bg-light"
                                            value="{{ !empty($this->rows[$index]['npwp_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                            {{-- value="{{ !empty($this->rows[$index]['cv_file_id']) ? ($this->rows[$index]['cv_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                            disabled>
                                        
                                @if(viewHelper::handleFieldDisabled($this->application,false,true) != 'disabled')
                                        
                                        <button class="btn btn-outline-danger" type="button"
                                            wire:click="destroyAttachment('{{$this->rows[$index]['npwp_file_id']}}','{{ $index }}','npwp_file_id')"
                                            title="Hapus File">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                @endif
                                        <button class="btn btn-outline-success" type="button"
                                                wire:click="openModalPreview({{ $this->rows[$index]['npwp_file_id'] }})"
                                                title="Lihat File">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>

                                            @else
                                            <input type="file"
                                            name="npwp_file_id"
                                            id="npwp_file_id_{{ $index }}"
                                            class="form-control"
                                            accept=".pdf">
                                        @endif
                                    </div>
                                {{-- End New Component --}}
                            </div>
                            {{-- Materi --}}
                            <div class="col-12 col-lg-6">
                                <label class="form-label fw-bold mb-1">Materi</label>
                                {{-- Start New component   --}}
                                    <div class="input-group">
                                        @if (!empty($this->rows[$index]['material_file_id']))
                                        <input type="text"
                                            class="form-control bg-light"
                                            value="{{ !empty($this->rows[$index]['material_file_id']) ? 'File terupload' : 'Belum ada file' }}"
                                            {{-- value="{{ !empty($this->rows[$index]['cv_file_id']) ? ($this->rows[$index]['cv_file_id']->getClientOriginalName() ?? 'File terupload') : 'Belum ada file' }}" --}}
                                            disabled>
                                        
                                @if(viewHelper::handleFieldDisabled($this->application,false,true) != 'disabled')
                                    
                                        <button class="btn btn-outline-danger" type="button"
                                            wire:click="destroyAttachment('{{$this->rows[$index]['material_file_id']}}','{{ $index }}','material_file_id')"
                                            title="Hapus File">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                @endif
                                        <button class="btn btn-outline-success" type="button"
                                                wire:click="openModalPreview({{ $this->rows[$index]['material_file_id'] }})"
                                                title="Lihat File">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>

                                            @else
                                            <input type="file"
                                            name="material_file_id"
                                            id="material_file_id_{{ $index }}"
                                            class="form-control"
                                            accept=".pdf">
                                        @endif
                                    </div>
                                {{-- End New Component --}}
                            </div>
                        </div>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) != 'disabled')

                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-paper-plane"></i> Submit
                                </button>
                            </div>
                        </div>
                        @endif
                        </form>
                    </div>
                </div>
          
            </div>
        @endforeach
    </div>
</div>

<script type="module">

</script> 