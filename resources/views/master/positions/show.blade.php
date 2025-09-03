{{-- filepath: c:\laragon\www\application_letters\resources\views\master\users\detail.blade.php --}}
@extends('layouts.main')

@section('content')
<div class="container mt-5 d-flex justify-content-center">
    <div class="row">
        <div class="col-md-12 col-sm-12">
                <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h4 class="mb-0">Detail Departemen</h4>
            <a href="{{route('departments.index')}}" class="btn btn-sm btn-outline-secondary me-2">Daftar</a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
            <dt class="col-sm-3">Nama</dt>
            <dd class="col-sm-9">{{ $department->name }}</dd>

            <dt class="col-sm-3">Code</dt>
            <dd class="col-sm-9">{{ $department->code }}</dd>

            <dt class="col-sm-3">Parent</dt>
            <dd class="col-sm-9">{{ $department->parent->name ?? '-' }}</dd>

                <dt class="col-sm-3">Approval Oleh</dt>
                <dd class="col-sm-9">
                    @if($department->approval_by === 'self')
                        Departemen Sendiri
                    @elseif($department->approval_by === 'parent')
                        Departemen Parent
                    @elseif($department->approval_by === 'central')
                        Departemen Rektorat
                    @else
                        {{ $department->approval_by }}
                    @endif

                </dd>

                <dt class="col-sm-3">Kuota Pengajuan</dt>
                <dd class="col-sm-9">{{ $department->limit_submission }}</dd>
            </dl>
        </div>
    </div>
        </div>
    </div>

</div>
@endsection