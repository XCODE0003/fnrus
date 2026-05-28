/* =====================================================================
   bg-fx — soft drifting gradient orbs, ONLY at edges/corners
   - DISABLED on mobile (<768px) to keep scroll smooth
   - 4 large soft radial blobs anchored at the corners, drifting locally
   - Center of viewport stays clean — no orbs cover content
   - pointer-events: none — clicks pass through
   ===================================================================== */
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.innerWidth < 768) return;

    var canvas = document.createElement('canvas');
    canvas.id = 'bg-fx';
    document.documentElement.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var W = 0, H = 0, DPR = Math.min(window.devicePixelRatio || 1, 1.25);
    var orbs = [];
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

    function makeOrbs(){
        // Brand palette — purples
        var palette = [
            [148, 104, 255],   // #9468ff — brand purple
            [124, 82, 240],    // deeper purple
            [180, 145, 255],   // lighter lavender
            [90, 60, 200]      // dark accent
        ];
        // Anchor each orb to a CORNER. Small amplitude → drifts only locally,
        // never crosses into the central content area.
        var anchors = [
            { cx: 0.08, cy: 0.10 },  // top-left
            { cx: 0.92, cy: 0.08 },  // top-right
            { cx: 0.06, cy: 0.92 },  // bottom-left
            { cx: 0.94, cy: 0.90 }   // bottom-right
        ];
        orbs = [];
        for (var i = 0; i < 4; i++){
            var a = anchors[i];
            var c = palette[i % palette.length];
            orbs.push({
                cx: a.cx,
                cy: a.cy,
                ax: rand(0.04, 0.08),       // tiny local drift
                ay: rand(0.04, 0.08),
                fx: rand(0.000015, 0.000035),
                fy: rand(0.000015, 0.000035),
                phx: rand(0, Math.PI * 2),
                phy: rand(0, Math.PI * 2),
                radius: rand(320, 480),
                color: c,
                baseAlpha: rand(0.20, 0.32),
                pulseSpeed: rand(0.0003, 0.0006),
                pulsePhase: rand(0, Math.PI * 2)
            });
        }
    }

    function step(now){
        if (!running) return;
        var t = now - t0;

        ctx.clearRect(0, 0, W, H);

        for (var i = 0; i < orbs.length; i++){
            var o = orbs[i];
            var x = (o.cx + Math.sin(t * o.fx + o.phx) * o.ax) * W;
            var y = (o.cy + Math.cos(t * o.fy + o.phy) * o.ay) * H;
            var pulse = 0.85 + 0.15 * Math.sin(t * o.pulseSpeed + o.pulsePhase);
            var alpha = o.baseAlpha * pulse;

            var g = ctx.createRadialGradient(x, y, 0, x, y, o.radius);
            g.addColorStop(0,    'rgba(' + o.color[0] + ',' + o.color[1] + ',' + o.color[2] + ',' + alpha + ')');
            g.addColorStop(0.45, 'rgba(' + o.color[0] + ',' + o.color[1] + ',' + o.color[2] + ',' + (alpha * 0.3) + ')');
            g.addColorStop(1,    'rgba(' + o.color[0] + ',' + o.color[1] + ',' + o.color[2] + ',0)');
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(x, y, o.radius, 0, Math.PI * 2);
            ctx.fill();
        }

        // Cut out the central viewport area — guarantees content stays clean
        // even if a large orb's gradient bleeds toward middle.
        ctx.globalCompositeOperation = 'destination-out';
        var cx = W / 2, cy = H / 2;
        var rOuter = Math.min(W, H) * 0.55;
        var rInner = Math.min(W, H) * 0.18;
        var cut = ctx.createRadialGradient(cx, cy, rInner, cx, cy, rOuter);
        cut.addColorStop(0,   'rgba(0,0,0,1)');
        cut.addColorStop(0.7, 'rgba(0,0,0,0.55)');
        cut.addColorStop(1,   'rgba(0,0,0,0)');
        ctx.fillStyle = cut;
        ctx.fillRect(0, 0, W, H);
        ctx.globalCompositeOperation = 'source-over';

        requestAnimationFrame(step);
    }

    window.addEventListener('resize', function(){
        if (window.innerWidth < 768){
            running = false;
            canvas.style.display = 'none';
            return;
        }
        canvas.style.display = '';
        running = true;
        resize();
        makeOrbs();
        requestAnimationFrame(step);
    });
    document.addEventListener('visibilitychange', function(){
        running = !document.hidden;
        if (running) requestAnimationFrame(step);
    });

    resize();
    makeOrbs();
    requestAnimationFrame(step);
})();
