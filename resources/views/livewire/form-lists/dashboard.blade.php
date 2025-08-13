{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\dashboard.blade.php --}}
<div class="container-fluid">
    <!-- Header Dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Dashboard</h4>
            <p class="text-muted mb-0">Selamat datang di sistem manajemen surat</p>
        </div>
        <div class="">
             <div class="text-muted">
                <i class="bx bx-calendar me-1"></i>
                {{ date('d F Y') }}
            </div>
            <div class="mb-2">
    <select class="form-select form-select-sm" style="min-width:200px; display:inline-block;"
        wire:change="selected_department">
        <option value="">Pilih Departemen</option>
        @foreach($this->department_list as $dept)
        <option value="{{ $dept['value'] }}">{{ $dept['label'] }}</option>
            <option value="{{ $dept['value'] }}" {!! $dept['is_selected']?'selected':'' !!}>{{ $dept['label'] }}</option>  
        @endforeach
    </select>
    {{$selected_department}}
</div>
        </div>
       
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <!-- Total Pengajuan -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-gradient-primary text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-75 mb-2">Total Pengajuan Departemen</h6>
                            <div class="d-flex">
                                <h2 class="mb-0 fw-bold">{{ $totalPengajuan ?? 100 }} </h2>
                                <span class="ms-3 d-flex align-self-center">Dokumen</span>
                            </div>
                            
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bx bx-file-blank text-white" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Butuh Proses Saya -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bx bx-time-five text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-warning">{{ count($need_process_apps ?? []) }}</h3>
                            <p class="mb-0 text-muted small">Butuh Proses Saya</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sedang Diproses -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bx bx-loader-circle text-info" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-info">{{ $sedangDiproses ?? 8 }}</h3>
                            <p class="mb-0 text-muted small">Sedang Diproses</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ditolak -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bx bx-x-circle text-danger" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-danger">{{ $ditolak ?? 7 }}</h3>
                            <p class="mb-0 text-muted small">Ditolak</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disetujui -->
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bx bx-check-circle text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-success">{{ $disetujui ?? 5 }}</h3>
                            <p class="mb-0 text-muted small">Disetujui</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dokumen Butuh Proses -->
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-transparent border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="bx bx-task text-warning me-2"></i>
                        Laporan Kegiatan Butuh Proses Saya
                    </h5>
                    <p class="text-muted mb-0 small">Daftar Laporan Kegiatan yang membutuhkan persetujuan Anda</p>
                </div>
                <span class="badge bg-warning text-dark px-3 py-2">
                    {{ count($need_process_apps ?? []) }} Dokumen
                </span>
            </div>
        </div>
        <div class="card-body pt-3">
            @if(count($need_process_apps ?? []) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 fw-semibold">No</th>
                                <th class="border-0 fw-semibold">Kegiatan</th>
                                <th class="border-0 fw-semibold">Jenis</th>
                                <th class="border-0 fw-semibold">Status</th>
                                <th class="border-0 fw-semibold">Pemroses</th>
                                <th class="border-0 fw-semibold">Departemen</th>
                                <th class="border-0 fw-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($need_process_apps as $application)
                                <tr>
                                    <td class="fw-semibold">{{ $no++ }}</td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;" title="{{ $application->activity_name }}">
                                            {{ $application->activity_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $application->trans_type == 1 ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ $application->trans_type == 1 ? 'Pengajuan' : 'Laporan' }}
                                        </span>
                                    </td>
                                    <td>{!! $application->trans_type == 1? viewHelper::statusSubmissionHTML($application->approval_status):viewHelper::statusReportHTML($application->report?->approval_status) !!}</td>
                                    <td>{!!  $application->trans_type == 1? viewHelper::getCurrentUserProcess($application):viewHelper::getCurrentUserProcess($application,true) !!}</td>

                                    <td>
                                        <small class="text-muted">{{ $application->department->name }}</small>
                                    </td>
                             
                                    <td>

                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('applications.detail', ['application_id' => $application->id]) }}" 
                                               class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class='bx bx-info-circle'></i>
                                            </a>
                                            @if ($application->trans_type == 1)
                                            <a href="{{ route('applications.create.draft', ['application_id' => $application->id]) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class='bx bxs-edit'></i>
                                            </a>
                                            @else
                                            <a href="{{ route('reports.create', ['application_id' => $application->id]) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class='bx bxs-edit'></i>
                                            </a>
                                            @endif
                                            
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bx bx-check-double text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h6 class="text-muted">Tidak ada dokumen yang butuh proses Anda</h6>
                    <p class="text-muted small mb-0">Semua dokumen sudah diproses dengan baik</p>
                </div>
            @endif
        </div>
    </div>
</div>

