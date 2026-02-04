<div>
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
        <div class="breadcrumb-title ">Pengajuan Kegiatan</div>
    </div>
    <div class="card">
        <div class="card-body">
            <!-- Search and Filter Section -->
            <div class="d-lg-flex align-items-center mb-4 gap-3">
                <div class="position-relative flex-grow-1">
                    <input type="text"
                    class="form-control ps-5 radius-30"
                    placeholder="Cari Nama Kegiatan"
                    wire:model.live.debounce.1000ms="search" />
                <span class="position-absolute top-50 product-show translate-middle-y">
                    <i class="bx bx-search"></i>
                </span>
                </div>
                
                <!-- New Application Button -->
                <div class="ms-auto">
                    @if (viewHelper::canDo('create_application'))
                        <a href="{{ route('applications.create') }}" class="btn btn-primary radius-30 mt-2 mt-lg-0 {{viewHelper::actionPermissionButton('create-new-application')? '':'disabled'}}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{viewHelper::actionPermissionButton('create-new-application')? 'Buat pengajuan proposal baru':'Lakukan submit LPJ sebelum melakukan pengajuan baru'}}">
                            <i class="bx bxs-plus-square"></i> Pengajuan Baru
                        </a>
                    @endif
                </div>
            </div>
           <div class="mb-3 d-flex flex-wrap gap-2">
                <div style="min-width: 200px;">
                    <select class="form-select form-select-sm" wire:model.live="status_approval">
                        <option value="">Filter Status</option>
                        <option value="need-my-process">Butuh proses saya</option>
                        <option value="ongoing">Sedang Diproses</option>
                        <option value="finished">Approved & Finish</option>
                        <option value="need-revision">Butuh Revisi</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
                <div style="min-width: 200px;">
                    <select class="form-select form-select-sm" wire:model.live="department_id">
                        <option value="">Filter Departemen</option>
                        @foreach($department_options as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- <div class="mb-2 d-flex">
                <!-- Filter Dropdown (Add more filters as needed) -->
                <div class="ms-3 me-3">
                    <div class=""></div>
                    <select class="form-select">
                        <option selected>Filter by Status</option>
                        <option value="1">Sedang Diproses</option>
                        <option value="2">Approved & Finish</option>
                        <option value="1">Butuh Revisi</option>
                        <option value="3">Ditolak</option>
                    </select>
                </div>
                <div class="ms-3">
                    <div class=""></div>
                    <select class="form-select">
                        <option selected>Filter by Status</option>
                        <option value="1">Sedang Diproses</option>
                        <option value="2">Approved & Finish</option>
                        <option value="1">Butuh Revisi</option>
                        <option value="3">Ditolak</option>
                    </select>
                </div>
            </div> --}}

            <!-- Table for Applications -->
            <div class="table-responsive">
                <table class="table mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Kegiatan</th>
                            <th>Tanggal Kegiatan</th>
                            <th>Status Pengajuan</th>
                            <th>Pemroses Saat ini</th>
                            <th>Departemen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $key => $application)
                        <tr>
                            <td class="text-center">{{$key+1}}.</td>
                            <td>
                                <div class="">
                                    {{ $application->activity_name }}
                                </div>
                                {{-- <div class="small text-muted mt">
                                    Sumber Pendanaan : {{ $application->funding_source == 1? 'BLU':'BOPTN'}}
                                </div> --}}
                            </td>
                            <td>
                                    @if (!empty($application->detail?->activity_dates))
                                        @php
                                            $dates = explode(',', $application->detail->activity_dates);
                                            $formattedDates = array_map(function($date) {
                                                return '<span class="text-muted text-nowrap">'.ViewHelper::humanReadableDate($date, true,true).'</span>';
                                            }, $dates);
                                        @endphp
                                        {!!   implode('<br><hr class="my-1">', $formattedDates) !!}
                                    @else
                                        <span class="text-muted">Belum Ditetapkan</span>
                                    @endif

                            </td>
                            <td>{!! viewHelper::statusSubmissionHTML($application->current_approval_status) !!}</td>
                            <td>
                                @php
                                    $user_text = viewHelper::getCurrentUserProcess($application);
                                @endphp

                                    <div class="fw-semibold">{{ $user_text['name'] ?? '-' }}</div>
                                    <div class="small text-muted">
                                        {{ $user_text['position'] ?? '-' }}
                                        @if(!empty($user_text['department']))
                                            <span class="mx-1">|</span> {{ $user_text['department'] }}
                                        @endif
                        </div>

                            </td>
                            <td>{{$application->department->name}}</td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="{{route('applications.detail',['application_id'=>$application->id])}}" class="btn btn-sm btn-outline-info me-3"><i class='bx bx-info-circle me-0 pe-0'></i></a>
                                    <a href="{{route('applications.create.draft',['application_id'=>$application->id])}}" class="btn btn-sm btn-outline-primary"><i class='bx bxs-edit me-0 pe-0'></i></a>
                                    {{-- <a href="javascript:;" class="ms-3 text-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"><i class='bx bxs-trash'></i></a> --}}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No applications found.</td>
                        </tr>
                        @endforelse

                        <!-- Remaining Submission Opportunities for Department -->
                        <tr>
                            @if ($department)
                            <td colspan="7" class="">
                                    
                                Sisa kesempatan pengajuan proposal Departemen: 
                                <span class="fw-bold">{{$department->limit_submission - $department->current_limit_submission }}</span> dari <span class="fw-bold">{{$department->limit_submission}}</span>. 
                                {{viewHelper::actionPermissionButton('create-new-application')? '' : 'Submit LPJ sebelum melakukan pengajuan baru'}}
                            </td>
                                @endif

                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div class="mt-4">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</div>
