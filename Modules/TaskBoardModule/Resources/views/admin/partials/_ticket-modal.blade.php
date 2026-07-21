<div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl ticket-modal-dialog">
        <form method="post" action="{{ route('admin.task-board.tickets.store') }}" id="ticketForm" class="modal-content ticket-modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="ticketMethod" value="POST">
            <input type="hidden" name="ticket_id" id="ticketId" value="">

            <div class="ticket-modal-topbar">
                <div class="ticket-modal-topbar-main">
                    <div class="ticket-modal-topbar-meta">
                        <span class="ticket-modal-key" id="ticketModalKey">{{ translate('New_Ticket') }}</span>
                        <div class="ticket-modal-topbar-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btnDeleteTicket">{{ translate('Delete') }}</button>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <input type="text"
                           name="title"
                           id="ticketTitle"
                           class="ticket-title-input"
                           required
                           maxlength="255"
                           placeholder="{{ translate('Ticket_title') }}">
                </div>
            </div>

            <div class="ticket-modal-body">
                <div class="ticket-modal-main">
                    <div class="ticket-section">
                        <div class="ticket-section-label">{{ translate('Description') }}</div>
                        <div class="staff-chat-compose-wrap position-relative">
                            @include('taskboardmodule::admin.partials._mention-toolbar')
                            <textarea name="description"
                                      id="ticketDescription"
                                      class="form-control ticket-description-input staff-chat-message-input"
                                      rows="8"
                                      placeholder="{{ translate('Use_@to_tag_staff_or_toolbar_for_entities') }}"></textarea>
                        </div>
                    </div>

                    <div class="ticket-section ticket-activity-section">
                        <div class="ticket-activity-tabs">
                            <button type="button" class="ticket-activity-tab active" data-activity-tab="comments" id="tabCommentsBtn">
                                {{ translate('Comments') }}
                            </button>
                            <button type="button" class="ticket-activity-tab" data-activity-tab="activity" id="tabActivityBtn">
                                {{ translate('Activity') }}
                            </button>
                        </div>

                        <div class="ticket-activity-panel active" data-activity-panel="comments">
                            <div id="ticketCommentsList" class="ticket-comments-list"></div>
                            <div class="staff-chat-compose-wrap position-relative ticket-comment-compose" id="commentComposeWrap">
                                @include('taskboardmodule::admin.partials._mention-toolbar')
                                <textarea id="ticketCommentBody"
                                          class="form-control staff-chat-message-input border-0"
                                          rows="3"
                                          placeholder="{{ translate('Write_a_comment_use_@to_tag') }}"></textarea>
                                <div id="ticketCommentFilesPreview" class="ticket-comment-files-preview d-flex flex-wrap gap-2 mt-2"></div>
                                <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <label class="ticket-attach-btn mb-0" title="{{ translate('Images') }}">
                                            <span class="material-symbols-outlined">image</span>
                                            <input type="file"
                                                   id="ticketCommentImages"
                                                   class="d-none"
                                                   multiple
                                                   accept=".{{ implode(',.', array_column(IMAGEEXTENSION, 'key')) }},image/*">
                                        </label>
                                        <label class="ticket-attach-btn mb-0" title="{{ translate('Files') }}">
                                            <span class="material-symbols-outlined">attach_file</span>
                                            <input type="file"
                                                   id="ticketCommentFiles"
                                                   class="d-none"
                                                   multiple
                                                   accept=".{{ implode(',.', array_column(ALLOWED_FILE_TYPE, 'key')) }}">
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="btnAddComment">{{ translate('Add_Comment') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-activity-panel" data-activity-panel="activity">
                            <div id="ticketActivityList" class="ticket-activity-list"></div>
                        </div>
                    </div>
                </div>

                <aside class="ticket-modal-sidebar">
                    <div class="ticket-side-field">
                        <div class="ticket-side-label">{{ translate('Created_By') }}</div>
                        <div class="ticket-created-by" id="ticketCreatedBy">
                            <span class="ticket-created-by-empty text-muted">—</span>
                        </div>
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketColumnId">{{ translate('Status') }}</label>
                        <select name="column_id" id="ticketColumnId" class="form-select form-select-sm" required>
                            @foreach($columns as $column)
                                <option value="{{ $column->id }}">{{ $column->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketAssignees">{{ translate('Assignees') }}</label>
                        <select name="assignee_ids[]" id="ticketAssignees" class="form-select form-select-sm" multiple>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ trim($employee->first_name.' '.$employee->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketStartDate">{{ translate('Start_date') }}</label>
                        <input type="date" name="start_date" id="ticketStartDate" class="form-control form-control-sm">
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketEndDate">{{ translate('End_date') }}</label>
                        <input type="date" name="end_date" id="ticketEndDate" class="form-control form-control-sm">
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketBookings">{{ translate('Linked_bookings') }}</label>
                        <select name="booking_ids[]" id="ticketBookings" class="form-select form-select-sm" multiple></select>
                    </div>

                    <div class="ticket-side-field">
                        <label class="ticket-side-label" for="ticketLeads">{{ translate('Linked_leads') }}</label>
                        <select name="lead_ids[]" id="ticketLeads" class="form-select form-select-sm" multiple></select>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>
