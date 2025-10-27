@extends('voyager::master')

@section('page_title', __('voyager::generic.edit').' Role')

@section('content')
<div class="page-content container-fluid">
<form
    action="{{ $dataTypeContent->id
        ? route('voyager.roles.update', $dataTypeContent->id)
        : route('voyager.roles.store') }}"
    method="POST">
    @csrf
    @if($dataTypeContent->id)
        @method('PUT')
    @endif

        <div class="row">
            <div class="col-md-8">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="name">Role Name</label>
                            <input type="text" class="form-control" name="name" value="{{ $dataTypeContent->name }}">
                        </div>
                        <div class="form-group">
                            <label for="display_name">Display Name</label>
                            <input type="text" class="form-control" name="display_name" value="{{ $dataTypeContent->display_name }}">
                        </div>
                        <div class="form-group">
                            <label for="guard_name">Guard Name</label>
                            <input type="text" class="form-control" name="guard_name" value="{{ $dataTypeContent->guard_name }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Permissions column --}}
            <div class="col-md-4">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <h4><b>Permissions</b></h4>
                        <hr>
                        @php
                            use Spatie\Permission\Models\Permission;
                            $permissions = Permission::all();
                            $rolePermissions = $dataTypeContent->permissions->pluck('id')->toArray();
                        @endphp

                        @foreach($permissions as $perm)
                            <div class="form-check">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                        {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                    {{ $perm->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-primary">Save Role</button>
        </div>
    </form>
</div>
@stop
