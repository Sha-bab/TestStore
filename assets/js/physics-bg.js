/* ============================================================
   TEST STORE — Android & Dev Themed Physics Background Animation
   Floating elements: Android Bugdroid, Code Tags </>, Terminal >_,
   Curly Braces {}, Git Branches, Smartphones, Microchips,
   Dev Badges (APK, DEV, SDK, KOTLIN), Mini IDE & App Cards.
   ============================================================ */

(function () {
  'use strict';

  const canvas = document.getElementById('tsPhysicsCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const container = canvas.parentElement;

  /* ── Respect prefers-reduced-motion ─────────────────────────── */
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.style.display = 'none';
    return;
  }

  /* ── Color Palette (Emerald + Android Green + Teal + Cyan) ───── */
  const COLORS = [
    'rgba(5, 150, 105, 0.24)',   // Emerald
    'rgba(13, 148, 136, 0.26)',  // Teal
    'rgba(16, 185, 129, 0.22)',  // Light Emerald
    'rgba(52, 211, 153, 0.28)',  // Mint
    'rgba(6, 182, 212, 0.22)',   // Cyan
    'rgba(61, 220, 132, 0.26)',  // Android Green (#3DDC84)
  ];

  const STROKE_COLORS = [
    'rgba(5, 150, 105, 0.72)',
    'rgba(13, 148, 136, 0.75)',
    'rgba(16, 185, 129, 0.70)',
    'rgba(52, 211, 153, 0.78)',
    'rgba(6, 182, 212, 0.68)',
    'rgba(61, 220, 132, 0.82)',  // Android Green
  ];

  const BADGE_WORDS = ['APK', 'DEV', 'APP', 'KOTLIN', 'JAVA', 'SDK', 'API', 'DEBUG', 'BUILD', 'GIT', 'adb', 'null'];
  const CODE_TOKENS = ['01', '10', '{ }', '</>', '&&', '=>', 'fn()', 'val', 'fun', 'apk', 'try'];

  const PARTICLE_TYPES = [
    'android', 'android', 'codeTag', 'codeTag',
    'terminal', 'braces', 'gitBranch', 'smartphone',
    'chip', 'bug', 'devBadge', 'binary', 'node'
  ];

  const CARD_TYPES = ['editor', 'app', 'terminal', 'pipeline'];

  /* ── Canvas Sizing & Retina DPR ─────────────────────────────── */
  let dpr = 1;
  let viewW = 0;
  let viewH = 0;

  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    viewW = container.offsetWidth;
    viewH = container.offsetHeight;
    canvas.width  = Math.round(viewW * dpr);
    canvas.height = Math.round(viewH * dpr);
    canvas.style.width  = viewW + 'px';
    canvas.style.height = viewH + 'px';
  }

  /* ── Random Helpers ─────────────────────────────────────────── */
  function rand(min, max) { return Math.random() * (max - min) + min; }
  function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

  /* ── Interactive Mouse Position ─────────────────────────────── */
  const mouse = { x: -9999, y: -9999, active: false };
  window.addEventListener('mousemove', function (e) {
    const rect = canvas.getBoundingClientRect();
    if (
      e.clientX >= rect.left && e.clientX <= rect.right &&
      e.clientY >= rect.top  && e.clientY <= rect.bottom
    ) {
      mouse.x = e.clientX - rect.left;
      mouse.y = e.clientY - rect.top;
      mouse.active = true;
    } else {
      mouse.active = false;
    }
  }, { passive: true });

  window.addEventListener('mouseleave', function () {
    mouse.active = false;
    mouse.x = -9999;
    mouse.y = -9999;
  });

  /* ── Dev Particle Class (Floating Android / Code / Dev Icons) ── */
  class DevParticle {
    constructor() { this.reset(true); }

    reset(init) {
      this.type   = pick(PARTICLE_TYPES);
      this.x      = rand(20, Math.max(viewW - 20, 40));
      this.y      = init ? rand(0, viewH) : viewH + rand(20, 70);
      
      // Sizing tuned for each symbol type
      this.size   = this.type === 'android'    ? rand(22, 36)
                  : this.type === 'smartphone' ? rand(24, 34)
                  : this.type === 'devBadge'   ? rand(18, 24)
                  : this.type === 'chip'       ? rand(20, 30)
                  : this.type === 'codeTag'    ? rand(18, 28)
                  : this.type === 'terminal'   ? rand(18, 26)
                  : this.type === 'gitBranch'  ? rand(22, 32)
                  : this.type === 'bug'        ? rand(18, 26)
                  : this.type === 'node'       ? rand(5, 10)
                  : rand(14, 22);

      this.color  = pick(COLORS);
      this.stroke = pick(STROKE_COLORS);
      this.text   = this.type === 'devBadge' ? pick(BADGE_WORDS) : pick(CODE_TOKENS);

      // Gentle upward floating velocity
      this.vx = rand(-0.06, 0.06);
      this.vy = rand(-0.09, -0.035);

      this.wobble      = rand(0, Math.PI * 2);
      this.wobbleSpeed = rand(0.003, 0.008);
      this.wobbleAmp   = rand(0.3, 0.7);

      // Slight rotation
      this.angle    = rand(-0.3, 0.3);
      this.rotSpeed = rand(-0.0015, 0.0015);

      // Smooth opacity breathing
      this.alpha      = rand(0.55, 0.88);
      this.alphaDir   = Math.random() > 0.5 ? 1 : -1;
      this.alphaSpeed = rand(0.0015, 0.0035);
    }

    update() {
      this.wobble += this.wobbleSpeed;
      this.x += this.vx + Math.sin(this.wobble) * this.wobbleAmp * 0.45;
      this.y += this.vy;
      this.angle += this.rotSpeed;

      // Mouse repulsion
      if (mouse.active) {
        const dx = this.x - mouse.x;
        const dy = this.y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const repelRadius = 85;
        if (dist < repelRadius && dist > 0) {
          const force = (repelRadius - dist) / repelRadius;
          this.x += (dx / dist) * force * 1.6;
          this.y += (dy / dist) * force * 1.6;
        }
      }

      // Alpha oscillation
      this.alpha += this.alphaDir * this.alphaSpeed;
      if (this.alpha >= 0.92) { this.alpha = 0.92; this.alphaDir = -1; }
      if (this.alpha <= 0.35) { this.alpha = 0.35; this.alphaDir =  1; }

      // Recycle offscreen
      if (this.y < -80 || this.x < -80 || this.x > viewW + 80) {
        this.reset(false);
      }
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.angle);

      switch (this.type) {
        case 'android':    this._drawAndroid();    break;
        case 'codeTag':    this._drawCodeTag();    break;
        case 'terminal':   this._drawTerminal();   break;
        case 'braces':     this._drawBraces();     break;
        case 'gitBranch':  this._drawGitBranch();  break;
        case 'smartphone': this._drawSmartphone(); break;
        case 'chip':       this._drawChip();       break;
        case 'bug':        this._drawBug();        break;
        case 'devBadge':   this._drawDevBadge();   break;
        case 'binary':     this._drawBinary();     break;
        case 'node':       this._drawNode();       break;
      }

      ctx.restore();
    }

    /* ── Android Bugdroid Head ───────────────────────── */
    _drawAndroid() {
      const r = this.size * 0.52;
      const strokeW = Math.max(1.2, r * 0.08);

      // Semicircular dome
      ctx.beginPath();
      ctx.arc(0, 0, r, Math.PI, 0, false);
      ctx.closePath();
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = strokeW;
      ctx.stroke();

      // Antennae
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.4, r * 0.12);
      ctx.lineCap = 'round';

      const aLen = r * 0.42;
      const lx = -r * 0.52;
      const ly = -r * 0.72;
      ctx.beginPath();
      ctx.moveTo(lx, ly);
      ctx.lineTo(lx - Math.sin(Math.PI / 6) * aLen, ly - Math.cos(Math.PI / 6) * aLen);
      ctx.stroke();

      const rx = r * 0.52;
      const ry = -r * 0.72;
      ctx.beginPath();
      ctx.moveTo(rx, ry);
      ctx.lineTo(rx + Math.sin(Math.PI / 6) * aLen, ry - Math.cos(Math.PI / 6) * aLen);
      ctx.stroke();

      // Eyes
      const eyeR = Math.max(1.2, r * 0.11);
      const eyeY = -r * 0.42;
      const eyeX = r * 0.42;
      ctx.fillStyle = '#ffffff';
      ctx.beginPath();
      ctx.arc(-eyeX, eyeY, eyeR, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(eyeX, eyeY, eyeR, 0, Math.PI * 2);
      ctx.fill();
    }

    /* ── Code Tag </> ────────────────────────────────── */
    _drawCodeTag() {
      const s = this.size * 0.5;
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.6, this.size * 0.09);
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';

      // Left bracket <
      ctx.beginPath();
      ctx.moveTo(-s * 0.45, -s * 0.55);
      ctx.lineTo(-s * 0.95, 0);
      ctx.lineTo(-s * 0.45, s * 0.55);
      ctx.stroke();

      // Slash /
      ctx.beginPath();
      ctx.moveTo(-s * 0.12, s * 0.65);
      ctx.lineTo(s * 0.12, -s * 0.65);
      ctx.stroke();

      // Right bracket >
      ctx.beginPath();
      ctx.moveTo(s * 0.45, -s * 0.55);
      ctx.lineTo(s * 0.95, 0);
      ctx.lineTo(s * 0.45, s * 0.55);
      ctx.stroke();
    }

    /* ── Terminal Prompt >_ ─────────────────────────── */
    _drawTerminal() {
      const s = this.size * 0.5;
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.6, this.size * 0.1);
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';

      // >
      ctx.beginPath();
      ctx.moveTo(-s * 0.85, -s * 0.52);
      ctx.lineTo(-s * 0.28, 0);
      ctx.lineTo(-s * 0.85, s * 0.52);
      ctx.stroke();

      // _
      ctx.beginPath();
      ctx.moveTo(s * 0.05, s * 0.46);
      ctx.lineTo(s * 0.82, s * 0.46);
      ctx.stroke();
    }

    /* ── Curly Braces { } ───────────────────────────── */
    _drawBraces() {
      ctx.font = `600 ${Math.round(this.size * 0.88)}px 'Fira Code', 'Courier New', monospace`;
      ctx.fillStyle = this.stroke;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('{ }', 0, 0);
    }

    /* ── Git Branch / Commit Node ───────────────────── */
    _drawGitBranch() {
      const s = this.size * 0.5;
      const nodeR = Math.max(2.2, this.size * 0.12);
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.4, this.size * 0.08);
      ctx.lineCap = 'round';

      // Main trunk line
      ctx.beginPath();
      ctx.moveTo(-s * 0.4, -s * 0.65);
      ctx.lineTo(-s * 0.4, s * 0.65);
      ctx.stroke();

      // Branch curve
      ctx.beginPath();
      ctx.moveTo(-s * 0.4, s * 0.15);
      ctx.bezierCurveTo(-s * 0.35, -s * 0.25, s * 0.35, -s * 0.05, s * 0.4, -s * 0.4);
      ctx.stroke();

      // Nodes
      ctx.fillStyle = this.stroke;
      ctx.beginPath();
      ctx.arc(-s * 0.4, s * 0.55, nodeR, 0, Math.PI * 2);
      ctx.fill();

      ctx.beginPath();
      ctx.arc(-s * 0.4, -s * 0.55, nodeR, 0, Math.PI * 2);
      ctx.fill();

      ctx.beginPath();
      ctx.arc(s * 0.4, -s * 0.42, nodeR, 0, Math.PI * 2);
      ctx.fill();
    }

    /* ── Smartphone Outline ─────────────────────────── */
    _drawSmartphone() {
      const w = this.size * 0.62;
      const h = this.size * 1.05;
      const r = this.size * 0.15;

      // Phone body
      ctx.beginPath();
      ctx.roundRect(-w * 0.5, -h * 0.5, w, h, r);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.2, this.size * 0.06);
      ctx.stroke();

      // Screen window
      const sw = w * 0.74;
      const sh = h * 0.66;
      ctx.beginPath();
      ctx.roundRect(-sw * 0.5, -sh * 0.46, sw, sh, r * 0.5);
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1;
      ctx.stroke();

      // Camera dot
      ctx.beginPath();
      ctx.arc(0, -h * 0.42, Math.max(1.2, this.size * 0.04), 0, Math.PI * 2);
      ctx.fillStyle = this.stroke;
      ctx.fill();

      // Home indicator bar
      ctx.beginPath();
      ctx.roundRect(-w * 0.18, h * 0.38, w * 0.36, Math.max(1.2, this.size * 0.04), 1);
      ctx.fill();
    }

    /* ── Microchip / CPU ────────────────────────────── */
    _drawChip() {
      const s = this.size * 0.52;
      const pinLen = this.size * 0.14;
      const pinW   = Math.max(1.1, this.size * 0.07);

      // Core box
      ctx.beginPath();
      ctx.roundRect(-s * 0.5, -s * 0.5, s, s, this.size * 0.08);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.2, this.size * 0.06);
      ctx.stroke();

      // Inner silicon square
      ctx.beginPath();
      ctx.roundRect(-s * 0.25, -s * 0.25, s * 0.5, s * 0.5, 2);
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1;
      ctx.stroke();

      // Connector pins on 4 sides
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = pinW;
      ctx.lineCap = 'round';
      const offs = [-s * 0.25, s * 0.25];
      offs.forEach(off => {
        // Top
        ctx.beginPath(); ctx.moveTo(off, -s * 0.5); ctx.lineTo(off, -s * 0.5 - pinLen); ctx.stroke();
        // Bottom
        ctx.beginPath(); ctx.moveTo(off, s * 0.5); ctx.lineTo(off, s * 0.5 + pinLen); ctx.stroke();
        // Left
        ctx.beginPath(); ctx.moveTo(-s * 0.5, off); ctx.lineTo(-s * 0.5 - pinLen, off); ctx.stroke();
        // Right
        ctx.beginPath(); ctx.moveTo(s * 0.5, off); ctx.lineTo(s * 0.5 + pinLen, off); ctx.stroke();
      });
    }

    /* ── Debugger Beetle / Bug ───────────────────────── */
    _drawBug() {
      const s = this.size * 0.44;

      // Body
      ctx.beginPath();
      ctx.ellipse(0, s * 0.1, s * 0.6, s * 0.78, 0, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = Math.max(1.2, this.size * 0.07);
      ctx.stroke();

      // Head
      ctx.beginPath();
      ctx.arc(0, -s * 0.75, s * 0.36, 0, Math.PI * 2);
      ctx.fillStyle = this.stroke;
      ctx.fill();

      // Antennae
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1.2;
      ctx.lineCap = 'round';
      ctx.beginPath();
      ctx.moveTo(-s * 0.18, -s * 0.95);
      ctx.lineTo(-s * 0.52, -s * 1.35);
      ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(s * 0.18, -s * 0.95);
      ctx.lineTo(s * 0.52, -s * 1.35);
      ctx.stroke();

      // 6 Legs
      const legYs = [-s * 0.35, s * 0.1, s * 0.5];
      legYs.forEach(ly => {
        ctx.beginPath();
        ctx.moveTo(-s * 0.55, ly);
        ctx.lineTo(-s * 0.98, ly - s * 0.18);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(s * 0.55, ly);
        ctx.lineTo(s * 0.98, ly - s * 0.18);
        ctx.stroke();
      });
    }

    /* ── Dev Badge Pill (APK, DEV, SDK, etc.) ───────── */
    _drawDevBadge() {
      ctx.font = `700 ${Math.round(this.size * 0.52)}px 'Fira Code', 'Consolas', monospace`;
      const txt = this.text;
      const metrics = ctx.measureText(txt);
      const padX = this.size * 0.36;
      const w = metrics.width + padX * 2;
      const h = this.size * 0.78;
      const r = h * 0.5;

      ctx.beginPath();
      ctx.roundRect(-w * 0.5, -h * 0.5, w, h, r);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1.2;
      ctx.stroke();

      ctx.fillStyle = this.stroke;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(txt, 0, 1);
    }

    /* ── Code Token / Binary Bit (01, fn(), etc.) ───── */
    _drawBinary() {
      ctx.font = `600 ${Math.round(this.size * 0.68)}px 'Fira Code', 'Consolas', monospace`;
      ctx.fillStyle = this.stroke;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(this.text, 0, 0);
    }

    /* ── Ambient Node ───────────────────────────────── */
    _drawNode() {
      ctx.beginPath();
      ctx.arc(0, 0, this.size * 0.5, 0, Math.PI * 2);
      ctx.fillStyle = this.color;
      ctx.fill();
      ctx.strokeStyle = this.stroke;
      ctx.lineWidth = 1.2;
      ctx.stroke();
    }
  }

  /* ── Dev Card Class (Floating IDE Windows & App Icons) ──────── */
  class DevCard {
    constructor() { this.reset(true); }

    reset(init) {
      this.cardType = pick(CARD_TYPES);
      this.w = this.cardType === 'editor'   ? rand(56, 72)
             : this.cardType === 'terminal' ? rand(60, 76)
             : this.cardType === 'pipeline' ? rand(58, 70)
             : rand(44, 52);
      this.h = this.cardType === 'app' ? this.w : this.w * 0.66;

      this.x = rand(this.w, Math.max(viewW - this.w, 40));
      this.y = init ? rand(0, viewH) : viewH + this.h + rand(20, 60);

      this.vx = rand(-0.035, 0.035);
      this.vy = rand(-0.065, -0.025);

      this.wobble      = rand(0, Math.PI * 2);
      this.wobbleSpeed = rand(0.002, 0.005);
      this.wobbleAmp   = rand(0.2, 0.45);

      this.angle    = rand(-0.15, 0.15);
      this.rotSpeed = rand(-0.0006, 0.0006);

      this.alpha      = rand(0.55, 0.85);
      this.alphaDir   = 1;
      this.alphaSpeed = rand(0.001, 0.0025);

      this.bgColor   = pick([
        'rgba(255, 255, 255, 0.88)',
        'rgba(240, 253, 244, 0.85)',
        'rgba(220, 252, 231, 0.80)'
      ]);
      this.bordColor = pick(STROKE_COLORS);
    }

    update() {
      this.wobble += this.wobbleSpeed;
      this.x += this.vx + Math.sin(this.wobble) * this.wobbleAmp * 0.35;
      this.y += this.vy;
      this.angle += this.rotSpeed;

      // Mouse repulsion
      if (mouse.active) {
        const dx = this.x - mouse.x;
        const dy = this.y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        const repelRadius = 110;
        if (dist < repelRadius && dist > 0) {
          const force = (repelRadius - dist) / repelRadius;
          this.x += (dx / dist) * force * 1.5;
          this.y += (dy / dist) * force * 1.5;
        }
      }

      this.alpha += this.alphaDir * this.alphaSpeed;
      if (this.alpha >= 0.88) { this.alpha = 0.88; this.alphaDir = -1; }
      if (this.alpha <= 0.38) { this.alpha = 0.38; this.alphaDir =  1; }

      if (this.y < -this.h * 2) this.reset(false);
    }

    draw() {
      const w = this.w;
      const h = this.h;
      const r = Math.min(12, w * 0.22);

      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.angle);

      // Card shadow
      ctx.shadowColor = this.bordColor;
      ctx.shadowBlur  = 10;

      // Card background
      ctx.beginPath();
      ctx.roundRect(-w * 0.5, -h * 0.5, w, h, r);
      ctx.fillStyle = this.bgColor;
      ctx.fill();

      ctx.shadowBlur  = 0;
      ctx.strokeStyle = this.bordColor;
      ctx.lineWidth   = 1.2;
      ctx.stroke();

      // Card interior content
      switch (this.cardType) {
        case 'editor':   this._drawEditor(w, h);   break;
        case 'app':      this._drawAppIcon(w, h);  break;
        case 'terminal': this._drawTerminal(w, h); break;
        case 'pipeline': this._drawPipeline(w, h); break;
      }

      ctx.restore();
    }

    /* ── Card 1: Mini IDE Code Editor ───────────────── */
    _drawEditor(w, h) {
      const startX = -w * 0.5 + 8;
      const topY   = -h * 0.5 + 6;

      // 3 traffic dots
      const dotColors = ['#ef4444', '#f59e0b', '#10b981'];
      dotColors.forEach((color, i) => {
        ctx.beginPath();
        ctx.arc(startX + i * 5.5, topY, 1.8, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
      });

      // Header divider line
      ctx.beginPath();
      ctx.moveTo(-w * 0.5 + 5, topY + 4);
      ctx.lineTo(w * 0.5 - 5, topY + 4);
      ctx.strokeStyle = 'rgba(16, 185, 129, 0.2)';
      ctx.lineWidth = 0.8;
      ctx.stroke();

      // Simulated code syntax lines
      const lineData = [
        { x: -w * 0.5 + 8,  y: topY + 9,  len: w * 0.45, color: '#059669' },
        { x: -w * 0.5 + 14, y: topY + 14, len: w * 0.55, color: '#0d9488' },
        { x: -w * 0.5 + 14, y: topY + 19, len: w * 0.35, color: '#0284c7' },
        { x: -w * 0.5 + 8,  y: topY + 24, len: w * 0.22, color: '#10b981' }
      ];

      lineData.forEach(l => {
        if (l.y < h * 0.5 - 4) {
          ctx.beginPath();
          ctx.roundRect(l.x, l.y, l.len, 2.2, 1);
          ctx.fillStyle = l.color;
          ctx.fill();
        }
      });
    }

    /* ── Card 2: Modern Android App Tile ────────────── */
    _drawAppIcon(w, h) {
      const s = w * 0.5;

      // Inner Android head emblem
      const r = s * 0.42;
      ctx.beginPath();
      ctx.arc(0, 1, r, Math.PI, 0, false);
      ctx.closePath();
      ctx.fillStyle = '#059669';
      ctx.fill();

      // Antennae
      ctx.strokeStyle = '#059669';
      ctx.lineWidth = 1.3;
      ctx.lineCap = 'round';
      ctx.beginPath();
      ctx.moveTo(-r * 0.5, -r * 0.7);
      ctx.lineTo(-r * 0.8, -r * 1.15);
      ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(r * 0.5, -r * 0.7);
      ctx.lineTo(r * 0.8, -r * 1.15);
      ctx.stroke();

      // Eyes
      ctx.fillStyle = '#ffffff';
      ctx.beginPath();
      ctx.arc(-r * 0.4, -r * 0.35, 1.2, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(r * 0.4, -r * 0.35, 1.2, 0, Math.PI * 2);
      ctx.fill();

      // Mini "APK" label at bottom
      ctx.font = `700 7px 'Fira Code', monospace`;
      ctx.fillStyle = '#0d9488';
      ctx.textAlign = 'center';
      ctx.fillText('APK', 0, h * 0.36);
    }

    /* ── Card 3: Terminal Console Window ────────────── */
    _drawTerminal(w, h) {
      const startX = -w * 0.5 + 8;
      const topY   = -h * 0.5 + 6;

      // Header dots
      for (let i = 0; i < 3; i++) {
        ctx.beginPath();
        ctx.arc(startX + i * 5, topY, 1.6, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(5, 150, 105, 0.4)';
        ctx.fill();
      }

      // Command prompt text
      ctx.font = `600 7.5px 'Fira Code', monospace`;
      ctx.fillStyle = '#047857';
      ctx.textAlign = 'left';
      ctx.fillText('>_ adb install', -w * 0.5 + 7, topY + 13);

      // Status pill
      ctx.beginPath();
      ctx.roundRect(-w * 0.5 + 7, topY + 18, w * 0.65, 3, 1);
      ctx.fillStyle = '#10b981';
      ctx.fill();
    }

    /* ── Card 4: Git Branch & Pipeline Card ─────────── */
    _drawPipeline(w, h) {
      const startX = -w * 0.5 + 10;

      // Git branch glyph
      ctx.strokeStyle = '#059669';
      ctx.lineWidth = 1.2;
      ctx.beginPath();
      ctx.moveTo(startX, -h * 0.32);
      ctx.lineTo(startX, h * 0.32);
      ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(startX, 0);
      ctx.bezierCurveTo(startX + 4, -4, startX + 8, -4, startX + 9, -h * 0.2);
      ctx.stroke();

      ctx.fillStyle = '#059669';
      ctx.beginPath(); ctx.arc(startX, -h * 0.28, 1.8, 0, Math.PI * 2); ctx.fill();
      ctx.beginPath(); ctx.arc(startX, h * 0.28, 1.8, 0, Math.PI * 2); ctx.fill();
      ctx.beginPath(); ctx.arc(startX + 9, -h * 0.2, 1.8, 0, Math.PI * 2); ctx.fill();

      // Branch name & checkmark
      ctx.font = `600 7.5px 'Fira Code', monospace`;
      ctx.fillStyle = '#0f2a1e';
      ctx.textAlign = 'left';
      ctx.fillText('v2.4 [OK]', startX + 15, -1);

      ctx.font = `600 6px 'Fira Code', monospace`;
      ctx.fillStyle = '#0d9488';
      ctx.fillText('passed', startX + 15, 8);
    }
  }

  /* ── Init Particles & Cards ─────────────────────────────────── */
  let particles = [];
  let cards     = [];

  function initParticles() {
    // Proportional count based on screen area
    const particleCount = Math.min(Math.floor((viewW * viewH) / 6000), 75);
    const cardCount     = Math.min(Math.max(Math.floor(viewW / 120), 4), 14);

    particles = Array.from({ length: particleCount }, () => new DevParticle());
    cards     = Array.from({ length: cardCount }, () => new DevCard());
  }

  /* ── Background Constellation Lines ─────────────────────────── */
  function drawConnections() {
    const maxDist = 85;
    const maxDistSq = maxDist * maxDist;
    const len = particles.length;

    for (let i = 0; i < len; i++) {
      const p1 = particles[i];
      // Only connect certain lighter particles to avoid dense web
      if (p1.type !== 'node' && p1.type !== 'binary' && p1.type !== 'braces') continue;

      for (let j = i + 1; j < len; j++) {
        const p2 = particles[j];
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        const distSq = dx * dx + dy * dy;

        if (distSq < maxDistSq) {
          const dist = Math.sqrt(distSq);
          const alpha = (1 - dist / maxDist) * 0.16 * Math.min(p1.alpha, p2.alpha);
          ctx.strokeStyle = `rgba(5, 150, 105, ${alpha})`;
          ctx.lineWidth = 0.8;
          ctx.beginPath();
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.stroke();
        }
      }
    }
  }

  /* ── Animation Loop ─────────────────────────────────────────── */
  let rafId;
  function animate() {
    ctx.save();
    ctx.scale(dpr, dpr);
    ctx.clearRect(0, 0, viewW, viewH);

    // Draw subtle network lines first
    drawConnections();

    // Draw floating dev & app cards
    cards.forEach(c => {
      c.update();
      c.draw();
    });

    // Draw dev / code / android particles
    particles.forEach(p => {
      p.update();
      p.draw();
    });

    ctx.restore();
    rafId = requestAnimationFrame(animate);
  }

  /* ── Resize Listener ────────────────────────────────────────── */
  function onResize() {
    cancelAnimationFrame(rafId);
    resize();
    initParticles();
    animate();
  }

  /* ── Boot ───────────────────────────────────────────────────── */
  resize();
  initParticles();
  animate();

  if (window.ResizeObserver) {
    const ro = new ResizeObserver(onResize);
    ro.observe(container);
  }
  window.addEventListener('resize', onResize, { passive: true });

}());
