{{-- filepath: c:\laragon\www\application_letters\resources\views\master\departments\edit.blade.php --}}
@extends('layouts.main')

@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4>Edit Departemen</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('departments.update', $department->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="name" class="fw-bold">Nama</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $department->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="code" class="fw-bold">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                        value="{{ old('code', $department->code) }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="parent_id" class="fw-bold">Departemen Parent</label>
                    <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id" required>
                        <option value="">-- Pilih Departemen Parent --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('parent_id', $department->parent_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="limit_submission" class="fw-bold">Kuota Pengajuan</label>
                    <input type="number" class="form-control @error('limit_submission') is-invalid @enderror" id="limit_submission" name="limit_submission"
                        value="{{ old('limit_submission', $department->limit_submission) }}" required>
                    @error('limit_submission')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="approval_by">Approval Oleh</label>
                    <select class="form-select @error('approval_by') is-invalid @enderror" id="approval_by" name="approval_by" required>
                        <option value="">-- Pilih Approval --</option>
                        <option value="self" {{ old('approval_by', $department->approval_by) == 'self' ? 'selected' : '' }}>
                            Departemen sendiri
                        </option>
                        <option value="parent" {{ old('approval_by', $department->approval_by) == 'parent' ? 'selected' : '' }}>
                            Department Parent
                        </option>
                        <option value="central" {{ old('approval_by', $department->approval_by) == 'central' ? 'selected' : '' }}>
                            Departemen Rektorat
                        </option>
                    </select>
                    @error('approval_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group d-flex justify-content-center">
                    <a href="{{ route('departments.index') }}" class="btn btn-sm btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection