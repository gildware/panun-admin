"use strict";

(function ($) {
    function initAdminNavMenuGroups() {
        var $nav = $(".aside .aside-body > ul.nav").first();
        if (!$nav.length || $nav.data("menu-groups-initialized")) {
            return;
        }

        var groups = [];
        $nav.children("li.nav-category").each(function () {
            var $category = $(this);
            var $items = $category.nextUntil("li.nav-category");
            if (!$items.length) {
                return;
            }
            groups.push({ category: $category, items: $items });
        });

        groups.forEach(function (group) {
            var $group = $('<li class="nav-menu-group"></li>');
            var $toggle = $(
                '<button type="button" class="nav-menu-group-toggle nav-category"></button>'
            );
            var $label = $('<span class="nav-menu-group-label"></span>');
            var $icon = $(
                '<span class="material-icons nav-menu-group-icon" aria-hidden="true">expand_more</span>'
            );

            $label.append(group.category.contents());
            $toggle.append($label).append($icon);

            if (group.category.attr("title")) {
                $toggle.attr("title", group.category.attr("title"));
            }

            var $itemsWrap = $('<ul class="nav nav-menu-group-items"></ul>');
            $itemsWrap.append(group.items);

            $group.append($toggle).append($itemsWrap);
            group.category.replaceWith($group);
        });

        $nav.data("menu-groups-initialized", true);

        $nav.on("click", ".nav-menu-group-toggle", function (event) {
            event.preventDefault();
            var body = $("body");
            if (
                body.hasClass("aside-folded") &&
                !body.hasClass("open-aside-folded")
            ) {
                return;
            }

            var $group = $(this).closest(".nav-menu-group");
            var $items = $group.children(".nav-menu-group-items");
            var willOpen = !$group.hasClass("is-open");

            $(".aside .aside-body .nav-menu-group")
                .not($group)
                .removeClass("is-open")
                .children(".nav-menu-group-items")
                .stop(true, true)
                .slideUp(200);

            $group.toggleClass("is-open", willOpen);
            if (willOpen) {
                $items.stop(true, true).slideDown(200);
            } else {
                $items.stop(true, true).slideUp(200);
            }
        });
    }

    function syncAdminNavMenuGroupActiveState() {
        $(".aside .aside-body .nav-menu-group").each(function () {
            var $group = $(this);
            $group.toggleClass(
                "is-active",
                $group.find(".active-menu").length > 0
            );
        });
    }

    function openAdminNavMenuGroupsWithActive() {
        $(".aside .aside-body .nav-menu-group").each(function () {
            var $group = $(this);
            var $items = $group.children(".nav-menu-group-items");
            if ($group.find(".active-menu").length) {
                $group.addClass("is-open");
                $items.show();
            } else {
                $group.removeClass("is-open");
                $items.hide();
            }
        });
        syncAdminNavMenuGroupActiveState();
    }

    window.initAdminNavMenuGroups = initAdminNavMenuGroups;
    window.openAdminNavMenuGroupsWithActive = openAdminNavMenuGroupsWithActive;

    var ADMIN_MULTI_SELECT_SELECTORS = [
        ".js-select-multi",
        ".category-select",
        ".subcategory-select",
        ".zone-select",
        ".assignee-select",
        ".zone__select",
        ".category__select",
        ".sub-category__select",
        ".service__select",
        ".provider__select",
        ".staff__select",
        ".booking-status__select",
        ".customer__select",
        ".select-zone",
        ".select-user",
        ".select-users",
        ".service-select",
        ".subscribed-services-category-filter",
    ].join(", ");

    function shouldSkipAdminSelect2Init(el) {
        if (el.hasAttribute("size") && parseInt(el.getAttribute("size"), 10) > 1) {
            return true;
        }

        return $(el).hasClass("zone-tree-select");
    }

    function adminSelect2OptionsFromElement($el) {
        var options = { width: "100%" };
        var placeholder = $el.data("placeholder");

        if (placeholder) {
            options.placeholder = placeholder;
        }

        var dropdownParent =
            $el.data("dropdownParent") || $el.data("dropdown-parent");
        if (dropdownParent) {
            options.dropdownParent = $(dropdownParent);
        } else {
            var $modal = $el.closest(".modal");
            var $offcanvas = $el.closest(".offcanvas");

            if ($modal.length) {
                options.dropdownParent = $modal;
            } else if ($offcanvas.length) {
                options.dropdownParent = $offcanvas;
            }
        }

        var $leadDrawer = $el.closest("#leadFilterDrawer");
        if ($leadDrawer.length) {
            if (!options.placeholder && $leadDrawer.data("selectPlaceholder")) {
                options.placeholder = $leadDrawer.data("selectPlaceholder");
            }
            if (!options.dropdownParent) {
                options.dropdownParent = $leadDrawer;
            }
        }

        if ($el.data("allowClear")) {
            options.allowClear = true;
        }

        if ($el.hasClass("subscribed-services-category-filter")) {
            options.allowClear = true;
            options.closeOnSelect = false;
        }

        return options;
    }

    function bootAdminSelect2($el, options) {
        options = options || {};
        var force = !!options.force;

        if (!force && $el.hasClass("select2-hidden-accessible")) {
            return;
        }

        if ($el.data("select2")) {
            $el.select2("destroy");
        }

        $el.select2(adminSelect2OptionsFromElement($el));
    }

    window.initAdminPageSelect2 = function (root, options) {
        options = options || {};

        if (!$.fn.select2) {
            return;
        }

        var $root = root ? $(root) : $(document);
        var includeSingle = options.includeSingle !== false;

        if (includeSingle) {
            $root.find(".js-select").filter("select").each(function () {
                if (shouldSkipAdminSelect2Init(this)) {
                    return;
                }

                bootAdminSelect2($(this), options);
            });
        }

        $root
            .find(ADMIN_MULTI_SELECT_SELECTORS)
            .filter("select[multiple]")
            .each(function () {
                if (shouldSkipAdminSelect2Init(this)) {
                    return;
                }

                bootAdminSelect2($(this), options);
            });

        $root.find(".js-select[multiple]").each(function () {
            if (shouldSkipAdminSelect2Init(this)) {
                return;
            }

            bootAdminSelect2($(this), options);
        });
    };

    $(document).ready(function () {
        initAdminNavMenuGroups();
    });

    $(window).on("load", function () {
        openAdminNavMenuGroupsWithActive();
        initAdminPageSelect2(document);
    });
})(jQuery);
