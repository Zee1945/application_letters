<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">LPJ</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">List LPJ</li>
                </ol>
            </nav>
        </div>
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
                            <td>{!! viewHelper::statusReportHTML($application->report?->approval_status) !!}</td>
                            <td>{!! viewHelper::getCurrentUserProcess($application,true) !!}</td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="javascript:;" class="me-3"><i class="fa-regular fa-eye"></i></a>
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
        </div>
    </div>
</div>
