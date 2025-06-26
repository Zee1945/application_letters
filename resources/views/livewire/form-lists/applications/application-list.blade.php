<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Application</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">List Application</li>
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
              <div class="ms-auto">
                <a href="{{ route('applications.create') }}" class="btn btn-primary radius-30 mt-2 mt-lg-0 {{viewHelper::actionPermissionButton('create-new-application')? '':'disabled'}}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="
                {{viewHelper::actionPermissionButton('create-new-application')? 'Buat pengajuan proposal baru':'Lakukan submit LPJ sebelum melakukan pengajuan baru'}}
                "><i class="bx bxs-plus-square"></i>Pengajuan Baru</a>
              </div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kegiatan</th>
                            <th>Sumber Pendanaan</th>
                            <th>Status Pengajuan</th>
                            <th>Pemroses Saat ini</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $key => $application)
                        <tr>
                            <td>
                                {{$key+1}}
                            </td>
                            <td>{{ $application->activity_name }}</td>
                            <td>{{ $application->funding_source == 1? 'BLU':'BTOP'}}</td>
                            <td>{!! viewHelper::statusSubmissionHTML($application->approval_status) !!}</td>
                            <td>{!! viewHelper::getCurrentUserProcess($application) !!}</td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="javascript:;" class="me-3"><i class="fa-regular fa-eye"></i></a>
                                    <a href="{{route('applications.create.draft',['application_id'=>$application->id])}}" class=""><i class='bx bxs-edit'></i></a>
                                    <a href="javascript:;" class="ms-3"><i class='bx bxs-trash'></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No applications found.</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td colspan="6">
                            Limit pengajuan proposal Department : <span class="fw-bold"> {{$department->current_limit_submission }}</span> dari <span class="fw-bold"> {{$department->limit_submission}}</span> batas pengajuan proposal. {{viewHelper::actionPermissionButton('create-new-application')? '':'Submit LPJ sebelum melakukan pengajuan baru'}}

                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
