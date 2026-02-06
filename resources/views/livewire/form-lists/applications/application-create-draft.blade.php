<div>
    <!-- Enhanced Loading States -->
    <div wire:loading.class="opacity-50" wire:target="nextStep,prevStep">
        <!-- Your form content -->
    </div>

    <!-- Loading Overlay -->
    {{-- <div wire:loading wire:target="saveDraft,submitModalConfirm"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
        style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="bg-white p-4 rounded shadow">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-3" role="status"></div>
                <span>Menyimpan data...</span>
            </div>
        </div>
    </div> --}}
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Form Pengajuan</div>
    </div>
    <!--end breadcrumb-->
    <div class="row">
        <div class="col-12 mx-auto">
            <div class="card shadow-sm mb-4=1">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-sm-12">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon me-3">
                                    <i class="fa-solid fa-calendar-days fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <div class="">
                                        <h5 class="mb-1 text-uppercase fw-bold text-dark text-wrap text-truncate">
                                            {{ $this->application->activity_name }}</h5>
                                    </div>
                                    <div class="d-flex align-items-center text-muted">
                                        {{-- <span
                                            class="badge bg-{{ $this->application->current_approval_status == 12 ? 'success' : 'warning' }} me-2">
                                            --}}
                                            {{-- {{
                                            viewHelper::getApprovalStatusText($this->application->current_approval_status) }}
                                            --}}
                                            {!! viewHelper::statusSubmissionHTML($application->current_approval_status) !!}
                                            {{-- </span> --}}
                                        <small class="ms-1"> Oleh :
                                            {!! viewHelper::getCurrentUserProcess($application)['name'] !!}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 text-end">
                            <div class="btn-group-vertical d-md-none d-block mb-2"></div>
                            <div class="btn-group" role="group">
                                {{-- @if ($application->current_approval_status == 12)
                                <button class="btn btn-outline-primary btn-sm" wire:click="downloadDocx">
                                    <i class="fa-solid fa-download me-1"></i>Download Document
                                </button>
                                @endif --}}
                                <a href="{{route('applications.detail', ['application_id' => $this->application->id])}}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class='bx bx-info-circle'></i> Detail
                                </a>

                                

                                
                                {{-- <button class="btn btn-outline-secondary btn-sm" wire:click="debug">
                                    <i class="fa-solid fa-bug me-1"></i>Debug
                                </button> --}}


                            </div>
                                  @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                            <div class="btn-group" role="group">
    <button class="btn btn-sm btn-outline-primary px-4 me-2"
        wire:click="regenerateDocument" wire:loading.attr="disabled"
        wire:target="regenerateDocument">
        <span wire:loading.remove wire:target="regenerateDocument">
            <i class="fa-solid fa-rotate-right me-1"></i> Regenerate Document
        </span>
        <span wire:loading wire:target="regenerateDocument">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Memproses...
        </span>
    </button>
</div>
@endif

                            @if (viewHelper::actionPermissionButton('approval_process', $this->application))
                                <div class="btn-group mt-2 w-100" role="group">
                                    <button class="btn btn-danger btn-sm" wire:click="openModalConfirm('reject')">
                                        <i class="fa-solid fa-times me-1"></i>Reject
                                    </button>
                                    <button class="btn btn-warning btn-sm" wire:click="openModalConfirm('revise')">
                                        <i class="fa-solid fa-edit me-1"></i>Revisi
                                    </button>
                                    <button class="btn btn-success btn-sm" wire:click="openModalConfirm('approve')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <hr />

                    <div id="stepper2" class="bs-stepper">
                        <div class="card">
                            <div class="card-header overflow-auto">
                                <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between"
                                    role="tablist">
                                    <div class="step d-block" data-target="#test-l-1">
                                        <div class="step-trigger {{$this->step == 1 ? 'active' : ''}}" role="tab"
                                            wire:click="directStep('1')" id="stepper1trigger1" aria-controls="test-l-1">
                                            <div class="bs-stepper-circle">1</div>
                                            <div class="">
                                                <h5 class="mb-0 steper-title">Umum</h5>
                                                <p class="mb-0 steper-sub-title">Formulir umum</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-2">
                                        <div class="step-trigger {{$this->step == 2 ? 'active' : ''}} " role="tab"
                                            wire:click="directStep('2')" id="stepper1trigger2" aria-controls="test-l-2">
                                            <div class="bs-stepper-circle">2</div>
                                            <div class="">
                                                <h5 class="mb-0 steper-title">
                                                    {{count($this->participants) > 0 ? 'Peran' : 'Peran dan RAB'}}</h5>
                                                <p class="mb-0 steper-sub-title">
                                                    {{count($this->participants) > 0 ? 'Peran dalam kegiatan' : 'Peran dalam kegiatan dan RAB'}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-3">
                                        <div class="step-trigger {{$this->step == 3 ? 'active' : ''}} {{count($this->participants) == 0 ? 'disabled' : ''}}"
                                            role="tab" @if(count($this->participants) > 0) wire:click="directStep('3')"
                                            @else style="cursor: not-allowed; opacity: 0.5;" data-bs-toggle="tooltip"
                                            title="Lengkapi peserta terlebih dahulu" @endif id="stepper1trigger3"
                                            aria-controls="test-l-3">
                                            <div class="bs-stepper-circle">3</div>
                                            <div class="">
                                                <h5 class="mb-0 steper-title">Rundown</h5>
                                                <p class="mb-0 steper-sub-title">Susunan Acara</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-4">
                                        <div class="step-trigger {{$this->step == 4 ? 'active' : ''}} {{count($this->participants) == 0 ? 'disabled' : ''}}"
                                            role="tab" @if(count($this->participants) > 0) wire:click="directStep('4')"
                                            @else style="cursor: not-allowed; opacity: 0.5;" data-bs-toggle="tooltip"
                                            title="Lengkapi rundown acara terlebih dahulu" @endif id="stepper1trigger4"
                                            aria-controls="test-l-4">
                                            <div class="bs-stepper-circle">4</div>
                                            <div class="">
                                                <h5 class="mb-0 steper-title">RAB</h5>
                                                <p class="mb-0 steper-sub-title">Rancangan Anggaran</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-5">
                                        <div class="step-trigger {{$this->step == 5 ? 'active' : ''}} {{count($this->draft_costs) == 0 ? 'disabled' : ''}}"
                                            role="tab" @if(count($this->draft_costs) > 0 && $this->application->current_seq_user_approval > 3) wire:click="directStep('5')" @else
                                                style="cursor: not-allowed; opacity: 0.5;" data-bs-toggle="tooltip"
                                            title="Lengkapi rancangan anggaran biaya terlebih dahulu" @endif
                                            id="stepper1trigger5" aria-controls="test-l-5">
                                            <div class="bs-stepper-circle">5</div>
                                            <div class="">
                                                <h5 class="mb-0 steper-title">Nomor Surat</h5>
                                                <p class="mb-0 steper-sub-title">Isian Nomor Surat</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div
                                    class="row {{$application->current_approval_status == 2 || $application->current_approval_status > 20 ? '' : 'd-none'}}">
                                    <div class="alert {{$application->current_approval_status == 2 ? 'alert-warning' : 'alert-danger'}}"
                                        role="alert">
                                        <div class="d-flex">
                                            <div class="icon d-flex align-items-center"
                                                style="width: calc(100vw - (91rem))">
                                                <i
                                                    class="fa-solid {{$application->current_approval_status == 2 ? 'fa-triangle-exclamation' : 'fa-circle-xmark'}} fw-3 ms-1 fs-2"></i>
                                            </div>
                                            <div class="description d-flex flex-column w-100">
                                                <div class="d-flex justify-content-between">
                                                       <h6 class="title">
                                                    {{$application->current_approval_status == 2 ? $this->alert_title.' Butuh Untuk Direvisi !' : $this->alert_title.' Formulir Ditolak !'}}
                                                </h6>
                                                    <small>
                                                            <i>{!! viewHelper::formatDateToHumanReadable($application->currentUserApproval->updated_at, 'd-m-Y H:i:s') !!}</i></small>
                                                </div>
                                             
                                                @if (!empty($application->note))
                                                <div class="d-flex flex-column">
                                                        
                                                    <div>
                                                        <span
                                                            class="fw-bold">{{viewHelper::explodeName(explode('###',$application->note)[0])['name']}}</span>

                                                            (<span>{{viewHelper::explodeName(explode('###',$application->note)[0])['position']}}</span>
                                                        -<span
                                                            >{{viewHelper::explodeName(explode('###',$application->note)[0])['department']}}</span>)
                                                            
                                                    </div>

                                                    <div class="" style="font-style: italic">
                                                        
                                                    </div>
                                                    <div class="notes">
                                                        "{{explode('###',$application->note)[1]}}"
                                                    </div>
                                                    
                                                </div>
                                                @endif
                                                <div class="d-flex w-100 justify-content-end">
                                                                             
                                                    </div>

                                            </div>
                                            

                                        </div>
                                    </div>
                                </div>
                                <div class="">
                                    {{-- <form onSubmit="return false"> --}}
                                        <div id="test-l-1" role="tabpanel"
                                            class="{{$this->step == '1' ? '' : 'bs-stepper-pane'}}"
                                            aria-labelledby="stepper1trigger1">


                                            {{-- Session Error Messages --}}
                                            @if (session('error'))
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <strong>Error!</strong> {{ session('error') }}
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                            @endif

                                            @if ($errors->any())
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <strong>Terdapat kesalahan:</strong>
                                                    <ul class="mb-0">
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                            @endif

                                            <h5 class="mb-1">Formulir Umum</h5>
                                            <p class="mb-4">Formulir untuk Gambaran Umum Maksud dan Tujuan Acara</p>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="Outcome" class="form-label fw-bold">Hasil
                                                        (Outcome)</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="Outcome"
                                                        wire:model="activity_outcome"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="UnitOfMeasurement" class="form-label fw-bold">Indikator
                                                        kinerja kegiatan</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="UnitOfMeasurement"
                                                        wire:model="performance_indicator"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="Outcome" class="form-label fw-bold">Keluaran
                                                        (Output)</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="Outcome"
                                                        wire:model="activity_output"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="UnitOfMeasurement" class="form-label fw-bold">Satuan
                                                        Ukur</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="UnitOfMeasurement"
                                                        wire:model="unit_of_measurment"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="ActivityVolume" class="form-label fw-bold">Volume
                                                        Kegiatan
                                                    </label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="ActivityVolume"
                                                        wire:model="activity_volume"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="GeneralDescription" class="form-label fw-bold">Gambaran
                                                        Umum</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="GeneralDescription"
                                                        wire:model="general_description"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="PurposeAndObjectives" class="form-label fw-bold">Maksud
                                                        dan
                                                        Tujuan</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="PurposeAndObjectives"
                                                        wire:model="objectives"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="Beneficiary" class="form-label fw-bold">Penerima
                                                        Manfaat</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="Beneficiary"
                                                        wire:model="beneficiaries"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="activity_scope" class="form-label fw-bold">Lingkup
                                                        Aktifitas</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="activity_scope"
                                                        wire:model="activity_scope"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="implementation_method" class="form-label fw-bold">
                                                        Metode pelaksanaan</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="implementation_method"
                                                        wire:model="implementation_method"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="implementation_stages" class="form-label fw-bold">
                                                        Tahapan pelaksanaan</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="implementation_stages"
                                                        wire:model="implementation_stages"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="activity_location" class="form-label fw-bold"> Lokasi
                                                        Kegiatan</label>
                                                    <textarea {!! viewHelper::handleFieldDisabled($this->application) !!} class="form-control" id="activity_location"
                                                        wire:model="activity_location"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label for="InputDate" class="form-label fw-bold">Tanggal
                                                        Pelaksanaan</label>
                                                    <div class="d-flex align-items-center" id="date-range">
                                                        <!-- Input Tanggal Mulai -->
                                                        <input type="text" class="form-control w-100" id="InputDate"
                                                            aria-label="Tanggal Pelaksanaan" wire:model="activity_dates"
                                                            {!! viewHelper::handleFieldDisabled($this->application) !!}
                                                            >
                                                        <!-- Label Sampai -->
                                                    </div>
                                                    <small>*isi dengan format dd-mm-yyyy (contoh: 20-05-2025), jika
                                                        acara lebih dari 1 hari, tambah tanggal kedua dst dengan pemisah
                                                        tanda koma (,) </small>

                                                </div>

                                                <div class="col-12 d-flex justify-content-end">
                                                    <div class="d-flex">
                                                        @if (viewHelper::actionPermissionButton('submit', $this->application))
                                                            <button class="btn btn-warning text-white px-4 me-2"
                                                                wire:click="saveDraft('1')" wire:loading.attr="disabled"
                                                                wire:target="saveDraft">
                                                                <span wire:loading.remove wire:target="saveDraft">
                                                                    <i class="fa-solid fa-bookmark me-1"></i> Save Draft
                                                                </span>
                                                                <span wire:loading wire:target="saveDraft">
                                                                    <span
                                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                                    Menyimpan...
                                                                </span>
                                                            </button>
                                                        @endif
                                                            @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                                                                <button class="btn btn-success text-white px-4 me-2"
                                                                    wire:click="saveDraft('1')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Data
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>
                                                            @endif
                                                        <!-- Step 1 - Tombol Save Draft sudah benar -->
                                                        {{-- @if
                                                        (viewHelper::actionPermissionButton('submit',$this->application))
                                                        <button class="btn btn-primary px-4 border-none bg-warning me-2"
                                                            wire:click="saveDraft('1')" wire:loading.attr="disabled"
                                                            wire:target="saveDraft">
                                                            <span wire:loading.remove wire:target="saveDraft">
                                                                <i class="fa-solid fa-bookmark"></i> Save Draft
                                                            </span>
                                                            <span wire:loading wire:target="saveDraft">
                                                                <i class="spinner-border spinner-border-sm me-2"></i>
                                                                Saving...
                                                            </span>
                                                        </button>
                                                        @endif --}}
                                                        <button class="btn btn-primary px-4"
                                                            wire:click="nextStep">Next<i
                                                                class='bx bx-right-arrow-alt ms-2'></i></button>
                                                    </div>
                                                </div>
                                            </div><!---end row-->



                                        </div>

                                        <div id="test-l-2" role="tabpanel"
                                            class="{{$this->step == '2' ? '' : 'bs-stepper-pane'}}"
                                            aria-labelledby="stepper1trigger2">

                                            <div class="d-flex justify-content-between">
                                                <div class="">
                                                    <h5 class="mb-1">Peran dalam Kegiatan</h5>
                                                    <p class="mb-4">Untuk memilih Narasumber, Moderator, Panitia maupun
                                                        Peserta Acara .</p>
                                                </div>
                                                <div class="action-button">
                                                    @if (count($this->participants) > 0)
                                                        <button
                                                            class="btn btn-outline-success border border-1 btn-sm border-success"
                                                            wire:click='exportPreviousData'><i
                                                                class="fa-solid fa-file-excel me-2"></i> Download Data Saat
                                                            Ini</button>
                                                        <button
                                                            class="btn btn-outline-secondary border border-1 btn-sm border-secondary"
                                                            wire:click='clearAllParticipant' {!! viewHelper::handleFieldDisabled($this->application) !!}><i
                                                                class="fa-solid fa-repeat me-2"></i> Reset Data
                                                            Template</button>

                                                    @endif
                                                </div>
                                            </div>


                                            <div class="row g-3">
                                                @if (count($this->participants) > 0)
                                                    <div class="col-12">
                                                        <label for="InputUsername" class="form-label fw-bold mx-auto">
                                                            <h6>Daftar Narasumber dan Moderator</h6>
                                                        </label>
                                                        <div class="">
                                                            <livewire:forms.table-participants
                                                                :participants="$this->participants"
                                                                :participantType="'speaker'" />
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="InputUsername" class="form-label fw-bold mx-auto">
                                                            <h6>Daftar Panitia</h6>
                                                        </label>
                                                        <livewire:forms.table-participants
                                                            :participants="$this->participants"
                                                            :participantType="'commitee'" />
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="InputUsername" class="form-label fw-bold mx-auto">
                                                            <h6>Daftar Peserta</h6>
                                                        </label>
                                                        <livewire:forms.table-participants
                                                            :participants="$this->participants"
                                                            :participantType="'participant'" />
                                                    </div>
                                                @else
                                                    <!-- Button trigger modal -->
                                                    <div class="d-flex w-100 flex-column justify-content-center">
                                                        <div class="mb-3 d-flex flex-column justify-content-center">
                                                            <label for="file" class="form-label mx-auto">Download Template
                                                                Excell</label>
                                                            <div class="d-flex justify-content-center">
                                                                <button class="btn btn-success btn-md mx-auto btn-hover"
                                                                    wire:loading.class="opacity-50"
                                                                    wire:click="downloadTemplateExcel">
                                                                    <i class="fa-solid fa-file-excel me-2"></i> Download
                                                                    Template Peserta
                                                                </button>
                                                            </div>

                                                        </div>
                                                        <div class="mb-3 d-flex flex-column justify-content-center">
                                                            <form wire:submit.prevent="importParticipant">
                                                                <label for="file" class="form-label mx-auto">Pilih File
                                                                    Excel
                                                                    (.xlsx)</label>
                                                                <div class="d-flex justify-content-center">
                                                                    <input type="file" wire:model="excel_participant"
                                                                        id="file" class="form-control" accept=".xlsx,.xls">

                                                                    <button type="submit"
    class="btn btn-sm btn-info text-white w-25 btn-hover fw-bold"
    wire:loading.class="opacity-50"
    @if(empty($this->excel_participant)) disabled @endif>
    <i class="fa-solid fa-gears me-2"></i> Generate File
</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                @endif

                                                <div class="col-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-3 ">
                                                        <button class="btn btn-outline-secondary px-4"
                                                            wire:click="prevStep"><i
                                                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                                        <div class="d-flex">
                                                            @if (viewHelper::actionPermissionButton('submit', $this->application))
                                                                <button class="btn btn-warning text-white px-4 me-2"
                                                                    wire:click="saveDraft('3')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-bookmark me-1"></i> Save Draft
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>

                                                            @endif
                                                            @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                                                                <button class="btn btn-success text-white px-4 me-2"
                                                                    wire:click="saveDraft('3')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Data
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>

                                                            @endif

                                                            <button class="btn btn-primary px-4"
                                                                wire:click="nextStep">Next<i
                                                                    class='bx bx-right-arrow-alt ms-2'></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!---end row-->

                                        </div>

                                        <div id="test-l-3" role="tabpanel"
                                            class="{{$this->step == '3' ? '' : 'bs-stepper-pane'}}"
                                            aria-labelledby="stepper1trigger3">
                                            <h5 class="mb-1">Susunan Acara</h5>
                                            <p class="mb-4">Berisi Jadwal Susunan Acara</p>


                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <livewire:forms.table-rundown :rundowns="$this->rundowns"
                                                        :participants="$this->participants"
                                                        :handleDisable="viewHelper::handleFieldDisabled($this->application)" />
                                                    {{-- <livewire:forms.table-participants
                                                        :participants="$this->participants"
                                                        :participantType="'participant'" /> --}}
                                                        @if (
                                                            viewHelper::handleFieldDisabled($this->application)
                                                            !== 'disabled'
                                                        )
                                                               <div class="alert alert-info d-flex align-items-center my-2"
                                                        role="alert" style="font-size: 0.97rem;">
                                                        <i class="fa-solid fa-circle-info me-2"></i>
                                                        <div>
                                                            <strong>Catatan:</strong> Sebaiknya klik <span
                                                                class="fw-bold">Sync Data</span> terlebih dahulu sebelum
                                                            menekan <span class="fw-bold">Save Draft</span>. Hal ini
                                                            untuk memastikan data terakhir yang Anda input pada tabel
                                                            rundown benar-benar tersimpan, terutama jika Anda baru saja
                                                            selesai mengedit di kolom atau textarea.
                                                        </div>
                                                    </div>
                                                        @endif    

                                                 
                                                </div>
                                                <div class="col-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-3 ">
                                                        <button class="btn btn-outline-secondary px-4"
                                                            wire:click="prevStep"><i
                                                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                                        <div class="d-flex">
                                                            @if (viewHelper::actionPermissionButton('submit', $this->application))
                                                                <button class="btn btn-info px-4 me-2" wire:click="syncData"
                                                                    wire:loading.attr="disabled" wire:target="syncData">
                                                                    <span wire:loading.remove wire:target="syncData">
                                                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Sync
                                                                        Data
                                                                    </span>
                                                                    <span wire:loading wire:target="syncData">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Memproses...
                                                                    </span>
                                                                </button>
                                                                <button class="btn btn-warning text-white px-4 me-2"
                                                                    wire:click="saveDraft('3')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-bookmark me-1"></i> Save Draft
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>
                                                            @endif
                                                                     @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                                                                 <button class="btn btn-info px-4 me-2" wire:click="syncData"
                                                                    wire:loading.attr="disabled" wire:target="syncData">
                                                                    <span wire:loading.remove wire:target="syncData">
                                                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Sync
                                                                        Data
                                                                    </span>
                                                                    <span wire:loading wire:target="syncData">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Memproses...
                                                                    </span>
                                                                </button>
                                                                     <button class="btn btn-success text-white px-4 me-2"
                                                                    wire:click="saveDraft('3')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Data
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>

                                                            @endif
                                                            <button class="btn btn-primary px-4"
                                                                wire:click="nextStep">Next<i
                                                                    class='bx bx-right-arrow-alt ms-2'></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!---end row-->

                                        </div>

                                        <div id="test-l-4" role="tabpanel"
                                            class="{{$this->step == '4' ? '' : 'bs-stepper-pane'}}"
                                            aria-labelledby="stepper1trigger4">
                                            <h5 class="mb-1">Rancangan Anggaran Biaya</h5>
                                            <p class="mb-4">Tabel Rancangan Anggaran Biaya Kegiatan</p>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <livewire:forms.table-draft-cost :draftCosts="$this->draft_costs" />
                                                </div>

                                                <div class="col-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-3 ">
                                                        <button class="btn btn-outline-secondary px-4"
                                                            wire:click="prevStep"><i
                                                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                                        <div class="">
                                                            @if (viewHelper::actionPermissionButton('submit', $this->application))
                                                                <button class="btn btn-warning text-white px-4 me-2"
                                                                    wire:click="saveDraft('4')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-bookmark me-1"></i> Save Draft
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>
                                                                <button class="btn btn-success px-4"
                                                                    wire:click="openModalConfirmSubmit">Submit</button>
                                                            @endif
                                                                     @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                                                                <button class="btn btn-success text-white px-4 me-2"
                                                                    wire:click="saveDraft('3')" wire:loading.attr="disabled"
                                                                    wire:target="saveDraft">
                                                                    <span wire:loading.remove wire:target="saveDraft">
                                                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Data
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraft">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>

                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!---end row-->

                                        </div>
                                        <div id="test-l-5" role="tabpanel"
                                            class="{{$this->step == '5' ? '' : 'bs-stepper-pane'}}"
                                            aria-labelledby="stepper1trigger5">
                                            <h5 class="mb-1">Nomor Surat</h5>
                                            <p class="mb-4">Form isian nomor surat</p>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    {{-- filepath:
                                                    c:\laragon\www\application_letters\resources\views\livewire\form-lists\applications\application-create-draft.blade.php
                                                    --}}
                                                    @forelse ($this->letter_numbers as $key => $item)
                                                        <div class="mb-3">

                                                            <div class="row g-2 align-items-center">
                                                                <div class="col-md-6 col-12">
                                                                    <label for="{{$item['letter_name']}}"
                                                                        class="form-label fw-bold">
                                                                        {{ $item['letter_label'] }}
                                                                    </label>

                                                                     @if($item['type_field'] === 'textarea')
                    <textarea
                        wire:model="letter_numbers.{{$key}}.letter_number"
                        class="form-control"
                        name="{{$item['letter_name']}}"
                        id="{{$item['letter_name']}}"
                        {!! viewHelper::handleFieldDisabled($this->application, true) !!}></textarea>
                @else
                    <input type="{{$item['type_field']}}"
                        wire:model="letter_numbers.{{$key}}.letter_number"
                        class="form-control"
                        name="{{$item['letter_name']}}"
                        id="{{$item['letter_name']}}"
                        {!! viewHelper::handleFieldDisabled($this->application, true) !!}>
                @endif
                                                                </div>
                                                                @if ($item['is_with_date'])
                                                                    <div class="col-md-6 col-12">
                                                                        <label for="{{$item['letter_name']}}_date"
                                                                            class="form-label fw-bold mb-1">
                                                                            Tanggal {{ $item['letter_label'] }}
                                                                        </label>
                                                                        <input type="date"
                                                                            wire:model="letter_numbers.{{$key}}.letter_date"
                                                                            class="form-control"
                                                                            name="{{$item['letter_name']}}_date"
                                                                            id="{{$item['letter_name']}}_date" {!! viewHelper::handleFieldDisabled($this->application, true) !!}>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @empty
                                                    @endforelse
                                                </div>

                                                <div class="col-12">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center gap-3 ">
                                                        <button class="btn btn-primary px-4" wire:click="prevStep"><i
                                                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                                        <div class="">
                                                            @if (viewHelper::actionPermissionButton('submit-letter-number', $this->application))
                                                                {{-- <button
                                                                    class="btn btn-primary px-4 border-none bg-warning me-2"
                                                                    wire:click="saveDraft('5')"><i
                                                                        class="fa-solid fa-bookmark"></i>Save Draft</button>
                                                                --}}
                                                                <button class="btn btn-warning text-white px-4 me-2"
                                                                    wire:click="saveDraftLetterNumber" wire:loading.attr="disabled"
                                                                    wire:target="saveDraftLetterNumber">
                                                                    <span wire:loading.remove wire:target="saveDraftLetterNumber">
                                                                        <i class="fa-solid fa-bookmark me-1"></i> Save Draft
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraftLetterNumber">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>
                                                                <button class="btn btn-success px-4"
                                                                    wire:click="openModalConfirmSubmit('true')">Submit</button>
                                                                {{-- <button class="btn btn-success px-4"
                                                                    wire:click="openModalLoadingGenerateDoc">Submit</button>
                                                                --}}
                                                            @endif
                                                                     @if (viewHelper::actionPermissionButton('admin-submit', $this->application))
                                                                <button class="btn btn-success text-white px-4 me-2"
                                                                    wire:click="saveDraftLetterNumber" wire:loading.attr="disabled"
                                                                    wire:target="saveDraftLetterNumber">
                                                                    <span wire:loading.remove wire:target="saveDraftLetterNumber">
                                                                        <i class="fa-solid fa-floppy-disk me-1"></i> Update Data
                                                                    </span>
                                                                    <span wire:loading wire:target="saveDraftLetterNumber">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Menyimpan...
                                                                    </span>
                                                                </button>

                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!---end row-->

                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal generate document loading --}}
            <div class="modal fade" id="modalLoadingGenerateDocument" tabindex="-1"
                aria-labelledby="modalLoadingGenerateDocumentLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered" style="min-width: 25rem;">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-body py-5 px-4">
                            <div class="d-flex flex-column align-items-center gap-4">
                                    <img src="{{ asset('assets/images/rubic-cube-loading.gif') }}" alt="Loading..."
    style="width: 6rem; height: 6rem;">
<div class="fw-bold fs-5 text-center text-primary">
    Dokumen Anda sedang diproses <span class="dot-animated"></span>
</div>
<p class="text-center text-muted mb-0" style="max-width: 350px;">
    Silakan cek status dokumen anda secara berkala pada halaman <span class="fw-bold text-primary">Lihat Detail</span>
                                

                                    <div class="d-flex justify-content-center gap-2 w-100 mt-2">
                                        <button class="btn btn-hover btn-outline-secondary flex-fill border-2"
                                            wire:click="closeModalLoadingGenerateDoc">Tutup</button>
                                        <a href="{{ route('applications.detail', ['application_id' => $this->application_id]) }}"
                                            class="btn btn-hover btn-primary flex-fill">Lihat Detail</a>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal generate document loading --}}


            {{-- Modal Confirm Approval --}}
            <div class="modal fade" id="modalConfirm" tabindex="-1" aria-labelledby="modalConfirmLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-light border-0">
                            <h1 class="modal-title fs-5 fw-bold text-dark d-flex align-items-center gap-2"
                                id="modalConfirmLabel">
                                <i
                                    class="fa-solid {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['icon_class'] }} text-{{ viewHelper::handleConfirmModal($this->open_modal_confirm)['color'] }}"></i>
                                {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['title'] }}
                            </h1>
                            <button type="button" class="btn-close" wire:click="closeModalConfirm"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4">
                            @if ($this->open_modal_confirm == 'approve')
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                        style="width: 70px; height: 70px;">
                                        <i class="fa-solid fa-circle-check fs-1 text-success"></i>
                                    </div>
                                    <h4 class="text-center fw-semibold mb-2">Apakah Anda yakin ingin menyetujui?</h4>
                                </div>
                                <div class="d-flex justify-content-center gap-3 mt-4">
                                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                                        <i class="fa-solid fa-times me-1"></i> Batal
                                    </button>
                                    <button type="button" class="btn btn-success px-4" wire:click="submitModalConfirm"
                                        wire:loading.attr="disabled" wire:target="submitModalConfirm">
                                        <span wire:loading.remove wire:target="submitModalConfirm">
                                            <i class="fa-solid fa-check me-1"></i> Setujui
                                        </span>
                                        <span wire:loading wire:target="submitModalConfirm">
                                            <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                        </span>
                                    </button>
                                </div>
                            @else
                                <form>
                                    <div class="mb-3">
                                        <label for="message-text" class="col-form-label fw-bold">
                                            Beri Alasan
                                            {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['text_reaseon'] }}
                                        </label>
                                        <textarea class="form-control" wire:model="notes" id="message-text" rows="3"
                                            placeholder="Tulis alasan Anda di sini..."></textarea>
                                    </div>
                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <button type="button" class="btn btn-outline-secondary px-4"
                                            data-bs-dismiss="modal">
                                            <i class="fa-solid fa-times me-1"></i> Batal
                                        </button>
                                        <button type="button" class="btn btn-warning px-4" wire:click="submitModalConfirm"
                                            wire:loading.attr="disabled" wire:target="submitModalConfirm">
                                            <span wire:loading.remove wire:target="submitModalConfirm">
                                                <i class="fa-solid fa-paper-plane me-1"></i> Submit
                                            </span>
                                            <span wire:loading wire:target="submitModalConfirm">
                                                <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Confirm Approval --}}

            {{-- Modal Confirm Submit --}}
            <div class="modal fade" id="modalConfirmSubmit" tabindex="-1" aria-labelledby="modalConfirmSubmitLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-md">
                    <div class="modal-content shadow-lg border-0">
                        <div class="modal-header bg-light border-0">
                            <h1 class="modal-title fs-5 fw-bold text-dark" id="modalConfirmSubmitLabel">
                                <i class="fa-solid fa-circle-question text-warning me-2"></i> Konfirmasi Pengajuan
                            </h1>
                            <button type="button" class="btn-close" wire:click="closeModalConfirm"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body pb-4">
                            <div class="d-flex flex-column align-items-center gap-3">
                                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                    style="width: 70px; height: 70px;">
                                    <i class="fa-solid fa-circle-check fs-1 text-success"></i>
                                </div>
                                <h4 class="text-center fw-semibold mb-2">Apakah Data Pengajuan Sudah Sesuai?</h4>
                                <p class="text-center text-muted mb-0" style="max-width: 350px;">
                                    Pastikan seluruh data yang Anda input sudah benar sebelum melakukan submit. Setelah
                                    submit, data tidak dapat diubah kembali.
                                </p>
                            </div>
                            <div class="d-flex justify-content-center gap-3 mt-4">
                                <button type="button" class="btn btn-outline-secondary px-4"
                                    wire:click="closeModalConfirmSubmit">
                                    <i class="fa-solid fa-times me-1"></i> Batal
                                </button>
                                @if ($this->is_submit_letter_number)
                                    <button type="button" class="btn btn-success px-4"
                                        wire:click="openModalLoadingGenerateDoc" wire:loading.attr="disabled"
                                        wire:target="openModalLoadingGenerateDoc">
                                        <span wire:loading.remove wire:target="openModalLoadingGenerateDoc">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Ya, Submit
                                        </span>
                                        <span wire:loading wire:target="openModalLoadingGenerateDoc">
                                            <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                        </span>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-success px-4" wire:click="saveDraft('1','true')"
                                        wire:loading.attr="disabled" wire:target="saveDraft">
                                        <span wire:loading.remove wire:target="saveDraft">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Ya, Submit
                                        </span>
                                        <span wire:loading wire:target="saveDraft">
                                            <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Modal Confirm Submit --}}



            <script type="module">
                document.addEventListener('livewire:init', () => {
                    Livewire.on('open-modal', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirm');
                        modal.show();
                    });
                    Livewire.on('open-modal-confirm-submit', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirmSubmit');
                        modal.show();
                    });
                    Livewire.on('close-modal-confirm-submit', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirmSubmit');
                        modal.hide();
                    });
                    Livewire.on('close-modal', (event) => {
                        const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirm');
                        modal.hide();
                    });
                    Livewire.on('open-modal-loading-generate-doc', (event) => {
                        const modals = bootstrap.Modal.getOrCreateInstance('#modalLoadingGenerateDocument');
                        modals.show();
                    });
                    Livewire.on('close-modal-loading-generate-doc', (event) => {
                        const modals = bootstrap.Modal.getOrCreateInstance('#modalLoadingGenerateDocument');
                        modals.hide();
                    });
                    Livewire.on('rundownUpdated', () => {
                        console.log('Rundown data has been updated');
                    });
                });
            </script>
        </div>
        <!--end row-->

    </div>