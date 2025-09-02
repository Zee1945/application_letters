{{-- filepath: c:\laragon\www\application_letters\resources\views\master\users\detail.blade.php --}}
@extends('layouts.main')

@section('content')
<div class="container mt-5 d-flex justify-content-center">
    <div class="row">
        <div class="col-md-12 col-sm-12">
                <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h4 class="mb-0">Detail User</h4>
            <a href="{{route('users.index')}}" class="btn btn-sm btn-outline-secondary me-2">Daftar</a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nama</dt>
                <dd class="col-sm-9">{{ $user->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3">Jabatan</dt>
                <dd class="col-sm-9">{{ $user->position->name ?? '-' }}</dd>

                <dt class="col-sm-3">Departemen</dt>
                <dd class="col-sm-9">{{ $user->department->name ?? '-' }}</dd>
            </dl>
        </div>
    </div>
        </div>
    </div>

</div>
@endsection