@extends('layouts.main')

@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4>Tambah Departemen</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('departments.store') }}">
                @csrf
                {{-- @method('PUT') --}}

                <div class="form-group mb-3">
                    <label for="name" class="fw-bold">Nama</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="name" class="fw-bold">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="parent_id" class="fw-bold">Departemen Parent</label>
                    <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id" required>
                        <option value="">-- Pilih Departemen Parent --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('parent_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="name" class="fw-bold">Kuota Pengajuan</label>
                    <input type="number" class="form-control @error('limit_submission') is-invalid @enderror" id="limit_submission" name="limit_submission" required>
                    @error('limit_submission')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="approval_by">Approval Oleh</label>
                    <select class="form-select @error('approval_by') is-invalid @enderror" id="approval_by" name="approval_by" required>
                        <option value="">-- Pilih  Approval --</option>
                            <option value="self" {{ old('approval_by') == $dept->id ? 'selected' : '' }}>
                                Departemen <span id="selfDepartment"></span>
                            </option>
                            <option value="parent" {{ old('approval_by') == 'parent' ? 'selected' : '' }}>
                                Department <span id="parentDepartment"></span>
                            </option>
                            <option value="central" {{ old('approval_by') == 'central' ? 'selected' : '' }}>
                                Departemen Rektorat
                            </option>
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
               


                <div class="form-group d-flex justify-content-center">
                    <a href="{{route('departments.index')}}" class="btn btn-sm btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script type="module">
    $(document).ready(function(){
        var self= 'Sendiri';
        var parent= 'Parent';
        $('#selfDepartment').html(self);
        $('#parentDepartment').html(parent);
        
        $('#name').change(function(){
            $('#selfDepartment').html($(this).val());
        });
        $('#parent_id').change(function(){
             var selectedText = $(this).find('option:selected').text();              
            $('#parentDepartment').html(selectedText);


            
        });
        
    });
</script>
@endsection
