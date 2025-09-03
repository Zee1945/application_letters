{{-- filepath: c:\laragon\www\application_letters\resources\views\master\positions\edit.blade.php --}}
@extends('layouts.main')

@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4>Edit Jabatan</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('positions.update', $position->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="name" class="fw-bold">Nama</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $position->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="role" class="fw-bold">Role</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" 
                                {{ old('role', $position->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group d-flex justify-content-center">
                    <a href="{{ route('positions.index') }}" class="btn btn-sm btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection