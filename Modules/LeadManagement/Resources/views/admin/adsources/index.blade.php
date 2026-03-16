@extends('adminmodule::layouts.new-master')

@section('title', translate('Manage_Ad_Source'))

@push('css_or_js')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{ translate('Manage_Ad_Source') }}</h2>
                    </div>

                    <div class="card mb-30">
                        <div class="card-body p-30">
                            <h5 class="mb-3">{{ translate('Add_New_Ad_Source') }}</h5>
                            <form action="{{ route('admin.lead.adsource.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="search" value="{{ $search }}">
                                <input type="hidden" name="status" value="{{ $status }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label">{{ translate('Name') }} *</label>
                                        <input type="text" class="form-control" name="name" required
                                               value="{{ old('name') }}" placeholder="{{ translate('Name') }}">
                                        @error('name')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ translate('Description') }}</label>
                                        <input type="text" class="form-control" name="description"
                                               value="{{ old('description') }}" placeholder="{{ translate('Description') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-3">
                                            <p class="opacity-75 max-w220">
                                                {{ translate('Image format')}} - {{ implode(', ', array_column(IMAGEEXTENSION, 'key')) }}
                                                {{ translate('Image Size') }} - {{ translate('maximum size') }} {{ readableUploadMaxFileSize('image') }}
                                            </p>
                                            <div class="d-flex align-items-center flex-column">
                                                <div class="upload-file">
                                                    <input type="file"
                                                           class="upload-file__input"
                                                           name="image"
                                                           accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }}, |image/*"
                                                           data-maxFileSize="{{ readableUploadMaxFileSize('image') }}">
                                                    <div class="upload-file__img">
                                                        <img
                                                            src="{{asset('assets/admin-module/img/media/upload-file.png')}}"
                                                            alt="{{translate('image')}}">
                                                    </div>
                                                    <span class="upload-file__edit">
                                                        <span class="material-icons">edit</span>
                                                    </span>
                                                </div>
                                                @error('image')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn--primary">{{ translate('Add') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <form action="{{ route('admin.lead.adsource.index') }}" method="GET" class="d-flex flex-wrap gap-3 mb-3">
                                <input type="text" class="form-control w-auto" name="search" value="{{ $search }}"
                                       placeholder="{{ translate('Search_here') }}" style="max-width: 200px;">
                                <select class="form-select w-auto" name="status" style="max-width: 150px;">
                                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                                </select>
                                <button type="submit" class="btn btn--secondary">{{ translate('Filter') }}</button>
                            </form>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('Sl') }}</th>
                                        <th>{{ translate('Image') }}</th>
                                        <th>{{ translate('Name') }}</th>
                                        <th>{{ translate('Description') }}</th>
                                        <th class="text-center">{{ translate('Leads_Count') }}</th>
                                        <th class="text-center">{{ translate('Status') }}</th>
                                        <th class="text-center">{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($adSources as $key => $adSource)
                                        <tr>
                                            <td>{{ $adSources->firstItem() + $key }}</td>
                                            <td>
                                                @if($adSource->image)
                                                    <img src="{{ asset('storage/ad-source/' . $adSource->image) }}" alt="" class="rounded" style="max-width: 50px; max-height: 50px; object-fit: cover;" onerror="this.src='{{ asset('assets/placeholder.png') }}'">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $adSource->name }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($adSource->description, 40) ?: '—' }}</td>
                                            <td class="text-center">{{ $adSource->leads_count }}</td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill {{ $adSource->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $adSource->is_active ? translate('Active') : translate('Inactive') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.lead.adsource.status-update', $adSource->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $adSource->is_active ? 'btn-secondary' : 'btn-success' }}">
                                                        {{ $adSource->is_active ? translate('Deactivate') : translate('Activate') }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.lead.adsource.edit', $adSource->id) }}" class="btn btn-sm btn--primary">
                                                    {{ translate('Edit') }}
                                                </a>
                                                <form action="{{ route('admin.lead.adsource.destroy', $adSource->id) }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Are_you_sure') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ translate('Delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">{{ translate('No_ad_sources_found') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                {{ $adSources->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
