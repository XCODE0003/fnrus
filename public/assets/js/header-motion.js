(function () {
    'use strict';

    function markCurrentMenuItem(header) {
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
    }

    function revealHeader(header, reducedMotion) {
        if (reducedMotion) return;

        if (window.gsap) {
            window.gsap.fromTo(header,
                { autoAlpha: 0 },
                {
                    autoAlpha: 1,
                    duration: 0.28,
                    ease: 'power2.out',
                    overwrite: 'auto',
                    clearProps: 'opacity,visibility'
                }
            );
            return;
        }

        header.animate([
            { opacity: 0 },
            { opacity: 1 }
        ], {
            duration: 280,
            easing: 'cubic-bezier(.2,.75,.2,1)',
            fill: 'backwards'
        });
    }

    function initMobileMenuMotion(menu, reducedMotion) {
        if (!menu || reducedMotion) return;

        var observer = new MutationObserver(function () {
            if (!menu.classList.contains('_active') || !window.matchMedia('(max-width: 1179px)').matches) return;

            var items = menu.querySelectorAll('.header__menu__item');
            if (window.gsap) {
                window.gsap.killTweensOf(items);
                window.gsap.fromTo(items,
                    { autoAlpha: 0, x: 12 },
                    {
                        autoAlpha: 1,
                        x: 0,
                        duration: 0.26,
                        stagger: 0.035,
                        ease: 'power2.out',
                        overwrite: 'auto',
                        clearProps: 'transform,opacity,visibility'
                    }
                );
                return;
            }

            items.forEach(function (item, index) {
                item.animate([
                    { opacity: 0, transform: 'translateX(12px)' },
                    { opacity: 1, transform: 'translateX(0)' }
                ], {
                    duration: 260,
                    delay: index * 35,
                    easing: 'cubic-bezier(.2,.75,.2,1)',
                    fill: 'backwards'
                });
            });
        });

        observer.observe(menu, { attributes: true, attributeFilter: ['class'] });
    }

    function initEdgeLight(header, reducedMotion) {
        var runner = header.querySelector('.header__edge-light-runner');
        if (!runner || reducedMotion) return;

        if (window.gsap) {
            var orbit = window.gsap.to(runner, {
                rotation: 360,
                duration: 7.5,
                ease: 'none',
                repeat: -1,
                transformOrigin: '50% 50%'
            });

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) orbit.pause();
                else orbit.resume();
            });
            return;
        }

        runner.animate([
            { transform: 'rotate(0deg)' },
            { transform: 'rotate(360deg)' }
        ], {
            duration: 7500,
            iterations: Infinity,
            easing: 'linear'
        });
    }

    function init() {
        var header = document.querySelector('.header');
        if (!header) return;

        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        markCurrentMenuItem(header);
        revealHeader(header, reducedMotion);
        initMobileMenuMotion(header.querySelector('.header__menu'), reducedMotion);
        initEdgeLight(header, reducedMotion);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
