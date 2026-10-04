/* ============================================================================
   PORTFOLIO — script.js
   ----------------------------------------------------------------------------
   Vanilla ES6+. No dependencies. Every feature is an isolated module below,
   so you can delete a block you don't need without touching the others.

   Contents
     01. Config
     02. Utilities
     03. Footer year (auto-updates)
     04. Navbar: scrolled shadow
     05. Navbar: mobile drawer
     06. Smooth scrolling for anchor links
     07. Scrollspy: highlight the active nav link
     08. Reveal-on-scroll animations
     09. Terminal boot typing
     10. Mouse parallax
     11. Contact form: validation + fake submit

   Visual tuning (speeds, glow strengths, distances) lives in the
   "FX CONTROL PANEL" block in :root at the top of style.css.
   ========================================================================= */

'use strict';

/* ============================================================================
   01. CONFIG
   ----------------------------------------------------------------------------
   EDIT THESE to match your markup.

   NOTE ON WHERE THINGS ARE TUNED:
     • Anything visual that CSS can express is a token in style.css, under the
       "FX CONTROL PANEL" comment in :root. Keep it there.
     • The values below are only for the things JavaScript has to drive
       itself — timings, pixel distances, and matchMedia breakpoints.
   ========================================================================= */
const CONFIG = {
  // Width of the fixed navbar. Read from CSS rather than hardcoded so it
  // stays correct when the mobile breakpoint shrinks --nav-h to 60px.
  navbarHeight: () =>
    parseInt(
      getComputedStyle(document.documentElement).getPropertyValue('--nav-h'),
      10
    ) || 68,

  // How far from the top of a section counts as "you're viewing it".
  // Bump this if the highlight feels out of sync with the heading.
  scrollspyOffset: 0.35, // fraction of viewport height (0 - 1)

  // Auto-close the mobile drawer after a link is clicked
  closeMenuOnNavClick: true,

  // Contact form endpoint.
  //   null            -> demo mode: validates, then fakes a success message
  //   'your-form-id'  -> Formspree (https://formspree.io), posts via fetch
  //   '/api/contact'  -> your own backend that accepts JSON POST
  formEndpoint: null,

  /* ---- Mouse parallax ------------------------------------------------------
     How far each layer slides, in px, when the pointer reaches the far edge
     of the window. The parallax module normalises the pointer to a -1..1
     range first, so these are "maximum travel" numbers.

     foreground -> the hero terminal, written straight to the element
     background -> the body::before grid, written as --parallax-x/y because a
                   pseudo-element has no element to attach an inline style to.

     Set either to 0 to switch that layer off without touching any other code.
     ------------------------------------------------------------------------ */
  parallax: {
    enabled: true,
    foreground: 26, // px the hero terminal travels
    background: 9,  // px the grid travels. Try 30-40% of the foreground.
    // Below this viewport width the layout stacks to one column (see the
    // max-width: 768px block in style.css), so a sideways push would shove
    // the terminal off-centre. This is the gate that actually switches the
    // mouse parallax off on phones — keep it in sync with that breakpoint.
    disableBelowWidth: 768,
    // Only run for real pointing devices (mouse/trackpad). A finger "drag"
    // would fight with scrolling, so coarse pointers are excluded.
    requireFinePointer: true,
  },

  /* ---- Terminal boot typing ----------------------------------------------
     The per-line stagger itself lives in CSS as --fx-type-stagger. This is
     just how long to wait after page load before the sequence starts, so the
     hero copy lands first and the terminal types in behind it.
     ------------------------------------------------------------------------ */
  typing: {
    enabled: true,
    startDelay: 420, // ms after init
  },
};

/* ============================================================================
   02. UTILITIES
   ========================================================================= */

/** Shorthand for querySelector. */
const $ = (selector, scope = document) => scope.querySelector(selector);

/** Shorthand for querySelectorAll, returned as a real Array. */
const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

/** True if the visitor asked the OS to reduce motion. */
const prefersReducedMotion = () =>
  window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Returns a function you can call to cancel a pending rAF callback.
 * Used to keep scroll handlers cheap — we only run one update per frame
 * instead of on every single scroll event.
 */
function throttleRaf(fn) {
  let ticking = false;
  return (...args) => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(() => {
      fn(...args);
      ticking = false;
    });
  };
}

/** Clamp a number between a min and max. */
const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

/** Lerp: move `from` toward `to` by `amount` (0..1). Used to smooth parallax. */
const lerp = (from, to, amount) => from + (to - from) * amount;

/* ============================================================================
   03. FOOTER YEAR
   Keeps the copyright date from ever going stale.
   ========================================================================= */
function initFooterYear() {
  const el = $('#year');
  if (!el) return;
  el.textContent = new Date().getFullYear();
}

/* ============================================================================
   04. NAVBAR — add a shadow/border once the page is scrolled
   ========================================================================= */
function initNavbarShadow() {
  const navbar = $('#navbar');
  if (!navbar) return;

  const update = throttleRaf(() => {
    // Threshold of 40px before the style kicks in
    navbar.classList.toggle('is-scrolled', window.scrollY > 40);
  });

  window.addEventListener('scroll', update, { passive: true });
  update(); // run once in case the page loads already-scrolled
}

/* ============================================================================
   05. NAVBAR — mobile drawer (hamburger)
   ========================================================================= */
function initMobileMenu() {
  const toggle = $('#navToggle');
  const menu = $('#navMenu');
  if (!toggle || !menu) return;

  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    // Stop the page behind the drawer from scrolling while it's open
    document.body.style.overflow = open ? 'hidden' : '';
  };

  const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

  // Hamburger button
  toggle.addEventListener('click', () => setOpen(!isOpen()));

  // Close when tapping a link
  $$('#navMenu .nav__link').forEach((link) => {
    link.addEventListener('click', () => {
      if (CONFIG.closeMenuOnNavClick) setOpen(false);
    });
  });

  // Close on Escape
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      setOpen(false);
      toggle.focus(); // return focus to the button for keyboard users
    }
  });

  // Close if the viewport grows back to desktop. Must match the max-width
  // breakpoint in style.css where the hamburger is first shown.
  window.matchMedia('(min-width: 769px)').addEventListener('change', (event) => {
    if (event.matches) setOpen(false);
  });

  // Close when clicking outside the drawer
  document.addEventListener('click', (event) => {
    if (!isOpen()) return;
    if (!menu.contains(event.target) && !toggle.contains(event.target)) setOpen(false);
  });
}

/* ============================================================================
   06. SMOOTH SCROLLING
   ----------------------------------------------------------------------------
   CSS has `scroll-behavior: smooth` already. This handler adds the two
   things CSS alone can't do:
     - correct offset for the fixed navbar (via scroll-padding-top)
     - smooth scrolling for browsers without native support
   ========================================================================= */
function initSmoothScroll() {
  // Only hijack links that point to a section on THIS page
  const anchors = $$('a[href^="#"]').filter((link) => {
    const id = link.getAttribute('href');
    return id.length > 1 && document.querySelector(id);
  });

  anchors.forEach((link) => {
    link.addEventListener('click', (event) => {
      const target = $(link.getAttribute('href'));
      if (!target) return;

      event.preventDefault();

      // Close the mobile drawer first so the layout is settled before scrolling
      const toggle = $('#navToggle');
      const menu = $('#navMenu');
      if (menu && menu.classList.contains('is-open') && toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
        document.body.style.overflow = '';
      }

      const scrollTo = () => {
        // getBoundingClientRect + scrollY gives the absolute document position
        const top =
          target.getBoundingClientRect().top + window.scrollY - CONFIG.navbarHeight();
        window.scrollTo({ top, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
      };

      if (CONFIG.closeMenuOnNavClick) {
        // Double requestAnimationFrame: guarantees the drawer's layout has
        // been recalculated before we measure the target position
        requestAnimationFrame(() => requestAnimationFrame(scrollTo));
      } else {
        scrollTo();
      }

      // Update the URL hash without triggering a second jump
      history.pushState(null, '', link.getAttribute('href'));
    });
  });
}

/* ============================================================================
   07. SCROLLSPY — highlight the nav link for the section you're viewing
   ----------------------------------------------------------------------------
   Works by checking which section's top edge is closest to a line placed
   partway down the viewport.
   ========================================================================= */
function initScrollSpy() {
  const links = $$('#navMenu .nav__link');
  const sections = links
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

  if (!sections.length) return;

  /** @param {string|null} id section id to highlight, or null for none */
  const setActive = (id) => {
    links.forEach((link) => {
      const isCurrent = id !== null && link.getAttribute('href') === `#${id}`;
      link.classList.toggle('is-active', isCurrent);

      if (isCurrent) {
        link.setAttribute('aria-current', 'true');
      } else {
        link.removeAttribute('aria-current');
      }
    });
  };

  const update = throttleRaf(() => {
    // The line we test against, measured from the top of the viewport
    const line = window.scrollY + window.innerHeight * CONFIG.scrollspyOffset;

    let currentId = sections[0].id;

    sections.forEach((section) => {
      const top = section.getBoundingClientRect().top + window.scrollY;
      if (top <= line) currentId = section.id; // still the last one we passed
    });

    // At the very bottom of the page, always light up the last link —
    // otherwise short final sections can never become "active".
    const atBottom =
      window.innerHeight + window.scrollY >= document.body.offsetHeight - 2;

    if (atBottom) {
      currentId = sections[sections.length - 1].id;
    }

    // Still sitting at the top of the page: no section is being viewed yet,
    // so highlight nothing rather than lighting up "About" prematurely.
    if (window.scrollY < 40) currentId = null;

    setActive(currentId);
  });

  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
}

/* ============================================================================
   08. REVEAL ON SCROLL
   ----------------------------------------------------------------------------
   Cards and skill blocks start hidden (CSS .reveal) and get .active
   when they enter the viewport.

   HOW TO ADD: put class="reveal" on any element in the HTML.
   To stagger several elements, set a delay in the markup:
       <article class="card reveal" style="--i: 1">
   ========================================================================= */
function initRevealOnScroll() {
  const items = $$('.reveal');
  if (!items.length) return;

  // No IntersectionObserver (or reduced motion requested): show everything now
  if (!('IntersectionObserver' in window) || prefersReducedMotion()) {
    items.forEach((el) => el.classList.add('active'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('active');
        observer.unobserve(entry.target); // animate once, then stop watching
      });
    },
    {
      root: null,               // viewport
      rootMargin: '0px 0px -12% 0px', // trigger slightly before it's fully visible
      threshold: 0.12,          // 12% of the element visible is enough
    }
  );

  items.forEach((el) => observer.observe(el));
}

/* ============================================================================
   09. TERMINAL BOOT TYPING
   ----------------------------------------------------------------------------
   Fades the terminal's output in line by line so it looks like it just booted.

   The staggering is NOT done here with a loop of setTimeouts. Instead we stamp
   each line with its own --i index and add a single .is-typed class to the
   parent. CSS reads --i and builds the transition-delay from it. One class
   toggle, zero timers, and the whole thing stays declarative:

       .terminal__body.is-typed .terminal__line { ... }
       transition-delay: calc(var(--i) * var(--fx-type-stagger));

   Retune the per-line pace with --fx-type-stagger in style.css, and the
   start time with CONFIG.typing.startDelay.
   ========================================================================= */
function initTerminalTyping() {
  const body = $('.terminal__body');
  if (!body) return;

  const lines = $$('.terminal__line', body);
  if (!lines.length) return;

  const revealAll = () => {
    // Stamp each line with its position in the sequence.
    lines.forEach((line, index) => line.style.setProperty('--i', index));
    // One toggle; CSS does the rest.
    body.classList.add('is-typed');
  };

  // Reduced motion, or the feature switched off: show everything at once.
  if (!CONFIG.typing.enabled || prefersReducedMotion()) {
    revealAll();
    return;
  }

  window.setTimeout(revealAll, CONFIG.typing.startDelay);
}

/* ============================================================================
   10. MOUSE PARALLAX
   ----------------------------------------------------------------------------
   The hero terminal and the background grid drift against each other as the
   pointer moves. The grid travels a shorter distance, which is what sells the
   sense of depth — a single layer moving alone just looks broken.

   PERFORMANCE NOTES
     • Only `transform` is ever written, so this runs on the compositor and
       triggers zero layout or paint.
     • Writes are rAF-batched: many pointermove events collapse into one
       update per frame.
     • The loop self-terminates once the layers have caught up, so when the
       mouse is still we cost nothing.
     • Pointer values are lerped toward the target rather than applied
       directly, which is what makes the movement feel weighted rather than
       glued to the cursor.

   TO DISABLE: set CONFIG.parallax.enabled = false, or foreground/background
   to 0 — no other code needs to change.

   The two layers are deliberately asymmetric — the foreground travels several
   times further than the background. That gap is what reads as depth. Equal
   values just look like the page is sliding around.
   ========================================================================= */
function initMouseParallax() {
  const cfg = CONFIG.parallax;

  const foreground = $('.hero__visual');

  if (!cfg.enabled || !foreground) return;

  // Someone who asked for reduced motion gets no parallax at all.
  if (prefersReducedMotion()) return;

  // Gate on the two conditions CSS can't: a real hovering pointer, and a
  // viewport wide enough that a sideways push won't shove the layout around.
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const wideEnough = window.matchMedia(`(min-width: ${cfg.disableBelowWidth}px)`);

  const shouldRun = () =>
    wideEnough.matches && (!cfg.requireFinePointer || finePointer.matches);

  // Pointer position, normalised to -1..1 on both axes. 0,0 is dead centre.
  let targetX = 0;
  let targetY = 0;
  let currentX = 0;
  let currentY = 0;
  let running = false;

  /**
   * Writes the eased position onto both layers.
   *
   * The background grid is body::before — a pseudo-element, so there is no
   * element to query and no inline transform to write to. Instead we set two
   * custom properties on <body>, and the body::before rule in style.css does
   * `transform: translate3d(calc(var(--parallax-x) * 1px), ...)`. That is the
   * only way to drive a pseudo-element from script.
   */
  const render = () => {
    // Multiply by -1 so content leans toward the cursor as you approach it,
    // rather than being shoved away from it.
    foreground.style.transform =
      `translate3d(${(-currentX * cfg.foreground).toFixed(2)}px, ` +
      `${(-currentY * cfg.foreground).toFixed(2)}px, 0)`;

    document.body.style.setProperty(
      '--parallax-x',
      (-currentX * cfg.background).toFixed(2)
    );
    document.body.style.setProperty(
      '--parallax-y',
      (-currentY * cfg.background).toFixed(2)
    );
  };

  /** Eases toward the target until it converges, then stops. */
  const tick = () => {
    const done =
      Math.abs(targetX - currentX) < 0.001 && Math.abs(targetY - currentY) < 0.001;

    if (done) {
      // Snap to exact to avoid leaving a sub-pixel drift behind.
      currentX = targetX;
      currentY = targetY;
      render();
      running = false;
      return;
    }

    // 0.08 is the "weight" of the movement. Lower = heavier/slower.
    currentX = lerp(currentX, targetX, 0.08);
    currentY = lerp(currentY, targetY, 0.08);
    render();

    window.requestAnimationFrame(tick);
  };

  const start = () => {
    if (running) return;
    running = true;
    window.requestAnimationFrame(tick);
  };

  /** Snaps both layers back to centre and clears the inline values. */
  const reset = () => {
    targetX = targetY = 0;
    currentX = currentY = 0;
    running = false;
    foreground.style.transform = '';
    document.body.style.removeProperty('--parallax-x');
    document.body.style.removeProperty('--parallax-y');
  };

  const onPointerMove = (event) => {
    if (!shouldRun() || document.hidden) return;
    // Normalise the pointer position to -1..1, clamped so an off-screen or
    // synthetic event can never send a layer flying off the viewport.
    targetX = clamp((event.clientX / window.innerWidth) * 2 - 1, -1, 1);
    targetY = clamp((event.clientY / window.innerHeight) * 2 - 1, -1, 1);
    start();
  };

  window.addEventListener('pointermove', onPointerMove, { passive: true });

  // Recentring — pointer left the window, tab lost focus, or we shrank past
  // the breakpoint. Any of these should drop the layers back to neutral.
  document.addEventListener('mouseleave', () => {
    targetX = targetY = 0;
    start();
  });
  window.addEventListener('blur', reset);
  wideEnough.addEventListener('change', (event) => {
    if (!event.matches) reset();
  });
  finePointer.addEventListener('change', (event) => {
    if (!event.matches) reset();
  });
}

/* ============================================================================
   11. CONTACT FORM
   ----------------------------------------------------------------------------
   Front-end only by default: it validates the fields and shows a success
   message without sending anything anywhere.

   TO MAKE IT REAL, set CONFIG.formEndpoint to one of:
     • Formspree : 'https://formspree.io/f/your-form-id'
     • Your API  : '/api/contact'
   Then this handler will POST the data as JSON for you.
   ========================================================================= */
function initContactForm() {
  const form = $('#contactForm');
  if (!form) return;

  const status = $('#formStatus');

  /** Simple email shape check — good enough for front-end validation. */
  const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);

  /**
   * Validates one field and paints its error message.
   * @returns {boolean} true if the field is valid
   */
  function validateField(input) {
    const value = input.value.trim();
    let error = '';

    if (!value) {
      error = `${input.name.charAt(0).toUpperCase() + input.name.slice(1)} is required.`;
    } else if (input.type === 'email' && !isEmail(value)) {
      error = 'Enter a valid email address.';
    } else if (input.name === 'message' && value.length < 10) {
      error = 'Message should be at least 10 characters.';
    }

    const message = form.querySelector(`[data-error-for="${input.name}"]`);
    if (message) message.textContent = error;

    input.classList.toggle('is-invalid', Boolean(error));
    input.setAttribute('aria-invalid', error ? 'true' : 'false');

    return !error;
  }

  /** Validates every field at once. */
  function validateAll() {
    const fields = $$('input[required], textarea[required]', form);
    return fields.map(validateField).every(Boolean);
  }

  // Re-validate a field once the visitor fixes it (clears the error live)
  $$('input, textarea', form).forEach((input) => {
    input.addEventListener('input', () => {
      if (input.classList.contains('is-invalid')) validateField(input);
    });
    input.addEventListener('blur', () => {
      if (input.value.trim() !== '') validateField(input);
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    // Honeypot: a hidden field real users never fill in. Bots that fill
    // every input get silently ignored.
    const trap = form.querySelector('input[name="_gotcha"]');
    if (trap && trap.value !== '') return;

    if (!validateAll()) {
      // Focus the first bad field so keyboard users land on the problem
      const firstBad = $('.is-invalid', form);
      if (firstBad) firstBad.focus();
      return;
    }

    const button = $('button[type="submit"]', form);
    const originalText = button ? button.innerHTML : '';

    if (button) {
      button.disabled = true;
      button.textContent = 'Sending…';
    }
    if (status) {
      status.textContent = '';
      status.classList.remove('is-success', 'is-error');
    }

    // Collect the payload, skipping empty fields and the honeypot
    const payload = {};
    new FormData(form).forEach((value, key) => {
      if (key !== '_gotcha' && value !== '') payload[key] = value;
    });

    try {
      if (CONFIG.formEndpoint) {
        /* ---- REAL SUBMIT: remove this whole if-block to go demo-only ---- */
        const response = await fetch(CONFIG.formEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error(`Server responded ${response.status}`);
      } else {
        /* ---- DEMO MODE: pretend it worked after a short delay ---- */
        await new Promise((resolve) => setTimeout(resolve, 900));
      }

      form.reset();
      $$('.form__error', form).forEach((el) => (el.textContent = ''));
      $$('.is-invalid', form).forEach((el) => el.classList.remove('is-invalid'));

      if (status) {
        status.classList.add('is-success');
        status.textContent = CONFIG.formEndpoint
          ? '> message sent — thanks, I\'ll reply soon.'
          : '> demo mode — looks good! Point CONFIG.formEndpoint at a real endpoint to send it.';
      }
    } catch (error) {
      console.error('Contact form failed:', error);
      if (status) {
        status.classList.add('is-error');
        status.textContent = '> something broke. Email me directly instead.';
      }
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = originalText;
      }
    }
  });
}

/* ============================================================================
   INIT
   ----------------------------------------------------------------------------
   Each feature is wrapped so that one failure can't take down the rest.
   Add your own modules to this list as you build new sections.
   ========================================================================= */
function init() {
  // Mark that JS is running, so CSS can un-hide .reveal content if needed
  document.documentElement.classList.remove('no-js');

  const modules = [
    initFooterYear,
    initNavbarShadow,
    initMobileMenu,
    initSmoothScroll,
    initScrollSpy,
    initRevealOnScroll,
    initTerminalTyping,
    initMouseParallax,
    initContactForm,
  ];

  modules.forEach((module) => {
    try {
      module();
    } catch (error) {
      console.error(`Failed to run ${module.name}:`, error);
    }
  });
}

// Wait for the DOM so every querySelector above finds its element
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}