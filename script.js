/* ============================================================================
   PORTFOLIO — script.js
   ----------------------------------------------------------------------------
   Vanilla ES6+, no dependencies. Every feature is an isolated module, and
   init() wraps each one in try/catch, so deleting or breaking one block never
   takes the others down.

   Contents
     01. Config
     02. Utilities
     03. Boot screen (plays once per session)
     04. Footer: year, clock, session id
     05. Scroll progress bar
     06. Navbar: scrolled state
     07. Navbar: mobile drawer
     08. Smooth scrolling for anchor links
     09. Scrollspy
     10. Reveal on scroll
     11. Scramble ("decrypt") text
     12. Hero terminal boot typing
     13. Hero role typewriter
     14. Hero simulated live feed
     15. Metrics: counters, gauges, auto-computed numbers
     16. Marquee
     17. Mouse parallax
     18. Cursor glow
     19. Project cards: tilt + spotlight
     20. Project filter
     21. Journey timeline progress
     22. Mindset: hardening demo
     23. FAQ: filter + search
     24. Interactive terminal
     25. Contact: copy email + form
     26. Accent colour switcher
     27. Network canvas background
     28. Matrix rain easter egg (+ Konami code)
     29. Scroll effects: hero fade, moving grid, marquee speed, headings, tag stagger

   Visual tuning (speeds, glow strengths, distances) lives in the
   "FX CONTROL PANEL" in :root at the top of style.css.
   ========================================================================= */

'use strict';

/* ============================================================================
   01. CONFIG — the only values you should need to edit
   ========================================================================= */
const CONFIG = {
  // Height of the fixed navbar, read from CSS so it stays correct on mobile.
  navbarHeight: () =>
    parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h'), 10) || 68,

  scrollspyOffset: 0.35,     // fraction of viewport height that counts as "viewing" a section
  closeMenuOnNavClick: true,

  // Contact form.
  //   null                          -> opens the visitor's email app with the message pre-filled
  //   'https://formspree.io/f/xxxx' -> posts JSON to Formspree (or any endpoint that accepts it)
  // The address used for the email fallback is read from the Email link in index.html.
  formEndpoint: null,

  boot: { enabled: true },   // set false to remove the intro screen entirely

  parallax: {
    enabled: true,
    foreground: 26,          // px the hero visual travels at the window edge
    background: 9,           // px the grid travels (keep it ~30-40% of foreground)
    disableBelowWidth: 768,
    requireFinePointer: true,
  },

  typing: { enabled: true, startDelay: 420 },   // hero terminal boot

  // The hero subtitle cycles through these. The first one should match the
  // text already in index.html.
  roles: {
    enabled: true,
    list: [
      'Cybersecurity Student & Penetration Tester',
      'Flutter & Dart Developer',
      'PHP & MySQL Builder',
      'CTF Enthusiast',
    ],
  },

  tilt: { max: 6 },          // max card tilt in degrees; 0 turns tilt off (spotlight stays)

  network: {
    enabled: true,
    density: 26000,          // px² of screen per node (bigger = fewer nodes)
    minNodes: 18,
    maxNodes: 64,
    link: 130,               // max distance (px) at which two nodes connect
    speed: 0.28,             // drift speed
    maxDpr: 1.5,             // cap pixel ratio for performance
  },
};

/* ============================================================================
   02. UTILITIES
   ========================================================================= */
const $  = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const hasFinePointer = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

/** One rAF-batched update per frame — keeps scroll handlers cheap. */
function throttleRaf(fn) {
  let ticking = false;
  return (...args) => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(() => { fn(...args); ticking = false; });
  };
}

const clamp = (v, min, max) => Math.min(Math.max(v, min), max);
const lerp  = (a, b, t) => a + (b - a) * t;
const wait  = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const rand  = (min, max) => min + Math.random() * (max - min);
const pick  = (arr) => arr[Math.floor(Math.random() * arr.length)];
const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

/** localStorage/sessionStorage can throw (private mode, blocked cookies). */
function safeStorage(kind, key, value) {
  try {
    const store = kind === 'session' ? window.sessionStorage : window.localStorage;
    if (value === undefined) return store.getItem(key);
    store.setItem(key, value);
  } catch (error) { /* storage unavailable — carry on */ }
  return null;
}

/** Runs `callback(el)` once for each element the first time it is visible. */
function onceVisible(elements, callback, options = {}) {
  if (!elements.length) return;
  if (!('IntersectionObserver' in window)) { elements.forEach(callback); return; }
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);
      callback(entry.target);
    });
  }, { threshold: options.threshold ?? 0.35, rootMargin: options.rootMargin ?? '0px 0px -8% 0px' });
  elements.forEach((el) => observer.observe(el));
}

/** Counts a number up inside `el`. */
function animateCount(el, to, { duration = 1100, suffix = '', from = 0 } = {}) {
  const start = performance.now();
  const step = (now) => {
    const t = clamp((now - start) / duration, 0, 1);
    el.textContent = Math.round(lerp(from, to, easeOutCubic(t))) + suffix;
    if (t < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

/* ============================================================================
   03. BOOT SCREEN
   ----------------------------------------------------------------------------
   Plays once per browser session (so it never nags returning visitors),
   can be skipped with the button or Escape, and is skipped automatically for
   visitors who prefer reduced motion. Returns a Promise that resolves when
   the page is ready to start its entrance animations.
   ========================================================================= */
function initBoot() {
  return new Promise((resolve) => {
    const boot = $('#boot');
    const root = document.documentElement;

    const skipNow = !boot || !CONFIG.boot.enabled ||
      root.classList.contains('skip-boot') || prefersReducedMotion();
    if (skipNow) {
      if (boot) boot.remove();
      resolve();
      return;
    }

    const log = $('#bootLog');
    const fill = $('#bootFill');
    const skip = $('#bootSkip');

    const script = [
      { text: '> initialising secure session', after: 420 },
      { text: '> loading modules: ui, terminal, network', after: 440 },
      { text: '> handshake complete [tls 1.3]', after: 400 },
      { text: '> access granted. welcome.', after: 380, ok: true },
    ];

    let finished = false;
    let timer = 0;
    document.body.style.overflow = 'hidden';

    const finish = () => {
      if (finished) return;
      finished = true;
      clearTimeout(timer);
      document.removeEventListener('keydown', onKey);
      if (fill) fill.style.transform = 'scaleX(1)';
      boot.classList.add('is-done');
      document.body.style.overflow = '';
      safeStorage('session', 'portfolio-booted', '1');
      window.setTimeout(() => boot.remove(), 750);
      resolve();
    };

    const onKey = (event) => { if (event.key === 'Escape') finish(); };
    document.addEventListener('keydown', onKey);
    if (skip) {
      skip.addEventListener('click', finish);
      skip.focus({ preventScroll: true });
    }

    let index = 0;
    const next = () => {
      if (finished) return;
      if (index >= script.length) { timer = window.setTimeout(finish, 420); return; }
      const entry = script[index++];
      const line = document.createElement('span');
      if (entry.ok) line.className = 'ok';
      line.textContent = entry.text + '\n';
      if (log) log.appendChild(line);
      if (fill) fill.style.transform = `scaleX(${(index / script.length).toFixed(3)})`;
      timer = window.setTimeout(next, entry.after);
    };
    if (fill) fill.style.transition = 'transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)';
    next();
  });
}

/* ============================================================================
   04. FOOTER — year, local clock, session id
   ========================================================================= */
function initFooterSys() {
  const year = $('#year');
  if (year) year.textContent = new Date().getFullYear();

  const sess = $('#sessId');
  if (sess) {
    let id = '';
    if (window.crypto && crypto.getRandomValues) {
      id = Array.from(crypto.getRandomValues(new Uint8Array(4)))
        .map((b) => b.toString(16).padStart(2, '0')).join('');
    } else {
      id = Math.random().toString(16).slice(2, 10).padEnd(8, '0');
    }
    sess.textContent = id;
  }

  const clock = $('#clock');
  if (clock) {
    const tick = () => { clock.textContent = new Date().toLocaleTimeString([], { hour12: false }); };
    tick();
    window.setInterval(tick, 1000);
  }
}

/* ============================================================================
   05. SCROLL PROGRESS — top bar + footer percentage
   ========================================================================= */
function initScrollProgress() {
  const bar = $('#progress span');
  const pct = $('#scrollPct');
  if (!bar && !pct) return;

  const update = throttleRaf(() => {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    const p = max > 0 ? clamp(window.scrollY / max, 0, 1) : 0;
    if (bar) bar.style.transform = `scaleX(${p.toFixed(4)})`;
    if (pct) pct.textContent = Math.round(p * 100);
  });

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

/* ============================================================================
   06. NAVBAR — add a background once the page is scrolled
   ========================================================================= */
function initNavbarShadow() {
  const navbar = $('#navbar');
  if (!navbar) return;
  const update = throttleRaf(() => navbar.classList.toggle('is-scrolled', window.scrollY > 40));
  window.addEventListener('scroll', update, { passive: true });
  update();
}

/* ============================================================================
   07. NAVBAR — mobile drawer
   ========================================================================= */
function initMobileMenu() {
  const toggle = $('#navToggle');
  const menu = $('#navMenu');
  if (!toggle || !menu) return;

  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  };
  const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

  toggle.addEventListener('click', () => setOpen(!isOpen()));

  $$('#navMenu .nav__link').forEach((link) => {
    link.addEventListener('click', () => { if (CONFIG.closeMenuOnNavClick) setOpen(false); });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) { setOpen(false); toggle.focus(); }
  });

  // Must match the max-width breakpoint in style.css where the hamburger appears (900px).
  window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
    if (event.matches) setOpen(false);
  });

  document.addEventListener('click', (event) => {
    if (!isOpen()) return;
    if (!menu.contains(event.target) && !toggle.contains(event.target)) setOpen(false);
  });
}

/* ============================================================================
   08. SMOOTH SCROLLING
   ========================================================================= */
function initSmoothScroll() {
  const anchors = $$('a[href^="#"]').filter((link) => {
    const id = link.getAttribute('href');
    return id.length > 1 && document.querySelector(id);
  });

  anchors.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = $(link.getAttribute('href'));
      if (!target) return;
      event.preventDefault();

      const toggle = $('#navToggle');
      const menu = $('#navMenu');
      if (menu && menu.classList.contains('is-open') && toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
        document.body.style.overflow = '';
      }

      const scrollTo = () => {
        const top = target.getBoundingClientRect().top + window.scrollY - CONFIG.navbarHeight();
        window.scrollTo({ top, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
      };
      requestAnimationFrame(() => requestAnimationFrame(scrollTo));
      history.pushState(null, '', link.getAttribute('href'));
    });
  });
}

/* ============================================================================
   09. SCROLLSPY
   ========================================================================= */
function initScrollSpy() {
  const links = $$('#navMenu .nav__link');
  const sections = links.map((l) => document.querySelector(l.getAttribute('href'))).filter(Boolean);
  if (!sections.length) return;

  const setActive = (id) => {
    links.forEach((link) => {
      const current = id !== null && link.getAttribute('href') === `#${id}`;
      link.classList.toggle('is-active', current);
      if (current) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current');
    });
  };

  const update = throttleRaf(() => {
    const line = window.scrollY + window.innerHeight * CONFIG.scrollspyOffset;
    let currentId = sections[0].id;
    sections.forEach((section) => {
      const top = section.getBoundingClientRect().top + window.scrollY;
      if (top <= line) currentId = section.id;
    });
    // Sections that aren't in the nav (e.g. Mindset) belong to the one above them.
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 2) {
      currentId = sections[sections.length - 1].id;
    }
    if (window.scrollY < 40) currentId = null;
    setActive(currentId);
  });

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

/* ============================================================================
   10. REVEAL ON SCROLL
   Add class="reveal" to any element; set style="--i: n" to stagger siblings.
   ========================================================================= */
function initRevealOnScroll() {
  const items = $$('.reveal');
  if (!items.length) return;

  if (!('IntersectionObserver' in window) || prefersReducedMotion()) {
    items.forEach((el) => el.classList.add('active'));
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('active');
      observer.unobserve(entry.target);
    });
  }, { root: null, rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

  items.forEach((el) => observer.observe(el));
}

/* ============================================================================
   11. SCRAMBLE TEXT — the "decrypting" effect on headings
   Hero name runs after the boot screen; section titles run on first view.
   ========================================================================= */
function scrambleText(el, duration = 850) {
  if (!el || el.dataset.scrambled || prefersReducedMotion()) return;
  el.dataset.scrambled = '1';

  const finalText = el.textContent;
  const glyphs = '!<>-_/[]{}=+*^?#01';
  const wasInline = getComputedStyle(el).display === 'inline';
  const width = el.getBoundingClientRect().width;
  if (wasInline) { el.style.display = 'inline-block'; el.style.minWidth = `${width}px`; }

  const start = performance.now();
  const step = (now) => {
    const t = clamp((now - start) / duration, 0, 1);
    const resolved = Math.floor(t * finalText.length);
    let out = '';
    for (let i = 0; i < finalText.length; i++) {
      const ch = finalText[i];
      out += (ch === ' ' || i < resolved) ? ch : glyphs[Math.floor(Math.random() * glyphs.length)];
    }
    el.textContent = out;
    if (t < 1) {
      requestAnimationFrame(step);
    } else {
      el.textContent = finalText;
      if (wasInline) { el.style.display = ''; el.style.minWidth = ''; }
    }
  };
  requestAnimationFrame(step);
}

function initScrambleTitles() {
  onceVisible($$('.scramble'), (el) => scrambleText(el, 800), { threshold: 0.6 });
}

/* ============================================================================
   12. HERO TERMINAL BOOT TYPING
   Stamps each line with --i and toggles one class; CSS does the staggering.
   ========================================================================= */
function initTerminalTyping() {
  const body = $('.terminal__body');
  if (!body) return;
  const lines = $$('.terminal__line', body);
  if (!lines.length) return;

  const revealAll = () => {
    lines.forEach((line, i) => line.style.setProperty('--i', i));
    body.classList.add('is-typed');
  };

  if (!CONFIG.typing.enabled || prefersReducedMotion()) { revealAll(); return; }
  window.setTimeout(revealAll, CONFIG.typing.startDelay);
}

/* ============================================================================
   13. HERO ROLE TYPEWRITER
   The subtitle types out, pauses, deletes, and moves to the next role.
   Screen readers always get the static text (see the .sr-only span).
   ========================================================================= */
function initRoles() {
  const el = $('#roleText');
  const cfg = CONFIG.roles;
  if (!el || !cfg.enabled || !cfg.list.length || prefersReducedMotion()) return;

  let index = 0;
  let text = el.textContent;

  (async function loop() {
    await wait(2800);
    for (;;) {
      while (text.length) { text = text.slice(0, -1); el.textContent = text; await wait(24); }
      await wait(260);
      index = (index + 1) % cfg.list.length;
      const target = cfg.list[index];
      for (let i = 1; i <= target.length; i++) { text = target.slice(0, i); el.textContent = text; await wait(52); }
      await wait(2000);
    }
  })();
}

/* ============================================================================
   14. HERO SIMULATED LIVE FEED
   Decorative and labelled "simulated" — it is not real telemetry.
   ========================================================================= */
function initFeed() {
  const list = $('#feedList');
  if (!list) return;

  const MAX_LINES = 5;
  const POOL = [
    ['info',  'port scan on lab-net, rate limited'],
    ['block', 'sql injection pattern in /login, dropped'],
    ['warn',  'repeated failed logins, lockout after 5'],
    ['info',  'tls handshake ok, tls 1.3'],
    ['block', 'path traversal ../../etc/passwd, blocked'],
    ['info',  'nmap -sV lab-target: 3 services found'],
    ['warn',  'outdated library flagged, patch queued'],
    ['block', 'xss payload in comment field, sanitised'],
    ['info',  'dependency audit complete'],
    ['block', 'brute force from 10.0.0.x, ip banned'],
  ];
  const LABEL = { info: '[INFO]', warn: '[WARN]', block: '[BLOCK]' };
  let lastIndex = -1;

  const add = (animate = true) => {
    let i;
    do { i = Math.floor(Math.random() * POOL.length); } while (i === lastIndex);
    lastIndex = i;
    const [level, message] = POOL[i];

    const li = document.createElement('li');
    if (!animate) li.style.animation = 'none';

    const time = document.createElement('span');
    time.className = 'feed__time';
    time.textContent = new Date().toLocaleTimeString([], { hour12: false });

    const lvl = document.createElement('span');
    lvl.className = `feed__lvl feed__lvl--${level}`;
    lvl.textContent = LABEL[level];

    const msg = document.createElement('span');
    msg.className = 'feed__msg';
    msg.textContent = message;

    li.append(time, lvl, msg);
    list.appendChild(li);
    while (list.children.length > MAX_LINES) list.firstElementChild.remove();
  };

  for (let i = 0; i < 4; i++) add(false);
  if (prefersReducedMotion()) { add(false); return; }

  // Only tick while the hero is on screen and the tab is visible.
  let inView = true;
  if ('IntersectionObserver' in window) {
    new IntersectionObserver((entries) => { inView = entries[0].isIntersecting; }).observe($('#hero') || list);
  }
  const tick = () => {
    if (inView && !document.hidden) add(true);
    window.setTimeout(tick, rand(1500, 2900));
  };
  window.setTimeout(tick, 1800);
}

/* ============================================================================
   15. METRICS — counters, gauges, auto-computed numbers
   The "projects" and "tools" numbers are counted from the page itself, so
   they stay correct when you add a project or a skill tag.
   ========================================================================= */
function initMetrics() {
  // ---- Computed numbers ----------------------------------------------------
  const cards = $$('#projectGrid .card');
  const mobile = cards.filter((c) => c.dataset.type === 'mobile').length;
  const web = cards.filter((c) => c.dataset.type === 'web').length;

  const tools = new Set($$('.skills__group .tags li').map((li) => li.textContent.trim().toLowerCase()));

  const setCount = (el, n, suffix = '') => {
    if (!el) return;
    el.dataset.count = String(n);
    if (suffix) el.dataset.suffix = suffix;
    el.textContent = n + suffix;
  };
  if (cards.length) {
    setCount($('#hudProjects'), cards.length);
    const heroProjects = $('.hero__stats [data-count]');
    if (heroProjects) setCount(heroProjects, cards.length, heroProjects.dataset.suffix || '');
    const meta = $('#hudProjectsMeta');
    if (meta) meta.textContent = [mobile && `${mobile} mobile`, web && `${web} web`].filter(Boolean).join(', ');
  }
  if (tools.size) setCount($('#hudTools'), tools.size);

  // ---- Animate when scrolled into view -----------------------------------
  const counters = $$('[data-count]');
  const gauges = $$('.gauge');

  if (prefersReducedMotion() || !('IntersectionObserver' in window)) return;

  counters.forEach((el) => { el.textContent = '0' + (el.dataset.suffix || ''); });
  gauges.forEach((g) => {
    const fill = $('.gauge__fill', g);
    const num = $('.gauge__num', g);
    if (fill) fill.style.strokeDashoffset = '100';
    if (num) num.textContent = '0%';
  });

  onceVisible(counters, (el) => animateCount(el, Number(el.dataset.count) || 0, { suffix: el.dataset.suffix || '' }));

  onceVisible(gauges, (g) => {
    const value = clamp(Number(g.dataset.value) || 0, 0, 100);
    const fill = $('.gauge__fill', g);
    const num = $('.gauge__num', g);
    if (fill) fill.style.strokeDashoffset = String(100 - value);
    if (num) animateCount(num, value, { suffix: '%', duration: 1500 });
  }, { threshold: 0.5 });
}

/* ============================================================================
   16. MARQUEE — clones the tech list so the loop never shows a gap
   ========================================================================= */
function initMarquee() {
  const track = $('#marqueeTrack');
  if (!track || prefersReducedMotion()) return;

  const originals = Array.from(track.children);
  const copy = (list) => list.forEach((li) => {
    const clone = li.cloneNode(true);
    clone.setAttribute('aria-hidden', 'true');
    track.appendChild(clone);
  });

  // Make one "half" at least as wide as the screen, then duplicate that half:
  // the CSS animation slides exactly -50%, which lands on the identical copy.
  const oneCopyWidth = track.scrollWidth || 1200;
  const needed = Math.max(1, Math.ceil(window.innerWidth / oneCopyWidth));
  for (let i = 1; i < needed; i++) copy(originals);
  copy(Array.from(track.children));
}

/* ============================================================================
   17. MOUSE PARALLAX
   Only `transform` is written; rAF-batched; loop stops when layers settle.
   ========================================================================= */
function initMouseParallax() {
  const cfg = CONFIG.parallax;
  const foreground = $('.hero__visual');
  if (!cfg.enabled || !foreground || prefersReducedMotion()) return;

  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const wideEnough = window.matchMedia(`(min-width: ${cfg.disableBelowWidth}px)`);
  const shouldRun = () => wideEnough.matches && (!cfg.requireFinePointer || finePointer.matches);

  let targetX = 0, targetY = 0, currentX = 0, currentY = 0, running = false;

  const render = () => {
    foreground.style.transform =
      `translate3d(${(-currentX * cfg.foreground).toFixed(2)}px, ${(-currentY * cfg.foreground).toFixed(2)}px, 0)`;
    document.body.style.setProperty('--parallax-x', (-currentX * cfg.background).toFixed(2));
    document.body.style.setProperty('--parallax-y', (-currentY * cfg.background).toFixed(2));
  };

  const tick = () => {
    const done = Math.abs(targetX - currentX) < 0.001 && Math.abs(targetY - currentY) < 0.001;
    if (done) { currentX = targetX; currentY = targetY; render(); running = false; return; }
    currentX = lerp(currentX, targetX, 0.08);
    currentY = lerp(currentY, targetY, 0.08);
    render();
    window.requestAnimationFrame(tick);
  };
  const start = () => { if (running) return; running = true; window.requestAnimationFrame(tick); };

  const reset = () => {
    targetX = targetY = currentX = currentY = 0;
    running = false;
    foreground.style.transform = '';
    document.body.style.removeProperty('--parallax-x');
    document.body.style.removeProperty('--parallax-y');
  };

  window.addEventListener('pointermove', (event) => {
    if (!shouldRun() || document.hidden) return;
    targetX = clamp((event.clientX / window.innerWidth) * 2 - 1, -1, 1);
    targetY = clamp((event.clientY / window.innerHeight) * 2 - 1, -1, 1);
    start();
  }, { passive: true });

  document.addEventListener('mouseleave', () => { targetX = targetY = 0; start(); });
  window.addEventListener('blur', reset);
  wideEnough.addEventListener('change', (e) => { if (!e.matches) reset(); });
  finePointer.addEventListener('change', (e) => { if (!e.matches) reset(); });
}

/* ============================================================================
   18. CURSOR GLOW — soft light trailing the mouse (fine pointers only)
   ========================================================================= */
function initCursorGlow() {
  const el = $('#cursorGlow');
  if (!el || !hasFinePointer() || prefersReducedMotion()) return;

  let x = 0, y = 0, cx = 0, cy = 0, raf = 0, first = true;

  const tick = () => {
    cx = lerp(cx, x, 0.16);
    cy = lerp(cy, y, 0.16);
    el.style.transform = `translate3d(${cx.toFixed(1)}px, ${cy.toFixed(1)}px, 0)`;
    raf = (Math.abs(x - cx) > 0.4 || Math.abs(y - cy) > 0.4) ? requestAnimationFrame(tick) : 0;
  };

  window.addEventListener('pointermove', (event) => {
    if (event.pointerType === 'touch') return;
    x = event.clientX; y = event.clientY;
    if (first) { cx = x; cy = y; first = false; }
    el.classList.add('is-on');
    if (!raf) raf = requestAnimationFrame(tick);
  }, { passive: true });

  document.addEventListener('mouseleave', () => el.classList.remove('is-on'));
}

/* ============================================================================
   19. PROJECT CARDS — spotlight + subtle 3D tilt
   Spotlight follows the pointer via --mx / --my. Tilt is skipped for
   reduced motion and for touch.
   ========================================================================= */
function initCardEffects() {
  const faces = $$('.card__face');
  if (!faces.length || !hasFinePointer()) return;
  const tiltOn = CONFIG.tilt.max > 0 && !prefersReducedMotion();

  faces.forEach((face) => {
    face.addEventListener('pointermove', (event) => {
      const rect = face.getBoundingClientRect();
      const px = (event.clientX - rect.left) / rect.width;
      const py = (event.clientY - rect.top) / rect.height;
      face.style.setProperty('--mx', `${(event.clientX - rect.left).toFixed(0)}px`);
      face.style.setProperty('--my', `${(event.clientY - rect.top).toFixed(0)}px`);
      if (tiltOn) {
        const max = CONFIG.tilt.max;
        face.style.transform =
          `perspective(900px) rotateX(${((0.5 - py) * max).toFixed(2)}deg) rotateY(${((px - 0.5) * max).toFixed(2)}deg)`;
      }
    });
    face.addEventListener('pointerleave', () => { face.style.transform = ''; });
  });
}

/* ============================================================================
   20. PROJECT FILTER — buttons use data-filter, cards use data-type
   ========================================================================= */
function initProjectFilter() {
  const buttons = $$('.filters [data-filter]');
  const cards = $$('#projectGrid .card');
  if (!buttons.length || !cards.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.filter;
      buttons.forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });

      cards.forEach((card) => {
        const show = filter === 'all' || card.dataset.type === filter;
        const wasHidden = card.classList.contains('is-hidden');
        card.classList.toggle('is-hidden', !show);
        if (!show) return;
        card.classList.add('active'); // a card hidden before it was revealed must still appear
        if (wasHidden) {
          card.classList.remove('is-entering');
          void card.offsetWidth; // restart the animation
          card.classList.add('is-entering');
        }
      });
    });
  });
}

/* ============================================================================
   21. JOURNEY TIMELINE — the line fills as you scroll
   ========================================================================= */
function initTimeline() {
  const timeline = $('#timeline');
  if (!timeline) return;
  const items = $$('.tl', timeline);

  const update = throttleRaf(() => {
    const line = window.innerHeight * 0.6;
    const rect = timeline.getBoundingClientRect();
    const p = clamp((line - rect.top) / rect.height, 0, 1);
    timeline.style.setProperty('--tl', p.toFixed(4));
    items.forEach((item) => item.classList.toggle('is-reached', item.getBoundingClientRect().top < line));
  });

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

/* ============================================================================
   22. MINDSET — "Harden it" demo
   Rows flip from Exposed to Hardened one at a time and the posture meter
   climbs. It auto-plays once when scrolled into view; the button replays it.
   ========================================================================= */
function initHardening() {
  const root = $('#harden');
  if (!root) return;

  const btn = $('#hardenBtn');
  const btnText = $('#hardenBtnText');
  const items = $$('.hitem', root);
  const fill = $('#postureFill');
  const num = $('#postureNum');
  const status = $('#hardenStatus');
  const bad = $('.codepane__body--bad', root);
  const good = $('.codepane__body--good', root);
  const file = $('#codeFile');
  if (!btn || !items.length) return;

  const BASE = 17;
  const reduce = prefersReducedMotion();
  let timers = [];
  let state = 'exposed';
  let touched = false;

  const clearTimers = () => { timers.forEach(clearTimeout); timers = []; };

  const render = (count) => {
    items.forEach((item, i) => {
      const fixed = i < count;
      item.classList.toggle('is-fixed', fixed);
      const chip = $('.hitem__chip', item);
      if (chip) chip.textContent = fixed ? 'Hardened' : 'Exposed';
    });
    const pct = count === 0 ? BASE : Math.round(BASE + ((100 - BASE) * count) / items.length);
    if (fill) fill.style.transform = `scaleX(${pct / 100})`;
    if (num) num.textContent = `${pct}%`;
    root.dataset.state = count === items.length ? 'hardened' : 'exposed';
    if (bad && good) { bad.hidden = count >= 1; good.hidden = count < 1; }
    if (file) file.textContent = count >= 1 ? 'login.php — prepared statement' : 'login.php — vulnerable';
  };

  const run = () => {
    clearTimers();
    btnText.textContent = 'Reset';
    btn.setAttribute('aria-pressed', 'true');
    if (reduce) { render(items.length); }
    else items.forEach((_, i) => timers.push(setTimeout(() => render(i + 1), 360 * (i + 1))));
    timers.push(setTimeout(() => {
      if (status) status.textContent = `Security posture 100 percent. All ${items.length} habits applied.`;
    }, reduce ? 0 : 360 * items.length));
  };

  const reset = () => {
    clearTimers();
    render(0);
    btnText.textContent = 'Harden it';
    btn.setAttribute('aria-pressed', 'false');
    if (status) status.textContent = 'Reset to the exposed state.';
  };

  btn.addEventListener('click', () => {
    touched = true;
    state = state === 'exposed' ? 'hardened' : 'exposed';
    if (state === 'hardened') run(); else reset();
  });

  render(0);

  if (!reduce) {
    onceVisible([root], () => {
      window.setTimeout(() => {
        if (touched || state !== 'exposed') return;
        state = 'hardened';
        run();
      }, 700);
    }, { threshold: 0.5 });
  }
}

/* ============================================================================
   23. FAQ — category filter + live search
   ========================================================================= */
function initFaq() {
  const list = $('#faqList');
  if (!list) return;

  const items = $$('.qa', list);
  const buttons = $$('[data-faq]');
  const search = $('#faqSearch');
  const empty = $('#faqEmpty');
  let category = 'all';
  let query = '';

  const apply = () => {
    let shown = 0;
    items.forEach((item) => {
      const matchesCat = category === 'all' || item.dataset.cat === category;
      const matchesText = !query || item.textContent.toLowerCase().includes(query);
      const ok = matchesCat && matchesText;
      item.hidden = !ok;
      if (ok) { shown++; item.classList.add('active'); }
    });
    if (empty) empty.hidden = shown !== 0;
  };

  buttons.forEach((btn) => {
    btn.addEventListener('click', () => {
      category = btn.dataset.faq;
      buttons.forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', String(on));
      });
      apply();
    });
  });

  if (search) {
    search.addEventListener('input', () => { query = search.value.trim().toLowerCase(); apply(); });
  }
}

/* ============================================================================
   24. INTERACTIVE TERMINAL
   ----------------------------------------------------------------------------
   A small simulated shell. Nothing here touches a real system, and all output
   is inserted with textContent, never innerHTML.

   Project, skill and contact output is read from the page itself, so it stays
   in sync when you edit index.html.

   TO ADD A COMMAND: add an entry to `commands` below:
       mycmd: { desc: 'what it does', run(args) { line('hello'); } },
   ========================================================================= */
function initShell() {
  const out = $('#shellOut');
  const form = $('#shellForm');
  const input = $('#shellInput');
  const shell = $('#shell');
  if (!out || !form || !input) return;

  const reduce = prefersReducedMotion();
  const sleep = (ms) => wait(reduce ? 0 : ms);
  const history = [];
  let historyPos = 0;
  let busy = false;

  /* ---- output helpers ---- */
  const scroll = () => { out.scrollTop = out.scrollHeight; };
  const line = (text = '', cls = '') => {
    const div = document.createElement('div');
    div.className = `sh-line ${cls}`.trim();
    div.textContent = text;
    out.appendChild(div);
    scroll();
    return div;
  };
  const linkLine = (prefix, label, href) => {
    const div = document.createElement('div');
    div.className = 'sh-line';
    if (prefix) div.append(prefix);
    const a = document.createElement('a');
    a.href = href;
    a.textContent = label;
    if (/^https?:/i.test(href)) { a.target = '_blank'; a.rel = 'noopener'; }
    div.appendChild(a);
    out.appendChild(div);
    scroll();
  };

  /* ---- read data from the page ---- */
  const getProjects = () => $$('#projectGrid .card').map((card) => {
    const desc = $('.card__desc', card);
    const clone = desc ? desc.cloneNode(true) : null;
    if (clone) $$('.muted', clone).forEach((n) => n.remove());
    const link = $('.card__link', card);
    return {
      title: ($('.card__title', card) || {}).textContent || 'Untitled',
      type: card.dataset.type || '',
      desc: clone ? clone.textContent.replace(/\s+/g, ' ').trim() : '',
      tags: $$('.tags li', card).map((li) => li.textContent.trim()),
      href: link ? link.getAttribute('href') : '',
    };
  });
  const getSkills = () => $$('.skills__group').map((group) => ({
    name: ($('.skills__group-title', group) || {}).textContent || '',
    items: $$('.tags li', group).map((li) => li.textContent.trim()),
  }));
  const getEmail = () => {
    const link = $('#emailLink');
    return link ? link.getAttribute('href').replace(/^mailto:/i, '').split('?')[0] : '';
  };

  const THEMES = ['cyan', 'green', 'violet', 'amber'];
  const FILES = {
    'readme.txt': () => {
      line('Abin Kuriakose — cybersecurity student and aspiring penetration tester.');
      line('This site is hand-built HTML, CSS and vanilla JavaScript.');
    },
    'passions.txt': () => line('web application security · penetration testing · secure development · CTFs'),
    'contact.txt': () => commands.contact.run(),
  };

  /* ---- commands ---- */
  const commands = {
    help: {
      desc: 'list available commands',
      run() {
        line('Available commands:', 'sh-hl');
        Object.entries(commands)
          .filter(([, c]) => !c.hidden)
          .forEach(([name, c]) => line(`  ${name.padEnd(12)} ${c.desc}`));
      },
    },
    about: {
      desc: 'who I am',
      run() {
        line('Abin Kuriakose — cybersecurity student & aspiring penetration tester.', 'sh-hl');
        line('Focus: web application security, network recon, secure development.');
        line('Builds with Flutter, PHP and MySQL.');
      },
    },
    whoami: { desc: 'print the current user', run() { line('cybersecurity_student'); } },
    skills: {
      desc: 'list my toolkit',
      run() {
        getSkills().forEach((group) => {
          line(group.name, 'sh-hl');
          line('  ' + group.items.join(', '));
        });
      },
    },
    projects: {
      desc: 'list my projects',
      run() {
        getProjects().forEach((p, i) => {
          line(`[${i + 1}] ${p.title}${p.type ? ` (${p.type})` : ''}`, 'sh-hl');
          if (p.desc) line('    ' + p.desc);
          if (p.tags.length) line('    stack: ' + p.tags.join(', '));
          if (p.href && p.href !== '#') linkLine('    ', p.href, p.href);
        });
      },
    },
    contact: {
      desc: 'how to reach me',
      run() {
        const email = getEmail();
        if (email) linkLine('email     ', email, `mailto:${email}`);
        $$('a.social').forEach((a) => {
          const label = ($('strong', a) || {}).textContent || 'link';
          linkLine(`${label.toLowerCase().padEnd(10)}`, a.getAttribute('href'), a.getAttribute('href'));
        });
      },
    },
    ls: { desc: 'list files', run() { line(Object.keys(FILES).join('   ')); } },
    cat: {
      desc: 'read a file, e.g. cat readme.txt',
      run(args) {
        const name = args[0];
        if (!name) { line('usage: cat <file>', 'sh-err'); return; }
        if (/passwd|shadow/.test(name)) { line(`cat: ${name}: Permission denied. Nice try.`, 'sh-err'); return; }
        if (Object.prototype.hasOwnProperty.call(FILES, name)) FILES[name]();
        else line(`cat: ${name}: No such file`, 'sh-err');
      },
    },
    nmap: {
      desc: 'simulated port scan (try: nmap target.local)',
      async run(args) {
        const target = args.find((a) => !a.startsWith('-'));
        if (!target) { line('usage: nmap [options] <target>   (try: nmap target.local)'); return; }
        if (!['target.local', 'localhost', '127.0.0.1'].includes(target)) {
          line(`nmap: ${target} is not a practice target. Only scan systems you own or have written permission to test.`, 'sh-err');
          return;
        }
        line(`Starting Nmap scan on ${target} (simulated)`, 'sh-hl');
        await sleep(450);
        line('Host is up (0.00040s latency).');
        await sleep(400);
        line('PORT      STATE   SERVICE');
        for (const row of [['22/tcp', 'open', 'ssh'], ['80/tcp', 'open', 'http'], ['443/tcp', 'open', 'https'], ['3306/tcp', 'closed', 'mysql']]) {
          await sleep(260);
          line(`${row[0].padEnd(9)} ${row[1].padEnd(7)} ${row[2]}`, row[1] === 'open' ? 'sh-ok' : '');
        }
        await sleep(300);
        line('Done: 1 IP address (1 host up). This output is simulated for the demo.', 'sh-ok');
      },
    },
    theme: {
      desc: 'change accent colour: theme <cyan|green|violet|amber>',
      run(args) {
        const name = (args[0] || '').toLowerCase();
        if (!THEMES.includes(name)) { line('usage: theme <' + THEMES.join('|') + '>'); return; }
        window.dispatchEvent(new CustomEvent('portfolio:theme', { detail: name }));
        line(`accent set to ${name}`, 'sh-ok');
      },
    },
    matrix: {
      desc: 'wake up, Neo',
      run() {
        if (prefersReducedMotion()) { line('matrix: disabled because your system prefers reduced motion.', 'sh-err'); return; }
        window.dispatchEvent(new CustomEvent('portfolio:matrix'));
        line('Follow the white rabbit. (click or press Esc to exit)', 'sh-ok');
      },
    },
    history: {
      desc: 'show command history',
      run() { history.forEach((h, i) => line(`  ${String(i + 1).padStart(2)}  ${h}`)); },
    },
    date: { desc: 'print the local date', run() { line(new Date().toString()); } },
    echo: { desc: 'print text', run(args) { line(args.join(' ')); } },
    clear: { desc: 'clear the screen', run() { out.textContent = ''; } },
    sudo: { desc: 'try it', hidden: true, run() { line('visitor is not in the sudoers file. This incident will be reported.', 'sh-err'); } },
    exit: { desc: 'leave', hidden: true, run() { line('There is no escape. Try the nav bar instead.'); } },
  };

  /* ---- execution ---- */
  const exec = async (raw) => {
    const text = raw.trim();
    line(`$ ${raw}`, 'sh-cmd');
    if (!text) return;

    history.push(text);
    historyPos = history.length;

    const [name, ...args] = text.split(/\s+/);
    const key = name.toLowerCase();
    const cmd = Object.prototype.hasOwnProperty.call(commands, key) ? commands[key] : null;
    if (!cmd) { line(`command not found: ${name}. Type "help" for a list.`, 'sh-err'); return; }

    busy = true;
    try { await cmd.run(args); }
    catch (error) { line(`error: ${error.message}`, 'sh-err'); }
    finally { busy = false; }
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (busy) return;
    const value = input.value;
    input.value = '';
    exec(value);
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (history.length) { historyPos = Math.max(0, historyPos - 1); input.value = history[historyPos] || ''; }
    } else if (event.key === 'ArrowDown') {
      event.preventDefault();
      historyPos = Math.min(history.length, historyPos + 1);
      input.value = history[historyPos] || '';
    } else if (event.key === 'Tab') {
      event.preventDefault();
      const partial = input.value.trim().toLowerCase();
      if (!partial || partial.includes(' ')) return;
      const matches = Object.keys(commands).filter((c) => c.startsWith(partial) && !commands[c].hidden);
      if (matches.length === 1) input.value = matches[0] + ' ';
      else if (matches.length > 1) line(matches.join('   '));
    } else if (event.key === 'l' && event.ctrlKey) {
      event.preventDefault();
      out.textContent = '';
    }
  });

  // Suggested-command chips
  $$('[data-cmd]').forEach((chip) => {
    chip.addEventListener('click', () => { if (!busy) exec(chip.dataset.cmd); });
  });

  // Clicking the shell focuses the input (unless selecting text or clicking a link)
  if (shell) {
    shell.addEventListener('click', (event) => {
      if (event.target.closest('a')) return;
      if (window.getSelection && String(window.getSelection())) return;
      input.focus({ preventScroll: true });
    });
  }

  line('Welcome. This is a simulated shell — nothing here touches a real system.', 'sh-ok');
  line('Type "help" to see what it can do.');
  line('');
}

/* ============================================================================
   25. CONTACT — copy email + form
   The email address is read from the Email link's href, so you only ever edit
   it in one place. The footer link and the terminal stay in sync with it.
   ========================================================================= */
function initContact() {
  const emailLink = $('#emailLink');
  const getEmail = () => (emailLink ? emailLink.getAttribute('href').replace(/^mailto:/i, '').split('?')[0] : '');

  // Keep the visible text and the footer link in sync with the single source.
  if (emailLink) {
    const small = $('small', emailLink);
    if (small) small.textContent = getEmail();
    const footerEmail = $('#footerEmail');
    if (footerEmail) footerEmail.setAttribute('href', emailLink.getAttribute('href'));
  }

  // ---- Copy button ----
  const copyBtn = $('#copyEmail');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      const email = getEmail();
      let ok = false;
      try {
        await navigator.clipboard.writeText(email);
        ok = true;
      } catch (error) {
        const ta = document.createElement('textarea');
        ta.value = email;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;left:-9999px;top:0;';
        document.body.appendChild(ta);
        ta.select();
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        ta.remove();
      }
      copyBtn.textContent = ok ? 'Copied' : 'Press Ctrl+C';
      copyBtn.classList.toggle('is-done', ok);
      window.setTimeout(() => { copyBtn.textContent = 'Copy'; copyBtn.classList.remove('is-done'); }, 1800);
    });
  }

  // ---- Form ----
  const form = $('#contactForm');
  if (!form) return;
  const status = $('#formStatus');
  const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);

  const validateField = (input) => {
    const value = input.value.trim();
    let error = '';
    if (!value) error = `${input.name.charAt(0).toUpperCase() + input.name.slice(1)} is required.`;
    else if (input.type === 'email' && !isEmail(value)) error = 'Enter a valid email address.';
    else if (input.name === 'message' && value.length < 10) error = 'Message should be at least 10 characters.';

    const message = form.querySelector(`[data-error-for="${input.name}"]`);
    if (message) message.textContent = error;
    input.classList.toggle('is-invalid', Boolean(error));
    input.setAttribute('aria-invalid', error ? 'true' : 'false');
    return !error;
  };
  const validateAll = () => $$('input[required], textarea[required]', form).map(validateField).every(Boolean);

  $$('input, textarea', form).forEach((input) => {
    input.addEventListener('input', () => { if (input.classList.contains('is-invalid')) validateField(input); });
    input.addEventListener('blur', () => { if (input.value.trim() !== '') validateField(input); });
  });

  const setStatus = (text, kind) => {
    if (!status) return;
    status.textContent = text;
    status.classList.toggle('is-success', kind === 'success');
    status.classList.toggle('is-error', kind === 'error');
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const trap = form.querySelector('input[name="_gotcha"]');
    if (trap && trap.value !== '') return; // honeypot: bots fill every field

    if (!validateAll()) {
      const firstBad = $('.is-invalid', form);
      if (firstBad) firstBad.focus();
      return;
    }

    const payload = {};
    new FormData(form).forEach((value, key) => { if (key !== '_gotcha' && value !== '') payload[key] = value; });

    const button = $('button[type="submit"]', form);
    const originalHtml = button ? button.innerHTML : '';
    if (button) { button.disabled = true; button.textContent = 'Sending…'; }
    setStatus('', '');

    try {
      if (CONFIG.formEndpoint) {
        const response = await fetch(CONFIG.formEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error(`Server responded ${response.status}`);
        form.reset();
        $$('.form__error', form).forEach((el) => (el.textContent = ''));
        setStatus("> message sent. Thanks, I'll reply soon.", 'success');
      } else {
        // No backend configured: hand the message to the visitor's email app.
        const email = getEmail();
        if (!email || email === 'your@email.com') {
          console.warn('Set your real address in the Email link (index.html) or set CONFIG.formEndpoint.');
          setStatus("> this form isn't connected yet. Please use the GitHub or LinkedIn links.", 'error');
        } else {
          const subject = encodeURIComponent(`Portfolio message from ${payload.name}`);
          const body = encodeURIComponent(`${payload.message}\n\n— ${payload.name} (${payload.email})`);
          window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
          setStatus('> opening your email app. If nothing opens, copy my address above.', 'success');
        }
      }
    } catch (error) {
      console.error('Contact form failed:', error);
      setStatus('> something broke. Email me directly instead.', 'error');
    } finally {
      if (button) { button.disabled = false; button.innerHTML = originalHtml; }
    }
  });
}

/* ============================================================================
   26. ACCENT COLOUR SWITCHER
   The button cycles cyan > green > violet > amber and remembers the choice.
   The terminal's `theme` command uses the same code path via an event.
   ========================================================================= */
function initTheme() {
  const THEMES = ['cyan', 'green', 'violet', 'amber'];
  const root = document.documentElement;
  const btn = $('#themeBtn');

  const current = () => {
    const t = root.getAttribute('data-theme');
    return THEMES.includes(t) ? t : 'cyan';
  };

  const apply = (name) => {
    if (!THEMES.includes(name)) name = 'cyan';
    if (name === 'cyan') root.removeAttribute('data-theme'); else root.setAttribute('data-theme', name);
    safeStorage('local', 'portfolio-theme', name === 'cyan' ? '' : name);
    if (btn) btn.setAttribute('aria-label', `Change accent colour (current: ${name})`);
    window.dispatchEvent(new CustomEvent('portfolio:themechange', { detail: name }));
  };

  // Clean up a bad saved value, then sync the button label.
  if (root.hasAttribute('data-theme') && !THEMES.includes(root.getAttribute('data-theme'))) root.removeAttribute('data-theme');
  if (btn) btn.setAttribute('aria-label', `Change accent colour (current: ${current()})`);

  if (btn) {
    btn.addEventListener('click', () => apply(THEMES[(THEMES.indexOf(current()) + 1) % THEMES.length]));
  }
  window.addEventListener('portfolio:theme', (event) => apply(event.detail));
}

/* ============================================================================
   27. NETWORK CANVAS BACKGROUND
   ----------------------------------------------------------------------------
   A slow-drifting graph of nodes. Every few seconds a red "threat" node flies
   in from off-screen and is blocked when it reaches the network (a green ring
   pulses). Nodes lean toward the mouse. Pauses when the tab is hidden, is
   capped at a modest pixel ratio, and draws a single still frame for
   visitors who prefer reduced motion.
   ========================================================================= */
function initNetwork() {
  const canvas = $('#net');
  const cfg = CONFIG.network;
  if (!canvas) return;
  const ctx = canvas.getContext && canvas.getContext('2d');
  if (!ctx || !cfg.enabled) { canvas.style.display = 'none'; return; }

  const reduce = prefersReducedMotion();
  let w = 0, h = 0, dpr = 1;
  let nodes = [], threats = [], pulses = [];
  let raf = 0, last = 0, nextThreat = 0;
  let rgb = '34,211,238';
  const mouse = { x: -1e4, y: -1e4 };

  const readTheme = () => {
    const v = getComputedStyle(document.documentElement).getPropertyValue('--accent-rgb').trim();
    if (v) rgb = v.replace(/\s+/g, '');
  };

  const build = () => {
    const count = clamp(Math.round((w * h) / cfg.density), cfg.minNodes, cfg.maxNodes);
    nodes = Array.from({ length: count }, () => ({
      x: Math.random() * w, y: Math.random() * h,
      vx: (Math.random() - 0.5) * cfg.speed, vy: (Math.random() - 0.5) * cfg.speed,
      r: Math.random() * 1.1 + 0.7, flash: 0,
    }));
  };

  const spawnThreat = () => {
    if (!nodes.length) return;
    const edge = Math.floor(Math.random() * 4);
    const x = edge === 0 ? -20 : edge === 1 ? w + 20 : Math.random() * w;
    const y = edge === 2 ? -20 : edge === 3 ? h + 20 : Math.random() * h;
    threats.push({ x, y, target: pick(nodes) });
  };

  const draw = (k, t) => {
    ctx.clearRect(0, 0, w, h);

    // move nodes
    for (const n of nodes) {
      n.x += n.vx * k; n.y += n.vy * k;
      if (n.x < -10) n.x = w + 10; else if (n.x > w + 10) n.x = -10;
      if (n.y < -10) n.y = h + 10; else if (n.y > h + 10) n.y = -10;
      const dx = mouse.x - n.x, dy = mouse.y - n.y, d2 = dx * dx + dy * dy;
      if (d2 < 22500 && d2 > 1) { const d = Math.sqrt(d2); n.x += (dx / d) * 0.12 * k; n.y += (dy / d) * 0.12 * k; }
      if (n.flash > 0) n.flash = Math.max(0, n.flash - 0.02 * k);
    }

    // links
    const L = cfg.link, L2 = L * L;
    ctx.lineWidth = 1;
    for (let i = 0; i < nodes.length; i++) {
      const a = nodes[i];
      for (let j = i + 1; j < nodes.length; j++) {
        const b = nodes[j];
        const dx = a.x - b.x, dy = a.y - b.y, d2 = dx * dx + dy * dy;
        if (d2 >= L2) continue;
        let alpha = (1 - Math.sqrt(d2) / L) * 0.2;
        const mx = (a.x + b.x) / 2 - mouse.x, my = (a.y + b.y) / 2 - mouse.y;
        if (mx * mx + my * my < 14400) alpha += 0.18;
        ctx.strokeStyle = `rgba(${rgb},${alpha.toFixed(3)})`;
        ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
      }
    }

    // nodes
    for (const n of nodes) {
      ctx.fillStyle = `rgba(${rgb},${(0.45 + n.flash * 0.5).toFixed(2)})`;
      ctx.beginPath(); ctx.arc(n.x, n.y, n.r + n.flash * 2.5, 0, Math.PI * 2); ctx.fill();
    }

    if (reduce) return;

    // threats: fly toward a node, get blocked on arrival
    if (t >= nextThreat && threats.length < 2) { spawnThreat(); nextThreat = t + rand(3500, 7000); }
    for (let i = threats.length - 1; i >= 0; i--) {
      const th = threats[i];
      const dx = th.target.x - th.x, dy = th.target.y - th.y, d = Math.hypot(dx, dy);
      if (d < 8) {
        pulses.push({ x: th.target.x, y: th.target.y, r: 4, a: 0.7 });
        th.target.flash = 1;
        threats.splice(i, 1);
        continue;
      }
      const ux = dx / d, uy = dy / d, speed = 1.15 * k;
      th.x += ux * speed; th.y += uy * speed;
      const g = ctx.createLinearGradient(th.x - ux * 34, th.y - uy * 34, th.x, th.y);
      g.addColorStop(0, 'rgba(251,113,133,0)');
      g.addColorStop(1, 'rgba(251,113,133,0.55)');
      ctx.strokeStyle = g; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.moveTo(th.x - ux * 34, th.y - uy * 34); ctx.lineTo(th.x, th.y); ctx.stroke();
      ctx.fillStyle = 'rgba(251,113,133,0.95)';
      ctx.beginPath(); ctx.arc(th.x, th.y, 2.4, 0, Math.PI * 2); ctx.fill();
    }

    // block pulses
    for (let i = pulses.length - 1; i >= 0; i--) {
      const p = pulses[i];
      p.r += 0.9 * k; p.a -= 0.012 * k;
      if (p.a <= 0) { pulses.splice(i, 1); continue; }
      ctx.strokeStyle = `rgba(52,211,153,${p.a.toFixed(3)})`; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.stroke();
    }
  };

  const step = (t) => {
    raf = requestAnimationFrame(step);
    const dt = Math.min(34, t - (last || t));
    last = t;
    draw(dt / 16.67, t);
  };
  const start = () => { if (raf || reduce) return; last = 0; nextThreat = performance.now() + 2500; raf = requestAnimationFrame(step); };
  const stop = () => { cancelAnimationFrame(raf); raf = 0; };

  const resize = () => {
    const widthChanged = window.innerWidth !== w;
    w = window.innerWidth; h = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, cfg.maxDpr);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    if (widthChanged || !nodes.length) build();
    if (reduce) draw(0, 0);
  };

  readTheme();
  resize();
  window.addEventListener('resize', throttleRaf(resize));
  window.addEventListener('portfolio:themechange', () => { readTheme(); if (reduce) draw(0, 0); });

  if (!reduce) {
    window.addEventListener('pointermove', (event) => {
      if (event.pointerType === 'touch') return;
      mouse.x = event.clientX; mouse.y = event.clientY;
    }, { passive: true });
    document.addEventListener('mouseleave', () => { mouse.x = mouse.y = -1e4; });
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else start(); });
    start();
  }
}

/* ============================================================================
   28. MATRIX RAIN EASTER EGG
   Trigger: type `matrix` in the terminal, or enter the Konami code
   (up up down down left right left right b a). Click or Esc to close.
   ========================================================================= */
function initMatrix() {
  const canvas = $('#matrix');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const glyphs = 'アイウエオカキクケコサシスセソタチツテトナニヌネノ0123456789ABCDEF<>/{}[]=+*';
  const size = 16;
  let drops = [], timer = 0, closeTimer = 0, color = '#22d3ee';

  const stop = () => {
    clearInterval(timer);
    clearTimeout(closeTimer);
    canvas.classList.remove('is-on');
  };

  const draw = () => {
    ctx.fillStyle = 'rgba(2,4,8,0.14)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = color;
    ctx.font = `${size}px monospace`;
    for (let i = 0; i < drops.length; i++) {
      ctx.fillText(glyphs[Math.floor(Math.random() * glyphs.length)], i * size, drops[i] * size);
      if (drops[i] * size > canvas.height && Math.random() > 0.975) drops[i] = 0;
      drops[i]++;
    }
  };

  const start = () => {
    if (prefersReducedMotion()) return;
    stop();
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    drops = Array.from({ length: Math.ceil(canvas.width / size) }, () => Math.random() * -40);
    const rgb = getComputedStyle(document.documentElement).getPropertyValue('--accent-rgb').trim();
    color = rgb ? `rgb(${rgb})` : '#22d3ee';
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    canvas.classList.add('is-on');
    timer = window.setInterval(draw, 55);
    closeTimer = window.setTimeout(stop, 9000);
  };

  canvas.addEventListener('click', stop);
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') stop(); });
  window.addEventListener('portfolio:matrix', start);

  // Konami code
  const code = ['arrowup', 'arrowup', 'arrowdown', 'arrowdown', 'arrowleft', 'arrowright', 'arrowleft', 'arrowright', 'b', 'a'];
  let pos = 0;
  document.addEventListener('keydown', (event) => {
    if (/^(input|textarea)$/i.test(event.target.tagName)) return;
    const key = event.key.toLowerCase();
    if (key === code[pos]) { pos++; if (pos === code.length) { pos = 0; start(); } }
    else pos = key === code[0] ? 1 : 0;
  });
}


/* ============================================================================
   29. SCROLL EFFECTS
   ----------------------------------------------------------------------------
   Everything here is driven by the scroll position (not just "became visible"):

     • Hero fade     the hero copy drifts up and fades as you scroll past
                     (--hero-p, 0 to 1, written on .hero)
     • Moving grid   the background grid slides with the page, so the page
                     feels deeper (--grid-shift, wraps seamlessly every 56px)
     • Marquee       the tech strip speeds up and leans with scroll speed
     • Headings      each section's eyebrow slides in and its underline draws
     • Tag stagger   tags pop in one after another inside a revealed group
     • Stagger       children of [data-stagger] reveal in sequence

   Reduced motion: everything is shown immediately and no scroll-linked
   motion runs.
   ========================================================================= */
function initStaggerGroups() {
  $$('[data-stagger]').forEach((group) => {
    Array.from(group.children).forEach((child, i) => {
      child.classList.add('reveal');
      child.style.setProperty('--i', String(i));
    });
  });
  // Tag pop-in index (CSS reads --k)
  $$('.skills__group .tags, .tl .tags').forEach((list) => {
    Array.from(list.children).forEach((li, k) => li.style.setProperty('--k', String(k)));
  });
}

function initSectionHeads() {
  const heads = $$('.section__head');
  if (prefersReducedMotion()) { heads.forEach((h) => h.classList.add('is-in')); return; }
  onceVisible(heads, (el) => el.classList.add('is-in'), { threshold: 0.3, rootMargin: '0px 0px -6% 0px' });
}

function initScrollFX() {
  if (prefersReducedMotion()) return;

  const root = document.documentElement;
  const hero = $('#hero');
  const marquee = $('.marquee');
  const track = $('#marqueeTrack');
  const cue = $('#scrollCue');
  const GRID = 56; // must match --fx-grid-size in style.css

  let heroHeight = hero ? hero.offsetHeight : 1;
  let lastY = window.scrollY;
  let velocity = 0;
  let raf = 0;

  const marqueeAnimation = () => {
    try { return track ? track.getAnimations()[0] || null : null; } catch (error) { return null; }
  };

  const frame = () => {
    raf = 0;
    const y = window.scrollY;
    const dy = y - lastY;
    lastY = y;
    velocity = lerp(velocity, dy, 0.18);

    if (hero) hero.style.setProperty('--hero-p', clamp(y / (heroHeight * 0.75), 0, 1).toFixed(3));
    root.style.setProperty('--grid-shift', (-((y * 0.25) % GRID)).toFixed(1));
    if (cue) cue.classList.toggle('is-gone', y > 60);

    const anim = marqueeAnimation();
    if (marquee) marquee.style.setProperty('--skew', clamp(velocity * 0.15, -9, 9).toFixed(2));
    if (anim) anim.playbackRate = 1 + clamp(Math.abs(velocity) * 0.2, 0, 8);

    if (Math.abs(velocity) > 0.05 || dy !== 0) {
      raf = requestAnimationFrame(frame);
    } else {
      if (marquee) marquee.style.setProperty('--skew', '0');
      if (anim) anim.playbackRate = 1;
    }
  };

  window.addEventListener('scroll', () => { if (!raf) raf = requestAnimationFrame(frame); }, { passive: true });
  window.addEventListener('resize', () => { heroHeight = hero ? hero.offsetHeight : 1; });
  frame();
}

/* ============================================================================
   INIT
   ----------------------------------------------------------------------------
   Modules that don't depend on the boot screen run immediately. Anything that
   plays an entrance animation waits for the boot screen to finish, so the
   hero lands as the intro fades out. Each module is wrapped so one failure
   can't take down the rest.
   ========================================================================= */
function runModules(modules) {
  modules.forEach((module) => {
    try { module(); }
    catch (error) { console.error(`Failed to run ${module.name}:`, error); }
  });
}

function init() {
  document.documentElement.classList.remove('no-js');

  runModules([
    initFooterSys,
    initScrollProgress,
    initNavbarShadow,
    initMobileMenu,
    initSmoothScroll,
    initScrollSpy,
    initTheme,
    initNetwork,
    initMatrix,
    initCursorGlow,
    initMouseParallax,
    initMetrics,
    initMarquee,
    initScrollFX,
    initCardEffects,
    initProjectFilter,
    initTimeline,
    initHardening,
    initFaq,
    initShell,
    initContact,
  ]);

  let boot;
  try { boot = initBoot(); } catch (error) { console.error('Failed to run initBoot:', error); boot = Promise.resolve(); }

  boot.then(() => {
    runModules([
      initStaggerGroups,   // must run before initRevealOnScroll so it sees the new .reveal items
      initSectionHeads,
      initRevealOnScroll,
      initTerminalTyping,
      initRoles,
      initFeed,
      initScrambleTitles,
    ]);
    // Decrypt-style entrance for the hero name
    try { scrambleText($('#heroName'), 1000); } catch (error) { console.error(error); }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
