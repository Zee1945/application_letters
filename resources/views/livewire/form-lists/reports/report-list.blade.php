<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
        <div class="breadcrumb-title ">Laporan Kegiatan (LPJ)</div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-lg-flex align-items-center mb-4 gap-3">
                <div class="position-relative">
                    <input type="text" class="form-control ps-5 radius-30" placeholder="Search Order"> <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
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
                            <th>Aksi</th>
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
                            <td>{!! viewHelper::getCurrentUserProcess($application,true)['name'] !!}</td>
                            <td>{{$application->department->name}}</td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="{{route('applications.detail',['application_id'=>$application->id])}}" class="me-3"><i class='bx bx-info-circle'></i></a>
                                    <a href="{{route('reports.create',['application_id'=>$application->id])}}" class=""><i class='bx bxs-edit'></i></a>
                                    <a href="javascript:;" class="ms-3"><i class='bx bxs-trash'></i></a>
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
