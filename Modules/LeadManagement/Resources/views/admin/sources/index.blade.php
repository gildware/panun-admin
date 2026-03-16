@extends('adminmodule::layouts.new-master')

@section('title', translate('Lead_Source'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{ translate('Lead_Source') }}</h2>
                    </div>

                    <div class="card mb-30">
                        <div class="card-body p-30">
                            <h5 class="mb-3">{{ translate('Add_New_Source') }}</h5>
                            <form action="{{ route('admin.lead.source.store') }}" method="post">
                                @csrf
                                <input type="hidden" name="search" value="{{ $search }}">
                                <input type="hidden" name="status" value="{{ $status }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">{{ translate('Name') }} *</label>
                                        <input type="text" class="form-control" name="name" required
                                               value="{{ old('name') }}" placeholder="{{ translate('Name') }}">
                                        @error('name')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ translate('Description') }}</label>
                                        <input type="text" class="form-control" name="description"
                                               value="{{ old('description') }}" placeholder="{{ translate('Description') }}">
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
                            <form action="{{ route('admin.lead.source.index') }}" method="GET" class="d-flex flex-wrap gap-3 mb-3">
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
                                        <th>{{ translate('Name') }}</th>
                                        <th>{{ translate('Description') }}</th>
                                        <th class="text-center">{{ translate('Leads_Count') }}</th>
                                        <th class="text-center">{{ translate('Status') }}</th>
                                        <th class="text-center">{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($sources as $key => $source)
                                        <tr>
                                            <td>{{ $sources->firstItem() + $key }}</td>
                                            <td>{{ $source->name }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($source->description, 50) ?: '—' }}</td>
                                            <td class="text-center">{{ $source->leads_count }}</td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill {{ $source->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $source->is_active ? translate('Active') : translate('Inactive') }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.lead.source.status-update', $source->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $source->is_active ? 'btn-secondary' : 'btn-success' }}">
                                                        {{ $source->is_active ? translate('Deactivate') : translate('Activate') }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.lead.source.edit', $source->id) }}" class="btn btn-sm btn--primary">
                                                    {{ translate('Edit') }}
                                                </a>
                                                <form action="{{ route('admin.lead.source.destroy', $source->id) }}" method="post" class="d-inline"
                                                      onsubmit="return confirm('{{ translate('Are_you_sure') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ translate('Delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">{{ translate('No_sources_found') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                {{ $sources->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
