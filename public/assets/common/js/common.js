(function ($) {
    ("use strict");

    $(document).ready(function () {
        // When Select2 opens
        $(document).on('select2:open', function () {
            $('.down-icon').addClass('active');
        });

        // When Select2 closes
        $(document).on('select2:close', function () {
            $('.down-icon').removeClass('active');
        });
    });
    


    // --- Fixed Action Button ---
    let isFixed = false;

    function checkContentHeight() {
        let windowHeight = $(window).height();
        let contentHeight = $(document).height();
        let scrollPosition = $(window).scrollTop();
        let $actionWrapper = $(".action-btn-wrapper");
        let $parent = $actionWrapper.parent();

        setTimeout(() => {
            if (contentHeight > windowHeight) {
                if (!isFixed) {
                    $parent.addClass("fixed-bottom");
                    $actionWrapper.addClass("fixed");
                    isFixed = true;
                }

                if (scrollPosition + windowHeight >= contentHeight - 100) {
                    if (isFixed) {
                        $actionWrapper.removeClass("fixed");
                        $parent.removeClass("fixed-bottom");
                        isFixed = false;
                    }
                }
            } else {
                if (isFixed) {
                    $actionWrapper.removeClass("fixed");
                    $parent.removeClass("fixed-bottom");
                    isFixed = false;
                }
            }
        }, 500);
    }

    checkContentHeight();

    $(window).on("resize scroll", function() {
        checkContentHeight();
    });

    // --- Easy setup guide
    $(document).ready(function () {
        const $guideButton = $('.view-guideline-btn');
        const $guideDropdown = $('.easy-setup-dropdown');
        const dismissKey = 'easy_setup_guide_dismissed';

        if (localStorage.getItem(dismissKey) === '1') {
            $guideButton.hide();
            $guideDropdown.hide().removeClass('show');
            return;
        }

        $guideButton.on('click', function (event) {
            if ($(event.target).closest('.view-guideline-dismiss').length) {
                return;
            }
            if (this.dataset.dragged === '1') {
                return;
            }

            $guideDropdown.addClass('show');
            $(this).removeClass('show');
        });

        $('.easy-setup-dropdown_close').on('click', function () {
            $guideDropdown.removeClass('show');
            $guideButton.addClass('show');
        });

        $('.view-guideline-dismiss').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            localStorage.setItem(dismissKey, '1');
            $guideDropdown.hide().removeClass('show');
            $guideButton.hide();
        });

        // Make setup guide button and panel draggable.
        function enableDrag($element, handleSelector, storageKey) {
            if (!$element.length) return;

            const el = $element.get(0);
            const savedPosition = localStorage.getItem(storageKey);
            if (savedPosition) {
                try {
                    const position = JSON.parse(savedPosition);
                    if (typeof position.top === 'number') {
                        el.style.top = position.top + 'px';
                        el.style.bottom = 'auto';
                    }
                    if (typeof position.left === 'number') {
                        el.style.left = position.left + 'px';
                        el.style.right = 'auto';
                        el.style.insetInlineStart = 'auto';
                        el.style.insetInlineEnd = 'auto';
                    }
                } catch (error) {
                    localStorage.removeItem(storageKey);
                }
            }

            let dragging = false;
            let startX = 0;
            let startY = 0;
            let originLeft = 0;
            let originTop = 0;
            let moved = false;

            const handle = handleSelector ? $element.find(handleSelector).get(0) : el;
            if (!handle) return;

            handle.classList.add('easy-setup-drag-handle');

            handle.addEventListener('mousedown', function (event) {
                if (event.button !== 0) return;
                if ($(event.target).closest('button, a, input, textarea, select, label').length) return;

                const rect = el.getBoundingClientRect();
                dragging = true;
                moved = false;
                startX = event.clientX;
                startY = event.clientY;
                originLeft = rect.left;
                originTop = rect.top;

                el.style.left = rect.left + 'px';
                el.style.top = rect.top + 'px';
                el.style.right = 'auto';
                el.style.bottom = 'auto';
                el.style.insetInlineStart = 'auto';
                el.style.insetInlineEnd = 'auto';
                el.classList.add('easy-setup-dragging');

                event.preventDefault();
            });

            document.addEventListener('mousemove', function (event) {
                if (!dragging) return;

                const deltaX = event.clientX - startX;
                const deltaY = event.clientY - startY;
                let nextLeft = originLeft + deltaX;
                let nextTop = originTop + deltaY;

                const maxLeft = window.innerWidth - el.offsetWidth;
                const maxTop = window.innerHeight - el.offsetHeight;

                nextLeft = Math.max(0, Math.min(nextLeft, Math.max(0, maxLeft)));
                nextTop = Math.max(0, Math.min(nextTop, Math.max(0, maxTop)));

                if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
                    moved = true;
                }

                el.style.left = nextLeft + 'px';
                el.style.top = nextTop + 'px';
            });

            document.addEventListener('mouseup', function () {
                if (!dragging) return;

                dragging = false;
                el.classList.remove('easy-setup-dragging');
                localStorage.setItem(storageKey, JSON.stringify({
                    left: parseFloat(el.style.left) || 0,
                    top: parseFloat(el.style.top) || 0,
                }));

                if (moved) {
                    el.dataset.dragged = '1';
                    setTimeout(() => {
                        delete el.dataset.dragged;
                    }, 120);
                }
            });
        }

        enableDrag($guideButton, null, 'easy_setup_button_position');
        enableDrag($guideDropdown, '.d-flex.justify-content-between.align-items-center.gap-2.mb-20', 'easy_setup_dropdown_position');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.easy-setup-dropdown, .view-guideline-btn').length) {
            $('.easy-setup-dropdown').removeClass('show');
            $('.view-guideline-btn').addClass('show');
        }
    });


})(jQuery);


