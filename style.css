/* ============================================================================
   PORTFOLIO STYLESHEET — Abin Kuriakose
   ----------------------------------------------------------------------------
   Table of contents
     01. Design tokens + FX control panel
     02. Reset & base
     03. Layout helpers
     04. Background layers (grid, scanline, glow, network canvas, cursor)
     05. Boot screen
     06. Buttons, tags, badges, chips
     07. Navbar
     08. Hero (terminal + live feed)
     09. At-a-glance strip + marquee
     10. Section shell
     11. About (dossier)
     12. Journey (timeline)
     13. Mindset (hardening demo)
     14. Projects (filters + cards)
     15. Skills (tags + gauges)
     16. Interactive terminal
     17. FAQ
     18. Contact (links + form)
     19. Footer
     20. Scroll-reveal states
     21. Responsive
     22. Accessibility (reduced motion, print)
   ========================================================================= */

/* ============================================================================
   01. DESIGN TOKENS
   Change the palette here and the whole site follows. The accent colour is
   stored as an "r,g,b" triplet so glows and tints can reuse it. The four
   [data-theme] blocks below are what the nav colour button cycles through.
   ========================================================================= */
:root {
  --bg:            #05070d;
  --surface:       #0b1120;
  --surface-2:     #10192d;
  --border:        #1b2540;
  --border-bright: #2a3a60;

  --text:          #e6edf7;
  --text-muted:    #9aa6bd;
  --text-dim:      #7a869e;

  --accent-rgb:    34, 211, 238;
  --accent-2-rgb:  59, 130, 246;
  --accent:        rgb(var(--accent-rgb));
  --accent-2:      rgb(var(--accent-2-rgb));
  --accent-soft:   rgba(var(--accent-rgb), 0.12);
  --on-accent:     #04121a;

  --green:         #34d399;
  --red:           #fb7185;
  --amber:         #fbbf24;
  --purple:        #c4b5fd;

  --glow-accent:   0 0 22px rgba(var(--accent-rgb), 0.35);
  --glow-green:    0 0 22px rgba(52, 211, 153, 0.32);

  --font-sans: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --font-mono: "JetBrains Mono", "Fira Code", ui-monospace, "Cascadia Code", Consolas, monospace;

  --nav-h: 68px;
  --gutter: 24px;
  --section-y: 104px;
  --radius: 14px;
  --radius-sm: 9px;
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  --speed: 0.32s;

  /* ---- FX CONTROL PANEL — retune the animation system from here ----------
     lower time = faster · lower px = subtler · higher alpha = brighter     */
  --fx-grid-size: 56px;
  --fx-grid-alpha: 0.05;
  --fx-scan-speed: 9s;
  --fx-scan-alpha: 0.55;
  --fx-scan-band: 260px;
  --fx-scan-lines: 0.32;
  --fx-pulse-speed: 3.2s;
  --fx-pulse-min: 0.3;
  --fx-pulse-max: 0.8;
  --fx-blink-speed: 1.05s;
  --fx-dot-speed: 2s;
  --fx-float-speed: 6.5s;
  --fx-float-distance: 10px;
  --fx-terminal-glow: 0.3;
  --fx-type-stagger: 130ms;
  --fx-type-duration: 340ms;
  --reveal-distance: 28px;
  --reveal-step: 90ms;
  --fx-btn-pulse-speed: 1.6s;
  --fx-btn-scale: 1.045;
  --reveal-inner-y: 18px;
  --fx-marquee-speed: 42s;
}

:root[data-theme="green"]  { --accent-rgb: 52, 211, 153;  --accent-2-rgb: 34, 211, 238; }
:root[data-theme="violet"] { --accent-rgb: 167, 139, 250; --accent-2-rgb: 96, 165, 250; }
:root[data-theme="amber"]  { --accent-rgb: 251, 191, 36;  --accent-2-rgb: 251, 113, 133; }

/* ============================================================================
   02. RESET & BASE
   ========================================================================= */
*, *::before, *::after { box-sizing: border-box; }
* { margin: 0; }
[hidden] { display: none !important; }

html {
  scroll-behavior: smooth;
  scroll-padding-top: var(--nav-h);
  -webkit-text-size-adjust: 100%;
}

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-sans);
  font-size: 16px;
  line-height: 1.65;
  -webkit-font-smoothing: antialiased;
  overflow-x: hidden;
}

img, svg, video, canvas { display: block; max-width: 100%; }

/* Elements waiting to slide in from the side sit outside the viewport. `clip`
   (unlike `hidden`) stops them widening the page without breaking position: sticky. */
main, .footer { overflow-x: clip; }
a { color: inherit; text-decoration: none; }
ul, ol { list-style: none; padding: 0; }
button, input, textarea { font: inherit; color: inherit; }
button { background: none; border: 0; cursor: pointer; }
code { font-family: var(--font-mono); font-size: 0.9em; color: var(--accent); }
::selection { background: rgba(var(--accent-rgb), 0.3); }

h1, h2, h3, h4 { line-height: 1.18; font-weight: 700; letter-spacing: -0.02em; }
strong { font-weight: 600; color: #fff; }

:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 4px; }

.accent { color: var(--accent); text-shadow: 0 0 26px rgba(var(--accent-rgb), 0.4); }
.muted  { color: var(--text-dim); }

.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}

.skip-link {
  position: absolute; top: -60px; left: 16px; z-index: 200;
  padding: 10px 16px; background: var(--accent); color: var(--on-accent);
  font-weight: 600; border-radius: var(--radius-sm);
  transition: top var(--speed) var(--ease);
}
.skip-link:focus { top: 14px; }

/* Syntax / terminal colours reused in several places */
.c-prompt { color: var(--green); }
.c-cyan   { color: var(--accent); }
.c-green  { color: var(--green); }
.c-red    { color: var(--red); }
.c-amber  { color: var(--amber); }
.c-purple { color: var(--purple); }
.c-dim    { color: var(--text-dim); }

/* ============================================================================
   03. LAYOUT HELPERS
   ========================================================================= */
.container {
  width: min(1180px, 100%);
  margin-inline: auto;
  padding-inline: var(--gutter);
}
.grid { display: grid; gap: 26px; }
.grid--cards { grid-template-columns: repeat(auto-fit, minmax(min(100%, var(--min, 270px)), 1fr)); }

/* ============================================================================
   04. BACKGROUND LAYERS
   Everything is `fixed` with a negative z-index so it paints behind content.
   The grid and scanline are pseudo-elements on <body> (zero extra markup).
   ========================================================================= */
body::before {
  content: "";
  position: fixed;
  inset: -60px;
  z-index: -3;
  pointer-events: none;
  background-image:
    repeating-linear-gradient(to bottom,
      rgba(0, 0, 0, var(--fx-scan-lines)) 0px, rgba(0, 0, 0, var(--fx-scan-lines)) 1px,
      transparent 1px, transparent 3px),
    linear-gradient(rgba(var(--accent-rgb), var(--fx-grid-alpha)) 1px, transparent 1px),
    linear-gradient(90deg, rgba(var(--accent-rgb), var(--fx-grid-alpha)) 1px, transparent 1px);
  background-size: auto, var(--fx-grid-size) var(--fx-grid-size), var(--fx-grid-size) var(--fx-grid-size);
  -webkit-mask-image: radial-gradient(ellipse 85% 65% at 50% 0%, #000 30%, transparent 88%);
          mask-image: radial-gradient(ellipse 85% 65% at 50% 0%, #000 30%, transparent 88%);
  transform: translate3d(calc(var(--parallax-x, 0) * 1px), calc(var(--parallax-y, 0) * 1px + var(--grid-shift, 0) * 1px), 0);
  will-change: transform;
}

body::after {
  content: "";
  position: fixed;
  left: 0; right: 0; top: 0;
  height: var(--fx-scan-band);
  z-index: -1;
  pointer-events: none;
  opacity: var(--fx-scan-alpha);
  background: linear-gradient(to bottom,
    transparent 0%, rgba(var(--accent-rgb), 0.05) 42%, rgba(52, 211, 153, 0.06) 52%, transparent 100%);
  will-change: transform;
  animation: scan var(--fx-scan-speed) linear infinite;
}
@keyframes scan {
  from { transform: translate3d(0, calc(var(--fx-scan-band) * -1), 0); }
  to   { transform: translate3d(0, 100vh, 0); }
}

.bg-glow {
  position: fixed; z-index: -2;
  width: 620px; height: 620px; top: -220px; right: -160px;
  border-radius: 50%; pointer-events: none;
  filter: blur(120px); opacity: 0.5;
  background: radial-gradient(circle, rgba(var(--accent-rgb), 0.35), transparent 68%);
  animation: drift 16s ease-in-out infinite alternate;
}
@keyframes drift { to { transform: translate3d(-70px, 70px, 0) scale(1.12); } }

/* Animated network graph (drawn by script.js) */
.net {
  position: fixed; inset: 0; z-index: -2;
  width: 100%; height: 100%;
  pointer-events: none; opacity: 0.8;
}

/* Soft light that follows a mouse pointer (fine pointers only) */
.cursor-glow {
  position: fixed; left: 0; top: 0; z-index: -1;
  width: 520px; height: 520px; margin: -260px 0 0 -260px;
  border-radius: 50%; pointer-events: none; opacity: 0;
  background: radial-gradient(circle, rgba(var(--accent-rgb), 0.1), transparent 65%);
  transition: opacity 0.4s;
  will-change: transform;
}
.cursor-glow.is-on { opacity: 1; }

/* Reading progress */
.progress {
  position: fixed; top: 0; left: 0; right: 0; height: 2px; z-index: 70;
  pointer-events: none;
}
.progress span {
  display: block; height: 100%; transform-origin: 0 50%; transform: scaleX(0);
  background: linear-gradient(90deg, var(--accent), var(--accent-2));
  box-shadow: 0 0 10px rgba(var(--accent-rgb), 0.7);
}

/* Matrix rain overlay (easter egg: type `matrix` in the terminal) */
.matrix {
  position: fixed; inset: 0; z-index: 90; width: 100%; height: 100%;
  background: rgba(2, 4, 8, 0.88);
  opacity: 0; pointer-events: none; transition: opacity 0.5s;
}
.matrix.is-on { opacity: 1; pointer-events: auto; cursor: pointer; }

/* ============================================================================
   05. BOOT SCREEN
   ========================================================================= */
.boot {
  position: fixed; inset: 0; z-index: 100;
  display: grid; place-items: center;
  background: var(--bg);
  transition: opacity 0.6s var(--ease), visibility 0.6s;
}
.boot.is-done { opacity: 0; visibility: hidden; pointer-events: none; }
.skip-boot .boot, .no-js .boot { display: none; }

.boot__panel { width: min(520px, calc(100% - 48px)); }
.boot__log {
  min-height: 7.6em;
  font: 500 14px/1.9 var(--font-mono);
  color: var(--text-muted);
  white-space: pre-wrap;
}
.boot__log .ok { color: var(--green); }
.boot__bar {
  height: 3px; margin-top: 18px; border-radius: 3px;
  background: var(--border); overflow: hidden;
}
.boot__bar span {
  display: block; height: 100%; width: 100%;
  transform-origin: 0 50%; transform: scaleX(0);
  background: linear-gradient(90deg, var(--accent), var(--accent-2));
  box-shadow: var(--glow-accent);
}
.boot__skip {
  margin-top: 22px; padding: 6px 2px;
  font: 500 12.5px var(--font-mono); color: var(--text-dim);
  border-bottom: 1px dashed var(--border-bright);
  transition: color var(--speed);
}
.boot__skip:hover { color: var(--accent); }

/* ============================================================================
   06. BUTTONS, TAGS, BADGES, CHIPS
   ========================================================================= */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 10px;
  padding: 13px 24px; border-radius: var(--radius-sm);
  font-weight: 600; font-size: 15px; line-height: 1.2;
  border: 1px solid transparent;
  transition: transform var(--speed) var(--ease), box-shadow var(--speed), background var(--speed), border-color var(--speed), color var(--speed);
}
.btn--primary {
  background: var(--accent); color: var(--on-accent);
  box-shadow: var(--glow-accent);
}
.btn--primary:hover {
  transform: scale(var(--fx-btn-scale));
  animation: btnPulse var(--fx-btn-pulse-speed) ease-in-out infinite;
}
.btn--primary:disabled { opacity: 0.6; cursor: progress; animation: none; transform: none; }
.btn--ghost { border-color: var(--border-bright); color: var(--text); background: rgba(255, 255, 255, 0.02); }
.btn--ghost:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
.btn--full { width: 100%; }
.btn__icon { transition: transform var(--speed) var(--ease); }
.btn:hover .btn__icon { transform: translateX(4px); }
@keyframes btnPulse {
  0%, 100% { box-shadow: 0 0 18px rgba(var(--accent-rgb), 0.35); }
  50%      { box-shadow: 0 0 34px rgba(var(--accent-rgb), 0.7); }
}

.tags { display: flex; flex-wrap: wrap; gap: 8px; }
.tags li {
  padding: 4px 12px; border-radius: 999px;
  font: 500 12.5px/1.5 var(--font-mono); color: var(--text-muted);
  background: rgba(255, 255, 255, 0.025); border: 1px solid var(--border);
  transition: color var(--speed), border-color var(--speed), background var(--speed);
}
.tags li:hover { color: var(--accent); border-color: rgba(var(--accent-rgb), 0.5); background: var(--accent-soft); }

.badge {
  display: inline-block; padding: 2px 10px; border-radius: 999px;
  font: 600 11.5px/1.7 var(--font-mono);
  border: 1px solid rgba(52, 211, 153, 0.4); color: var(--green); background: rgba(52, 211, 153, 0.08);
}

.chip {
  padding: 7px 16px; border-radius: 999px;
  font: 500 13px/1.4 var(--font-mono); color: var(--text-muted);
  border: 1px solid var(--border-bright); background: rgba(255, 255, 255, 0.02);
  transition: color var(--speed), border-color var(--speed), background var(--speed);
}
.chip:hover { color: var(--accent); border-color: rgba(var(--accent-rgb), 0.6); }
.chip.is-active {
  color: var(--on-accent); background: var(--accent); border-color: var(--accent);
}

/* ============================================================================
   07. NAVBAR
   ========================================================================= */
.navbar {
  position: fixed; inset: 0 0 auto 0; z-index: 50;
  height: var(--nav-h); display: flex; align-items: center;
  border-bottom: 1px solid transparent;
  transition: background var(--speed), border-color var(--speed);
}
.navbar.is-scrolled {
  background: rgba(5, 7, 13, 0.8);
  -webkit-backdrop-filter: blur(14px) saturate(140%);
          backdrop-filter: blur(14px) saturate(140%);
  border-bottom-color: var(--border);
}
.nav { display: flex; align-items: center; gap: 20px; }

.nav__brand { display: flex; align-items: center; gap: 10px; font-weight: 700; margin-right: auto; }
.nav__brand-mark {
  display: grid; place-items: center; width: 34px; height: 34px;
  font: 700 14px var(--font-mono); color: var(--accent);
  border: 1px solid rgba(var(--accent-rgb), 0.5); border-radius: 8px;
  background: var(--accent-soft);
}
.nav__brand-text { display: flex; flex-direction: column; line-height: 1.15; }
.nav__brand-sub { font: 500 11px var(--font-mono); color: var(--text-dim); letter-spacing: 0.04em; }

.nav__menu { display: flex; align-items: center; gap: 4px; }
.nav__link {
  position: relative; display: block; padding: 8px 12px;
  font: 500 13.5px var(--font-mono); color: var(--text-muted);
  transition: color var(--speed);
}
.nav__link::after {
  content: ""; position: absolute; left: 12px; right: 12px; bottom: 2px; height: 2px;
  background: var(--accent); box-shadow: var(--glow-accent);
  transform: scaleX(0); transform-origin: 0 50%; transition: transform var(--speed) var(--ease);
}
.nav__link:hover, .nav__link.is-active { color: var(--accent); }
.nav__link:hover::after, .nav__link.is-active::after { transform: scaleX(1); }

.nav__actions { display: flex; align-items: center; gap: 8px; }
.nav__theme {
  display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%;
  border: 1px solid var(--border-bright);
  transition: border-color var(--speed), transform var(--speed) var(--ease);
}
.nav__theme:hover { border-color: var(--accent); transform: rotate(40deg); }
.nav__theme-dot {
  width: 14px; height: 14px; border-radius: 50%;
  background: var(--accent); box-shadow: var(--glow-accent);
}

.nav__toggle {
  display: none; width: 40px; height: 40px; border-radius: 9px;
  border: 1px solid var(--border-bright);
  flex-direction: column; align-items: center; justify-content: center; gap: 5px;
}
.nav__toggle span {
  display: block; width: 18px; height: 2px; background: var(--text);
  transition: transform var(--speed) var(--ease), opacity var(--speed);
}
.nav__toggle[aria-expanded="true"] span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.nav__toggle[aria-expanded="true"] span:nth-child(2) { opacity: 0; }
.nav__toggle[aria-expanded="true"] span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ============================================================================
   08. HERO
   ========================================================================= */
.hero {
  position: relative; min-height: 100vh; min-height: 100svh;
  display: flex; align-items: center;
  padding: calc(var(--nav-h) + 40px) 0 72px;
}
.hero__inner {
  display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
  gap: 56px; align-items: center;
}

.hero__status {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 6px 14px; border-radius: 999px; margin-bottom: 22px;
  font: 500 13px var(--font-mono); color: var(--text-muted);
  border: 1px solid var(--border-bright); background: rgba(255, 255, 255, 0.02);
}
.dot {
  display: inline-block; flex: none; width: 8px; height: 8px; border-radius: 50%;
  background: var(--green);
  animation: dotPulse var(--fx-dot-speed) ease-out infinite;
}
@keyframes dotPulse {
  0%   { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6); }
  70%  { box-shadow: 0 0 0 10px rgba(52, 211, 153, 0); }
  100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
}

.hero__title { font-size: clamp(2.1rem, 4.8vw, 3.8rem); letter-spacing: -0.035em; margin-bottom: 14px; }
.hero__title .accent { display: block; white-space: nowrap; }
.glow-pulse { animation: glowPulse var(--fx-pulse-speed) ease-in-out infinite; }
@keyframes glowPulse {
  0%, 100% { text-shadow: 0 0 20px rgba(var(--accent-rgb), var(--fx-pulse-min)); }
  50%      { text-shadow: 0 0 38px rgba(var(--accent-rgb), var(--fx-pulse-max)); }
}

.hero__subtitle {
  min-height: 1.6em; margin-bottom: 20px;
  font: 500 clamp(1rem, 1.9vw, 1.3rem)/1.5 var(--font-mono); color: var(--text-muted);
}
.type-cursor {
  display: inline-block; width: 9px; height: 1.05em; margin-left: 4px; vertical-align: -0.14em;
  background: var(--accent); animation: blink var(--fx-blink-speed) steps(1) infinite;
}
@keyframes blink { 50% { opacity: 0; } }

.hero__copy {
  transform: translate3d(0, calc(var(--hero-p, 0) * -44px), 0);
  opacity: calc(1 - var(--hero-p, 0) * 0.85);
}
.hero__visual { opacity: calc(1 - var(--hero-p, 0) * 0.85); }

/* Scroll cue */
.scroll-cue {
  position: absolute; left: 50%; bottom: 22px; z-index: 2;
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  transform: translateX(-50%);
  font: 500 11.5px var(--font-mono); color: var(--text-dim);
  transition: opacity 0.4s, color var(--speed);
}
.scroll-cue:hover { color: var(--accent); }
.scroll-cue.is-gone { opacity: 0; pointer-events: none; }
.scroll-cue__mouse {
  display: block; width: 22px; height: 34px; border-radius: 12px; padding-top: 6px;
  border: 1.5px solid var(--border-bright);
}
.scroll-cue__mouse span {
  display: block; width: 3px; height: 7px; margin: 0 auto; border-radius: 2px; background: var(--accent);
  animation: wheel 1.8s var(--ease) infinite;
}
@keyframes wheel {
  0%   { opacity: 0; transform: translateY(0); }
  25%  { opacity: 1; }
  80%  { opacity: 0; transform: translateY(12px); }
  100% { opacity: 0; transform: translateY(12px); }
}

.hero__text { max-width: 54ch; color: var(--text-muted); font-size: 1.05rem; margin-bottom: 30px; }
.hero__actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 40px; }
.hero__actions .btn { padding: 12px 18px; font-size: 14.5px; }

.hero__stats { display: flex; flex-wrap: wrap; gap: 14px 38px; }
.hero__stats li { display: flex; flex-direction: column; line-height: 1.3; }
.hero__stats strong { font: 700 1.35rem var(--font-mono); color: var(--accent); }
.hero__stats span { font-size: 0.85rem; color: var(--text-dim); }

/* Visual column. Four nested layers on purpose (see index.html comment). */
.hero__visual { will-change: transform; }
.terminal-float { animation: floaty var(--fx-float-speed) ease-in-out infinite; }
@keyframes floaty {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50%      { transform: translate3d(0, calc(var(--fx-float-distance) * -1), 0); }
}

.terminal {
  border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(8, 12, 22, 0.88);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.02) inset;
  transition: box-shadow var(--speed);
  overflow: hidden;
}
.terminal:hover { box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45), 0 0 40px rgba(var(--accent-rgb), var(--fx-terminal-glow)); }
.terminal__bar, .shell__bar {
  display: flex; align-items: center; gap: 7px; padding: 11px 16px;
  border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.025);
}
.terminal__dot { width: 11px; height: 11px; border-radius: 50%; background: var(--border-bright); }
.terminal__dot:nth-child(1) { background: #ff5f57; }
.terminal__dot:nth-child(2) { background: #febc2e; }
.terminal__dot:nth-child(3) { background: #28c840; }
.terminal__title { margin-left: 10px; font: 500 12px var(--font-mono); color: var(--text-dim); }

/* The <pre> is `normal` white-space so the newlines between line-spans collapse;
   each .terminal__line re-enables `pre` for its own text. */
.terminal__body {
  margin: 0; padding: 18px 20px 20px;
  font: 400 13.5px/1.7 var(--font-mono); white-space: normal; overflow-x: auto;
}
.terminal__line {
  display: block; min-height: 1.7em; white-space: pre;
  opacity: 0; transform: translate3d(0, 5px, 0);
  transition: opacity var(--fx-type-duration) ease, transform var(--fx-type-duration) var(--ease);
  transition-delay: calc(var(--i, 0) * var(--fx-type-stagger));
}
.terminal__body.is-typed .terminal__line { opacity: 1; transform: none; }
.caret { display: inline-block; width: 8px; height: 1.05em; margin-left: 2px; vertical-align: -0.15em; background: var(--accent); animation: blink var(--fx-blink-speed) steps(1) infinite; }

/* Simulated live feed */
.feed {
  margin-top: 16px; border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(8, 12, 22, 0.88); overflow: hidden;
}
.feed__head {
  display: flex; align-items: center; justify-content: space-between; padding: 9px 16px;
  border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.025);
  font: 500 12px var(--font-mono);
}
.feed__title { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); }
.feed__pulse { width: 7px; height: 7px; border-radius: 50%; background: var(--red); animation: blink 1.4s steps(1) infinite; }
.feed__tag {
  padding: 1px 8px; border-radius: 999px; color: var(--amber);
  border: 1px solid rgba(251, 191, 36, 0.4); font-size: 11px;
}
.feed__list { padding: 10px 16px 12px; min-height: 9.6em; font: 400 12px/1.7 var(--font-mono); }
.feed__list li {
  display: flex; gap: 10px; white-space: nowrap; overflow: hidden;
  animation: feedIn 0.45s var(--ease) both;
}
.feed__time { color: var(--text-dim); flex: none; }
.feed__lvl { flex: none; width: 5.2em; }
.feed__lvl--info  { color: var(--accent); }
.feed__lvl--warn  { color: var(--amber); }
.feed__lvl--block { color: var(--green); }
.feed__msg { color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; }
@keyframes feedIn { from { opacity: 0; transform: translate3d(0, 6px, 0); } to { opacity: 1; transform: none; } }

/* ============================================================================
   09. AT-A-GLANCE STRIP + MARQUEE
   ========================================================================= */
.hud { padding: 8px 0 var(--section-y); overflow-x: clip; }
.hud__grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  border: 1px solid var(--border); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.7);
  -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
  overflow: hidden;
}
.hud__item {
  display: flex; flex-direction: column; gap: 2px; padding: 22px 26px;
  border-right: 1px solid var(--border);
}
.hud__item:last-child { border-right: 0; }
.hud__label { font: 500 12.5px var(--font-mono); color: var(--text-dim); }
.hud__value { font: 700 2rem/1.25 var(--font-mono); color: var(--accent); }
.hud__value--text { font-size: 1.3rem; padding: 6px 0 4px; display: inline-flex; align-items: center; gap: 10px; }
.hud__value--ok { color: var(--green); }
.hud__meta { font-size: 0.85rem; color: var(--text-muted); }

.marquee {
  margin-top: 40px; overflow: hidden;
  transform: skewX(calc(var(--skew, 0) * 1deg));
  transition: transform 0.25s ease-out;
  -webkit-mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
          mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
}
.marquee__track { display: flex; width: max-content; animation: marquee var(--fx-marquee-speed) linear infinite; }
.marquee:hover .marquee__track { animation-play-state: paused; }
.marquee__track li {
  margin-right: 14px; padding: 7px 18px; border-radius: 999px;
  font: 500 13px var(--font-mono); color: var(--text-muted); white-space: nowrap;
  border: 1px solid var(--border); background: rgba(255, 255, 255, 0.02);
}
@keyframes marquee { to { transform: translate3d(-50%, 0, 0); } }

/* ============================================================================
   10. SECTION SHELL
   ========================================================================= */
.section { padding: var(--section-y) 0; }
.section--alt {
  background: rgba(10, 16, 30, 0.58);
  border-block: 1px solid var(--border);
}
.section__head { max-width: 62ch; margin-bottom: 52px; }
.section__eyebrow { margin-bottom: 10px; font: 500 13.5px var(--font-mono); color: var(--accent); }
.section__title { font-size: clamp(1.75rem, 3.6vw, 2.6rem); margin-bottom: 12px; }
.section__title::after {
  content: ""; display: block; width: 64px; height: 3px; margin-top: 14px; border-radius: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent-2)); box-shadow: var(--glow-accent);
  transform-origin: 0 50%;
  transition: transform 0.9s var(--ease) 0.25s;
}
.js .section__head:not(.is-in) .section__title::after { transform: scaleX(0); }
.js .section__eyebrow, .js .section__desc {
  transition: opacity 0.7s var(--ease), transform 0.7s var(--ease);
}
.js .section__head:not(.is-in) .section__eyebrow { opacity: 0; transform: translate3d(-18px, 0, 0); }
.js .section__head:not(.is-in) .section__desc    { opacity: 0; transform: translate3d(0, 12px, 0); }
.js .section__desc { transition-delay: 0.3s; }
.section__desc { color: var(--text-muted); font-size: 1.05rem; }

/* ============================================================================
   11. ABOUT
   ========================================================================= */
.about { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 56px; align-items: start; }
.about__body { display: grid; gap: 18px; color: var(--text-muted); font-size: 1.05rem; max-width: 66ch; }
.about__learning { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; }
.about__learning li {
  padding: 6px 14px; border-radius: var(--radius-sm);
  font: 500 13px var(--font-mono); color: var(--accent);
  background: var(--accent-soft); border: 1px solid rgba(var(--accent-rgb), 0.3);
}

.dossier {
  border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.85); overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
}
.dossier__bar {
  display: flex; align-items: center; gap: 7px; padding: 11px 16px;
  border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.025);
  font: 500 12px var(--font-mono); color: var(--text-dim);
}
.dossier__bar span { width: 10px; height: 10px; border-radius: 50%; background: var(--border-bright); }
.dossier__bar span:last-of-type { margin-right: 8px; }
.dossier__list { padding: 8px 20px 14px; font-family: var(--font-mono); font-size: 13.5px; }
.dossier__list > div { display: grid; grid-template-columns: 7.5em 1fr; gap: 12px; padding: 11px 0; border-bottom: 1px dashed var(--border); }
.dossier__list > div:last-child { border-bottom: 0; }
.dossier__list dt { color: var(--text-dim); }
.dossier__list dd { color: var(--text); }
.dossier__list dd.ok { color: var(--green); }

/* ============================================================================
   12. JOURNEY (timeline)
   The line fills as you scroll: script.js writes --tl (0 to 1) on .timeline.
   ========================================================================= */
.timeline { position: relative; display: grid; gap: 34px; padding-left: 48px; max-width: 760px; }
.timeline::before, .timeline::after {
  content: ""; position: absolute; left: 11px; top: 8px; width: 2px; border-radius: 2px;
}
.timeline::before { bottom: 8px; background: var(--border); }
.timeline::after {
  height: calc((100% - 16px) * var(--tl, 0));
  background: linear-gradient(to bottom, var(--accent), var(--accent-2));
  box-shadow: var(--glow-accent);
}
.tl { position: relative; }
.tl__dot {
  position: absolute; left: -43px; top: 4px; width: 14px; height: 14px; border-radius: 50%;
  background: var(--bg); border: 2px solid var(--border-bright);
  transition: background var(--speed), border-color var(--speed), box-shadow var(--speed);
}
.tl.is-reached .tl__dot { background: var(--accent); border-color: var(--accent); box-shadow: var(--glow-accent); }
.tl__phase { font: 500 12.5px var(--font-mono); color: var(--accent); margin-bottom: 4px; }
.tl__title { font-size: 1.35rem; margin-bottom: 8px; }
.tl__text { color: var(--text-muted); margin-bottom: 14px; max-width: 60ch; }

/* ============================================================================
   13. MINDSET (hardening demo)
   ========================================================================= */
.harden { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 0.9fr); gap: 28px; align-items: start; }
.harden__main {
  border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.8); padding: 22px;
}
.harden__bar { display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center; margin-bottom: 18px; }
.harden__posture { display: flex; flex-direction: column; line-height: 1.25; }
.harden__posture-label { font: 500 12px var(--font-mono); color: var(--text-dim); }
.harden__posture-num { font: 700 1.6rem var(--font-mono); color: var(--red); transition: color 0.5s; }
.harden[data-state="hardened"] .harden__posture-num { color: var(--green); }
.harden__track { height: 6px; border-radius: 6px; background: var(--border); overflow: hidden; }
.harden__track span {
  display: block; height: 100%; width: 100%; transform-origin: 0 50%; transform: scaleX(0.17);
  background: var(--red); transition: transform 1.2s var(--ease), background 0.5s;
}
.harden[data-state="hardened"] .harden__track span { background: var(--green); box-shadow: var(--glow-green); }
.harden__btn { padding: 11px 20px; font-size: 14px; }

.harden__list { display: grid; gap: 10px; }
.hitem {
  display: grid; grid-template-columns: 5.6rem 1fr; gap: 16px; align-items: center;
  padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.015);
  transition: border-color 0.5s, background 0.5s;
}
.hitem__chip {
  justify-self: start; min-width: 5.4rem; text-align: center; padding: 2px 10px; border-radius: 999px;
  font: 600 11.5px/1.8 var(--font-mono); color: var(--red);
  border: 1px solid rgba(251, 113, 133, 0.5); background: rgba(251, 113, 133, 0.08);
  transition: color 0.4s, border-color 0.4s, background 0.4s;
}
/* before + after share one grid cell so the row never changes height */
.hitem__before, .hitem__after {
  grid-column: 2; grid-row: 1; font-size: 0.95rem;
  transition: opacity 0.45s ease, transform 0.45s var(--ease);
}
.hitem__before { color: var(--text); }
.hitem__after  { color: var(--text); opacity: 0; transform: translate3d(0, 8px, 0); }
.hitem.is-fixed { border-color: rgba(52, 211, 153, 0.35); background: rgba(52, 211, 153, 0.04); }
.hitem.is-fixed .hitem__chip { color: var(--green); border-color: rgba(52, 211, 153, 0.5); background: rgba(52, 211, 153, 0.08); }
.hitem.is-fixed .hitem__before { opacity: 0; transform: translate3d(0, -8px, 0); }
.hitem.is-fixed .hitem__after  { opacity: 1; transform: none; }

.codepane {
  position: sticky; top: calc(var(--nav-h) + 20px);
  border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(8, 12, 22, 0.92); overflow: hidden;
}
.codepane__bar { padding: 11px 18px; border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.025); font: 500 12px var(--font-mono); color: var(--text-dim); }
.codepane__body { margin: 0; padding: 18px 20px; font: 400 13px/1.75 var(--font-mono); overflow-x: auto; white-space: pre; }
.codepane__body--bad  { border-left: 3px solid var(--red); }
.codepane__body--good { border-left: 3px solid var(--green); }

/* ============================================================================
   14. PROJECTS
   ========================================================================= */
.filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }

.card { display: flex; }
.card.is-hidden { display: none; }
.card.is-entering { animation: cardIn 0.5s var(--ease) both; }
@keyframes cardIn { from { opacity: 0; transform: translate3d(0, 14px, 0) scale(0.98); } to { opacity: 1; transform: none; } }

.card__face {
  position: relative; display: flex; flex-direction: column; width: 100%;
  padding: 26px; border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: linear-gradient(160deg, rgba(16, 25, 45, 0.9), rgba(11, 17, 32, 0.9));
  overflow: hidden;
  transition: transform 0.25s var(--ease), border-color var(--speed), box-shadow var(--speed);
  will-change: transform;
}
/* spotlight that follows the pointer — position set by script.js (--mx / --my) */
.card__face::before {
  content: ""; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
  background: radial-gradient(340px circle at var(--mx, 50%) var(--my, 50%), rgba(var(--accent-rgb), 0.15), transparent 62%);
  opacity: 0; transition: opacity var(--speed);
}
.card__face:hover { border-color: rgba(var(--accent-rgb), 0.55); box-shadow: 0 18px 50px rgba(0, 0, 0, 0.45), 0 0 30px rgba(var(--accent-rgb), 0.12); }
.card__face:hover::before { opacity: 1; }
.card__face > * { position: relative; }

.card__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.card__icon {
  display: grid; place-items: center; width: 42px; height: 42px; border-radius: 10px;
  font-size: 18px; color: var(--accent);
  background: var(--accent-soft); border: 1px solid rgba(var(--accent-rgb), 0.35);
}
.card__title { font-size: 1.2rem; margin-bottom: 10px; }
.card__desc { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 18px; }
.card .tags { margin-bottom: 22px; }
.card__link {
  margin-top: auto; align-self: flex-start;
  font: 500 13.5px var(--font-mono); color: var(--accent);
  border-bottom: 1px solid transparent; transition: border-color var(--speed);
}
.card__link:hover { border-bottom-color: var(--accent); }
.card__link--disabled { color: var(--text-dim); cursor: default; }

/* ============================================================================
   15. SKILLS
   ========================================================================= */
.skills { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 22px; margin-bottom: 56px; }
.skills__group {
  padding: 22px 24px; border: 1px solid var(--border); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.6);
}
.skills__group-title { margin-bottom: 16px; font: 600 14px var(--font-mono); color: var(--accent); letter-spacing: 0; }

.gauges { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 24px; }
.gauge { text-align: center; }
.gauge svg { width: 100%; max-width: 150px; margin: 0 auto 10px; overflow: visible; }
.gauge__track { fill: none; stroke: var(--border); stroke-width: 7; }
.gauge__fill {
  fill: none; stroke: var(--accent); stroke-width: 7; stroke-linecap: round;
  stroke-dasharray: 100; stroke-dashoffset: 100;
  transition: stroke-dashoffset 1.6s var(--ease);
  filter: drop-shadow(0 0 5px rgba(var(--accent-rgb), 0.6));
}
.gauge__num { font: 700 24px var(--font-mono); fill: #fff; }
.gauge figcaption { font: 500 13px var(--font-mono); color: var(--text-muted); }
.gauges__note { margin-top: 26px; text-align: center; font: 400 12.5px var(--font-mono); color: var(--text-dim); }

/* ============================================================================
   16. INTERACTIVE TERMINAL
   ========================================================================= */
.shell {
  max-width: 860px; border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(6, 10, 18, 0.92); overflow: hidden;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
}
.shell__out {
  height: 360px; overflow-y: auto; padding: 16px 20px 6px;
  font: 400 13.5px/1.7 var(--font-mono); scrollbar-width: thin; scrollbar-color: var(--border-bright) transparent;
}
.sh-line { white-space: pre-wrap; word-break: break-word; min-height: 1.7em; color: var(--text-muted); }
.sh-line a { color: var(--accent); border-bottom: 1px dashed rgba(var(--accent-rgb), 0.5); }
.sh-line a:hover { border-bottom-style: solid; }
.sh-cmd { color: var(--text); }
.sh-ok  { color: var(--green); }
.sh-err { color: var(--red); }
.sh-hl  { color: var(--accent); }
.shell__form { display: flex; align-items: center; gap: 10px; padding: 8px 20px 16px; font: 400 13.5px var(--font-mono); }
.shell__prompt { flex: none; color: var(--green); }
.shell__input {
  flex: 1; min-width: 0; padding: 4px 0; background: transparent; border: 0; outline: 0;
  font: inherit; color: var(--text); caret-color: var(--accent);
}
.shell:focus-within { border-color: rgba(var(--accent-rgb), 0.55); }
.shell__chips { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }

/* ============================================================================
   17. FAQ
   ========================================================================= */
.faq__tools { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; margin-bottom: 26px; }
.faq__tools .filters { margin-bottom: 0; }
.faq__search input {
  width: min(320px, 100%); padding: 10px 16px; border-radius: 999px;
  font: 400 13.5px var(--font-mono);
  background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-bright);
  transition: border-color var(--speed), box-shadow var(--speed);
}
.faq__search input:focus { outline: 0; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.18); }
.faq__search input::placeholder { color: var(--text-dim); }

.faq__list { display: grid; gap: 12px; max-width: 860px; }
.qa { border: 1px solid var(--border); border-radius: var(--radius-sm); background: rgba(11, 17, 32, 0.65); transition: border-color var(--speed), background var(--speed); }
.qa[open] { border-color: rgba(var(--accent-rgb), 0.45); background: rgba(11, 17, 32, 0.9); }
.qa summary {
  display: flex; justify-content: space-between; align-items: center; gap: 18px;
  padding: 17px 22px; cursor: pointer; list-style: none; font-weight: 600;
}
.qa summary::-webkit-details-marker { display: none; }
.qa summary::after {
  content: "+"; flex: none; font: 500 20px var(--font-mono); color: var(--accent);
  transition: transform var(--speed) var(--ease);
}
.qa[open] summary::after { transform: rotate(45deg); }
.qa__body { padding: 0 22px 20px; color: var(--text-muted); max-width: 66ch; }
.qa[open] .qa__body { animation: qaOpen 0.4s var(--ease); }
@keyframes qaOpen { from { opacity: 0; transform: translate3d(0, -6px, 0); } to { opacity: 1; transform: none; } }
.faq__empty { margin-top: 18px; color: var(--text-dim); font-family: var(--font-mono); font-size: 13.5px; }

/* ============================================================================
   18. CONTACT
   ========================================================================= */
.contact { display: grid; grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr); gap: 40px; align-items: start; }
.contact__links { display: grid; gap: 14px; }

.social {
  display: flex; align-items: center; gap: 16px; padding: 16px 18px;
  border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.75);
  transition: border-color var(--speed), transform var(--speed) var(--ease), box-shadow var(--speed);
}
.social:hover { border-color: rgba(var(--accent-rgb), 0.6); transform: translateX(4px); box-shadow: 0 0 26px rgba(var(--accent-rgb), 0.1); }
.social--copy { justify-content: space-between; }
.social--copy:hover { transform: none; }
.social__main { display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1; }
.social__icon {
  display: grid; place-items: center; flex: none; width: 42px; height: 42px; border-radius: 10px;
  font: 700 15px var(--font-mono); color: var(--accent);
  background: var(--accent-soft); border: 1px solid rgba(var(--accent-rgb), 0.35);
}
.social__body { display: flex; flex-direction: column; min-width: 0; line-height: 1.35; }
.social__body small { color: var(--text-dim); font: 400 12.5px var(--font-mono); overflow: hidden; text-overflow: ellipsis; }
.social__copy {
  flex: none; padding: 6px 14px; border-radius: 999px;
  font: 500 12.5px var(--font-mono); color: var(--text-muted); border: 1px solid var(--border-bright);
  transition: color var(--speed), border-color var(--speed);
}
.social__copy:hover { color: var(--accent); border-color: var(--accent); }
.social__copy.is-done { color: var(--green); border-color: var(--green); }

.form {
  padding: 28px; border: 1px solid var(--border-bright); border-radius: var(--radius);
  background: rgba(11, 17, 32, 0.8); display: grid; gap: 18px;
}
.form__trap { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
.form__row { display: grid; gap: 6px; }
.form__label { font: 500 13px var(--font-mono); color: var(--text-muted); }
.form__input {
  width: 100%; padding: 12px 14px; border-radius: var(--radius-sm);
  background: rgba(5, 7, 13, 0.7); border: 1px solid var(--border-bright);
  transition: border-color var(--speed), box-shadow var(--speed);
}
.form__input--area { resize: vertical; min-height: 130px; }
.form__input::placeholder { color: var(--text-dim); }
.form__input:focus { outline: 0; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.18); }
.form__input.is-invalid { border-color: var(--red); }
.form__error { min-height: 1.2em; color: var(--red); font: 400 12.5px var(--font-mono); }
.form__status { min-height: 1.4em; font: 400 13px var(--font-mono); color: var(--text-muted); }
.form__status.is-success { color: var(--green); }
.form__status.is-error { color: var(--red); }

/* ============================================================================
   19. FOOTER
   ========================================================================= */
.footer { padding: 34px 0 30px; border-top: 1px solid var(--border); background: rgba(5, 7, 13, 0.7); }
.footer__inner { display: flex; flex-wrap: wrap; gap: 18px 32px; align-items: center; justify-content: space-between; }
.footer__copy { color: var(--text-dim); font-size: 0.9rem; }
.footer__links { display: flex; gap: 22px; font: 500 13.5px var(--font-mono); color: var(--text-muted); }
.footer__links a:hover, .footer__top:hover { color: var(--accent); }
.footer__top { font: 500 13.5px var(--font-mono); color: var(--text-muted); transition: color var(--speed); }
.footer__sys {
  display: flex; flex-wrap: wrap; gap: 6px 26px; margin-top: 22px; padding-top: 18px;
  border-top: 1px dashed var(--border); font: 400 12px var(--font-mono); color: var(--text-dim);
}
.footer__sys b { font-weight: 500; color: var(--accent); }

/* ============================================================================
   20. SCROLL-REVEAL STATES
   ========================================================================= */
.reveal {
  opacity: 0; transform: translate3d(0, var(--reveal-distance), 0);
  transition: opacity 0.75s var(--ease), transform 0.75s var(--ease);
  transition-delay: calc(var(--i, 0) * var(--reveal-step));
}
.reveal--left  { transform: translate3d(calc(var(--reveal-distance) * -1.8), 0, 0); }
.reveal--right { transform: translate3d(calc(var(--reveal-distance) * 1.8), 0, 0); }
.reveal--zoom  { transform: translate3d(0, var(--reveal-distance), 0) scale(0.93); }
.reveal.active { opacity: 1; transform: none; }

/* Tags pop in one after another when their group scrolls into view.
   script.js sets --k (0, 1, 2 ...) on each tag. */
.js .skills__group .tags li,
.js .tl .tags li {
  opacity: 0; transform: translate3d(0, 8px, 0) scale(0.94);
  transition:
    opacity 0.5s var(--ease) calc(var(--k, 0) * 55ms + 150ms),
    transform 0.5s var(--ease) calc(var(--k, 0) * 55ms + 150ms),
    color var(--speed), border-color var(--speed), background var(--speed);
}
.js .skills__group.active .tags li,
.js .tl.active .tags li { opacity: 1; transform: none; }

/* A ring pings out of each timeline dot as the line reaches it */
.tl.is-reached .tl__dot { animation: dotPing 1.2s ease-out 1; }
@keyframes dotPing {
  0%   { box-shadow: 0 0 0 0 rgba(var(--accent-rgb), 0.7); }
  100% { box-shadow: 0 0 0 16px rgba(var(--accent-rgb), 0); }
}
.no-js .reveal { opacity: 1; transform: none; }
.no-js .terminal__line { opacity: 1; transform: none; }

/* ============================================================================
   21. RESPONSIVE
   ========================================================================= */
@media (max-width: 1040px) {
  .nav__link { padding: 8px 8px; font-size: 13px; }
}

@media (max-width: 900px) {
  :root { --section-y: 76px; }

  .nav__toggle { display: flex; }
  .nav__menu {
    position: fixed; top: var(--nav-h); left: 0; right: 0;
    flex-direction: column; align-items: stretch; gap: 0; padding: 10px var(--gutter) 22px;
    background: rgba(5, 7, 13, 0.97); border-bottom: 1px solid var(--border);
    -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
    opacity: 0; visibility: hidden; transform: translate3d(0, -10px, 0);
    transition: opacity var(--speed), transform var(--speed) var(--ease), visibility var(--speed);
    max-height: calc(100vh - var(--nav-h)); overflow-y: auto;
  }
  .nav__menu.is-open { opacity: 1; visibility: visible; transform: none; }
  .nav__link { padding: 14px 4px; font-size: 15px; border-bottom: 1px solid var(--border); }
  .nav__link::after { display: none; }

  .hero { min-height: 0; padding-top: calc(var(--nav-h) + 28px); }
  .scroll-cue { display: none; }
  .hero__inner { grid-template-columns: minmax(0, 1fr); gap: 44px; }

  .hud__grid { grid-template-columns: repeat(2, 1fr); }
  .hud__item:nth-child(2) { border-right: 0; }
  .hud__item:nth-child(-n+2) { border-bottom: 1px solid var(--border); }

  .about, .contact, .harden { grid-template-columns: minmax(0, 1fr); }
  .about { gap: 36px; }
  .codepane { position: static; }
  .harden__bar { grid-template-columns: 1fr auto; }
  .harden__track { grid-column: 1 / -1; grid-row: 2; }
}

@media (max-width: 560px) {
  :root { --gutter: 18px; --nav-h: 60px; }
  .hero__actions .btn { flex: 1 1 100%; }
  .hud__grid { grid-template-columns: minmax(0, 1fr); }
  .hud__item { border-right: 0; border-bottom: 1px solid var(--border); }
  .hud__item:last-child { border-bottom: 0; }
  .hitem { grid-template-columns: minmax(0, 1fr); gap: 8px; }
  .hitem__before, .hitem__after { grid-column: 1; grid-row: 2; }
  .terminal__body, .codepane__body { font-size: 12px; }
  .feed__list { font-size: 11px; }
  .feed__lvl { width: 4.6em; }
  .shell__out { height: 300px; font-size: 12.5px; }
  .shell__form { font-size: 12.5px; flex-wrap: wrap; gap: 4px 10px; }
  .shell__input { flex: 1 1 100%; }
  .form { padding: 20px; }
  .social__body small { font-size: 11.5px; }
}

/* ============================================================================
   22. ACCESSIBILITY — reduced motion + print
   ========================================================================= */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.001ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.001ms !important;
    transition-delay: 0s !important;
    scroll-behavior: auto !important;
  }
  .reveal { opacity: 1; transform: none; }
  .terminal__line { opacity: 1; transform: none; }
  .hero__copy, .hero__visual { transform: none; opacity: 1; }
  .marquee { transform: none; }
  .scroll-cue { display: none; }
  .js .skills__group .tags li, .js .tl .tags li { opacity: 1; transform: none; }
  .js .section__head:not(.is-in) .section__eyebrow, .js .section__head:not(.is-in) .section__desc { opacity: 1; transform: none; }
  .js .section__head:not(.is-in) .section__title::after { transform: none; }
  body::after { display: none; }
  .marquee { -webkit-mask-image: none; mask-image: none; }
  .marquee__track { flex-wrap: wrap; width: auto; row-gap: 10px; }
  .boot { display: none; }
}

@media print {
  .boot, .net, .bg-glow, .cursor-glow, .matrix, .progress, .navbar, .hero__visual,
  .marquee, .shell, .shell__chips, .footer__sys { display: none !important; }
  body::before, body::after { display: none; }
  body { background: #fff; color: #000; }
  .reveal { opacity: 1 !important; transform: none !important; }
  .section, .hero { padding: 24px 0; min-height: 0; }
}
