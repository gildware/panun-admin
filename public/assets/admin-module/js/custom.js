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

    $(document).ready(function () {
        initAdminNavMenuGroups();
    });

    $(window).on("load", function () {
        openAdminNavMenuGroupsWithActive();
        if ($.fn.select2) {
            $(".js-select").select2();
        }
    });
})(jQuery);
