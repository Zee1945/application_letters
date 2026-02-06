<div>
    <div class="row">
        <div class="col-md-8 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="mb-2 d-flex justify-content-between">
                            <div>
                                <span class="fw-bold fs-5">Detail Pengajuan</span>
                            </div>
                            <div class="ms-auto">
                                <a class="btn btn-sm btn-outline-secondary" href="{{route('applications.create.draft',['application_id'=>$app->id])}}"><i class='bx bxs-edit'></i>Konten Pengajuan</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{route('reports.create',['application_id'=>$app->id])}}"><i class='bx bxs-edit'></i> Konten Laporan</a>
                    
                        @if (viewHelper::handleFieldDisabled($app) !== 'disabled')
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="if(confirm('Apakah Anda yakin ingin menghapus data ini beserta seluruh file dan detailnya?')) { @this.destroyRecursive({{$app->id}}) }"
                            >
                                <i class="bx bx-trash"></i> Hapus
                            </button>
                        @endif
                            </div>
                        </div>
                        <hr>
                    </div>
                                     <div
                                    class="row {{$app->current_approval_status == 2 || $app->current_approval_status > 20 ? '' : 'd-none'}}">
                                    <div class="alert {{$app->current_approval_status == 2 ? 'alert-warning' : 'alert-danger'}}"
                                        role="alert">
                                        <div class="d-flex">
                                            <div class="icon d-flex align-items-center"
                                                style="width: calc(100vw - (91rem))">
                                                <i
                                                    class="fa-solid {{$app->current_approval_status == 2 ? 'fa-triangle-exclamation' : 'fa-circle-xmark'}} fw-3 ms-1 fs-2"></i>
                                            </div>
                                            <div class="description d-flex flex-column w-100">
                                                <div class="d-flex justify-content-between">
                                                       <h6 class="title">
                                                      {{$app->current_approval_status == 2 ? $title_alert.' Butuh Untuk Direvisi !' : $title_alert.' Ditolak !'}}
                                                </h6>
                                                    <small>
                                                            <i>{!! viewHelper::formatDateToHumanReadable($app->currentUserApproval->updated_at, 'd-m-Y H:i:s') !!}</i></small>
                                                </div>
                                             
                                                @if (!empty($app->note))
                                                <div class="d-flex flex-column">
                                                        
                                                    <div>
                                                        <span
                                                            class="fw-bold">{{viewHelper::explodeName(explode('###',$app->note)[0])['name']}}</span>

                                                            (<span>{{viewHelper::explodeName(explode('###',$app->note)[0])['position']}}</span>
                                                        -<span
                                                            >{{viewHelper::explodeName(explode('###',$app->note)[0])['department']}}</span>)
                                                            
                                                    </div>

                                                    <div class="" style="font-style: italic">
                                                        
                                                    </div>
                                                    <div class="notes">
                                                        "{{explode('###',$app->note)[1]}}"
                                                    </div>
                                                    
                                                </div>
                                                @endif
                                                <div class="d-flex w-100 justify-content-end">
                                                                             
                                                    </div>

                                            </div>
                                            

                                        </div>
                                    </div>
                                </div>

                    <!-- Displaying Application Details -->
                    <div class="row mb-3 mt-3">
                        <div class="col-sm-3"><span class="fw-bold">Nama Kegiatan</span></div>
                        <div class="col-sm-9">
                            @if ($editable['activity_name']['is_edit'])
                                <div class="d-flex align-items-center">
                                    <input type="text" class="form-control form-control-sm me-2" wire:model="editable.activity_name.value">
                                    {{-- <button class="btn btn-outline-success btn-sm me-1" wire:click="submitEdit('activity_name')" title="Simpan"> --}}
                                    <button class="btn btn-outline-success btn-sm me-1" wire:click="openModalConfirm" title="Simpan">
                                        <i class="fa-solid fa-floppy-disk me-0"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" wire:click="cancelEdit('activity_name')" title="Batal">
                                        <i class="bx bx-x me-0"></i>
                                    </button>
                                </div>
                            @else
                                <div class="d-flex align-items-center">
                                    <span class="me-2">{{ $editable['activity_name']['value'] }}</span>
    @if (viewHelper::actionPermissionButton('edit-detail', $app) && !$this->is_editable_opened)

                                    <button class="btn btn-outline-primary btn-xs rounded-circle" wire:click="enableEdit('activity_name')" title="Edit">
                                        <i class='bx bxs-edit me-0' style="font-size: 0.9rem"></i>
                                    </button>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Sumber Pendanaan</span></div>
                        <div class="col-sm-9">
                            
                            @if ($editable['funding_source']['is_edit'])
    <div class="d-flex align-items-center">
        <select class="form-select form-select-sm me-2" wire:model="editable.funding_source.value">
            <option value="1">BLU</option>
            <option value="2">BOPTN</option>
        </select>
        <button class="btn btn-outline-success btn-sm me-1" wire:click="openModalConfirm" title="Simpan">
            <i class="fa-solid fa-floppy-disk me-0"></i>
        </button>
        <button class="btn btn-outline-danger btn-sm" wire:click="cancelEdit('funding_source')" title="Batal">
            <i class="bx bx-x me-0"></i>
        </button>
    </div>
@else
    <div class="d-flex align-items-center">
        <span class="me-2">{{ $editable['funding_source']['value'] == 1 ? 'BLU' : 'BOPTN' }}</span>
    @if (viewHelper::actionPermissionButton('edit-detail', $app) && !$this->is_editable_opened)
        <button class="btn btn-outline-primary btn-xs rounded-circle" wire:click="enableEdit('funding_source')" title="Edit">
            <i class='bx bxs-edit me-0' style="font-size: 0.9rem"></i>
        </button>
    @endif
    </div>
@endif
                        
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Status Pengajuan</span></div>
                        <div class="col-sm-9"><span>{!! viewHelper::statusSubmissionHTML($app->current_approval_status) !!}</span></div>
                    </div>
                 
                      <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Pengusul</span></div>
                        <div class="col-sm-9"><span>{{$app->createdBy->name }}</span></div>
                    </div>
                    {{-- <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Pemroses Saat Ini</span></div>
                        <div class="col-sm-9"><span>{!! viewHelper::getCurrentUserProcess($app) !!}</span></div>
                    </div> --}}
                  
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Departemen</span></div>
                        <div class="col-sm-9"><span>{{$app->department->name }}</span></div>
                    </div>
                   

                    <!-- File Information Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    {{-- <th class="text-center" style="width: 45%">Parent</th> --}}
                                    <th class="text-center" style="width: 45%">Status</th>
                                    <th style="width: 45%">Nama File</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($application_files as $item)
                                    <tr>
                                        {{-- <td>{{$item->fileType->parent?->name}}</td> --}}
                                        <td class="text-center">{!! viewHelper::generateStatusFileHTML($item->status_ready) !!}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    @if(Str::startsWith($item->fileType->code, 'daftar_kehadiran'))
                                                    <i class="bx bxs-file-doc me-2 font-24 text-primary"></i>
                                                    @else
                                                    <i class="bx bxs-file-pdf me-2 font-24 text-danger"></i>
                                                    @endif
                                                </div>
                                                @if ($item->status_ready == 3)
                                                    @if ($item->fileType->is_upload == 1 )
                                                          <span class="text-primary cursor-pointer"  wire:click="downloadFile('{{$item->file->path}}','{{$item->file->filename}}','{{$item->fileType->is_upload}}')"><u>{{$item->display_name}}</u></span>
                                                    @else
                                                         <span class="text-primary cursor-pointer"  wire:click="downloadFile('{{$item->file->path}}','{{$item->file->filename}}','{{$item->fileType->is_upload}}')"><u>{{$item->display_name}}</u> <span class="bg-pastel-primary rounded-circle">
                                                        <i class="fa-solid fa-signature"></i></span></span>
                                                    @endif
                                               
                                                @else
                                                <span> {{$item->display_name}}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
             <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="mb-2 d-flex justify-content-between">
                            <div>
                                <span class="fw-bold fs-5">Alur Pengajuan</span>
                            </div>
                        </div>
                        <hr>
                    </div>
                <div class="position-relative" style="margin-left: 10px;">
        <div class="timeline">
            @foreach ($user_approvers as $key => $user)
                @php
                    $user_text = explode('-', $user['user_text']);
                @endphp
                <div class="timeline-item d-flex mb-4">
                    <div class="timeline-sequence flex-shrink-0 d-flex flex-column align-items-center" style="width: 40px;">
                        <div class="rounded-circle bg-{{ViewHelper::generateColorSequence($app->current_seq_user_approval,$user->sequence,$app->current_approval_status)}} text-white d-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; font-size: 1rem;">
                            {{ $user['sequence'] }}
                        </div>
                        @if(!$loop->last)
                                <div class="flex-grow-1 border-start border-2 border-{{ViewHelper::generateColorSequence($app->current_seq_user_approval,$user->sequence,$app->current_approval_status)}}" style="min-height: 30px;"></div>
                        @endif
                    </div>
                    <div class="timeline-content ms-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-1">
                            {{ ucfirst($user['role_text']) }}
                        </span>
                        <div class="fw-semibold fs-6">{{ $user_text[0] ?? '-' }}</div>
                        <div class="small text-muted">
                            {{ $user_text[1] ?? '-' }}
                            @if(!empty($user_text[2]))
                                <span class="mx-1">|</span> {{ $user_text[2] }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
                </div>
                </div>
        </div>
    </div>
    <!--end row-->
              {{-- Modal Confirm Submit --}}
            <div class="modal fade" id="modalConfirmSubmit" tabindex="-1" aria-labelledby="modalConfirmSubmitLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-light border-0">
                            {{-- <h1 class="modal-title fs-5 fw-bold text-dark" id="modalConfirmSubmitLabel">
                                <i class="fa-solid fa-circle-question text-warning me-2"></i> Konfirmasi Perubahan
                            </h1> --}}
                            <button type="button" class="btn-close" wire:click="closeModalConfirm"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4">
                            <div class="d-flex flex-column align-items-center gap-3">
                                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center"
    style="width: 70px; height: 70px;">
    <i class="fa-solid fa-circle-question fs-1 text-info"></i>
</div>
                                <h4 class="text-center fw-semibold mb-2">Apakah Anda Yakin Ingin Mengubah Data?</h4>
                            </div>
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <button type="button" class="btn btn-outline-secondary px-4"
                                    wire:click="closeModalConfirm">
                                    <i class="fa-solid fa-times me-1"></i> Batal
                                </button>
      
                                    <button type="button" class="btn btn-success px-4" wire:click="submitEdit('1','true')"
                                        wire:loading.attr="disabled" wire:target="submitEdit">
                                        <span wire:loading.remove wire:target="submitEdit">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Ya
                                        </span>
                                        <span wire:loading wire:target="submitEdit">
                                            <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                        </span>
                                    </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Confirm Submit --}}
</div>


                   <script type="module">
                document.addEventListener('livewire:init', () => {
                    Livewire.on('open-modal-confirm-submit', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirmSubmit');
                        modal.show();
                    });
                    Livewire.on('close-modal-confirm-submit', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirmSubmit');
                        modal.hide();
                    });
                });
            </script>