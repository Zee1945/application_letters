<div>
    <!--breadcrumb-->
    <div class="page-breadcrumb d-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Applications</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item" aria-current="page">Application</li>
                    <li class="breadcrumb-item active" aria-current="page">Detail</li>
                </ol>
            </nav>
        </div>
    </div>
    <!--end breadcrumb-->

    <div class="row">
        <div class="col-12 col-lg-9 mx-auto">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="mb-2 d-flex justify-content-between">
                            <div>
                                <span class="fw-bold fs-5">Detail Pengajuan</span>
                            </div>
                            <div class="ms-auto">
                                <a class="btn btn-sm btn-outline-secondary" href="{{route('applications.create.draft',['application_id'=>$app->id])}}"><i class='bx bxs-edit'></i> Lihat Konten</a>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <!-- Displaying Application Details -->
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Nama Kegiatan</span></div>
                        <div class="col-sm-9"><span>{{$app->activity_name}}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Sumber Pendanaan</span></div>
                        <div class="col-sm-9"><span>{{$app->funding_source == '1' ? 'BLU' : 'BOPTN'}}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Status</span></div>
                        <div class="col-sm-9"><span>{!! viewHelper::statusSubmissionHTML($app->approval_status) !!}</span></div>
                    </div>
                      <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Pengusul</span></div>
                        <div class="col-sm-9"><span>{{$app->createdBy->name }}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Pemroses Saat Ini</span></div>
                        <div class="col-sm-9"><span>{!! viewHelper::getCurrentUserProcess($app) !!}</span></div>
                    </div>
                  
                    <div class="row mb-3">
                        <div class="col-sm-3"><span class="fw-bold">Departemen</span></div>
                        <div class="col-sm-9"><span>{{$app->department->name }}</span></div>
                    </div>

                    <!-- File Information Table -->
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    {{-- <th class="text-center" style="width: 45%">Parent</th> --}}
                                    <th class="text-center" style="width: 45%">Status</th>
                                    <th style="width: 45%">Nama File</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($app->applicationFiles as $item)
                                    <tr>
                                        {{-- <td>{{$item->fileType->parent?->name}}</td> --}}
                                        <td class="text-center">{!! viewHelper::generateStatusFileHTML($item->status_ready) !!}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <i class="bx bxs-file-pdf me-2 font-24 text-danger"></i>
                                                </div>
                                                @if ($item->status_ready == 3)
                                                <span class="text-primary cursor-pointer"  wire:click="downloadFile('{{$item->file->path}}','{{$item->file->filename}}')"><u>{{$item->display_name}}</u> <span class="bg-pastel-primary rounded-circle">
                                                    @if($item->fileType->code !== 'file-spj')
                                                    <i class="fa-solid fa-signature"></i></span></span>
                                                    @endif
                                                @else
                                                <span>{{$item->display_name}}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!--end row-->
</div>
