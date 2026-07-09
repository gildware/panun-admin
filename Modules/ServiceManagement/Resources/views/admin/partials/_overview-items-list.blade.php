<div class="overview-items-list" id="{{ $listId }}" data-item-type="{{ $itemType }}">
    @forelse($items as $item)
        <div class="overview-item-row border rounded p-2 mb-2">
            <div class="d-flex gap-2 align-items-start">
                <span class="material-icons overview-drag-handle mt-1" draggable="true">drag_indicator</span>
                <div class="flex-grow-1 overview-item-fields">
                    @if($itemType === 'process')
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm overview-item-icon">
                                    <option value="">{{ translate('select_icon') }}</option>
                                    @foreach($overviewIconOptions ?? [] as $opt)
                                        <option value="{{ $opt['key'] }}" {{ ($item['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control form-control-sm overview-item-title" placeholder="Step title" value="{{ $item['title'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-sm overview-item-image" placeholder="Step image URL (optional — use icon if empty)" value="{{ $item['image'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-sm overview-item-description" placeholder="Step description" value="{{ $item['description'] ?? '' }}">
                            </div>
                        </div>
                    @elseif($itemType === 'icon_title')
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm overview-item-icon">
                                    <option value="">{{ translate('select_icon') }}</option>
                                    @foreach($overviewIconOptions ?? [] as $opt)
                                        <option value="{{ $opt['key'] }}" {{ ($item['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="{{ $item['title'] ?? ($item['text'] ?? '') }}">
                            </div>
                        </div>
                    @elseif($itemType === 'chip' || $itemType === 'icon_text')
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm overview-item-icon">
                                    <option value="">{{ translate('select_icon') }}</option>
                                    @foreach($overviewIconOptions ?? [] as $opt)
                                        <option value="{{ $opt['key'] }}" {{ ($item['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm overview-item-icon-image" placeholder="Custom icon URL" value="{{ $item['icon_image'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="{{ $item['text'] ?? '' }}">
                            </div>
                        </div>
                    @elseif($itemType === 'top_icon')
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm overview-item-icon">
                                    @foreach($overviewIconOptions ?? [] as $opt)
                                        <option value="{{ $opt['key'] }}" {{ ($item['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm overview-item-color">
                                    @foreach(['green', 'blue', 'purple', 'orange'] as $color)
                                        <option value="{{ $color }}" {{ ($item['color'] ?? 'green') === $color ? 'selected' : '' }}>{{ $color }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm overview-item-text" placeholder="Label" value="{{ $item['text'] ?? '' }}">
                            </div>
                        </div>
                    @elseif($itemType === 'why_choose')
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm overview-item-icon">
                                    @foreach($overviewIconOptions ?? [] as $opt)
                                        <option value="{{ $opt['key'] }}" {{ ($item['icon'] ?? '') === $opt['key'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-sm overview-item-color">
                                    @foreach(['green', 'blue', 'purple', 'orange'] as $color)
                                        <option value="{{ $color }}" {{ ($item['color'] ?? 'green') === $color ? 'selected' : '' }}>{{ $color }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control form-control-sm overview-item-title" placeholder="Title" value="{{ $item['title'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-sm overview-item-description" placeholder="Description" value="{{ $item['description'] ?? '' }}">
                            </div>
                        </div>
                    @else
                        <input type="text" class="form-control form-control-sm overview-item-text" placeholder="Text" value="{{ $item['text'] ?? '' }}">
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger overview-remove-item" title="{{ translate('delete') }}">
                    <span class="material-icons fs-16">delete</span>
                </button>
            </div>
        </div>
    @empty
        <p class="text-muted fs-12 overview-empty-hint mb-0">{{ translate('no_items_added_yet') }}</p>
    @endforelse
</div>
