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
            <div class="card-title d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Daftar Pengajuan</h6>
                <div class="action">
                    <a href="{{ route('applications.create') }}" class="btn btn-outline-primary btn-sm"><i class='bx bx-plus me-2'></i><span>Pengajuan Baru</span></a>
                </div>
            </div>
            <hr/>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Sumber Pendanaan</th>
                            <th>Status Pengajuan</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($applications as $key => $application)
                            <tr>
                                <td>{{$key+1}}</td>
                                <td>{{ $application->activity_name }}</td>
                                <td>{{ $application->funding_source }}</td>
                                <td>{{ $application->document_status }}</td>
                                <td>
                                    {{-- <a href="{{ route('application.show', $application->id) }}" class="btn btn-primary btn-sm">View</a>
                                    <a href="{{ route('application.edit', $application->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    <form action="{{ route('application.destroy', $application->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form> --}}
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
