(function () {
    const searchInput = document.querySelector('[data-mrs-coupon-search]');
    const couponRows = Array.from(document.querySelectorAll('[data-mrs-coupon-row]'));
    const noticeInput = document.getElementById('mrs_gutschein_notice_text');
    const noticePreview = document.querySelector('.mrs-ge-preview span:last-child');

    function updateCouponRow(row) {
        const checkbox = row.querySelector('input[type="checkbox"]');
        row.classList.toggle('is-selected', checkbox && checkbox.checked);
    }

    couponRows.forEach((row) => {
        const checkbox = row.querySelector('input[type="checkbox"]');

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener('change', () => updateCouponRow(row));
        updateCouponRow(row);
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();

            couponRows.forEach((row) => {
                const haystack = row.getAttribute('data-search') || '';
                row.hidden = query && !haystack.includes(query);
            });
        });
    }

    if (noticeInput && noticePreview) {
        noticeInput.addEventListener('input', () => {
            noticePreview.textContent = noticeInput.value;
        });
    }
})();
