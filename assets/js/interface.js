'use strict';

/**
 * theme.js handles initial theme application.
 * interface.js handles interactive theme toggle, mobile navigation drawer, and admin sidebar.
 */

// ─── 1. Theme Toggle ──────────────────────────────────────────────────────────
document.querySelectorAll('[data-theme-toggle]').forEach(button => {
    const update = () => {
        const isDark = document.documentElement.dataset.theme === 'dark';
        button.setAttribute('aria-pressed', String(isDark));
        button.innerHTML = isDark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
    };
    update();
    button.addEventListener('click', () => {
        const newTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = newTheme;
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        try { localStorage.setItem('jhd-theme', newTheme); } catch (_) {}
        update();
    });
});

// ─── 2. Accessible Mobile Navigation Drawer (Spec stage 7) ────────────────────
(function initMobileDrawer() {
    const toggleBtn = document.getElementById('menuToggle');
    const drawer = document.getElementById('siteDrawer');
    const overlay = document.getElementById('drawerOverlay');
    const closeBtn = document.getElementById('drawerClose');

    if (!toggleBtn || !drawer || !overlay) return;

    let previousActiveElement = null;
    let previousBodyOverflow = '';
    let closeTimer = null;

    function openDrawer() {
        if (closeTimer) window.clearTimeout(closeTimer);
        previousActiveElement = document.activeElement;
        previousBodyOverflow = document.body.style.overflow;
        drawer.removeAttribute('inert');
        drawer.setAttribute('aria-hidden', 'false');
        drawer.classList.add('open');
        overlay.hidden = false;
        // Reflow for transition
        void overlay.offsetWidth;
        overlay.classList.add('show');
        toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';

        // Focus close button or first link
        const firstFocusable = drawer.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }

    function closeDrawer() {
        drawer.setAttribute('aria-hidden', 'true');
        drawer.classList.remove('open');
        overlay.classList.remove('show');
        toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = previousBodyOverflow;

        closeTimer = window.setTimeout(() => {
            if (drawer.classList.contains('open')) return;
            overlay.hidden = true;
            drawer.setAttribute('inert', '');
            closeTimer = null;
        }, 280);

        if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
            previousActiveElement.focus();
        } else if (toggleBtn) {
            toggleBtn.focus();
        }
    }

    // Toggle button click
    toggleBtn.addEventListener('click', () => {
        if (drawer.classList.contains('open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    // Close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }

    // Overlay click (click outside)
    overlay.addEventListener('click', closeDrawer);

    // Keyboard dismissal and focus containment for the modal drawer.
    document.addEventListener('keydown', (e) => {
        if (!drawer.classList.contains('open')) return;
        if (e.key === 'Escape') {
            closeDrawer();
            return;
        }
        if (e.key !== 'Tab') return;
        const focusable = [...drawer.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), summary, [tabindex]:not([tabindex="-1"])')]
            .filter(element => element.getClientRects().length > 0);
        if (!focusable.length) {
            e.preventDefault();
            drawer.focus();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) {
            e.preventDefault();
            first.focus();
        }
    });

    // Close drawer when any navigation link inside it is clicked
    drawer.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            // Give time for user to see click, then close
            closeDrawer();
        });
    });

    // Resize cleanup: if screen resized to desktop size, close drawer and reset overflow
    const desktopMedia = window.matchMedia('(min-width: 1200px)');
    desktopMedia.addEventListener('change', (e) => {
        if (e.matches && drawer.classList.contains('open')) {
            closeDrawer();
        }
    });
})();

// ─── 3. Admin Sidebar Toggle ──────────────────────────────────────────────────
(function initAdminSidebar() {
    const adminMenu = document.getElementById('adminSidebar');
    const adminToggle = document.getElementById('sidebarToggle');

    if (!adminMenu || !adminToggle) return;

    const close = () => {
        adminMenu.classList.remove('open');
        adminToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    adminToggle.addEventListener('click', () => {
        const willOpen = !adminMenu.classList.contains('open');
        adminMenu.classList.toggle('open', willOpen);
        adminToggle.setAttribute('aria-expanded', String(willOpen));
        if (willOpen) {
            adminMenu.querySelector('a')?.focus();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && adminMenu.classList.contains('open')) {
            close();
            adminToggle.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && adminMenu.classList.contains('open')) {
            if (!adminMenu.contains(e.target) && !adminToggle.contains(e.target)) {
                close();
            }
        }
    });

    window.matchMedia('(min-width: 769px)').addEventListener('change', (e) => {
        if (e.matches) close();
    });
})();
