"use strict";

(function ($) {
    if (!$ || !window.taskBoardRoutes) {
        return;
    }

    var routes = window.taskBoardRoutes;
    var csrf = window.taskBoardCsrf;

    function toastSuccess(msg) {
        if (window.toastr) toastr.success(msg);
    }

    function toastError(msg) {
        if (window.toastr) toastr.error(msg || "Something went wrong");
    }

    function ajaxHeaders() {
        return {
            "X-CSRF-TOKEN": csrf,
            Accept: "application/json",
        };
    }

    function initSelect2() {
        if (!$.fn.select2) return;
        $("#ticketAssignees").select2({
            width: "100%",
            dropdownParent: $("#ticketModal"),
            placeholder: "Select assignees",
        });
        $("#ticketBookings").select2({
            width: "100%",
            dropdownParent: $("#ticketModal"),
            placeholder: "Search bookings",
            ajax: {
                url: routes.searchBookings,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term || "" };
                },
                processResults: function (data) {
                    return {
                        results: (data.results || []).map(function (item) {
                            return { id: item.id, text: item.text };
                        }),
                    };
                },
            },
        });
        $("#ticketLeads").select2({
            width: "100%",
            dropdownParent: $("#ticketModal"),
            placeholder: "Search leads",
            ajax: {
                url: routes.searchLeads,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term || "" };
                },
                processResults: function (data) {
                    return {
                        results: (data.results || []).map(function (item) {
                            return { id: item.id, text: item.text };
                        }),
                    };
                },
            },
        });
    }

    function syncAssigneeFilterUi(selected) {
        var group = document.getElementById("taskBoardAssigneeAvatars");
        var inputs = document.getElementById("taskBoardAssigneeInputs");
        if (!group || !inputs) return;

        selected = (selected || []).map(String);
        var isAll = selected.length === 0;

        group.querySelectorAll("[data-employee-id]").forEach(function (el) {
            var id = String(el.getAttribute("data-employee-id") || "");
            if (id === "all") {
                el.classList.toggle("is-active", isAll);
                return;
            }
            var active = selected.indexOf(id) >= 0;
            el.classList.toggle("is-active", active);
            if (el.classList.contains("dropdown-item")) {
                el.classList.toggle("active", active);
            }
        });

        group.setAttribute("data-selected", JSON.stringify(selected));
        inputs.innerHTML = "";
        selected.forEach(function (id) {
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "assignee_ids[]";
            input.value = id;
            inputs.appendChild(input);
        });
    }

    function toggleAssigneeFilter(employeeId) {
        var group = document.getElementById("taskBoardAssigneeAvatars");
        if (!group) return;
        var selected = [];
        try {
            selected = JSON.parse(group.getAttribute("data-selected") || "[]") || [];
        } catch (e) {
            selected = [];
        }
        selected = selected.map(String);

        if (String(employeeId) === "all") {
            selected = [];
        } else {
            var id = String(employeeId);
            var idx = selected.indexOf(id);
            if (idx >= 0) {
                selected.splice(idx, 1);
            } else {
                selected.push(id);
            }
        }

        syncAssigneeFilterUi(selected);
        $("#taskBoardFilterForm").trigger("submit");
    }

    window.taskBoardAvatarImgError = function (img) {
        if (!img) return;
        var parent = img.closest(".day-detail-avatar, .dropdown-item");
        if (!parent) return;
        var title = parent.getAttribute("title") || "E";
        var initials = title
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (part) {
                return part.charAt(0);
            })
            .join("")
            .toUpperCase() || "E";
        var letter = (initials.replace(/[^A-Za-z]/g, "").charAt(0) || "e").toLowerCase();
        var letterClass = "day-detail-avatar-letter-" + letter;
        var span = document.createElement("span");
        span.className = img.closest(".dropdown-item")
            ? ("day-detail-mini-avatar day-detail-avatar-initials " + letterClass).trim()
            : "day-detail-avatar-initials";
        span.textContent = initials;
        img.replaceWith(span);
        if (parent.classList.contains("day-detail-avatar")) {
            parent.classList.add("has-initials", letterClass);
        }
    };

    window.taskBoardCardAvatarError = function (img) {
        if (!img || img.dataset.fallbackApplied === "1") return;
        img.dataset.fallbackApplied = "1";
        var name = img.getAttribute("title") || img.getAttribute("alt") || "E";
        var initials = name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (part) {
                return part.charAt(0);
            })
            .join("")
            .toUpperCase() || "E";
        var letter = (initials.replace(/[^A-Za-z]/g, "").charAt(0) || "e").toLowerCase();
        var span = document.createElement("span");
        if (img.classList.contains("ticket-created-by-avatar")) {
            span.className = "ticket-created-by-avatar is-fallback day-detail-avatar-letter-" + letter;
        } else if (img.classList.contains("task-card-creator-avatar")) {
            span.className = "task-card-creator-avatar task-card-avatar-fallback day-detail-avatar-letter-" + letter;
        } else {
            span.className = "task-card-avatar task-card-avatar-fallback day-detail-avatar-letter-" + letter;
        }
        span.title = name;
        span.textContent = initials;
        img.replaceWith(span);
    };

    function resolveMentions(text) {
        if (typeof window.resolveStaffChatTags === "function") {
            return window.resolveStaffChatTags(text);
        }
        return text;
    }

    function ticketCodeFromId(id) {
        return "TB-" + String(id || "").replace(/-/g, "").slice(0, 4).toUpperCase();
    }

    function initialsFromName(name) {
        var parts = String(name || "")
            .trim()
            .split(/\s+/)
            .filter(Boolean);
        if (!parts.length) return "U";
        return (parts[0].charAt(0) + (parts[1] ? parts[1].charAt(0) : "")).toUpperCase();
    }

    function setActivityTab(tab) {
        $(".ticket-activity-tab").removeClass("active");
        $('.ticket-activity-tab[data-activity-tab="' + tab + '"]').addClass("active");
        $(".ticket-activity-panel").removeClass("active");
        $('.ticket-activity-panel[data-activity-panel="' + tab + '"]').addClass("active");
    }

    var commentPendingFiles = [];

    function clearCommentPendingFiles() {
        commentPendingFiles = [];
        $("#ticketCommentImages").val("");
        $("#ticketCommentFiles").val("");
        $("#ticketCommentFilesPreview").empty();
    }

    function renderCommentPendingFiles() {
        var html = "";
        commentPendingFiles.forEach(function (file, index) {
            html +=
                '<span class="ticket-pending-file">' +
                escapeHtml(file.name) +
                ' <button type="button" data-index="' +
                index +
                '" class="ticket-pending-file-remove">&times;</button></span>';
        });
        $("#ticketCommentFilesPreview").html(html);
    }

    function appendPendingFiles(fileList) {
        Array.from(fileList || []).forEach(function (file) {
            commentPendingFiles.push(file);
        });
        renderCommentPendingFiles();
    }

    function renderCommentAttachments(attachments) {
        var html = "";
        (attachments || []).forEach(function (file) {
            if (file.is_image) {
                html +=
                    '<a class="ticket-comment-file" href="' +
                    escapeHtml(file.url) +
                    '" target="_blank" rel="noopener"><img src="' +
                    escapeHtml(file.url) +
                    '" alt=""></a>';
            } else {
                html +=
                    '<a class="ticket-comment-file" href="' +
                    escapeHtml(file.url) +
                    '" target="_blank" rel="noopener"><span class="material-symbols-outlined" style="font-size:16px">draft</span>' +
                    escapeHtml(file.name || "File") +
                    "</a>";
            }
        });
        return html ? '<div class="d-flex flex-wrap gap-2 mt-2">' + html + "</div>" : "";
    }

    function renderCreatedBy(creator) {
        var wrap = $("#ticketCreatedBy");
        if (!wrap.length) return;

        if (!creator || !creator.name) {
            wrap.html('<span class="ticket-created-by-empty text-muted">—</span>');
            return;
        }

        var letter = String(creator.initials || creator.name || "E")
            .replace(/[^A-Za-z]/g, "")
            .charAt(0)
            .toLowerCase() || "e";
        var letterClass = "day-detail-avatar-letter-" + letter;
        var avatarHtml = creator.photo
            ? '<img class="ticket-created-by-avatar" src="' +
              escapeHtml(creator.photo) +
              '" alt="' +
              escapeHtml(creator.name) +
              '" onerror="window.taskBoardCardAvatarError && window.taskBoardCardAvatarError(this)">'
            : '<span class="ticket-created-by-avatar is-fallback ' +
              letterClass +
              '">' +
              escapeHtml(creator.initials || initialsFromName(creator.name)) +
              "</span>";

        wrap.html(
            avatarHtml +
                '<span class="ticket-created-by-name">' +
                escapeHtml(creator.name) +
                "</span>"
        );
    }

    function resetTicketForm() {
        $("#ticketForm")[0].reset();
        $("#ticketMethod").val("POST");
        $("#ticketId").val("");
        $("#ticketForm").attr("action", routes.ticketsStore);
        $("#ticketModalKey").text("New Ticket");
        $("#ticketTitle").val("");
        $("#btnDeleteTicket").addClass("d-none");
        $("#ticketAssignees").val(null).trigger("change");
        $("#ticketBookings").empty().trigger("change");
        $("#ticketLeads").empty().trigger("change");
        renderCreatedBy(window.taskBoardCurrentUser || null);
        clearCommentPendingFiles();
        $("#ticketCommentsList").html('<div class="ticket-empty-state">Save the ticket first to add comments.</div>');
        $("#ticketActivityList").html('<div class="ticket-empty-state">No activity yet.</div>');
        window.staffChatTagRegistry = [];
        $("#commentComposeWrap").addClass("opacity-50 pe-none");
        $("#btnAddComment").prop("disabled", true);
        setActivityTab("comments");
    }

    function openCreateTicket(columnId) {
        resetTicketForm();
        if (columnId) {
            $("#ticketColumnId").val(columnId);
        }
        $("#ticketModal").modal("show");
        setTimeout(function () {
            $("#ticketTitle").trigger("focus");
        }, 200);
    }

    function renderComments(comments) {
        var html = "";
        (comments || []).forEach(function (c) {
            html +=
                '<div class="ticket-comment-item">' +
                '<div class="ticket-comment-avatar">' +
                escapeHtml(initialsFromName(c.user)) +
                "</div>" +
                '<div class="ticket-comment-card">' +
                '<div class="ticket-comment-meta"><strong>' +
                escapeHtml(c.user || "User") +
                "</strong> · " +
                escapeHtml(c.created_at || "") +
                "</div>" +
                '<div class="ticket-comment-body">' +
                (c.body_html || escapeHtml(c.body || "")) +
                "</div>" +
                renderCommentAttachments(c.attachments || []) +
                "</div></div>";
        });
        $("#ticketCommentsList").html(html || '<div class="ticket-empty-state">No comments yet.</div>');
    }

    function renderActivity(items) {
        var html = "";
        (items || []).forEach(function (a) {
            html +=
                '<div class="task-activity-item">' +
                '<div class="ticket-activity-avatar">' +
                escapeHtml(initialsFromName(a.actor)) +
                "</div>" +
                '<div class="ticket-activity-card">' +
                '<div class="ticket-activity-meta"><strong>' +
                escapeHtml(a.actor || "System") +
                "</strong> · " +
                escapeHtml(a.action || "") +
                "</div>" +
                '<div class="text-muted small">' +
                escapeHtml(a.created_at || "") +
                "</div></div></div>";
        });
        $("#ticketActivityList").html(html || '<div class="ticket-empty-state">No activity yet.</div>');
    }

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function openTicket(ticketId) {
        $.ajax({
            url: routes.ticketsShow + "/" + ticketId,
            headers: ajaxHeaders(),
            success: function (res) {
                var ticket = res.ticket;
                resetTicketForm();
                $("#ticketMethod").val("PUT");
                $("#ticketId").val(ticket.id);
                $("#ticketForm").attr("action", routes.ticketsUpdate + "/" + ticket.id);
                $("#ticketModalKey").text(ticketCodeFromId(ticket.id));
                $("#ticketTitle").val(ticket.title);
                $("#ticketDescription").val(ticket.description || "");
                $("#ticketColumnId").val(ticket.column_id);
                $("#ticketStartDate").val(ticket.start_date || "");
                $("#ticketEndDate").val(ticket.end_date || "");
                $("#ticketAssignees").val(ticket.assignee_ids || []).trigger("change");
                $("#btnDeleteTicket").removeClass("d-none").data("id", ticket.id);
                $("#commentComposeWrap").removeClass("opacity-50 pe-none");
                $("#btnAddComment").prop("disabled", false);
                renderCreatedBy(ticket.created_by);

                var bookingSelect = $("#ticketBookings");
                bookingSelect.empty();
                (ticket.links || [])
                    .filter(function (l) {
                        return l.type === "booking";
                    })
                    .forEach(function (l) {
                        bookingSelect.append(new Option(l.label, l.linkable_id, true, true));
                    });
                bookingSelect.trigger("change");

                var leadSelect = $("#ticketLeads");
                leadSelect.empty();
                (ticket.links || [])
                    .filter(function (l) {
                        return l.type === "lead";
                    })
                    .forEach(function (l) {
                        leadSelect.append(new Option(l.label, l.linkable_id, true, true));
                    });
                leadSelect.trigger("change");

                renderComments(ticket.comments);
                renderActivity(ticket.activity);
                setActivityTab("comments");
                $("#ticketModal").modal("show");
            },
            error: function () {
                toastError("Failed to load ticket");
            },
        });
    }

    function initSortables() {
        if (typeof Sortable === "undefined") return;

        var board = document.getElementById("taskBoardColumns");
        if (board) {
            Sortable.create(board, {
                animation: 150,
                handle: ".column-handle",
                draggable: ".task-column",
                ghostClass: "task-board-ghost-col",
                onEnd: function () {
                    var order = [];
                    $("#taskBoardColumns .task-column").each(function () {
                        order.push($(this).data("column-id"));
                    });
                    $.ajax({
                        url: routes.columnsReorder,
                        method: "POST",
                        headers: ajaxHeaders(),
                        data: { order: order },
                    });
                },
            });
        }

        $(".task-column-body").each(function () {
            Sortable.create(this, {
                group: "tickets",
                animation: 150,
                draggable: ".task-card",
                ghostClass: "sortable-ghost",
                onAdd: persistMove,
                onUpdate: persistMove,
            });
        });
    }

    function persistMove(evt) {
        var card = evt.item;
        var ticketId = card.getAttribute("data-ticket-id");
        var columnId = evt.to.getAttribute("data-column-id");
        var orderedIds = [];
        $(evt.to)
            .children(".task-card")
            .each(function () {
                orderedIds.push(this.getAttribute("data-ticket-id"));
            });
        var position = orderedIds.indexOf(ticketId);
        card.setAttribute("data-column-id", columnId);

        $.ajax({
            url: routes.ticketsMove + "/" + ticketId + "/move",
            method: "POST",
            headers: ajaxHeaders(),
            data: {
                column_id: columnId,
                position: position < 0 ? 0 : position,
                ordered_ids: orderedIds,
            },
            error: function () {
                toastError("Failed to move ticket");
                window.location.reload();
            },
        });
    }

    // Mentions: target textarea in the same compose wrap
    $(document).on("click", ".task-board-page .staff-tag-trigger, #ticketModal .staff-tag-trigger", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var wrap = $(this).closest(".staff-chat-compose-wrap");
        var textarea = wrap.find(".staff-chat-message-input")[0];
        if (!textarea) return;
        textarea.focus();
        // Re-use chat picker by temporarily swapping querySelector target
        var others = document.querySelectorAll(".staff-chat-message-input");
        others.forEach(function (el) {
            el.classList.remove("staff-chat-message-input");
            el.classList.add("staff-chat-message-input-temp");
        });
        textarea.classList.remove("staff-chat-message-input-temp");
        textarea.classList.add("staff-chat-message-input");
        $(this).trigger("click.taskboard-fallback");
        // Fire original handler path by dispatching a synthetic click after class swap isn't enough
        // because we stopped propagation. Call show via @ insert path:
        var type = $(this).data("tag-type");
        var event = new KeyboardEvent("keyup", { bubbles: true });
        // Open picker manually using chat's global search
        var picker = document.getElementById("staffChatEntityPicker");
        var pickerInput = document.getElementById("staffChatEntitySearchInput");
        if (picker && pickerInput) {
            picker.classList.remove("d-none");
            var rect = textarea.getBoundingClientRect();
            picker.style.top = Math.min(window.innerHeight - 280, rect.bottom + 8) + "px";
            picker.style.left = Math.max(12, rect.left) + "px";
            pickerInput.value = "";
            pickerInput.focus();
            pickerInput.dispatchEvent(new Event("input", { bubbles: true }));
            // Store active type by simulating toolbar: chat uses activeTagType private.
            // Force type via temporary data attribute read in overridden load - set window hook:
            window.__taskBoardTagType = type;
            // Trigger staff search with type by calling entity search ourselves if needed
            $.get({
                url: window.staffChatEntitySearchUrl,
                dataType: "json",
                data: { q: "", type: type },
                success: function (response) {
                    var results = document.getElementById("staffChatEntityResults");
                    if (!results) return;
                    results.innerHTML = "";
                    (response.results || []).forEach(function (item) {
                        var btn = document.createElement("button");
                        btn.type = "button";
                        btn.className = "list-group-item list-group-item-action py-2 px-2";
                        btn.innerHTML =
                            '<div class="fw-medium">' +
                            escapeHtml(item.label) +
                            '</div><div class="small text-muted">' +
                            escapeHtml(item.subtitle || "") +
                            "</div>";
                        btn.addEventListener("click", function () {
                            var labels = window.staffChatTypeLabels || {};
                            var display =
                                "@" + (labels[item.type] || item.type) + ":" + String(item.label || "").replace(/[\[\]]/g, "");
                            var token = "@[" + String(item.label || "").replace(/[\[\]]/g, "") + "](" + item.type + ":" + item.id + ")";
                            window.staffChatTagRegistry = window.staffChatTagRegistry || [];
                            window.staffChatTagRegistry.push({ display: display, token: token });
                            var start = textarea.selectionStart;
                            var end = textarea.selectionEnd;
                            var value = textarea.value;
                            textarea.value = value.slice(0, start) + display + " " + value.slice(end);
                            textarea.focus();
                            picker.classList.add("d-none");
                            // restore classes
                            document.querySelectorAll(".staff-chat-message-input-temp").forEach(function (el) {
                                el.classList.add("staff-chat-message-input");
                                el.classList.remove("staff-chat-message-input-temp");
                            });
                        });
                        results.appendChild(btn);
                    });
                },
            });
        }
    });

    $(document).on("click", "#btnNewTicket", function () {
        openCreateTicket();
    });

    $(document).on("click", ".btn-add-ticket-in-column", function () {
        openCreateTicket($(this).data("column-id"));
    });

    $(document).on("click", ".task-card", function (e) {
        if ($(e.target).closest(".dropdown").length) return;
        openTicket($(this).data("ticket-id"));
    });

    $(document).on("click", ".btn-edit-column", function () {
        $("#columnModalTitle").text("Edit Column");
        $("#columnId").val($(this).data("id"));
        $("#columnName").val($(this).data("name"));
        $("#columnColor").val($(this).data("color"));
        $("#columnMethod").val("PUT");
        $("#columnForm").attr("action", routes.columnsUpdate + "/" + $(this).data("id"));
        // Spoof method for Laravel
        if (!$("#columnForm input[name=_method]").length) {
            $("#columnForm").append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $("#columnForm input[name=_method]").val("PUT");
        }
        $("#columnModal").modal("show");
    });

    $("#columnModal").on("show.bs.modal", function (e) {
        if (!$(e.relatedTarget) || !$(e.relatedTarget).hasClass("btn-edit-column")) {
            if (!$("#columnId").val()) {
                $("#columnModalTitle").text("Add Column");
                $("#columnForm").attr("action", "{{-- set in blade --}}");
            }
        }
    });

    // Reset column modal when opened via Add Column button
    $('[data-bs-target="#columnModal"]').on("click", function () {
        $("#columnModalTitle").text("Add Column");
        $("#columnId").val("");
        $("#columnName").val("");
        $("#columnColor").val("#64748b");
        $("#columnMethod").val("POST");
        $("#columnForm").attr("action", $("#columnForm").data("store-action") || $("#columnForm").attr("action"));
        $("#columnForm input[name=_method]").val("POST");
    });

    $("#columnForm").on("submit", function (e) {
        var method = $("#columnMethod").val();
        if (method === "PUT") {
            // ensure PUT
            if ($("#columnForm input[name=_method]").length) {
                $("#columnForm input[name=_method]").val("PUT");
            }
        }
    });

    $(document).on("click", ".btn-delete-column", function () {
        if (!confirm("Delete this column and all its tickets?")) return;
        var id = $(this).data("id");
        $.ajax({
            url: routes.columnsDestroy + "/" + id,
            method: "POST",
            headers: ajaxHeaders(),
            data: { _method: "DELETE" },
            success: function () {
                toastSuccess("Column deleted");
                window.location.reload();
            },
            error: function () {
                toastError("Failed to delete column");
            },
        });
    });

    $("#ticketForm").on("submit", function (e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        var description = resolveMentions($("#ticketDescription").val() || "");
        fd.set("description", description);

        var method = $("#ticketMethod").val();
        if (method === "PUT") {
            fd.set("_method", "PUT");
        }

        $.ajax({
            url: $(form).attr("action"),
            method: "POST",
            headers: ajaxHeaders(),
            data: fd,
            processData: false,
            contentType: false,
            success: function () {
                toastSuccess("Ticket saved");
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || "Failed to save ticket";
                toastError(msg);
            },
        });
    });

    $("#btnDeleteTicket").on("click", function () {
        var id = $(this).data("id");
        if (!id || !confirm("Delete this ticket?")) return;
        $.ajax({
            url: routes.ticketsDestroy + "/" + id,
            method: "POST",
            headers: ajaxHeaders(),
            data: { _method: "DELETE" },
            success: function () {
                toastSuccess("Ticket deleted");
                window.location.reload();
            },
            error: function () {
                toastError("Failed to delete ticket");
            },
        });
    });

    $("#btnAddComment").on("click", function () {
        var ticketId = $("#ticketId").val();
        if (!ticketId) {
            toastError("Save the ticket first");
            return;
        }
        var body = resolveMentions($("#ticketCommentBody").val() || "");
        if (!body.trim() && commentPendingFiles.length === 0) {
            toastError("Please write a comment or attach a file");
            return;
        }

        var fd = new FormData();
        fd.append("body", body);
        commentPendingFiles.forEach(function (file) {
            fd.append("files[]", file);
        });

        $.ajax({
            url: routes.commentsStore + "/" + ticketId + "/comments",
            method: "POST",
            headers: ajaxHeaders(),
            data: fd,
            processData: false,
            contentType: false,
            success: function () {
                $("#ticketCommentBody").val("");
                clearCommentPendingFiles();
                openTicket(ticketId);
                toastSuccess("Comment added");
            },
            error: function (xhr) {
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                    "Failed to add comment";
                toastError(msg);
            },
        });
    });

    $(document).on("change", "#ticketCommentImages, #ticketCommentFiles", function () {
        appendPendingFiles(this.files);
        this.value = "";
    });

    $(document).on("click", ".ticket-pending-file-remove", function () {
        var index = parseInt($(this).data("index"), 10);
        if (!isNaN(index)) {
            commentPendingFiles.splice(index, 1);
            renderCommentPendingFiles();
        }
    });

    $(function () {
        // Prefer board-scoped mention toolbar targeting over staff-chat default (first textarea only).
        $(document).off("click", ".staff-tag-trigger");

        // Store original store action
        $("#columnForm").data("store-action", $("#columnForm").attr("action"));
        initSelect2();
        initSortables();

        $(document).on("click", ".task-board-sort-option", function () {
            var sort = $(this).data("sort");
            $("#taskBoardSortValue").val(sort);
            $("#taskBoardFilterForm").trigger("submit");
        });

        $(document).on("click", ".ticket-activity-tab", function () {
            setActivityTab($(this).data("activity-tab"));
        });

        $(document).on("click", "#taskBoardAssigneeAvatars [data-employee-id]", function (e) {
            var $btn = $(this);
            if ($btn.hasClass("is-more")) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            toggleAssigneeFilter($btn.attr("data-employee-id"));
        });
    });
})(window.jQuery);
