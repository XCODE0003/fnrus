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
           Scroll-based (getBoundingClientRect на скролле) вместо
           IntersectionObserver: IO иногда не доставлял колбэк под body{zoom},
           и блоки «застревали» невидимыми. Этот способ zoom-устойчив и
           гарантированно срабатывает при реальной прокрутке. */
        var _vQueue = [];
        var _vScheduled = false;
        function _vCheck() {
            _vScheduled = false;
            var vh = window.innerHeight || document.documentElement.clientHeight;
            for (var i = _vQueue.length - 1; i >= 0; i--) {
                var it = _vQueue[i];
                var r = it.el.getBoundingClientRect();
                if (r.top < vh * 0.92 && r.bottom > 0) {
                    _vQueue.splice(i, 1);
                    it.cb();
                }
            }
        }
        function _vSchedule() {
            if (_vScheduled) return;
            _vScheduled = true;
            setTimeout(_vCheck, 0); // rAF is paused on hidden tabs — use timeout
        }
        window.addEventListener('scroll', _vSchedule, { passive: true });
        window.addEventListener('resize', _vSchedule, { passive: true });
        // Re-check once layout/fonts/images settle (rect can shift after load).
        window.addEventListener('load', function () { setTimeout(_vCheck, 60); });

        function onView(el, cb) {
            if (!el) { cb(); return; }
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var r = el.getBoundingClientRect();
            if (r.top < vh * 0.92 && r.bottom > 0) { cb(); return; } // already visible
            _vQueue.push({ el: el, cb: cb });
        }

        /* ---- reveal: мягкое появление при прокрутке. ----
           Принципы (чтобы не было прошлых багов):
             • анимируем ТОЛЬКО opacity + translateY — на GPU, без лагов;
               никакого blur/scale (они вызывали клиппинг и «проваливание под фон»);
             • элементы, уже видимые при загрузке (above-the-fold), НЕ прячем —
               значит никакого «текст появился после загрузки страницы»;
             • clearProps в конце — чтобы hover и прочие трансформы потом работали. */
        function reveal(targets, opts) {
            opts = opts || {};
            var els = qa(targets);
            if (!els.length) return;
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var y = opts.y != null ? opts.y : 22;

            // Прячем только то, что сейчас НЕ видно (ниже сгиба).
            var hidden = els.filter(function (el) {
                var r = el.getBoundingClientRect();
                return !(r.top < vh * 0.95 && r.bottom > 0);
            });
            if (!hidden.length) return;

            gsap.set(hidden, { autoAlpha: 0, y: y });

            // Trigger on the first hidden element itself (not the section) so a
            // tall section never reveals its below-fold cards prematurely.
            onView(hidden[0], function () {
                gsap.to(hidden, {
                    autoAlpha: 1,
                    y: 0,
                    duration: opts.duration || 0.7,
                    ease: opts.ease || EASE,
                    stagger: opts.stagger != null ? opts.stagger : 0.08,
                    clearProps: 'transform,opacity,visibility'
                });
            });
        }

        /* ---- Счётчик чисел («8к+» → 0…8 + «к+») при появлении. ---- */
        function counter(el) {
            var m = el.textContent.trim().match(/^(\D*)([\d\s.,]+)(.*)$/);
            if (!m) return;
            var pre = m[1], suf = m[3];
            var end = parseFloat(m[2].replace(/[^\d.]/g, '')) || 0;
            if (!end) return;
            onView(el, function () {
                var o = { v: 0 };
                gsap.to(o, {
                    v: end, duration: 1.4, ease: SOFT,
                    onUpdate: function () { el.textContent = pre + Math.round(o.v) + suf; }
                });
            });
        }

        /* ============================ HERO intro — DISABLED ============
           The cinematic .from() timeline occasionally got stuck (elements left
           at opacity:0 / transformed → "главная ломается"). Removed entirely so
           the hero is always rendered immediately. */
        /* (home hero entrance animation intentionally removed) */

        /* ========================= GAME HERO =========================
           Интро-таймлайн убран — заголовок/счётчик/картинка показываются
           сразу, без появления после загрузки. */

        /* ВАЖНО: заголовки/сабтайтлы/бейджи НЕ анимируем — только карточки и
           блоки. Так текст не «появляется после загрузки» и не наезжает на
           соседний текст из-за остаточного смещения. */

        /* ===================== Секция «Новый уровень» ================ */
        if (q('.section2')) {
            reveal('.s2-card', { y: 28, stagger: 0.09, duration: 0.7, trigger: '.section2__grid' });
        }

        /* ============================ КАТАЛОГ ======================== */
        if (q('.catalog')) {
            reveal('.catalog-card', { y: 26, stagger: 0.06, duration: 0.7, trigger: '.catalog__cards-container' });
        }

        /* ===================== ОТЗЫВЫ (главная) ====================== */
        if (q('.reviews')) {
            reveal('.reviews .rev-card', { y: 22, stagger: 0.06, duration: 0.65, trigger: '.reviews-grid' });
        }

        /* ============================== FAQ ========================== */
        if (q('.faq')) {
            reveal('.faq__container .accordion', { y: 18, stagger: 0.06, duration: 0.6, trigger: '.faq__container' });
        }

        /* ================= Секции платформ (страница игры) =========== */
        qa('.game-list, .game-rec').forEach(function (sec) {
            var cards = sec.querySelectorAll('.game-card, .catalog-card');
            if (cards.length) reveal(cards, { y: 24, stagger: 0.07, trigger: sec });
        });

        /* Safety net: un-stick ONLY elements the user can currently see but
           that stayed hidden (rare IO miss). Below-the-fold blocks are left to
           the scroll reveal — we must not pop them in early. */
        setTimeout(function () {
            var vh = window.innerHeight || document.documentElement.clientHeight;
            qa('.game-rec__title, .game-card, .catalog-card, .s2-card, .reviews .rev-card, .reviews-grid .rev-card, .about-section__stat, .about-contact, .about-section__history__caption, .about-section__history__slide')
                .forEach(function (el) {
                    var r = el.getBoundingClientRect();
                    var inView = r.top < vh && r.bottom > 0;
                    var cs = getComputedStyle(el);
                    if (inView && (cs.visibility === 'hidden' || parseFloat(cs.opacity) < 0.05)) {
                        gsap.to(el, { autoAlpha: 1, y: 0, duration: 0.5, clearProps: 'transform,opacity,visibility' });
                    }
                });
        }, 2600);

        /* ===================== СТРАНИЦА «Каталог игр» ================= */
        /* (заголовок/сабтайтл не анимируем — только карточки ниже через
           общий .game-list/.game-rec проход) */

        /* ===================== СТРАНИЦА «Отзывы» ===================== */
        if (q('.reviews-page')) {
            reveal('.reviews-grid .rev-card', { y: 22, stagger: 0.06, trigger: '.reviews-grid' });
        }

        /* ===================== СТРАНИЦА «Статусы» ==================== */
        if (q('.status-page')) {
            reveal('.game-status-block', { y: 26, stagger: 0.07, trigger: '.cheat-statuses' });
        }

        /* ===================== СТРАНИЦА «О нас» ====================== */
        if (q('.about-page')) {
            /* Интро шапки «О нас» убрано — заголовок «МЫ ПРЕДЛАГАЕМ…» больше не
               появляется с блюром/сдвигом после загрузки, виден сразу. */
            reveal('.about-section__stat', { y: 26, stagger: 0.1, trigger: '.about-section__stats' });
            qa('.about-section__stat p').forEach(counter);
            reveal('.about-contact', { y: 22, stagger: 0.08, trigger: '.about-section__contacts' });
            if (q('.about-section__history')) {
                reveal('.about-section__history__slide', { y: 22, stagger: 0.08, trigger: '.about-section__history' });
            }
        }

        /* ============================ ФУТЕР — статичный (текст/ссылки) === */

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
