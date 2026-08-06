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

    function showModal(selector) {
        var el = document.querySelector(selector);
        if (!el) {
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(el).show();
            return;
        }
        $(selector).modal("show");
    }

    function destroySelect2() {
        ["#ticketAssignees", "#ticketBookings", "#ticketLeads"].forEach(function (selector) {
            var $el = $(selector);
            if (!$el.length) {
                return;
            }
            if ($el.hasClass("select2-hidden-accessible")) {
                try {
                    $el.select2("destroy");
                } catch (e) {}
            }
        });
    }

    function initSelect2() {
        if (!$.fn.select2 || !document.getElementById("ticketModal")) {
            return;
        }

        destroySelect2();

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

    function hideTaskBoardModals() {
        ["#ticketModal", "#columnModal"].forEach(function (selector) {
            var el = document.querySelector(selector);
            if (!el) {
                return;
            }
            if (window.bootstrap && window.bootstrap.Modal) {
                var instance = window.bootstrap.Modal.getInstance(el);
                if (instance) {
                    instance.hide();
                }
            } else {
                $(selector).modal("hide");
            }
        });
    }

    function hoistTaskBoardModals(root) {
        root = taskBoardRoot(root);
        ["ticketModal", "columnModal"].forEach(function (id) {
            var keeper = root.querySelector ? root.querySelector("#" + id) : null;
            if (!keeper) {
                keeper = document.getElementById(id);
            }

            document.querySelectorAll('[id="' + id + '"]').forEach(function (el) {
                if (el !== keeper) {
                    el.remove();
                }
            });

            if (keeper && keeper.parentElement !== document.body) {
                document.body.appendChild(keeper);
            }
        });

        var picker = root.querySelector ? root.querySelector("#staffChatEntityPicker") : null;
        if (!picker) {
            picker = document.getElementById("staffChatEntityPicker");
        }
        document.querySelectorAll('[id="staffChatEntityPicker"]').forEach(function (el) {
            if (el !== picker) {
                el.remove();
            }
        });

        var ticketModal = document.getElementById("ticketModal");
        if (picker && ticketModal && !ticketModal.contains(picker)) {
            ticketModal.appendChild(picker);
        }
    }

    function prepareTaskBoardUi() {
        $(document).off("click.taskboard", ".staff-tag-trigger");

        var $columnForm = $("#columnForm");
        if ($columnForm.length) {
            $columnForm.data("store-action", $columnForm.attr("action"));
        }
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
        showModal("#ticketModal");
        setTimeout(function () {
            $("#ticketTitle").trigger("focus");
        }, 200);
    }

    function formatCommentBody(comment) {
        if (comment.body_html) {
            return comment.body_html;
        }
        if (typeof window.formatStaffChatMessageHtml === "function") {
            return window.formatStaffChatMessageHtml(comment.body || "");
        }
        return escapeHtml(comment.body || "");
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
                formatCommentBody(c) +
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
        resetTicketForm();
        $("#ticketModalKey").text("Loading...");
        $("#ticketModal").addClass("ticket-modal-loading");
        showModal("#ticketModal");

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
                initComposeHighlights(document.getElementById("ticketModal") || document);
                $("#ticketDescription").val(ticket.description || "");
                scheduleComposeHighlight(document.getElementById("ticketDescription"));
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
                $("#ticketModal").removeClass("ticket-modal-loading");
            },
            error: function () {
                $("#ticketModal").removeClass("ticket-modal-loading");
                hideTaskBoardModals();
                toastError("Failed to load ticket");
            },
        });
    }

    var suppressTaskCardClick = false;

    function taskBoardRoot(root) {
        if (!root) {
            return document;
        }
        if (root.nodeType === 1) {
            return root;
        }
        if (root.detail && root.detail.root) {
            return root.detail.root;
        }
        return document;
    }

    function isManualSortMode(root) {
        root = taskBoardRoot(root);
        var input = root.querySelector
            ? root.querySelector("#taskBoardSortValue")
            : document.getElementById("taskBoardSortValue");
        return !input || String(input.value || "position") === "position";
    }

    function destroySortables(root) {
        root = taskBoardRoot(root);
        if (typeof Sortable === "undefined" || !root.querySelectorAll) {
            return;
        }

        var board = root.querySelector("#taskBoardColumns");
        if (board) {
            var boardSortable = Sortable.get(board);
            if (boardSortable) {
                boardSortable.destroy();
            }
        }

        root.querySelectorAll(".task-column-body").forEach(function (body) {
            var ticketSortable = Sortable.get(body);
            if (ticketSortable) {
                ticketSortable.destroy();
            }
        });
    }

    function setTaskBoardDragEnabled(root, enabled) {
        root = taskBoardRoot(root);
        var page = root.querySelector ? root.querySelector(".task-board-page") : null;
        if (page) {
            page.classList.toggle("task-board-drag-disabled", !enabled);
        }
    }

    function initSortables(root) {
        root = taskBoardRoot(root);
        if (typeof Sortable === "undefined") {
            return;
        }

        destroySortables(root);

        var manualSort = isManualSortMode(root);
        setTaskBoardDragEnabled(root, manualSort);

        var board = root.querySelector("#taskBoardColumns");
        if (board) {
            Sortable.create(board, {
                animation: 150,
                handle: ".column-handle",
                draggable: ".task-column",
                ghostClass: "task-board-ghost-col",
                scroll: true,
                bubbleScroll: true,
                forceAutoScrollFallback: true,
                onEnd: function () {
                    var order = [];
                    $(board)
                        .find(".task-column")
                        .each(function () {
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

        if (!manualSort) {
            return;
        }

        root.querySelectorAll(".task-column-body").forEach(function (body) {
            Sortable.create(body, {
                group: "tickets",
                animation: 150,
                draggable: ".task-card",
                ghostClass: "sortable-ghost",
                delay: 150,
                delayOnTouchOnly: true,
                scroll: true,
                bubbleScroll: true,
                forceAutoScrollFallback: true,
                onStart: function () {
                    suppressTaskCardClick = false;
                },
                onEnd: function (evt) {
                    if (evt.from !== evt.to || evt.oldIndex !== evt.newIndex) {
                        suppressTaskCardClick = true;
                        window.setTimeout(function () {
                            suppressTaskCardClick = false;
                        }, 0);
                    }
                },
                onAdd: persistMove,
                onUpdate: persistMove,
            });
        });
    }

    function initComposeHighlights(root) {
        if (typeof window.initStaffChatComposeHighlights !== "function") {
            return;
        }
        var scope = root && root.querySelector ? root : document;
        window.initStaffChatComposeHighlights(scope);
    }

    function syncComposeHighlight(textarea) {
        if (!textarea) {
            return;
        }
        if (typeof window.ensureStaffChatComposeTextarea === "function") {
            window.ensureStaffChatComposeTextarea(textarea);
            return;
        }
        initComposeHighlights(textarea.closest("#ticketModal") || document);
        if (typeof window.syncStaffChatComposeHighlight === "function") {
            window.syncStaffChatComposeHighlight(textarea);
        }
    }

    function scheduleComposeHighlight(textarea) {
        if (!textarea) {
            return;
        }
        syncComposeHighlight(textarea);
        requestAnimationFrame(function () {
            syncComposeHighlight(textarea);
        });
        window.setTimeout(function () {
            syncComposeHighlight(textarea);
        }, 60);
    }

    function bootTaskBoard(root) {
        root = taskBoardRoot(root);
        if (!root.querySelector || !root.querySelector(".task-board-page")) {
            return;
        }

        hideTaskBoardModals();
        prepareTaskBoardUi();
        hoistTaskBoardModals(root);
        initSelect2();
        initComposeHighlights(root);
        initSortables(root);
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

    window.bootTaskBoardPage = bootTaskBoard;

    if (!window.__taskBoardHandlersInstalled) {
        window.__taskBoardHandlersInstalled = true;

    $(document).on("click", "#btnNewTicket", function () {
        openCreateTicket();
    });

    $(document).on("click", ".btn-add-ticket-in-column", function () {
        openCreateTicket($(this).data("column-id"));
    });

    $(document).on("click", ".task-card", function (e) {
        if (suppressTaskCardClick) return;
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
        showModal("#columnModal");
    });

    $(document).on("show.bs.modal", "#columnModal", function (e) {
        if (!$(e.relatedTarget) || !$(e.relatedTarget).hasClass("btn-edit-column")) {
            if (!$("#columnId").val()) {
                $("#columnModalTitle").text("Add Column");
                $("#columnForm").attr("action", $("#columnForm").data("store-action") || $("#columnForm").attr("action"));
            }
        }
    });

    $(document).on("click", '[data-bs-target="#columnModal"]', function () {
        $("#columnModalTitle").text("Add Column");
        $("#columnId").val("");
        $("#columnName").val("");
        $("#columnColor").val("#64748b");
        $("#columnMethod").val("POST");
        $("#columnForm").attr("action", $("#columnForm").data("store-action") || $("#columnForm").attr("action"));
        $("#columnForm input[name=_method]").val("POST");
    });

    $(document).on("submit", "#columnForm", function () {
        var method = $("#columnMethod").val();
        if (method === "PUT") {
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

    $(document).on("submit", "#ticketForm", function (e) {
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

    $(document).on("click", "#btnDeleteTicket", function () {
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

    $(document).on("click", "#btnAddComment", function () {
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
                syncComposeHighlight(document.getElementById("ticketCommentBody"));
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

    $(document).on("click", ".task-board-sort-option", function () {
        var sort = $(this).data("sort");
        $("#taskBoardSortValue").val(sort);
        $("#taskBoardFilterForm").trigger("submit");
    });

    $(document).on("click", ".ticket-activity-tab", function () {
        setActivityTab($(this).data("activity-tab"));
    });

    $(document).on("shown.bs.modal", "#ticketModal", function () {
        initComposeHighlights(document.getElementById("ticketModal") || document);
        scheduleComposeHighlight(document.getElementById("ticketDescription"));
        scheduleComposeHighlight(document.getElementById("ticketCommentBody"));
    });

    $(document).on("click", "#ticketModal .staff-tag-trigger", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var wrap = $(this).closest(".staff-chat-compose-wrap");
        var textarea = wrap.find(".staff-chat-message-input")[0];
        if (!textarea || typeof window.openStaffChatEntityPicker !== "function") {
            return;
        }
        textarea.focus();
        window.openStaffChatEntityPicker(textarea, $(this).data("tag-type"));
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

    document.addEventListener("admin:page-loaded", function (event) {
        var nextRoot = event.detail && event.detail.root;
        if (!nextRoot || !nextRoot.querySelector || !nextRoot.querySelector(".task-board-page")) {
            hideTaskBoardModals();
        }
    });
    }

    bootTaskBoard(document.getElementById("admin-main") || document);
})(window.jQuery);
