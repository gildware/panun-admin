@extends('adminmodule::layouts.new-master')

@section('title', translate('Edit_Source'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{ translate('Edit_Source') }}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.source.update', $source->id) }}" method="post">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Name') }} *</label>
                                            <input type="text" class="form-control" name="name" required
                                                   value="{{ old('name', $source->name) }}">
                                            @error('name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Description') }}</label>
                                            <textarea class="form-control" name="description" rows="3">{{ old('description', $source->description) }}</textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn--primary">{{ translate('Update') }}</button>
                                            <a href="{{ route('admin.lead.source.index') }}" class="btn btn--secondary">{{ translate('Cancel') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
