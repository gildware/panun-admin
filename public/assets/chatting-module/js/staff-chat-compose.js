"use strict";

(function () {
    var activeTextarea = null;
    var activeTagType = "staff";
    var mentionStart = null;
    var searchTimer = null;

    var TAG_TYPES = ["Staff", "Customer", "Provider", "Booking", "Service", "Lead"];
    var TAG_TYPE_PATTERN = TAG_TYPES.join("|");
    var TOKEN_PATTERN = new RegExp(
        "@\\[([^\\]]*)\\]\\((staff|customer|provider|booking|service|lead):[^)]+\\)",
        "i"
    );
    var DISPLAY_TAG_PATTERN = new RegExp(
        "@(" + TAG_TYPE_PATTERN + "):([\\s\\S]*?)(?=\\s@(" + TAG_TYPE_PATTERN + "):|$)",
        "i"
    );
    var NEXT_TAG_PATTERN = new RegExp("@(?:\\[|(" + TAG_TYPE_PATTERN + "):)", "i");

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
            type: type,
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
        syncComposeHighlight(activeTextarea);
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

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderComposePill(type, typeLabel, name) {
        var safeType = escapeHtml(String(type || "staff").toLowerCase());
        return (
            '<span class="staff-chat-entity-badge staff-chat-entity-' + safeType + ' staff-chat-compose-pill">' +
            '<span class="staff-chat-entity-type">' + escapeHtml(typeLabel) + "</span>" +
            '<span class="staff-chat-entity-sep"> · </span>' +
            '<span class="staff-chat-entity-name">' + escapeHtml(String(name || "").trim()) + "</span>" +
            "</span>"
        );
    }

    function pillFromDisplayTag(displayTag) {
        var match = String(displayTag || "").match(/^@([^:]+):(.*)$/);
        if (!match) {
            return escapeHtml(displayTag);
        }
        return renderComposePill(match[1].toLowerCase(), match[1], match[2]);
    }

    function pillFromToken(label, type) {
        var typeLabel = typeLabels[String(type || "").toLowerCase()] || type;
        return renderComposePill(type, typeLabel, label);
    }

    function getSortedRegistry() {
        return (window.staffChatTagRegistry || []).slice().sort(function (a, b) {
            return b.display.length - a.display.length;
        });
    }

    function buildHighlightHtml(text) {
        var str = String(text || "");
        if (str === "") {
            return "\u200b";
        }

        var html = "";
        var i = 0;

        while (i < str.length) {
            var sub = str.slice(i);
            var matched = false;

            getSortedRegistry().some(function (entry) {
                if (sub.indexOf(entry.display) !== 0) {
                    return false;
                }
                html += pillFromDisplayTag(entry.display);
                i += entry.display.length;
                matched = true;
                return true;
            });

            if (matched) {
                continue;
            }

            var tokenMatch = sub.match(TOKEN_PATTERN);
            if (tokenMatch && tokenMatch.index === 0) {
                html += pillFromToken(tokenMatch[1], tokenMatch[2]);
                i += tokenMatch[0].length;
                continue;
            }

            var displayMatch = sub.match(DISPLAY_TAG_PATTERN);
            if (displayMatch && displayMatch.index === 0) {
                html += renderComposePill(
                    displayMatch[1].toLowerCase(),
                    displayMatch[1],
                    displayMatch[2]
                );
                i += displayMatch[0].length;
                continue;
            }

            var nextSpecial = sub.search(NEXT_TAG_PATTERN);
            if (nextSpecial === -1) {
                html += escapeHtml(sub);
                break;
            }

            if (nextSpecial === 0) {
                html += escapeHtml(sub.charAt(0));
                i += 1;
                continue;
            }

            html += escapeHtml(sub.slice(0, nextSpecial));
            i += nextSpecial;
        }

        return html || "\u200b";
    }

    function copyTextareaStyles(textarea, highlight) {
        if (!textarea || !highlight) {
            return;
        }

        var cs = window.getComputedStyle(textarea);
        highlight.style.fontSize = cs.fontSize;
        highlight.style.fontFamily = cs.fontFamily;
        highlight.style.fontWeight = cs.fontWeight;
        highlight.style.lineHeight = cs.lineHeight;
        highlight.style.letterSpacing = cs.letterSpacing;
        highlight.style.padding = cs.padding;
        highlight.style.paddingTop = cs.paddingTop;
        highlight.style.paddingRight = cs.paddingRight;
        highlight.style.paddingBottom = cs.paddingBottom;
        highlight.style.paddingLeft = cs.paddingLeft;
        highlight.style.border = cs.border;
        highlight.style.borderWidth = cs.borderWidth;
        highlight.style.borderRadius = cs.borderRadius;
        highlight.style.boxSizing = cs.boxSizing;
        highlight.style.wordBreak = cs.wordBreak;
        highlight.style.overflowWrap = cs.overflowWrap;
        highlight.style.textAlign = cs.textAlign;
        highlight.style.minHeight = cs.minHeight;
        highlight.style.width = "100%";
    }

    function syncComposeHighlight(textarea) {
        if (!textarea || !textarea.classList.contains("staff-chat-compose-input")) {
            return;
        }

        var editor = textarea.closest(".staff-chat-compose-editor");
        if (!editor) {
            return;
        }

        var highlight = editor.querySelector(".staff-chat-compose-highlight");
        if (!highlight) {
            return;
        }

        copyTextareaStyles(textarea, highlight);
        highlight.innerHTML = buildHighlightHtml(textarea.value);
        highlight.scrollTop = textarea.scrollTop;
        highlight.scrollLeft = textarea.scrollLeft;
    }

    function wrapComposeTextarea(textarea) {
        if (
            !textarea
            || textarea.closest(".staff-chat-compose-editor")
            || textarea.classList.contains("staff-chat-compose-plain")
        ) {
            return;
        }

        var editor = document.createElement("div");
        editor.className = "staff-chat-compose-editor";

        var highlight = document.createElement("div");
        highlight.className = "staff-chat-compose-highlight";
        highlight.setAttribute("aria-hidden", "true");

        textarea.classList.add("staff-chat-compose-input");
        textarea.parentNode.insertBefore(editor, textarea);
        editor.appendChild(highlight);
        editor.appendChild(textarea);

        textarea.addEventListener("input", function () {
            syncComposeHighlight(textarea);
        });
        textarea.addEventListener("scroll", function () {
            highlight.scrollTop = textarea.scrollTop;
            highlight.scrollLeft = textarea.scrollLeft;
        });
        textarea.addEventListener("resize", function () {
            syncComposeHighlight(textarea);
        });

        syncComposeHighlight(textarea);
        requestAnimationFrame(function () {
            syncComposeHighlight(textarea);
        });
    }

    function initComposeHighlights(root) {
        (root || document).querySelectorAll("textarea.staff-chat-message-input").forEach(wrapComposeTextarea);
    }

    window.syncStaffChatComposeHighlight = syncComposeHighlight;
    window.initStaffChatComposeHighlights = initComposeHighlights;

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
        syncComposeHighlight(textarea);
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
        syncComposeHighlight(textarea);
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
                "<span class=\"badge staff-chat-entity-picker-badge staff-chat-entity-" + escapeHtml(item.type) + "\">" + escapeHtml(typeLabel) + "</span>" +
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

        syncComposeHighlight(textarea);

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

    function findComposeTextarea(fromEl) {
        var scope = fromEl.closest(".input_msg_write, form, .lead-comment-compose, .comment-compose");
        if (scope) {
            var scoped = scope.querySelector(".staff-chat-message-input");
            if (scoped) {
                return scoped;
            }
        }

        var parentWrap = fromEl.closest(".staff-chat-compose-wrap");
        if (parentWrap && parentWrap.parentElement) {
            var sibling = parentWrap.parentElement.querySelector(".staff-chat-message-input");
            if (sibling) {
                return sibling;
            }
        }

        return document.querySelector(".staff-chat-message-input");
    }

    $(document).on("click", ".staff-tag-trigger", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var textarea = findComposeTextarea(this);
        if (!textarea) {
            return;
        }
        mentionStart = null;
        showPicker(textarea, $(this).data("tag-type"));
    });

    $(document).on("keyup", ".staff-chat-message-input", handleTextareaKeyup);
    $(document).on("input", ".staff-chat-message-input", function () {
        syncComposeHighlight(this);
    });

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

    $(document).on("focus", ".staff-chat-message-input", function () {
        wrapComposeTextarea(this);
    });

    $(document).ready(function () {
        initComposeHighlights();
    });
})();
