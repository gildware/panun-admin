<div class="task-column" data-column-id="{{ $column->id }}" style="--column-color: {{ $column->color }}">
    <div class="task-column-header column-handle">
        <span class="task-column-color"></span>
        <h3 class="task-column-title">{{ $column->name }}</h3>
        <span class="task-column-count">{{ $column->tickets->count() }}</span>
        <div class="dropdown">
            <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                <span class="material-symbols-outlined" style="font-size:18px">more_vert</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <button type="button" class="dropdown-item btn-edit-column"
                            data-id="{{ $column->id }}"
                            data-name="{{ $column->name }}"
                            data-color="{{ $column->color }}">
                        {{ translate('Edit') }}
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item text-danger btn-delete-column" data-id="{{ $column->id }}">
                        {{ translate('Delete') }}
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item btn-add-ticket-in-column" data-column-id="{{ $column->id }}">
                        {{ translate('Add_Ticket') }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <div class="task-column-body" data-column-id="{{ $column->id }}">
        @foreach($column->tickets as $ticket)
            @include('taskboardmodule::admin.partials._ticket-card', ['ticket' => $ticket, 'column' => $column])
        @endforeach
    </div>
</div>
