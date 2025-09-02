{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\reports\report-create.blade.php --}}
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-12 mx-auto">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-sm-12">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon me-3">
                                    <i class="fa-solid fa-calendar-days fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-uppercase fw-bold text-dark text-truncate">{{ $this->application->activity_name }}</h5>
                                    <div class="d-flex align-items-center text-muted">
                                        {!! viewHelper::statusReportHTML($this->application->report?->approval_status) !!}
                                        <small class="ms-1"> Oleh : {!! viewHelper::getCurrentUserProcess($this->application,true) !!}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 text-end">
                            <div class="btn-group" role="group">
                                {{-- @if ($application->report->approval_status == 11)
                                    <button class="btn btn-outline-secondary btn-sm" wire:click="debug">
                                        <i class="fa-solid fa-bug me-1"></i>Debug
                                    </button>
                                @endif --}}
                                    <a href="{{route('applications.detail',['application_id'=>$this->application->id])}}" class="btn btn-outline-secondary btn-sm" >
                                        <i class='bx bx-info-circle'></i> Detail
                                    </a>
                         
                            </div>
                                   @if (viewHelper::actionPermissionButton('approval_process',$this->application,true))
                                   <div class="btn-group mt-2 w-100" role="group">
                                    <button class="btn btn-danger btn-sm" wire:click="openModalConfirm('reject-report')">
                                        <i class="fa-solid fa-times me-1"></i>Reject
                                    </button>
                                    <button class="btn btn-warning btn-sm" wire:click="openModalConfirm('revise-report')">
                                        <i class="fa-solid fa-edit me-1"></i>Revisi
                                    </button>
                                    <button class="btn btn-success btn-sm" wire:click="openModalConfirm('approve-report')">
                                        <i class="fa-solid fa-check me-1"></i>Approve
                                    </button>
                                   </div>
                                @endif
                        </div>
                    </div>
                    <hr />
                    <!-- Alert for status -->
                    <div class="row {{$application->report->approval_status == 2 || $application->report->approval_status > 20?'':'d-none'}}">
                        <div class="alert {{$application->report->approval_status == 2?'alert-warning':'alert-danger'}}" role="alert">
                            <div class="d-flex">
                                <div class="icon d-flex align-items-center" style="width: calc(100vw - (91rem))">
                                    <i class="fa-solid {{$application->report->approval_status == 2 ? 'fa-triangle-exclamation' : 'fa-circle-xmark'}} fw-3 ms-1 fs-2"></i>
                                </div>
                                <div class="description d-flex flex-column">
                                    <h6 class="title"> {{$application->report->approval_status == 2?'Formulir Butuh Untuk Direvisi !':'Formulir Ditolak !'}}</h6>
                                    <div class="d-flex flex-column">
                                        <div>
                                            <span class="fw-bold">{{$application->report->currentUserApproval->user->name}}</span>
                                            <small> <i>({!! viewHelper::formatDateToHumanReadable($application->report->currentUserApproval->updated_at,'d-m-Y H:i:s') !!})</i></small>
                                        </div>
                                        <div class="notes">
                                            "{{$application->report->note}}"
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Stepper -->
                    <div id="stepper2" class="bs-stepper">
                        <div class="card">
                            <div class="card-header overflow-auto">
                                <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between" role="tablist">
                                    <div class="step d-block" data-target="#test-l-1">
                                        <div class="step-trigger {{$this->step == 1? 'active':''}}" role="tab" wire:click="directStep('1')" id="stepper1trigger1" aria-controls="test-l-1">
                                            <div class="bs-stepper-circle">1</div>
                                            <div>
                                                <h5 class="mb-0 steper-title">Umum</h5>
                                                <p class="mb-0 steper-sub-title">Formulir umum</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-2">
                                        <div class="step-trigger {{$this->step == 2? 'active':''}}" role="tab" wire:click="directStep('2')" id="stepper1trigger2" aria-controls="test-l-2">
                                            <div class="bs-stepper-circle">2</div>
                                            <div>
                                                <h5 class="mb-0 steper-title">Informasi Narasumber</h5>
                                                <p class="mb-0 steper-sub-title">Form Narasumber</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bs-stepper-line"></div>
                                    <div class="step" data-target="#test-l-3">
                                        <div class="step-trigger {{$this->step == 3? 'active':''}}" role="tab" wire:click="directStep('3')" id="stepper1trigger3" aria-controls="test-l-3">
                                            <div class="bs-stepper-circle">3</div>
                                            <div>
                                                <h5 class="mb-0 steper-title">Realisasi</h5>
                                                <p class="mb-0 steper-sub-title">Form Realisasi</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Step 1 -->
                                                           <div id="test-l-1" role="tabpanel" class="{{$this->step == '1'? '':'bs-stepper-pane'}}"
                                    aria-labelledby="stepper1trigger1">
                                    <h5 class="mb-1">Formulir Umum</h5>
                                    <p class="mb-4">Formulir untuk Gambaran Umum Kesimpulan Acara</p>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="Outcome" class="form-label fw-bold">Kata Pengantar</label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="Outcome"
                                                wire:model="introduction"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="Outcome" class="form-label fw-bold">Latar Belakang</label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="Outcome"
                                                wire:model="background"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="Outcome" class="form-label fw-bold">Materi</label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="Outcome"
                                                wire:model="speaker_material"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="PurposeAndObjectives" class="form-label fw-bold">Uraian Kegiatan </label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="activity_description" wire:model="activity_description" ></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="UnitOfMeasurement" class="form-label fw-bold">
                                                Kendala</label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="obstacles" wire:model="obstacles"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="UnitOfMeasurement" class="form-label fw-bold">Simpulan</label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="conclusion" wire:model="conclusion"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label for="ActivityVolume" class="form-label fw-bold">Saran
                                                </label>
                                            <textarea {!! viewHelper::handleFieldDisabled($this->application,false,true) !!} class="form-control" id="recommendations" wire:model="recommendations"></textarea>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex">
                                            @if (viewHelper::actionPermissionButton('submit-report',$this->application))
                                                <button class="btn btn-primary px-4 border-none bg-warning me-2" wire:click="store"><i class="fa-solid fa-bookmark"></i>Save Draft</button>
                                            @endif

                                                <button class="btn btn-primary px-4"
                                                    wire:click="nextStep">Next<i
                                                        class='bx bx-right-arrow-alt ms-2'></i></button>
                                            </div>
                                        </div>
                                    </div><!---end row-->



                                </div>

                                <div id="test-l-2" role="tabpanel" class="{{$this->step == '2'? '':'bs-stepper-pane'}}"
                                    aria-labelledby="stepper1trigger2">

                                    <div class="d-flex justify-content-between">
                                        <div class="">
                                            <h5 class="mb-1">Informasi Narasumber</h5>
                                            <p class="mb-4">Isi Form Informasi Narasumber</p>
                                        </div>
                                    </div>


                                    <div class="row g-3">
                                            <div class="col-12">
                                                <label for="InputUsername" class="form-label fw-bold mx-auto">
                                                    <h6>Isi Informasi Narasumber</h6>
                                                </label>
                                                <div class="">
                                                    <livewire:forms.table-speaker-informations :application="$this->application" :participants="$this->application->participants"/>
                                                </div>
                                            </div>



                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center gap-3 ">
                        <button class="btn btn-outline-secondary px-4" wire:click="prevStep"><i
                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                        <div class="d-flex">
                            @if (viewHelper::actionPermissionButton('submit',$this->application))
                            <button class="btn btn-primary px-4 border-none bg-warning me-2" wire:click="saveDraft('3')"><i class="fa-solid fa-bookmark"></i>Save Draft</button>
                            @endif
                            <button class="btn btn-primary px-4" wire:click="nextStep">Next<i
                                    class='bx bx-right-arrow-alt ms-2'></i></button>
                        </div>
                    </div>
                </div>
            </div><!---end row-->

        </div>



        <div id="test-l-4" role="tabpanel" class="{{$this->step == '3'? '':'bs-stepper-pane'}}" aria-labelledby="stepper1trigger3">
            <h5 class="mb-1">Realisasi Anggaran</h5>
            <p class="mb-4">Tabel Realisasi Anggaran Biaya Kegiatan</p>

            <div class="row g-3">
                <div class="col-12">
                   <livewire:forms.table-realization :draftCost="$this->draft_costs" :application="$this->application"/>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center gap-3 ">
                        <button class="btn btn-primary px-4" wire:click="prevStep"><i
                                class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                        <div class="">
                            @if (viewHelper::actionPermissionButton('submit-report',$this->application))
                                <button class="btn btn-primary px-4 border-none bg-warning me-2" wire:click="store"><i class="fa-solid fa-bookmark"></i>Save Draft</button>
                                {{-- <button class="btn btn-primary px-4 border-none bg-success me-2" wire:click="store(true)">Submit LPJ</button> --}}

                             {{-- <button type="button"
                                    class="btn btn-primary px-4 border-none bg-warning me-2"
                                    wire:click="store"
                                    wire:loading.attr="disabled"
                                    wire:target="store">
                                    <span wire:loading wire:target="store(true)" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Submit LPJ
                            </button> --}}
                             <button type="button"
                                    class="btn btn-primary px-4 border-none bg-success me-2"
                                    wire:click="store(true)"
                                    wire:loading.attr="disabled"
                                    wire:target="store(true)">
                                    <span wire:loading wire:target="store(true)" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Submit LPJ
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div><!---end row-->

        </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal dan script tetap, tidak perlu diubah -->
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalConfirm" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalConfirmLabel"><i class="fa-solid {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['icon_class']}} ms-2 text-{{ viewHelper::handleConfirmModal($this->open_modal_confirm)['color']}}"></i> {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['title']}}</h1>
                <button type="button" class="btn-close" wire:click="closeModalConfirm" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($this->open_modal_confirm == 'approve-report')
                <div class="">
                    <div class="row">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fa-solid fa-circle-check fs-1 text-success"></i>
                            <h4 class="text-center"> Apakah Anda yakin ingin menyetujui ?</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-secondary mx-1" data-bs-dismiss="modal">Batal</button>
                            {{-- <button type="button" class="btn btn-success mx-1" wire:click="submitModalConfirm">Setujui</button> --}}

                            <button type="button"
                                class="btn btn-success mx-1"
                                wire:click="submitModalConfirm"
                                wire:loading.attr="disabled"
                                wire:target="submitModalConfirm">
                                <span wire:loading wire:target="submitModalConfirm" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Setujui
                            </button>
                        </div>
                    </div>
                </div>
                @else
                <form>
                    <div class="mb-3">
                        <label for="message-text" class="col-form-label fw-bold">Beri Alasan {{ viewHelper::handleConfirmModal($this->open_modal_confirm)['text_reaseon']}}</label>
                        <textarea class="form-control" wire:model="notes" id="message-text"></textarea>
                        {{-- <livewire:forms.tinymce-editor :editorId="'notes'"/> --}}
                    </div>
                </form>
                @endif

            </div>
            <div class="modal-footer">
                @if ($this->open_modal_confirm != 'approve-report')
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" wire:click="submitModalConfirm">Submit</button>
                @endif
            </div>
        </div>
    </div>
</div>
<livewire:utils.modal-preview />




<script type="module">
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-modal', (event) => {
            const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirm');
            modal.show();
        });
        Livewire.on('close-modal', (event) => {
            const modal = bootstrap.Modal.getOrCreateInstance('#modalConfirm');
            modal.hide();
        });

    });
</script>

</div>