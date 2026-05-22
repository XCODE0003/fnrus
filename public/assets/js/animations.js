/* =============================================================
   Payson Keys — анимации витрины
   Триггер по видимости — IntersectionObserver (нативный API, не зависит
   от body{zoom} и ручной математики скролла — в отличие от ScrollTrigger).
   GSAP — только для самих твинов. Гарантия: ни один элемент не может
   «зависнуть» невидимым (страховочный таймер + видимость по умолчанию).
   ============================================================= */
(function () {
    'use strict';

    /* ---- Стрелки слайдеров: фолбэк, когда swiper не инициализирован ---- */
    function wireArrows() {
        document.querySelectorAll('.reviews, .game-list, .game-rec').forEach(function (sec) {
            var slider = sec.querySelector('.swiper');
            var arrows = sec.querySelector('.slider-arrows');
            if (!slider || !arrows || slider.classList.contains('swiper-initialized')) return;
            var prev = arrows.querySelector('.slider-arrows__prev');
            var next = arrows.querySelector('.slider-arrows__next');
            if (prev) prev.addEventListener('click', function () { slider.scrollBy({ left: -360, behavior: 'smooth' }); });
            if (next) next.addEventListener('click', function () { slider.scrollBy({ left: 360, behavior: 'smooth' }); });
        });
    }

    /* ---- Кнопка «Вверх» ---- */
    function wireToTop() {
        var btn = document.querySelector('.to-top');
        if (!btn) return;
        function upd() { btn.classList.toggle('is-visible', window.pageYOffset > 480); }
        window.addEventListener('scroll', upd, { passive: true });
        upd();
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ============================ GSAP ============================ */
    function animate(gsap) {
        /* prefers-reduced-motion → ничего не трогаем, всё видимо. */
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var EASE = 'power3.out';
        var GLIDE = 'expo.out';
        var SOFT = 'power2.out';
        var POP = 'back.out(1.7)';
        var desktop = window.matchMedia('(min-width: 1024px)').matches;

        var q = function (s) { return document.querySelector(s); };
        var qa = function (s) { return gsap.utils.toArray(s); };

        /* ---- Надёжный «появился в зоне видимости» ----
           IntersectionObserver срабатывает на реальном пересечении (учитывает
           zoom). Страховка: если по какой-то причине не сработал — таймер. */
        function onView(el, cb) {
            if (!el) { cb(); return; }
            var fired = false;
            function fire() { if (fired) return; fired = true; cb(); }
            if (!('IntersectionObserver' in window)) { fire(); return; }
            var io = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i++) {
                    if (entries[i].isIntersecting) { io.disconnect(); fire(); return; }
                }
            }, { threshold: 0.12, rootMargin: '0px 0px -7% 0px' });
            io.observe(el);
            setTimeout(function () { io.disconnect(); fire(); }, 7000);
        }

        /* ---- reveal: прячем через gsap.set, показываем при появлении.
           Симметричные start/end — анимируется только то, что задано. ---- */
        function reveal(targets, opts) {
            opts = opts || {};
            var els = qa(targets);
            if (!els.length) return;
            var start = { autoAlpha: 0 };
            var end = {
                autoAlpha: 1,
                duration: opts.duration || 0.9,
                ease: opts.ease || EASE,
                stagger: opts.stagger != null ? opts.stagger : 0.09,
                clearProps: 'transform,filter,opacity,visibility'
            };
            if (opts.y != null) { start.y = opts.y; end.y = 0; }
            if (opts.scale != null) { start.scale = opts.scale; end.scale = 1; }
            if (opts.blur) { start.filter = 'blur(' + opts.blur + 'px)'; end.filter = 'blur(0px)'; }
            gsap.set(els, start);
            var trigger = opts.trigger ? q(opts.trigger) : els[0];
            onView(trigger || els[0], function () { gsap.to(els, end); });
        }

        /* ---- Счётчик чисел («20к+» → 0…20 + «к+») ---- */
        function counter(el) {
            var m = el.textContent.trim().match(/^(\D*)([\d\s.,]+)(.*)$/);
            if (!m) return;
            var pre = m[1], suf = m[3];
            var end = parseFloat(m[2].replace(/[^\d.]/g, '')) || 0;
            onView(el, function () {
                var o = { v: 0 };
                gsap.to(o, {
                    v: end, duration: 1.6, ease: SOFT,
                    onUpdate: function () { el.textContent = pre + Math.round(o.v) + suf; }
                });
            });
        }

        /* ============================ HERO ============================ */
        if (q('.hero')) {
            gsap.timeline({ defaults: { ease: GLIDE, duration: 1, clearProps: 'transform,filter' } })
                .from('.hero__badge', { autoAlpha: 0, y: 26, scale: 0.92, duration: 0.8 })
                .fromTo('.hero__title',
                    { autoAlpha: 0, y: 60, filter: 'blur(16px)' },
                    { autoAlpha: 1, y: 0, filter: 'blur(0px)', duration: 1.2 }, '-=0.45')
                .from('.hero__title-bolt', { scale: 0, rotation: -160, duration: 0.85, ease: POP }, '-=0.7')
                .from('.hero__subtitle', { autoAlpha: 0, y: 28, duration: 0.8 }, '-=0.75')
                .from('.hero__actions > *', { autoAlpha: 0, y: 26, scale: 0.96, stagger: 0.12, duration: 0.75 }, '-=0.6')
                .from('.hero__image', { autoAlpha: 0, scale: 0.9, y: 26, duration: 1.25, ease: SOFT }, '-=1.1');
            if (q('.hero__image img')) {
                gsap.to('.hero__image img', {
                    y: 16, rotation: 0.5, duration: 4, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 1.4
                });
            }
        }

        /* ========================= GAME HERO ========================= */
        if (q('.game-hero')) {
            gsap.timeline({ defaults: { ease: GLIDE, duration: 0.95, clearProps: 'transform,filter' } })
                .fromTo('.game-hero__title',
                    { autoAlpha: 0, y: 48, filter: 'blur(14px)' },
                    { autoAlpha: 1, y: 0, filter: 'blur(0px)', duration: 1.1 })
                .from('.game-hero__desc, .game-hero__count, .game-hero__back',
                    { autoAlpha: 0, y: 24, stagger: 0.1, duration: 0.75 }, '-=0.65')
                .from('.game-hero__image', { autoAlpha: 0, scale: 0.9, duration: 1.15, ease: SOFT }, '-=0.95');
            if (q('.game-hero__image img')) {
                gsap.to('.game-hero__image img', {
                    y: 12, duration: 4.2, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 1.4
                });
            }
        }

        /* ===================== Секция «Новый уровень» ================ */
        if (q('.section2')) {
            reveal('.section2__badge', { y: 26, trigger: '.section2' });
            reveal('.section2__title', { y: 40, blur: 12, duration: 1.05, trigger: '.section2' });
            reveal('.s2-card', { scale: 0.9, y: 30, stagger: 0.1, trigger: '.section2__grid' });
        }

        /* ============================ КАТАЛОГ ======================== */
        if (q('.catalog')) {
            reveal('.catalog__badge, .catalog__title, .catalog__subtitle', { y: 28, stagger: 0.1, trigger: '.catalog' });
            reveal('.catalog-card', { scale: 0.94, stagger: 0.07, trigger: '.catalog__cards-container' });
        }

        /* ===================== ОТЗЫВЫ (главная) ====================== */
        if (q('.reviews')) {
            reveal('.reviews__badge, .reviews__title, .reviews__subtitle, .reviews__all', { y: 28, trigger: '.reviews' });
            reveal('.reviews .rev-card', { scale: 0.94, y: 22, stagger: 0.07, trigger: '.reviews-grid' });
        }

        /* ============================== FAQ ========================== */
        if (q('.faq')) {
            reveal('.faq__badge, .faq__title', { y: 26, trigger: '.faq' });
            reveal('.faq__container .accordion', { y: 30, stagger: 0.08, trigger: '.faq__container' });
        }

        /* ================= Секции платформ (страница игры) =========== */
        qa('.game-list, .game-rec').forEach(function (sec) {
            var head = sec.querySelector('.game-list__head, .game-rec__title');
            if (head) reveal(head, { y: 24, trigger: sec });
            var cards = sec.querySelectorAll('.game-card, .catalog-card');
            if (cards.length) reveal(cards, { scale: 0.92, stagger: 0.07, trigger: sec });
        });

        /* ===================== СТРАНИЦА «Каталог игр» ================= */
        if (q('.games-catalog')) {
            reveal('.games-catalog__title, .games-catalog__subtitle, .games-catalog__controls', { y: 26, trigger: '.games-catalog' });
        }

        /* ===================== СТРАНИЦА «Отзывы» ===================== */
        if (q('.reviews-page')) {
            reveal('.reviews-page__title, .reviews-page__subtitle, .reviews-page__leave-btn', { y: 26, trigger: '.reviews-page' });
            reveal('.reviews-grid .rev-card', { scale: 0.94, stagger: 0.06, trigger: '.reviews-grid' });
        }

        /* ===================== СТРАНИЦА «Статусы» ==================== */
        if (q('.status-page')) {
            reveal('.status-heading-section__section-caption, .status-heading-section__section-subcaption, .status-heading-section__attention, .status-heading-section__filter',
                { y: 26, trigger: '.status-heading-section' });
            reveal('.game-status-block', { scale: 0.96, y: 26, stagger: 0.07, trigger: '.cheat-statuses' });
        }

        /* ===================== СТРАНИЦА «О нас» ====================== */
        if (q('.about-page')) {
            /* Шапка — интро по загрузке (над сгибом, без зависимости от скролла). */
            if (q('.about-heading-section')) {
                gsap.timeline({ defaults: { ease: GLIDE, duration: 0.9, clearProps: 'transform,filter' } })
                    .from('.about-heading-section .section-category', { autoAlpha: 0, y: 24, scale: 0.94, duration: 0.7 })
                    .fromTo('.about-heading-section__section-caption',
                        { autoAlpha: 0, y: 44, filter: 'blur(14px)' },
                        { autoAlpha: 1, y: 0, filter: 'blur(0px)', duration: 1.05 }, '-=0.4')
                    .from('.about-heading-section__section-subcaption', { autoAlpha: 0, y: 24 }, '-=0.65')
                    .from('.about-heading-section__btn', { autoAlpha: 0, y: 22, scale: 0.96, duration: 0.65 }, '-=0.55');
            }
            reveal('.about-section__stat', { scale: 0.92, y: 26, stagger: 0.1, trigger: '.about-section__stats' });
            qa('.about-section__stat p').forEach(counter);
            reveal('.about-contact', { scale: 0.94, y: 22, stagger: 0.08, trigger: '.about-section__contacts' });
            if (q('.about-section__history')) {
                reveal('.about-section__history__caption', { y: 32, blur: 10, trigger: '.about-section__history' });
                reveal('.about-section__history__slide', { scale: 0.94, stagger: 0.08, trigger: '.about-section__history' });
            }
            reveal('.about-section__text', { y: 28, trigger: '.about-section__text' });
        }

        /* ============================ ФУТЕР ========================== */
        reveal('.footer__container > *', { y: 28, stagger: 0.09, trigger: '.footer' });

        /* ===================== Магнитные CTA (десктоп) =============== */
        if (desktop && gsap.quickTo) {
            qa('.hero__btn-catalog, .hero__btn-register, .reviews-page__leave-btn').forEach(function (btn) {
                var xTo = gsap.quickTo(btn, 'x', { duration: 0.5, ease: 'power3' });
                var yTo = gsap.quickTo(btn, 'y', { duration: 0.5, ease: 'power3' });
                btn.addEventListener('mousemove', function (e) {
                    var r = btn.getBoundingClientRect();
                    xTo((e.clientX - r.left - r.width / 2) * 0.26);
                    yTo((e.clientY - r.top - r.height / 2) * 0.26);
                });
                btn.addEventListener('mouseleave', function () { xTo(0); yTo(0); });
            });
        }
    }

    function init() {
        wireArrows();
        wireToTop();
        if (typeof window.gsap !== 'undefined') animate(window.gsap);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
