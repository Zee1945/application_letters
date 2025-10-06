<div>
    		<!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Formulir Pengajuan</div>
            </div>
            <!--end breadcrumb-->
            <div class="row">
                <div class="col-12 col-lg-9 mx-auto">
                    <div class="text-center">
                        <h5 class="mb-0 text-uppercase">Formulir Pengajuan</h5>
                        <hr/>
                    </div>
                    <div class="card">
                        <div class="card-body p-4">
                            <form class="row g-3" wire:submit.prevent="store">
                                <div class="col-md-12">
                                    <label for="activity_name" class="form-label fw-bold">Nama Kegiatan</label>
                                    <input type="text" class="form-control" name="activity_name" id="activity_name" wire:model="activity_name" placeholder="Isi nama kegiatan...">
                                </div>
                                <div class="col-md-12">
                                    <label for="input7" class="form-label fw-bold">Sumber Pendanaan</label>
                                    <select id="input7" class="form-select" wire:model="fund_source">
                                        <option selected>---Pilih sumber pendanaan---</option>
                                        <option value="1">BLU</option>
                                        <option value="2">BOPTN</option>
                                    </select>
                                </div>
<div class="col-md-12">
    <label class="form-label fw-bold mb-3">Pemroses Dokumen</label>
    <div class="position-relative" style="margin-left: 30px;">
        <div class="timeline">
            @foreach ($user_approvers as $key => $user)
                @php
                    $user_text = explode('-', $user['user_text']);
                @endphp
                <div class="timeline-item d-flex mb-4">
                    <div class="timeline-sequence flex-shrink-0 d-flex flex-column align-items-center" style="width: 40px;">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; font-size: 1rem;">
                            {{ $user['sequence'] }}
                        </div>
                        @if(!$loop->last)
                            <div class="flex-grow-1 border-start border-2 border-primary" style="min-height: 30px;"></div>
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

                                <div class="col-md-12">
                                    <div class="d-md-flex d-grid justify-content-center align-items-center gap-2">
                                        <a href="{{route('applications.index')}}" class="btn btn-outline-secondary px-4">Batal</a>
                                        <button type="submit"  class="btn btn-primary px-4">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            @push('scripts')
            <script type="module">
                document.addEventListener('livewire:init', function () {
                    $('#verificator').on('change', function (e) {
                        @this.verificator = $(this).val();
                    });
                })
            </script>
            @endpush
</div>

