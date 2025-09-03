{{-- filepath: c:\laragon\www\application_letters\resources\views\livewire\form-lists\master\user-list.blade.php --}}
<div>
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Master Jabatan</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="#"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Daftar Jabatan</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Section -->
            <div class="d-flex gap-2 mb-4">
                <form method="GET" action="#" class="d-flex flex-grow-1 gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama Jabatan..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
                <a href="{{ route('positions.create') }}" class="btn btn-success">+ Tambah</a>
            </div>

            <div class="table-responsive">
                <table class="table mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $key => $pos)
                        <tr>
                            <td>{{ $positions->firstItem() + $key }}</td>
                            <td>{{ $pos->name }}</td>
                            <td>
                                {{ $pos->roles->pluck('name')->implode(', ') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('positions.show', $pos->id) }}" class="btn btn-info btn-sm">Detail</a>
                                <a href="{{ route('positions.edit', $pos->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('positions.destroy', $pos->id) }}" method="POST" style="display:inline;">
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
                {{ $positions->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>