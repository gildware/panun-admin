"use strict";

(function () {
    var activeTextarea = null;
    var activeTagType = "staff";
    var mentionStart = null;
    var searchTimer = null;

    function getSearchUrl() {
        return window.staffChatEntitySearchUrl || "";
    }

    function getPickerElements() {
        return {
            picker: document.getElementById("staffChatEntityPicker"),
            pickerInput: document.getElementById("staffChatEntitySearchInput"),
            pickerResults: document.getElementById("staffChatEntityResults"),
        };
    }

    var typeLabels = window.staffChatTypeLabels || {
        staff: "Staff",
        customer: "Customer",
        provider: "Provider",
        booking: "Booking",
        service: "Service",
        lead: "Lead",
    };

    function buildToken(type, id, label) {
        var safeLabel = String(label || "").replace(/[\[\]]/g, "").trim();
        return "@[" + safeLabel + "](" + type + ":" + id + ")";
    }

    function buildDisplayTag(type, label) {
        var safeLabel = String(label || "").replace(/[\[\]]/g, "").trim();
        var typeLabel = typeLabels[type] || type;
        return "@" + typeLabel + ":" + safeLabel;
    }

    function registerTag(type, id, label) {
        window.staffChatTagRegistry = window.staffChatTagRegistry || [];
        var display = buildDisplayTag(type, label);
        window.staffChatTagRegistry.push({
            display: display,
            token: buildToken(type, id, label),
        });
        return display;
    }

    function insertTag(type, id, label) {
        var display = registerTag(type, id, label);
        if (mentionStart !== null) {
            replaceMentionQuery(activeTextarea, display);
        } else {
            insertAtCursor(activeTextarea, display + " ");
        }
    }

    function resolveStaffChatTags(message) {
        var resolved = String(message || "");
        (window.staffChatTagRegistry || []).slice().sort(function (a, b) {
            return b.display.length - a.display.length;
        }).forEach(function (entry) {
            resolved = resolved.split(entry.display).join(entry.token);
        });
        return resolved;
    }

    window.resolveStaffChatTags = resolveStaffChatTags;

    function insertAtCursor(textarea, text) {
        if (!textarea) {
            return;
        }
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var value = textarea.value;
        textarea.value = value.slice(0, start) + text + value.slice(end);
        var pos = start + text.length;
        textarea.setSelectionRange(pos, pos);
        textarea.focus();
    }

    function replaceMentionQuery(textarea, token) {
        if (!textarea || mentionStart === null) {
            insertAtCursor(textarea, token + " ");
            return;
        }
        var cursor = textarea.selectionStart;
        var before = textarea.value.slice(0, mentionStart);
        var after = textarea.value.slice(cursor);
        textarea.value = before + token + " " + after;
        var pos = before.length + token.length + 1;
        textarea.setSelectionRange(pos, pos);
        textarea.focus();
        mentionStart = null;
    }

    function hidePicker() {
        var els = getPickerElements();
        if (els.picker) {
            els.picker.classList.add("d-none");
        }
        mentionStart = null;
    }

    var pickerHints = {
        staff: "Search staff by name",
        service: "Search service by name",
        provider: "Search provider by company name or phone",
        customer: "Search customer by name, phone, or email",
        booking: "Search booking by booking ID",
        lead: "Search lead by name, phone, or ID",
    };

    function showPicker(textarea, type) {
        var els = getPickerElements();
        if (!els.picker || !els.pickerInput || !els.pickerResults) {
            return;
        }
        activeTextarea = textarea;
        activeTagType = type;
        els.picker.classList.remove("d-none");
        els.pickerInput.value = "";
        els.pickerResults.innerHTML = "";
        var hintEl = document.getElementById("staffChatEntityPickerHint");
        if (hintEl) {
            hintEl.textContent = pickerHints[type] || "";
        }
        els.pickerInput.placeholder = pickerHints[type] || "Search...";
        els.pickerInput.focus();
        loadResults("");
    }

    function renderResults(items) {
        var els = getPickerElements();
        if (!els.pickerResults) {
            return;
        }
        els.pickerResults.innerHTML = "";
        if (!items.length) {
            els.pickerResults.innerHTML = '<div class="list-group-item small text-muted">' + (window.staffChatNoResultsText || "No results") + "</div>";
            return;
        }
        items.forEach(function (item) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "list-group-item list-group-item-action py-2 px-2";
            var typeLabel = typeLabels[item.type] || item.type;
            btn.innerHTML = "<div class=\"d-flex align-items-center gap-2\">" +
                "<span class=\"badge bg-light text-primary border\">" + escapeHtml(typeLabel) + "</span>" +
                "<span class=\"fw-medium\">" + escapeHtml(item.label) + "</span>" +
                "</div>" +
                (item.subtitle ? "<div class=\"small text-muted ms-1\">" + escapeHtml(item.subtitle) + "</div>" : "");
            btn.addEventListener("click", function () {
                insertTag(item.type, item.id, item.label);
                hidePicker();
            });
            els.pickerResults.appendChild(btn);
        });
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function showLoading() {
        var els = getPickerElements();
        if (els.pickerResults) {
            els.pickerResults.innerHTML = '<div class="list-group-item small text-muted">...</div>';
        }
    }

    function loadResults(query) {
        var searchUrl = getSearchUrl();

        if (!searchUrl && activeTagType === "staff" && Array.isArray(window.staffChatStaffList)) {
            var q = String(query || "").toLowerCase();
            var filtered = window.staffChatStaffList.filter(function (item) {
                return !q || String(item.name).toLowerCase().indexOf(q) !== -1;
            }).slice(0, 12).map(function (item) {
                return { type: "staff", id: item.id, label: item.name, subtitle: item.presence_label || "" };
            });
            renderResults(filtered);
            return;
        }

        if (!searchUrl) {
            renderResults([]);
            return;
        }

        showLoading();

        $.get({
            url: searchUrl,
            dataType: "json",
            data: { q: query, type: activeTagType },
            success: function (response) {
                renderResults((response && response.results) ? response.results : []);
            },
            error: function () {
                if (typeof toastr !== "undefined") {
                    toastr.error("Search failed. Please try again.");
                }
                renderResults([]);
            },
        });
    }

    function handleTextareaKeyup(e) {
        var textarea = e.target;
        if (!textarea.classList.contains("staff-chat-message-input")) {
            return;
        }

        var value = textarea.value;
        var cursor = textarea.selectionStart;
        var before = value.slice(0, cursor);
        var atMatch = before.match(/@([^\s@]*)$/);

        if (atMatch) {
            mentionStart = cursor - atMatch[0].length;
            activeTextarea = textarea;
            activeTagType = "staff";
            var els = getPickerElements();
            if (!els.picker || !els.pickerInput) {
                return;
            }
            els.picker.classList.remove("d-none");
            els.pickerInput.value = atMatch[1] || "";
            loadResults(els.pickerInput.value);
            return;
        }

        if (mentionStart !== null) {
            hidePicker();
        }
    }

    $(document).on("click", ".staff-tag-trigger", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var textarea = document.querySelector(".staff-chat-message-input");
        if (!textarea) {
            return;
        }
        mentionStart = null;
        showPicker(textarea, $(this).data("tag-type"));
    });

    $(document).on("keyup", ".staff-chat-message-input", handleTextareaKeyup);

    $(document).on("input", "#staffChatEntitySearchInput", function () {
        clearTimeout(searchTimer);
        var query = this.value;
        searchTimer = setTimeout(function () {
            loadResults(query);
        }, 200);
    });

    $(document).on("click", function (e) {
        var els = getPickerElements();
        if (!els.picker || els.picker.classList.contains("d-none")) {
            return;
        }
        if ($(e.target).closest("#staffChatEntityPicker, .staff-tag-trigger").length) {
            return;
        }
        hidePicker();
    });

    $(document).on("click", ".staff-chat-entity-link.staff-chat-entity-staff", function (e) {
        var staffId = $(this).data("entity-id");
        if (staffId && typeof window.openStaffContact === "function") {
            e.preventDefault();
            window.openStaffContact(staffId);
        }
    });
})();
