<div>
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Form Pengajuan</div>
    </div>
    <!--end breadcrumb-->
    <div class="row">
        <div class="col-8 mx-auto">
            <div class="text-center">
                <h5 class="mb-0 text-uppercase">{{$this->application->activity_name}}</h5>
                <hr/>
            </div>
            <div id="stepper1" class="bs-stepper">
                <div class="card">
                  <div class="card-header overflow-auto">
                      <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between" role="tablist">
                          <div class="step" data-target="#test-l-1">
                            <div class="step-trigger" role="tab" id="stepper1trigger1" aria-controls="test-l-1">
                              <div class="bs-stepper-circle">1</div>
                              <div class="">
                                  <h5 class="mb-0 steper-title">Umum</h5>
                                  <p class="mb-0 steper-sub-title">Formulir umum</p>
                              </div>
                            </div>
                          </div>
                          <div class="bs-stepper-line"></div>
                          <div class="step" data-target="#test-l-2">
                              <div class="step-trigger" role="tab" id="stepper1trigger2" aria-controls="test-l-2">
                                <div class="bs-stepper-circle">2</div>
                                <div class="">
                                    <h5 class="mb-0 steper-title">Peran</h5>
                                    <p class="mb-0 steper-sub-title">Peran dalam kegiatan</p>
                                </div>
                              </div>
                            </div>
                          <div class="bs-stepper-line"></div>
                          <div class="step" data-target="#test-l-3">
                              <div class="step-trigger" role="tab" id="stepper1trigger3" aria-controls="test-l-3">
                                <div class="bs-stepper-circle">3</div>
                                <div class="">
                                    <h5 class="mb-0 steper-title">Rundown</h5>
                                    <p class="mb-0 steper-sub-title">Education Details</p>
                                </div>
                              </div>
                            </div>
                            <div class="bs-stepper-line"></div>
                              <div class="step" data-target="#test-l-4">
                                  <div class="step-trigger" role="tab" id="stepper1trigger4" aria-controls="test-l-4">
                                  <div class="bs-stepper-circle">4</div>
                                  <div class="">
                                      <h5 class="mb-0 steper-title">RAB</h5>
                                      <p class="mb-0 steper-sub-title">Rancangan Anggaran</p>
                                  </div>
                                  </div>
                              </div>
                            <div class="bs-stepper-line"></div>
                              <div class="step" data-target="#test-l-4">
                                  <div class="step-trigger" role="tab" id="stepper1trigger4" aria-controls="test-l-4">
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

                    <div class="bs-stepper-content">
                      <form onSubmit="return false">
                        <div id="test-l-1" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger1">
                          <h5 class="mb-1">Formulir Umum</h5>
                          <p class="mb-4">Formulir untuk Gambaran Umum Maksud dan Tujuan Acara</p>

                          <div class="row g-3">
                            <div class="col-12">
                                <label for="Outcome" class="form-label fw-bold">Hasil (Outcome)</label>
                                <textarea class="form-control" id="Outcome" wire:model="activity_output"  placeholder="Hasil (Outcome)" wire:model="activity_outcome"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="UnitOfMeasurement" class="form-label fw-bold">Satuan Ukur</label>
                                <textarea class="form-control" id="UnitOfMeasurement" wire:model="performance_indicator"  placeholder="Satuan Ukur"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="GeneralDescription" class="form-label fw-bold">Gambaran Umum</label>
                                <textarea class="form-control" id="GeneralDescription" wire:model="general_description"  placeholder="Gambaran Umum"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="PurposeAndObjectives" class="form-label fw-bold">Maksud dan Tujuan</label>
                                <textarea class="form-control" id="PurposeAndObjectives" wire:model="objectives"  placeholder="Maksud dan Tujuan"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="Beneficiary" class="form-label fw-bold">Penerima Manfaat</label>
                                <textarea class="form-control" id="Beneficiary" wire:model="beneficiaries"  placeholder="Penerima Manfaat"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="InputDate" class="form-label fw-bold">Tanggal Pelaksanaan</label>
                                <div class="d-flex align-items-center" id="date-range">
                                    <!-- Input Tanggal Mulai -->
                                    <input type="date" class="form-control w-50" id="InputDate" aria-label="Tanggal Pelaksanaan">
                                    <!-- Label Sampai -->
                                    <span class="d-flex w-50 align-items-baseline">


                                    <span class="ms-2 me-2 hide-is-sameday w-15">Sampai</span>
                                    <!-- Input Tanggal Selesai -->
                                    <input type="date" class="form-control hide-is-sameday w-35" id="InputEndDate" aria-label="Tanggal Selesai">
                                </span>
                                </div>
                                <!-- Checkbox -->
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="SameDayEvent" checked>
                                    <label class="form-check-label" for="SameDayEvent">
                                        Acara selesai di hari yang sama
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <div class="d-flex">
                                    <button class="btn btn-primary px-4 border-none bg-warning me-2"><i class='bx bx-bookmark'></i>Save Draft</button>
                                    <button class="btn btn-primary px-4" wire:click="nextStep">Next<i class='bx bx-right-arrow-alt ms-2'></i></button>
                                </div>
                            </div>
                        </div><!---end row-->



                        </div>

                        <div id="test-l-2" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger2">

                          <h5 class="mb-1">Peran dalam Kegiatan</h5>
                          <p class="mb-4">Untuk memilih Narasumber, Moderator, Panitia maupun Peserta Acara .</p>

                          <div class="row g-3">
                              <div class="col-12">
                                  <label for="InputUsername" class="form-label fw-bold mx-auto"><h6>Pilih Narasumber dan Moderator</h6></label>
                                  <div class="">
                                    <livewire:forms.table-participants :participantType="'speaker'"/>
                                  </div>
                              </div>
                              <div class="col-12">
                                  <label for="InputUsername" class="form-label fw-bold mx-auto"><h6>Pilih Panitia</h6></label>
                                  <livewire:forms.table-participants :participantType="'commitee'"/>
                              </div>
                              <div class="col-12">
                                  <label for="InputUsername" class="form-label fw-bold mx-auto"><h6>Pilih Peserta</h6></label>
                                  <livewire:forms.table-participants :participantType="'participant'"/>
                              </div>
                              <div class="col-12">
                                  <div class="d-flex justify-content-between align-items-center gap-3 ">
                                      <button class="btn btn-outline-secondary px-4" wire:click="prevStep"><i class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                      <div class="d-flex">
                                          <button class="btn btn-primary px-4 border-none bg-warning me-2"><i class='bx bx-bookmark'></i>Save Draft</button>
                                          <button class="btn btn-primary px-4" wire:click="nextStep">Next<i class='bx bx-right-arrow-alt ms-2'></i></button>
                                      </div>
                                  </div>
                              </div>
                          </div><!---end row-->

                        </div>

                        <div id="test-l-3" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger3">
                          <h5 class="mb-1">Your Education Information</h5>
                          <p class="mb-4">Inform companies about your education life</p>

                          <div class="row g-3">
                              <div class="col-12 col-lg-6">
                                  <label for="SchoolName" class="form-label">School Name</label>
                                  <input type="text" class="form-control" id="SchoolName" wire:model="kosong"  placeholder="School Name">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="BoardName" class="form-label">Board Name</label>
                                  <input type="text" class="form-control" id="BoardName" wire:model="kosong"  placeholder="Board Name">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="UniversityName" class="form-label">University Name</label>
                                  <input type="text" class="form-control" id="UniversityName" wire:model="kosong"  placeholder="University Name">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="InputCountry" class="form-label">Course Name</label>
                                  <select class="form-select" id="InputCountry" aria-label="Default select example">
                                      <option selected>---</option>
                                      <option value="1">One</option>
                                      <option value="2">Two</option>
                                      <option value="3">Three</option>
                                    </select>
                              </div>
                              <div class="col-12">
                                  <div class="d-flex justify-content-between align-items-center gap-3 ">
                                      <button class="btn btn-outline-secondary px-4" wire:click="prevStep"><i class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                      <button class="btn btn-primary px-4" wire:click="nextStep">Next<i class='bx bx-right-arrow-alt ms-2'></i></button>
                                  </div>
                              </div>
                          </div><!---end row-->

                        </div>

                        <div id="test-l-4" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger4">
                          <h5 class="mb-1">Work Experiences</h5>
                          <p class="mb-4">Can you talk about your past work experience?</p>

                          <div class="row g-3">
                              <div class="col-12 col-lg-6">
                                  <label for="Experience1" class="form-label">Experience 1</label>
                                  <input type="text" class="form-control" id="Experience1" wire:model="kosong"  placeholder="Experience 1">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="Position1" class="form-label">Position</label>
                                  <input type="text" class="form-control" id="Position1" wire:model="kosong"  placeholder="Position">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="Experience2" class="form-label">Experience 2</label>
                                  <input type="text" class="form-control" id="Experience2" wire:model="kosong"  placeholder="Experience 2">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="PhoneNumber" class="form-label">Position</label>
                                  <input type="text" class="form-control" id="PhoneNumber" wire:model="kosong"  placeholder="Position">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="Experience3" class="form-label">Experience 3</label>
                                  <input type="text" class="form-control" id="Experience3" wire:model="kosong"  placeholder="Experience 3">
                              </div>
                              <div class="col-12 col-lg-6">
                                  <label for="PhoneNumber" class="form-label">Position</label>
                                  <input type="text" class="form-control" id="PhoneNumber" wire:model="kosong"  placeholder="Position">
                              </div>
                              <div class="col-12">
                                  <div class="d-flex justify-content-between align-items-center gap-3 ">
                                      <button class="btn btn-primary px-4" wire:click="prevStep"><i class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                      <button class="btn btn-success px-4" wire:click="nextStep">Submit</button>
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
    </div>
    <!--end row-->
</div>

@push('scripts')
<script type="module">


$(document).ready(function() {
    console.log('ada isinya lek');
        // Memeriksa status checkbox saat halaman dimuat
        toggleDateFields();

        // Menambahkan event listener pada checkbox
        $('#SameDayEvent').on('change',function() {
            toggleDateFields();
        });

        // Fungsi untuk menyembunyikan atau menampilkan elemen berdasarkan checkbox
        function toggleDateFields() {
            $('.hide-is-sameday').each(function() {
                if ($('#SameDayEvent').is(':checked')) {
                    // Sembunyikan elemen jika checkbox dicentang
                    $(this).hide();
                } else {
                    // Tampilkan elemen jika checkbox tidak dicentang
                    $(this).show();
                }
            });
        }
        // $('#stepper1')[0].addEventListener('show.bs-stepper', function (event) {
        //         console.log('Step akan berubah ke:', event.detail.indexStep);
        //         $wire.dispatch('update-step', { step: event.detail.indexStep });
        // });

    });

    // window.nextButton = ()=>{
    //     window.stepper1.next()
    // }
    // window.prevButton = ()=>{
    //     window.stepper1.previous()
    // }


// console.log(window.stepper1);


// });

// $(document).ready(function () {
//     console.log('sini');

//   var stepper = new Stepper($('.bs-stepper')[0])
// })



</script>
@endpush

