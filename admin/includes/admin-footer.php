    </div><!-- /.admin-main -->
</div><!-- /.admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
(function() {
    // 1. Sidebar Scroll Persistence
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        // Restore saved position or make active link visible
        const savedPos = sessionStorage.getItem('admin_sidebar_scroll');
        if (savedPos !== null) {
            sidebar.scrollTop = parseInt(savedPos, 10);
        } else {
            const activeLink = sidebar.querySelector('.admin-nav-link.active');
            if (activeLink) {
                activeLink.scrollIntoView({ block: 'nearest', behavior: 'instant' });
            }
        }

        // Save position continuously on scroll
        sidebar.addEventListener('scroll', function() {
            sessionStorage.setItem('admin_sidebar_scroll', sidebar.scrollTop);
        }, { passive: true });

        // Save position when any link in sidebar is clicked
        sidebar.querySelectorAll('a').forEach(function(a) {
            a.addEventListener('click', function() {
                sessionStorage.setItem('admin_sidebar_scroll', sidebar.scrollTop);
            });
        });
    }

    // 2. Page Scroll Restoration for Filter Forms and Dropdown selects
    try {
        const savedPageScroll = sessionStorage.getItem('admin_page_scroll');
        if (savedPageScroll !== null) {
            window.scrollTo({ top: parseInt(savedPageScroll, 10), behavior: 'instant' });
            sessionStorage.removeItem('admin_page_scroll');
        }
    } catch(e) {}

    // Track when any form or onchange select is submitted
    document.querySelectorAll('form').forEach(function(f) {
        f.addEventListener('submit', function() {
            try { sessionStorage.setItem('admin_page_scroll', window.scrollY); } catch(e) {}
        });
    });
    document.querySelectorAll('select[onchange*="submit"]').forEach(function(sel) {
        sel.addEventListener('change', function() {
            try { sessionStorage.setItem('admin_page_scroll', window.scrollY); } catch(e) {}
        });
    });
})();
</script>
<?= isset($extra_js) ? $extra_js : '' ?>
</body>
</html>
