/* =====================================================================
   bg-fx — subtle decorative particles (desktop only)
   - DISABLED on mobile (<768px) to avoid scroll lag + reveal anim jank
   - Desktop: low density, slow drift, dim alpha
   - Always visible (no pause on scroll) — just stays calm in background
   - pointer-events: none — clicks pass through
   ===================================================================== */
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.innerWidth < 768) return;

    var canvas = document.createElement('canvas');
    canvas.id = 'bg-fx';
    document.documentElement.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var W = 0, H = 0, DPR = Math.min(window.devicePixelRatio || 1, 1.5);
    var particles = [];
    var mouse = { x: -9999, y: -9999, active: false };
    var running = true;

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

    function makeParticles(){
        particles = [];
        var density = 60000;
        var count = Math.round((W * H) / density);
        count = Math.max(14, Math.min(count, 38));
        for (var i = 0; i < count; i++){
            particles.push({
                x: Math.random() * W,
                y: Math.random() * H,
                vx: rand(-0.025, 0.025),     // very slow drift
                vy: rand(-0.025, 0.025),
                r: rand(0.9, 1.8),
                base: rand(0.18, 0.36),      // dim baseline
                pulse: rand(0, Math.PI * 2),
                pulseSpeed: rand(0.0003, 0.0006)
            });
        }
    }

    function step(t){
        if (!running) return;
        ctx.clearRect(0, 0, W, H);

        // Soft cursor halo — gentle hint, no flashing
        if (mouse.active){
            var g = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 170);
            g.addColorStop(0, 'rgba(140,100,255,0.045)');
            g.addColorStop(0.5, 'rgba(140,100,255,0.015)');
            g.addColorStop(1, 'rgba(140,100,255,0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(mouse.x, mouse.y, 170, 0, Math.PI * 2);
            ctx.fill();
        }

        var now = t || 0;

        // Particles
        for (var i = 0; i < particles.length; i++){
            var p = particles[i];
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < -10) p.x = W + 10; else if (p.x > W + 10) p.x = -10;
            if (p.y < -10) p.y = H + 10; else if (p.y > H + 10) p.y = -10;

            // gentle mouse attraction
            if (mouse.active){
                var dx = mouse.x - p.x, dy = mouse.y - p.y;
                var d2 = dx*dx + dy*dy;
                if (d2 < 22500){
                    var d = Math.sqrt(d2);
                    var f = (1 - d / 150) * 0.014;
                    p.x += dx / d * f;
                    p.y += dy / d * f;
                }
            }

            // very slow individual pulse
            var pulse = 0.8 + 0.2 * Math.sin(now * p.pulseSpeed + p.pulse);
            var alpha = p.base * pulse;

            // soft halo for signature glow
            var halo = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4.5);
            halo.addColorStop(0, 'rgba(170,140,255,' + (alpha * 0.32) + ')');
            halo.addColorStop(1, 'rgba(170,140,255,0)');
            ctx.fillStyle = halo;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r * 4.5, 0, Math.PI * 2);
            ctx.fill();

            // crisp core
            ctx.fillStyle = 'rgba(180,150,255,' + alpha + ')';
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();
        }

        // Thin connecting lines — dim, nearby pairs only
        var maxDist = 11000;
        for (var i = 0; i < particles.length; i++){
            for (var j = i + 1; j < particles.length; j++){
                var a = particles[i], b = particles[j];
                var dx = a.x - b.x, dy = a.y - b.y;
                var d2 = dx*dx + dy*dy;
                if (d2 < maxDist){
                    var op = (1 - d2 / maxDist) * 0.08;
                    ctx.strokeStyle = 'rgba(165,135,240,' + op + ')';
                    ctx.lineWidth = 0.5;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
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
        makeParticles();
        requestAnimationFrame(step);
    });
    window.addEventListener('mousemove', onMove, { passive: true });
    window.addEventListener('mouseleave', onLeave);
    document.addEventListener('visibilitychange', function(){
        running = !document.hidden;
        if (running) requestAnimationFrame(step);
    });

    resize();
    makeParticles();
    requestAnimationFrame(step);
})();
