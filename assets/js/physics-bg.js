/* ============================================================
   TEST STORE — Physics Background Animation
   Floating particles: dots, circles, crosses, wavy lines,
   app-icon pill shapes — inspired by Google Play Store aesthetic
   ============================================================ */

(function () {
  'use strict';

  const canvas = document.getElementById('tsPhysicsCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const container = canvas.parentElement;

  /* ── Color Palette (Emerald Green theme) ──────────────────── */
  const COLORS = [
    'rgba(5, 150, 105, 0.42)',
    'rgba(5, 150, 105, 0.55)',
    'rgba(13, 148, 136, 0.45)',
    'rgba(13, 148, 136, 0.58)',
    'rgba(16, 185, 129, 0.38)',
    'rgba(52, 211, 153, 0.48)',
    'rgba(5, 150, 105, 0.35)',
    'rgba(6, 182, 212, 0.40)',
  ];

  const STROKE_COLORS = [
    'rgba(5, 150, 105, 0.65)',
    'rgba(13, 148, 136, 0.70)',
    'rgba(16, 185, 129, 0.60)',
    'rgba(52, 211, 153, 0.72)',
    'rgba(6, 182, 212, 0.58)',
  ];

  const TYPES = ['dot', 'ring', 'cross', 'wave', 'pill', 'diamond', 'dot', 'ring', 'dot'];

  /* ── Resize Canvas ─────────────────────────────────────────── */
  function resize() {
    canvas.width  = container.offsetWidth;
    canvas.height = container.offsetHeight;
  }

  /* ── Random helpers ─────────────────────────────────────────── */
  function rand(min, max) { return Math.random() * (max - min) + min; }
  function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

  /* ── Particle class ─────────────────────────────────────────── */
  class Particle {
    constructor() { this.reset(true); }

    reset(init) {
      this.type   = pick(TYPES);
      this.x      = rand(0, canvas.width);
      this.y      = init ? rand(0, canvas.height) : canvas.height + rand(20, 60);
      this.size   = this.type === 'wave' ? rand(30, 60)
                  : this.type === 'pill' ? rand(18, 32)
                  : rand(4, 18);
      this.color  = pick(COLORS);
      this.stroke = pick(STROKE_COLORS);

      this.vx = rand(-0.22, 0.22);
      this.vy = rand(-0.28, -0.08);

      this.gravity     = rand(0.0005, 0.002);
      this.wobble      = rand(0, Math.PI * 2);
      this.wobbleSpeed = rand(0.008, 0.022);
      this.wobbleAmp   = rand(0.3, 1.1);

      this.angle    = rand(0, Math.PI * 2);
      this.rotSpeed = rand(-0.008, 0.008);

      this.alpha      = rand(0.65, 1.0);
      this.alphaDir   = 1;
      this.alphaSpeed = rand(0.003, 0.009);

      this.wavePoints = Math.floor(rand(3, 6));
    }

    update() {
      this.wobble += this.wobbleSpeed;
      this.x += this.vx + Math.sin(this.wobble) * this.wobbleAmp * 0.4;
      this.vy += this.gravity;
      this.y  += this.vy;
      this.angle += this.rotSpeed;

      this.alpha += this.alphaDir * this.alphaSpeed;
      if (this.alpha >= 1.0)  { this.alpha = 1.0;  this.alphaDir = -1; }
      if (this.alpha <= 0.40) { this.alpha = 0.40; this.alphaDir =  1; }

      if (this.y < -80 || this.x < -80 || this.x > canvas.width + 80) {
        this.reset(false);
      }
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.angle);
      switch (this.type) {
        case 'dot':     this._dot();     break;
        case 'ring':    this._ring();    break;
        case 'cross':   this._cross();   break;
        case 'wave':    this._wave();    break;
        case 'pill':    this._pill();    break;
        case 'diamond': this._diamond(); break;
      }
      ctx.restore();
    }

    _dot() {
      ctx.beginPath();
      ctx.arc(0, 0, this.size * 0.5, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1;
      ctx.stroke();
    }

    _ring() {
      ctx.beginPath();
      ctx.arc(0, 0, this.size * 0.55, 0, Math.PI * 2);
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1.8;
      ctx.stroke();
    }

    _cross() {
      const s = this.size * 0.55;
      const t = s * 0.28;
      ctx.fillStyle = this.stroke;
      ctx.beginPath();
      ctx.roundRect(-t, -s, t * 2, s * 2, t);
      ctx.fill();
      ctx.beginPath();
      ctx.roundRect(-s, -t, s * 2, t * 2, t);
      ctx.fill();
    }

    _wave() {
      const w    = this.size;
      const amp  = w * 0.25;
      const segs = this.wavePoints;
      ctx.beginPath();
      for (let i = 0; i <= segs * 8; i++) {
        const t  = i / (segs * 8);
        const px = (t - 0.5) * w * 2;
        const py = Math.sin(t * segs * Math.PI * 2) * amp;
        i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
      }
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth   = 2;
      ctx.lineCap     = 'round';
      ctx.stroke();
    }

    _pill() {
      const w = this.size * 1.6;
      const h = this.size * 0.65;
      const r = h * 0.5;
      ctx.beginPath();
      ctx.roundRect(-w * 0.5, -r, w, h, r);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1.2;
      ctx.stroke();
    }

    _diamond() {
      const s = this.size * 0.6;
      ctx.beginPath();
      ctx.moveTo(0, -s);
      ctx.lineTo(s * 0.7, 0);
      ctx.lineTo(0, s);
      ctx.lineTo(-s * 0.7, 0);
      ctx.closePath();
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1;
      ctx.stroke();
    }
  }

  /* ── Icon Particle (floating rounded square) ─────────────────── */
  class IconParticle {
    constructor() { this.reset(true); }

    reset(init) {
      this.size = rand(28, 50);
      this.x    = rand(this.size, canvas.width - this.size);
      this.y    = init ? rand(0, canvas.height) : canvas.height + this.size + 20;

      this.vx = rand(-0.14, 0.14);
      this.vy = rand(-0.18, -0.06);

      this.wobble      = rand(0, Math.PI * 2);
      this.wobbleSpeed = rand(0.006, 0.016);
      this.wobbleAmp   = rand(0.4, 1.2);

      this.angle    = rand(-0.25, 0.25);
      this.rotSpeed = rand(-0.003, 0.003);

      this.alpha      = rand(0.55, 0.90);
      this.alphaDir   = 1;
      this.alphaSpeed = rand(0.002, 0.006);

      this.bgColor   = pick(['rgba(255,255,255,0.85)', 'rgba(220,252,231,0.82)', 'rgba(187,247,208,0.75)']);
      this.bordColor = pick(STROKE_COLORS);
    }

    update() {
      this.wobble += this.wobbleSpeed;
      this.x += this.vx + Math.sin(this.wobble) * this.wobbleAmp * 0.35;
      this.y += this.vy;
      this.angle += this.rotSpeed;

      this.alpha += this.alphaDir * this.alphaSpeed;
      if (this.alpha >= 0.92) { this.alpha = 0.92; this.alphaDir = -1; }
      if (this.alpha <= 0.40) { this.alpha = 0.40; this.alphaDir =  1; }

      if (this.y < -this.size * 2) this.reset(false);
    }

    draw() {
      const s = this.size;
      const r = s * 0.28;
      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.angle);

      ctx.shadowColor = this.bordColor;
      ctx.shadowBlur  = 12;

      ctx.beginPath();
      ctx.roundRect(-s * 0.5, -s * 0.5, s, s, r);
      ctx.fillStyle = this.bgColor;
      ctx.fill();

      ctx.shadowBlur  = 0;
      ctx.strokeStyle = this.bordColor;
      ctx.lineWidth   = 1.2;
      ctx.stroke();

      ctx.restore();
    }
  }

  /* ── Init ───────────────────────────────────────────────────── */
  let particles = [];
  let icons     = [];

  function initParticles() {
    const count     = Math.min(Math.floor((canvas.width * canvas.height) / 5000), 120);
    const iconCount = Math.min(Math.floor(canvas.width / 90), 18);
    particles = Array.from({ length: count }, () => new Particle());
    icons     = Array.from({ length: iconCount }, () => new IconParticle());
  }

  /* ── Animation Loop ─────────────────────────────────────────── */
  let rafId;
  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    icons.forEach(ic => { ic.update(); ic.draw(); });
    particles.forEach(p => { p.update(); p.draw(); });
    rafId = requestAnimationFrame(animate);
  }

  /* ── Resize ─────────────────────────────────────────────────── */
  function onResize() {
    cancelAnimationFrame(rafId);
    resize();
    initParticles();
    animate();
  }

  /* ── Respect prefers-reduced-motion ─────────────────────────── */
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.style.display = 'none';
    return;
  }

  /* ── Boot ───────────────────────────────────────────────────── */
  resize();
  initParticles();
  animate();

  const ro = new ResizeObserver(onResize);
  ro.observe(container);
  window.addEventListener('resize', onResize, { passive: true });

}());
