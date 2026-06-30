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
            // Already in (or above) the viewport at init — reveal immediately.
            // Covers above-the-fold sections (e.g. the first .game-list head)
            // where IntersectionObserver can fail to deliver a callback under
            // body{zoom}, leaving the element stuck at opacity:0/hidden.
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var r = el.getBoundingClientRect();
            if (r.top < vh * 0.92 && r.bottom > 0) { fire(); return; }
            if (!('IntersectionObserver' in window)) { fire(); return; }
            var io = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i++) {
                    if (entries[i].isIntersecting) { io.disconnect(); fire(); return; }
                }
            }, { threshold: 0.12, rootMargin: '0px 0px -7% 0px' });
            io.observe(el);
            setTimeout(function () { io.disconnect(); fire(); }, 2500);
        }

        /* ---- reveal: ОТКЛЮЧЕНО по просьбе. ----
           Анимации появления (прятать через autoAlpha:0 + blur/translate/scale,
           затем проявлять при долистывании) убраны: они «поднимали» карточки и
           те обрезались скролл-контейнерами («проваливались под фон»), а текст
           появлялся уже после загрузки страницы. Теперь весь контент виден сразу.
           Оставлено no-op, чтобы все вызовы reveal(...) ниже были безвредны. */
        function reveal() { /* intentionally no-op */ }

        /* ---- Счётчик чисел — ОТКЛЮЧЕНО. Цифры («8к+») показываются сразу,
           без отложенной анимации после загрузки. ---- */
        function counter() { /* intentionally no-op */ }

        /* ============================ HERO intro — DISABLED ============
           The cinematic .from() timeline occasionally got stuck (elements left
           at opacity:0 / transformed → "главная ломается"). Removed entirely so
           the hero is always rendered immediately. */
        /* (home hero entrance animation intentionally removed) */

        /* ========================= GAME HERO =========================
           Интро-таймлайн убран — заголовок/счётчик/картинка показываются
           сразу, без появления после загрузки. */

        /* ===================== Секция «Новый уровень» ================ */
        if (q('.section2')) {
            reveal('.section2__badge', { y: 20, duration: 0.7, trigger: '.section2' });
            reveal('.section2__title', { y: 26, duration: 0.85, trigger: '.section2' });
            reveal('.s2-card', { y: 28, scale: 0.96, stagger: 0.09, duration: 0.7, trigger: '.section2__grid' });
        }

        /* ============================ КАТАЛОГ ======================== */
        if (q('.catalog')) {
            reveal('.catalog__badge, .catalog__title, .catalog__subtitle', { y: 22, stagger: 0.08, duration: 0.75, trigger: '.catalog' });
            reveal('.catalog-card', { y: 26, scale: 0.96, stagger: 0.06, duration: 0.7, trigger: '.catalog__cards-container' });
        }

        /* ===================== ОТЗЫВЫ (главная) ====================== */
        if (q('.reviews')) {
            reveal('.reviews__badge, .reviews__title, .reviews__subtitle, .reviews__all', { y: 22, stagger: 0.07, duration: 0.7, trigger: '.reviews' });
            reveal('.reviews .rev-card', { y: 22, scale: 0.96, stagger: 0.06, duration: 0.65, trigger: '.reviews-grid' });
        }

        /* ============================== FAQ ========================== */
        if (q('.faq')) {
            reveal('.faq__badge, .faq__title', { y: 20, stagger: 0.07, duration: 0.7, trigger: '.faq' });
            reveal('.faq__container .accordion', { y: 18, stagger: 0.06, duration: 0.6, trigger: '.faq__container' });
        }

        /* ================= Секции платформ (страница игры) =========== */
        qa('.game-list, .game-rec').forEach(function (sec) {
            /* .game-list__head animation removed entirely — it kept getting
               stuck at opacity:0/visibility:hidden. Only .game-rec__title animates. */
            var head = sec.querySelector('.game-rec__title');
            if (head) reveal(head, { y: 24, trigger: sec });
            var cards = sec.querySelectorAll('.game-card, .catalog-card');
            if (cards.length) reveal(cards, { scale: 0.92, stagger: 0.07, trigger: sec });
        });

        /* Bullet-proof safety net: anything a reveal hid that is still hidden
           after a moment (trigger never fired) — force it visible. Guarantees
           no element (e.g. the first .game-list__head) ever stays stuck. */
        setTimeout(function () {
            qa('.game-list__head, .game-rec__title, .game-card, .catalog-card, .s2-card, .reviews .rev-card, .reviews-grid .rev-card').forEach(function (el) {
                var cs = getComputedStyle(el);
                if (cs.visibility === 'hidden' || parseFloat(cs.opacity) < 0.05) {
                    gsap.set(el, { clearProps: 'opacity,visibility,transform,filter' });
                }
            });
        }, 2600);

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
            /* Интро шапки «О нас» убрано — заголовок «МЫ ПРЕДЛАГАЕМ…» больше не
               появляется с блюром/сдвигом после загрузки, виден сразу. */
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
