/* =====================================================================
   sticky-header — stable scroll state with hysteresis
   - enters compact state after 40px, leaves it below 12px
   - rAF-throttled and passive, so the transition cannot chatter
   ===================================================================== */
(function(){
    var header = document.querySelector('.header');
    if (!header) return;

    var ENTER_AT = 40;
    var EXIT_AT = 12;
    var ticking = false;
    var compact = false;

    function update(){
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

    update();
})();
