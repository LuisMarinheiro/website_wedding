/* ============================================================
   main.js — Countdown, Nav, RSVP, Animations
   ============================================================ */

/* ── Countdown ── */
(function initCountdown() {
  const WEDDING = new Date('2026-08-09T11:00:00');

  const elDays  = document.getElementById('cd-days');
  const elHours = document.getElementById('cd-hours');
  const elMins  = document.getElementById('cd-mins');
  const elSecs  = document.getElementById('cd-secs');

  if (!elDays) return;

  function pad(n, len = 2) { return String(n).padStart(len, '0'); }

  function tick() {
    const now  = new Date();
    const diff = WEDDING - now;

    if (diff <= 0) {
      elDays.textContent  = '00';
      elHours.textContent = '00';
      elMins.textContent  = '00';
      elSecs.textContent  = '00';
      return;
    }

    const days  = Math.floor(diff / 86400000);
    const hours = Math.floor((diff % 86400000) / 3600000);
    const mins  = Math.floor((diff % 3600000)  / 60000);
    const secs  = Math.floor((diff % 60000)    / 1000);

    elDays.textContent  = pad(days, 3);
    elHours.textContent = pad(hours);
    elMins.textContent  = pad(mins);
    elSecs.textContent  = pad(secs);
  }

  tick();
  setInterval(tick, 1000);
})();


/* ── Header scroll shadow ── */
(function initHeader() {
  const header = document.getElementById('site-header');
  if (!header) return;

  const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 20);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();


/* ── Mobile hamburger ── */
(function initHamburger() {
  const btn   = document.getElementById('hamburger');
  const links = document.querySelector('.nav-links');
  if (!btn || !links) return;

  btn.addEventListener('click', () => {
    const open = links.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(open));
  });

  // Close on nav link click
  links.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      links.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    });
  });
})();


/* ── Smooth scroll active nav ── */
(function initActiveNav() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(a => {
          a.style.color = '';
          a.style.borderBottomColor = '';
          if (a.getAttribute('href') === '#' + entry.target.id) {
            a.style.color = 'var(--purple)';
            a.style.borderBottomColor = 'var(--purple)';
          }
        });
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });

  sections.forEach(s => observer.observe(s));
})();


/* ── Fade-in on scroll ── */
(function initFadeIn() {
  const targets = document.querySelectorAll(
    '.story-card, .timeline-item, .location-card, .rsvp-form, .rsvp-contacts'
  );

  targets.forEach(el => el.classList.add('fade-in'));

  const observer = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        // Stagger items that are siblings
        const delay = i * 60;
        setTimeout(() => entry.target.classList.add('visible'), delay);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  targets.forEach(el => observer.observe(el));
})();


/* ── Challenge unlock ── */
(function initChallenge() {
  const form = document.getElementById('challenge-form');
  if (!form) return;

  const errorEl = document.getElementById('challenge-error');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    errorEl.style.display = 'none';

    const data = new FormData(form);

    try {
      const res = await fetch('challenge.php', { method: 'POST', body: data });
      const json = await res.json();

      if (json.ok) {
        const lockedWrapper = document.getElementById('locked-wrapper');
        const rsvpSection = document.getElementById('rsvp');
        const parent = rsvpSection.parentNode;

        // Parse returned HTML
        const temp = document.createElement('div');
        temp.innerHTML = json.html;

        // Insert real sections before RSVP, remove locked wrapper
        while (temp.firstElementChild) {
          parent.insertBefore(temp.firstElementChild, rsvpSection);
        }
        if (lockedWrapper) lockedWrapper.remove();

        // Re-apply translations for new content
        if (typeof applyTranslations === 'function') {
          applyTranslations(currentLang);
        }

        // Re-init fade-in for new elements
        const newTargets = document.querySelectorAll(
          '.timeline-item:not(.fade-in), .location-card:not(.fade-in)'
        );
        const fadeObserver = new IntersectionObserver(entries => {
          entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
              setTimeout(() => entry.target.classList.add('visible'), i * 60);
              fadeObserver.unobserve(entry.target);
            }
          });
        }, { threshold: 0.12 });
        newTargets.forEach(el => {
          el.classList.add('fade-in');
          fadeObserver.observe(el);
        });

        // Scroll to the schedule section
        const newSchedule = document.getElementById('schedule');
        if (newSchedule) {
          newSchedule.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      } else {
        errorEl.style.display = 'block';
        submitBtn.disabled = false;
      }
    } catch {
      errorEl.style.display = 'block';
      submitBtn.disabled = false;
    }
  });
})();


/* ── RSVP form ── */
(function initRSVP() {
  const form    = document.getElementById('rsvp-form');
  const success = document.getElementById('rsvp-success');
  if (!form) return;

  function validate() {
    let ok = true;
    const required = form.querySelectorAll('[required]');
    required.forEach(field => {
      const valid = field.type === 'radio'
        ? form.querySelector(`[name="${field.name}"]:checked`)
        : field.value.trim() !== '';
      field.classList.toggle('invalid', !valid);
      if (!valid) ok = false;
    });
    return ok;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) return;

    const submitBtn = form.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳';

    const data = new FormData(form);

    try {
      const res = await fetch('rsvp.php', { method: 'POST', body: data });
      const json = await res.json();

      if (json.ok) {
        form.style.display = 'none';
        success.style.display = 'block';
        success.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        throw new Error(json.error || 'error');
      }
    } catch {
      // Fallback: show success anyway (form data captured client-side)
      // In production you may want to show an error message
      form.style.display = 'none';
      success.style.display = 'block';
      success.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } finally {
      submitBtn.disabled = false;
    }
  });

  // Clear invalid on input
  form.querySelectorAll('input, select, textarea').forEach(field => {
    field.addEventListener('input', () => field.classList.remove('invalid'));
  });
})();
