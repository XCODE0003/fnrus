/* =====================================================================
   sticky-header — toggle .is-scrolled on .header after small scroll
   - rAF-throttled, passive listener
   - keeps header always visible (it's position:fixed in CSS)
   ===================================================================== */
(function(){
    var header = document.querySelector('.header');
    if (!header) return;

    var TH = 32;
    var ticking = false;

    function update(){
        var y = window.pageYOffset || document.documentElement.scrollTop;
        if (y > TH) header.classList.add('is-scrolled');
        else header.classList.remove('is-scrolled');
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
