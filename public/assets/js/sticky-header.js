/* =====================================================================
   sticky-header — stable scroll state with hysteresis
   - enters compact state after 40px, leaves it below 12px
   - rAF-throttled and passive, so the transition cannot chatter
   ===================================================================== */
(function(){
    var header = document.querySelector('.header');
    if (!header) return;

    var root = document.documentElement;
    var ENTER_AT = 40;
    var EXIT_AT = 12;
    var ticking = false;
    var compact = header.classList.contains('is-scrolled');
    var initialStateSettled = false;

    function update(){
        /* A modal fixes <html> and temporarily changes pageYOffset. That is
           not user scrolling, so keep the exact header state visible behind
           the overlay until the original scroll position is restored. */
        if (document.documentElement.classList.contains('scroll-locked')) {
            ticking = false;
            return;
        }

        var y = window.pageYOffset || document.documentElement.scrollTop;
        var nextCompact = compact ? y > EXIT_AT : y > ENTER_AT;

        if (nextCompact !== compact) {
            compact = nextCompact;
            header.classList.toggle('is-scrolled', compact);
        }

        ticking = false;
    }

    window.addEventListener('scroll', function(){
        if (!ticking){
            requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });

    function settleInitialState(){
        if (initialStateSettled) return;
        initialStateSettled = true;

        /* Scroll restoration is completed around pageshow. Sample it for two
           consecutive frames while CSS transitions are disabled, then hand
           control back to the normal animated scroll state. */
        requestAnimationFrame(function(){
            update();
            requestAnimationFrame(function(){
                update();
                root.classList.remove('header-state-restoring');
                root.classList.remove('header-initial-scrolled');
            });
        });
    }

    window.addEventListener('pageshow', settleInitialState, { once: true });
    if (document.readyState === 'complete') settleInitialState();

    /* Do not overwrite the state restored synchronously in <head> with the
       temporary scrollY=0 some browsers expose before pageshow. */
    if (!root.classList.contains('header-state-restoring') ||
        (window.pageYOffset || document.documentElement.scrollTop) > 0) {
        update();
    }
})();
