    window.pkUpdateHeaderUnreadBadge = function (countEl, count, dotEl) {
        if (!countEl) {
            return;
        }

        if (!dotEl && countEl.id) {
            dotEl = document.getElementById(countEl.id + '_dot');
        }

        count = parseInt(count, 10);
        if (isNaN(count) || count <= 0) {
            countEl.style.display = 'none';
            countEl.textContent = '';
            if (dotEl) {
                dotEl.style.display = 'none';
            }
            return;
        }

        if (count >= 9) {
            countEl.style.display = 'none';
            countEl.textContent = '';
            if (dotEl) {
                dotEl.style.display = 'block';
            }
            return;
        }

        if (dotEl) {
            dotEl.style.display = 'none';
        }
        countEl.textContent = String(count);
        countEl.style.display = 'flex';
    };
