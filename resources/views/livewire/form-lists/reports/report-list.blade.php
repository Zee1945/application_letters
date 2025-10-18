<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
        <div class="breadcrumb-title ">Laporan Kegiatan (LPJ)</div>
    </div>

    <div class="card">
        <div class="card-body">
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
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kegiatan</th>
                            <th>Status LPJ</th>
                            <th>Pemroses Saat ini</th>
                            <th>Departemen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $key => $application)
                        <tr>
                            <td>
                                {{$key+1}}
                            </td>
                            <td>{{ $application->activity_name }}</td>
                            <td>
                               {!! viewHelper::statusSubmissionHTML($application->current_approval_status) !!}
                            </td>
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
                                    <a href="{{route('applications.detail',['application_id'=>$application->id])}}" class="me-3"><i class='bx bx-info-circle'></i></a>
                                    <a href="{{route('reports.create',['application_id'=>$application->id])}}" class=""><i class='bx bxs-edit'></i></a>
                                    {{-- <a href="javascript:;" class="ms-3"><i class='bx bxs-trash'></i></a> --}}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No applications found.</td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>
               <!-- Pagination Section -->
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</div>
