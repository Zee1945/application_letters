<div>
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Application</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">List Application</li>
                </ol>
            </nav>
        </div>
    </div>
    <!--end breadcrumb-->

    <div class="card">
        <div class="card-body">
            <!-- Search and Filter Section -->
            <div class="d-lg-flex align-items-center mb-4 gap-3">
                <div class="position-relative flex-grow-1">
                    <input type="text" class="form-control ps-5 radius-30" placeholder="Search Order"> 
                    <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
                </div>
                <!-- Filter Dropdown (Add more filters as needed) -->
                <div class="ms-3">
                    <select class="form-select radius-30">
                        <option selected>Filter by Status</option>
                        <option value="1">Pending</option>
                        <option value="2">Approved</option>
                        <option value="3">Rejected</option>
                    </select>
                </div>
                <!-- New Application Button -->
                <div class="ms-auto">
                    <a href="{{ route('applications.create') }}" class="btn btn-primary radius-30 mt-2 mt-lg-0 {{viewHelper::actionPermissionButton('create-new-application')? '':'disabled'}}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{viewHelper::actionPermissionButton('create-new-application')? 'Buat pengajuan proposal baru':'Lakukan submit LPJ sebelum melakukan pengajuan baru'}}">
                        <i class="bx bxs-plus-square"></i> Pengajuan Baru
                    </a>
                </div>
            </div>

            <!-- Table for Applications -->
            <div class="table-responsive">
                <table class="table mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kegiatan</th>
                            <th>Sumber Pendanaan</th>
                            <th>Status Pengajuan</th>
                            <th>Pemroses Saat ini</th>
                            <th>Departemen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $key => $application)
                        <tr>
                            <td>{{$key+1}}</td>
                            <td>{{ $application->activity_name }}</td>
                            <td>{{ $application->funding_source == 1? 'BLU':'BOPTN'}}</td>
                            <td>{!! viewHelper::statusSubmissionHTML($application->approval_status) !!}</td>
                            <td>{!! viewHelper::getCurrentUserProcess($application) !!}</td>
                            <td>{{$application->department->name}}</td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="{{route('applications.detail',['application_id'=>$application->id])}}" class="me-3"><i class='bx bx-info-circle'></i></a>
                                    <a href="{{route('applications.create.draft',['application_id'=>$application->id])}}"><i class='bx bxs-edit'></i></a>
                                    <a href="javascript:;" class="ms-3 text-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"><i class='bx bxs-trash'></i></a>
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
                            <td colspan="7" class="">
                                Sisa kesempatan pengajuan proposal Departemen: 
                                <span class="fw-bold">{{$department->limit_submission - $department->current_limit_submission }}</span> dari <span class="fw-bold">{{$department->limit_submission}}</span>. 
                                {{viewHelper::actionPermissionButton('create-new-application')? '' : 'Submit LPJ sebelum melakukan pengajuan baru'}}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div class="d-flex justify-content-end mt-4">
                <nav>
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
