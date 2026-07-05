/* ============================================================
   TEST STORE — Main JavaScript
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {


    // ── Auto-dismiss alerts ─────────────────────────────────
    document.querySelectorAll('.ts-alert').forEach(el => {
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            el.style.transition = 'all 0.4s ease';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    // ── Search autocomplete (simple) ────────────────────────
    const searchInput = document.getElementById('navSearchInput');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // Could be extended with AJAX for real autocomplete
            }, 300);
        });
    }

    // ── Tab system ──────────────────────────────────────────
    document.querySelectorAll('[data-ts-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tsTab;
            const container = btn.closest('[data-ts-tabs]') || document;

            // Deactivate all tabs in same group
            (container === document
                ? document.querySelectorAll('[data-ts-tab]')
                : container.querySelectorAll('[data-ts-tab]')
            ).forEach(b => b.classList.remove('active'));

            document.querySelectorAll('.ts-tab-content').forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const pane = document.querySelector('#' + target);
            if (pane) pane.classList.add('active');
        });
    });

    // ── Star Rating UI ──────────────────────────────────────
    document.querySelectorAll('.ts-star-input-wrap').forEach(wrap => {
        const stars = wrap.querySelectorAll('.ts-star-pick');
        const input = wrap.querySelector('input[type="hidden"]');

        stars.forEach((star, i) => {
            star.addEventListener('mouseenter', () => highlightStars(stars, i));
            star.addEventListener('mouseleave', () => {
                const val = parseInt(input?.value || 0);
                highlightStars(stars, val - 1);
            });
            star.addEventListener('click', () => {
                if (input) input.value = i + 1;
                highlightStars(stars, i);
            });
        });
    });
    function highlightStars(stars, upTo) {
        stars.forEach((s, j) => {
            s.textContent = j <= upTo ? 'star' : 'star_border';
            s.style.color  = j <= upTo ? 'var(--ts-warning)' : 'var(--ts-text-muted)';
        });
    }

    // ── Lazy load images ────────────────────────────────────
    if ('IntersectionObserver' in window) {
        const imgObs = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    obs.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        document.querySelectorAll('img[data-src]').forEach(img => imgObs.observe(img));
    }

    // ── Smooth scroll anchors ────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const href = anchor.getAttribute('href');
            if (href === '#') { e.preventDefault(); return; }
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── Category strip scroll arrows ─────────────────────────
    const catStrip = document.querySelector('.ts-cat-strip');
    if (catStrip) {
        let isDown = false, startX, scrollLeft;
        catStrip.addEventListener('mousedown', e => {
            isDown = true;
            startX = e.pageX - catStrip.offsetLeft;
            scrollLeft = catStrip.scrollLeft;
            catStrip.style.cursor = 'grabbing';
        });
        catStrip.addEventListener('mouseleave', () => { isDown = false; catStrip.style.cursor = 'grab'; });
        catStrip.addEventListener('mouseup',    () => { isDown = false; catStrip.style.cursor = 'grab'; });
        catStrip.addEventListener('mousemove',  e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - catStrip.offsetLeft;
            catStrip.scrollLeft = scrollLeft - (x - startX) * 1.5;
        });
        catStrip.style.cursor = 'grab';
    }

    // ── Mobile sidebar toggle (admin/developer) ──────────────
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.ts-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    // ── Confirm dialogs for destructive actions ───────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    // ── File input preview ───────────────────────────────────
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', () => {
            const previewEl = document.querySelector(input.dataset.preview);
            if (!previewEl || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewEl.src = e.target.result;
                previewEl.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        });
    });

    // ── Navbar scroll effect ─────────────────────────────────
    const navbar = document.querySelector('.ts-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.style.background = 'rgba(240, 247, 242, 0.98)';
            } else {
                navbar.style.background = 'rgba(240, 247, 242, 0.94)';
            }
        }, { passive: true });
    }

    // ── Screenshot lightbox ──────────────────────────────────
    document.querySelectorAll('.ts-screenshot').forEach(img => {
        img.addEventListener('click', () => {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position:fixed;inset:0;background:rgba(0,0,0,0.9);
                display:flex;align-items:center;justify-content:center;
                z-index:9999;cursor:zoom-out;animation:fadeIn 0.2s ease;
            `;
            const fullImg = document.createElement('img');
            fullImg.src = img.src;
            fullImg.style.cssText = 'max-width:90vw;max-height:90vh;border-radius:8px;object-fit:contain;';
            overlay.appendChild(fullImg);
            overlay.addEventListener('click', () => overlay.remove());
            document.body.appendChild(overlay);
        });
    });

    // ── Counter animation for stats ──────────────────────────
    function animateCounter(el) {
        const target = parseInt(el.dataset.count || el.textContent.replace(/[^0-9]/g, ''));
        const suffix = el.dataset.suffix || '';
        let start = 0;
        const duration = 1200;
        const step = timestamp => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target).toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    const counterObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animateCounter(e.target);
                counterObs.unobserve(e.target);
            }
        });
    });
    document.querySelectorAll('[data-count]').forEach(el => counterObs.observe(el));

});
