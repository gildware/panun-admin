@extends('adminmodule::layouts.new-master')

@section('title', translate('Edit_Ad_Source'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{ translate('Edit_Ad_Source') }}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.adsource.update', $adSource->id) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Name') }} *</label>
                                            <input type="text" class="form-control" name="name" required
                                                   value="{{ old('name', $adSource->name) }}">
                                            @error('name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Description') }}</label>
                                            <textarea class="form-control" name="description" rows="3">{{ old('description', $adSource->description) }}</textarea>
                                        </div>
                                        <div class="mb-30">
                                            <label class="form-label d-block">{{ translate('Image') }}</label>
                                            <div class="d-flex gap-3 align-items-center">
                                                <div class="upload-file">
                                                    <input type="file"
                                                           class="upload-file__input"
                                                           name="image"
                                                           accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                           data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                    <div class="upload-file__img">
                                                        <img
                                                            src="{{ $adSource->image ? asset('storage/ad-source/' . $adSource->image) : asset('assets/admin-module/img/media/upload-file.png') }}"
                                                            alt="{{ translate('image') }}"
                                                            onerror="this.src='{{ asset('assets/placeholder.png') }}'">
                                                    </div>
                                                    <span class="upload-file__edit">
                                                        <span class="material-icons">edit</span>
                                                    </span>
                                                </div>
                                                <p class="opacity-75 mb-0">
                                                    {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                    {{ translate('Image Size') }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                                </p>
                                            </div>
                                            <small class="text-muted d-block mt-1">{{ translate('Leave empty to keep current image') }}</small>
                                            @error('image')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn--primary">{{ translate('Update') }}</button>
                                            <a href="{{ route('admin.lead.adsource.index') }}" class="btn btn--secondary">{{ translate('Cancel') }}</a>
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
