/* =====================================================================
   bg-fx — subtle foreground particles (decorative, non-intrusive)
   - low density, slow drift, dim alpha
   - pointer-events: none — clicks pass through
   - cursor draws a soft glow + gently attracts nearby particles
   ===================================================================== */
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var canvas = document.createElement('canvas');
    canvas.id = 'bg-fx';
    // Mount on documentElement to avoid body transforms/zoom shifting our fixed positioning
    document.documentElement.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var W = 0, H = 0, DPR = Math.min(window.devicePixelRatio || 1, 1.75);
    var particles = [];
    var mouse = { x: -9999, y: -9999, active: false };
    var running = true;
    var isMobile = window.innerWidth < 600;

    function resize(){
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = W * DPR;
        canvas.height = H * DPR;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        isMobile = W < 600;
    }

    function rand(a, b){ return a + Math.random() * (b - a); }

    function makeParticles(){
        particles = [];
        // Moderate density — visible but not crowded
        var density = isMobile ? 55000 : 32000;
        var count = Math.round((W * H) / density);
        count = Math.max(20, Math.min(count, isMobile ? 32 : 75));
        for (var i = 0; i < count; i++){
            particles.push({
                x: Math.random() * W,
                y: Math.random() * H,
                vx: rand(-0.10, 0.10),  // gentle drift
                vy: rand(-0.10, 0.10),
                r: rand(1.1, 2.4),       // slightly bigger
                base: rand(0.45, 0.80),  // brighter
                pulse: rand(0, Math.PI * 2)
            });
        }
    }

    function step(t){
        if (!running) return;
        ctx.clearRect(0, 0, W, H);

        // Soft cursor halo — visible but tasteful
        if (mouse.active){
            var g = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, 220);
            g.addColorStop(0, 'rgba(140,100,255,0.14)');
            g.addColorStop(0.4, 'rgba(140,100,255,0.05)');
            g.addColorStop(1, 'rgba(140,100,255,0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(mouse.x, mouse.y, 220, 0, Math.PI * 2);
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
                    var f = (1 - d / 150) * 0.04;
                    p.x += dx / d * f;
                    p.y += dy / d * f;
                }
            }

            // gentle pulse — slow
            var pulse = 0.7 + 0.3 * Math.sin(now * 0.001 + p.pulse);
            var alpha = p.base * pulse;

            // soft halo for that signature glow look
            var halo = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
            halo.addColorStop(0, 'rgba(170,140,255,' + (alpha * 0.4) + ')');
            halo.addColorStop(1, 'rgba(170,140,255,0)');
            ctx.fillStyle = halo;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2);
            ctx.fill();

            // crisp core
            ctx.fillStyle = 'rgba(190,160,255,' + alpha + ')';
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();
        }

        // Thin connecting lines — only nearby pairs
        var maxDist = isMobile ? 9000 : 12500;
        for (var i = 0; i < particles.length; i++){
            for (var j = i + 1; j < particles.length; j++){
                var a = particles[i], b = particles[j];
                var dx = a.x - b.x, dy = a.y - b.y;
                var d2 = dx*dx + dy*dy;
                if (d2 < maxDist){
                    var op = (1 - d2 / maxDist) * 0.16;
                    ctx.strokeStyle = 'rgba(165,135,240,' + op + ')';
                    ctx.lineWidth = 0.6;
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
        // Account for any positioning shift between viewport (where clientX/Y is)
        // and our canvas (which may not be at viewport 0,0 due to scrollbars/zoom).
        var r = canvas.getBoundingClientRect();
        return { x: r.left, y: r.top, sx: r.width / W || 1, sy: r.height / H || 1 };
    }
    function onMove(e){
        var o = getOffset();
        mouse.x = (e.clientX - o.x) / o.sx;
        mouse.y = (e.clientY - o.y) / o.sy;
        mouse.active = true;
    }
    function onTouch(e){
        if (e.touches && e.touches[0]){
            var o = getOffset();
            mouse.x = (e.touches[0].clientX - o.x) / o.sx;
            mouse.y = (e.touches[0].clientY - o.y) / o.sy;
            mouse.active = true;
        }
    }
    function onLeave(){ mouse.active = false; }

    window.addEventListener('resize', function(){ resize(); makeParticles(); });
    window.addEventListener('mousemove', onMove, { passive: true });
    window.addEventListener('touchmove', onTouch, { passive: true });
    window.addEventListener('mouseleave', onLeave);
    document.addEventListener('visibilitychange', function(){
        running = !document.hidden;
        if (running) requestAnimationFrame(step);
    });

    resize();
    makeParticles();
    requestAnimationFrame(step);
})();
