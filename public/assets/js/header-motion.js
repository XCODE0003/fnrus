(function () {
    'use strict';

    function init() {
        var header = document.querySelector('.header');
        if (!header) return;

        var menu = header.querySelector('.header__menu');
        var currentPath = window.location.pathname.replace(/\/$/, '') || '/';
        var exactMatch = false;

        header.querySelectorAll('.header__menu__item > a').forEach(function (link) {
            var url;
            try { url = new URL(link.href, window.location.origin); } catch (e) { return; }
            var linkPath = url.pathname.replace(/\/$/, '') || '/';
            if (url.origin === window.location.origin && linkPath !== '/' && linkPath === currentPath) {
                link.closest('.header__menu__item').classList.add('is-current');
                exactMatch = true;
            }
        });

        if (!exactMatch && document.querySelector('main.main, main.game, main.cheat-page, main.games-page')) {
            var catalogItem = header.querySelector('.header__menu__item--dropdown');
            if (catalogItem) catalogItem.classList.add('is-current');
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var gsap = window.gsap;
        if (!gsap) {
            var desktop = window.matchMedia('(min-width: 1180px)').matches;
            var introItems = desktop
                ? [header.querySelector('.header__logo')].concat(Array.from(header.querySelectorAll('.header__menu__item, .header__lang, .header__search, .header__login')))
                : Array.from(header.querySelectorAll('.header__logo, .header__lang, .header__search-btn, .header__login, .header__hamburger'));

            introItems.filter(Boolean).forEach(function (item, index) {
                item.animate([
                    { opacity: 0, transform: desktop && index === 0 ? 'translateX(-16px)' : 'translateY(-10px)' },
                    { opacity: 1, transform: 'translate(0, 0)' }
                ], {
                    duration: desktop ? 560 : 400,
                    delay: index * (desktop ? 42 : 34),
                    easing: 'cubic-bezier(.2,.75,.2,1)',
                    fill: 'backwards'
                });
            });

            if (menu && !desktop) {
                var nativeObserver = new MutationObserver(function () {
                    if (!menu.classList.contains('_active')) return;
                    menu.querySelectorAll('.header__menu__item').forEach(function (item, index) {
                        item.animate([
                            { opacity: 0, transform: 'translateX(16px)' },
                            { opacity: 1, transform: 'translateX(0)' }
                        ], {
                            duration: 340,
                            delay: index * 42,
                            easing: 'cubic-bezier(.2,.75,.2,1)',
                            fill: 'backwards'
                        });
                    });
                });
                nativeObserver.observe(menu, { attributes: true, attributeFilter: ['class'] });
            }
            return;
        }

        var mm = gsap.matchMedia();
        mm.add('(min-width: 1180px)', function () {
            var intro = gsap.timeline({ defaults: { ease: 'power3.out' } });
            intro.fromTo(header.querySelector('.header__logo'),
                { autoAlpha: 0, x: -18 },
                { autoAlpha: 1, x: 0, duration: 0.7, clearProps: 'transform,opacity,visibility' });
            intro.fromTo(header.querySelectorAll('.header__menu__item, .header__lang, .header__search, .header__login'),
                { autoAlpha: 0, y: -14 },
                { autoAlpha: 1, y: 0, duration: 0.58, stagger: 0.045, clearProps: 'transform,opacity,visibility' },
                '-=0.5');

            header.querySelectorAll('.header__menu__item > a, .header__menu__pill').forEach(function (item) {
                var yTo = gsap.quickTo(item, 'y', { duration: 0.28, ease: 'power3.out' });
                item.addEventListener('pointerenter', function () { yTo(-2); });
                item.addEventListener('pointerleave', function () { yTo(0); });
            });

            var login = header.querySelector('.header__login');
            if (login) {
                var loginScale = gsap.quickTo(login, 'scale', { duration: 0.3, ease: 'power3.out' });
                login.addEventListener('pointerenter', function () { loginScale(1.025); });
                login.addEventListener('pointerleave', function () { loginScale(1); });
            }
        });

        mm.add('(max-width: 1179px)', function () {
            gsap.fromTo(header.querySelectorAll('.header__logo, .header__lang, .header__search-btn, .header__login, .header__hamburger'),
                { autoAlpha: 0, y: -8 },
                { autoAlpha: 1, y: 0, duration: 0.42, stagger: 0.035, ease: 'power2.out', clearProps: 'transform,opacity,visibility' });

            if (!menu) return;
            var observer = new MutationObserver(function () {
                if (!menu.classList.contains('_active')) return;
                gsap.fromTo(menu.querySelectorAll('.header__menu__item'),
                    { autoAlpha: 0, x: 18 },
                    { autoAlpha: 1, x: 0, duration: 0.36, stagger: 0.045, ease: 'power3.out', clearProps: 'transform,opacity,visibility' });
            });
            observer.observe(menu, { attributes: true, attributeFilter: ['class'] });
            return function () { observer.disconnect(); };
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
