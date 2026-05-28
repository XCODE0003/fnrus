/* =====================================================================
   bg-fx — premium starfield (subtle, calm)
   - DISABLED on mobile (<768px) for performance + scroll smoothness
   - ~90 tiny stars with independent slow twinkle
   - A handful of "feature" stars get a soft glow halo
   - Very slight downward parallax drift for depth
   - Mouse passes through; nearby stars subtly brighten on hover
   - pointer-events: none — clicks pass through everything
   ===================================================================== */
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.innerWidth < 768) return;

    var canvas = document.createElement('canvas');
    canvas.id = 'bg-fx';
    document.documentElement.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var W = 0, H = 0, DPR = Math.min(window.devicePixelRatio || 1, 1.5);
    var stars = [];
    var mouse = { x: -9999, y: -9999, active: false };
    var running = true;
    var t0 = performance.now();

    function resize(){
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = W * DPR;
        canvas.height = H * DPR;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    }

    function rand(a, b){ return a + Math.random() * (b - a); }

    function makeStars(){
        stars = [];
        var area = W * H;
        var count = Math.min(140, Math.max(50, Math.round(area / 18000)));
        for (var i = 0; i < count; i++){
            var feature = Math.random() < 0.08;     // ~8% are bright "feature" stars
            stars.push({
                x: Math.random() * W,
                y: Math.random() * H,
                r: feature ? rand(1.4, 2.1) : rand(0.5, 1.2),
                base: feature ? rand(0.45, 0.75) : rand(0.18, 0.42),
                feature: feature,
                // Independent slow twinkle
                twinkleSpeed: rand(0.0004, 0.0011),
                twinklePhase: rand(0, Math.PI * 2),
                twinkleDepth: rand(0.35, 0.65),
                // Slight tint variation — mostly white, some lavender
                hue: Math.random() < 0.25 ? 'lavender' : 'white',
                // Parallax drift (very gentle, mostly downward)
                vy: rand(0.015, 0.05),
                vx: rand(-0.012, 0.012)
            });
        }
    }

    function step(now){
        if (!running) return;
        var t = now - t0;

        ctx.clearRect(0, 0, W, H);

        for (var i = 0; i < stars.length; i++){
            var s = stars[i];

            // drift
            s.x += s.vx;
            s.y += s.vy;
            if (s.y > H + 5)  { s.y = -5; s.x = Math.random() * W; }
            if (s.y < -5)     { s.y = H + 5; }
            if (s.x > W + 5)  s.x = -5;
            if (s.x < -5)     s.x = W + 5;

            // twinkle
            var pulse = 1 - s.twinkleDepth * (0.5 - 0.5 * Math.cos(t * s.twinkleSpeed + s.twinklePhase));
            var alpha = s.base * pulse;

            // mouse proximity glow boost
            if (mouse.active){
                var dx = mouse.x - s.x, dy = mouse.y - s.y;
                var d2 = dx*dx + dy*dy;
                if (d2 < 16000){
                    var prox = 1 - d2 / 16000;
                    alpha += prox * 0.25;
                }
            }

            var rgb = s.hue === 'lavender'
                ? '190,170,255'
                : '230,230,250';

            // feature stars: soft halo
            if (s.feature){
                var halo = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.r * 6);
                halo.addColorStop(0, 'rgba(' + rgb + ',' + (alpha * 0.45) + ')');
                halo.addColorStop(0.4, 'rgba(' + rgb + ',' + (alpha * 0.12) + ')');
                halo.addColorStop(1, 'rgba(' + rgb + ',0)');
                ctx.fillStyle = halo;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r * 6, 0, Math.PI * 2);
                ctx.fill();
            }

            // crisp core
            ctx.fillStyle = 'rgba(' + rgb + ',' + Math.min(alpha, 1) + ')';
            ctx.beginPath();
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
            ctx.fill();
        }

        requestAnimationFrame(step);
    }

    function getOffset(){
        var r = canvas.getBoundingClientRect();
        return { x: r.left, y: r.top, sx: r.width / W || 1, sy: r.height / H || 1 };
    }
    function onMove(e){
        var o = getOffset();
        mouse.x = (e.clientX - o.x) / o.sx;
        mouse.y = (e.clientY - o.y) / o.sy;
        mouse.active = true;
    }
    function onLeave(){ mouse.active = false; }

    window.addEventListener('resize', function(){
        if (window.innerWidth < 768){
            running = false;
            canvas.style.display = 'none';
            return;
        }
        canvas.style.display = '';
        running = true;
        resize();
        makeStars();
        requestAnimationFrame(step);
    });
    window.addEventListener('mousemove', onMove, { passive: true });
    window.addEventListener('mouseleave', onLeave);
    document.addEventListener('visibilitychange', function(){
        running = !document.hidden;
        if (running) requestAnimationFrame(step);
    });

    resize();
    makeStars();
    requestAnimationFrame(step);
})();
