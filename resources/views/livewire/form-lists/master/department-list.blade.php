{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\user-list.blade.php --}}
<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Master Departemen</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Daftar Departemen</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Section -->
            <div class="d-flex gap-2 mb-4">
                <form method="GET" action="#" class="d-flex flex-grow-1 gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama users..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
                <a href="{{ route('departments.create') }}" class="btn btn-success">+ Tambah</a>
            </div>

            <div class="table-responsive">
                <table class="table mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($departments as $key => $dep)
                        <tr>
                            <td>{{ $departments->firstItem() + $key }}</td>
                            <td>{{ $dep->name }}</td>
                            <td class="text-end">
                                <a href="{{ route('departments.show', $dep->id) }}" class="btn btn-info btn-sm">Detail</a>
                                <a href="{{ route('departments.edit', $dep->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('departments.destroy', $dep->id) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus jabatan ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada jabatan ditemukan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-4">
                {{ $departments->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>