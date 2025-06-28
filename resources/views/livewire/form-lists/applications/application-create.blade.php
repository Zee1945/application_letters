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
                                    <label for="activity_name" class="form-label">Nama Kegiatan</label>
                                    <input type="text" class="form-control" name="activity_name" id="activity_name" wire:model="activity_name" placeholder="Isi nama kegiatan...">
                                </div>
                                <div class="col-md-12">
                                    <label for="input7" class="form-label">Sumber Pendanaan</label>
                                    <select id="input7" class="form-select" wire:model="fund_source">
                                        <option selected>---Pilih sumber pendanaan---</option>
                                        <option value="1">BLU</option>
                                        <option value="2">BOPTN</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="verificator" class="form-label col-sm-4">Verifikator/Penandatangan</label>
                                    <div class="col-sm-8">
                                        @foreach ($user_approvers as $key => $user)
                                            <div class="">
                                                {{$user->name}}
                                            </div>
                                            @endforeach
                                    </div>

                                </div>

                                <div class="col-md-12">
                                    <div class="d-md-flex d-grid justify-content-center align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary px-4">Batal</button>
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

