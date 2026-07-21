<div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.task-board.columns.store') }}" id="columnForm" class="modal-content">
            @csrf
            <input type="hidden" name="_method" id="columnMethod" value="POST">
            <input type="hidden" name="column_id" id="columnId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="columnModalTitle">{{ translate('Add_Column') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ translate('Name') }}</label>
                    <input type="text" name="name" id="columnName" class="form-control" required maxlength="120">
                </div>
                <div class="mb-0">
                    <label class="form-label">{{ translate('Color') }}</label>
                    <input type="color" name="color" id="columnColor" class="form-control form-control-color" value="#64748b">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>
