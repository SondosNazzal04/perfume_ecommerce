import './commerce.js';
import { VeloraSplit, VeloraSmooth } from './lite-libs.js';
/** VELORA reference-matched three-card collection.
 * Local drag/cyclic engine (Swiper could not be bundled offline-safe in this build).
 * Duplicate cards only provide neighboring visuals at the ends; originals remain the accessible products.
 */
const PRODUCTS = [
  { id: 'noir', label: 'VELORA NOIR', heading: 'Velora <em>Noir</em>', image: 'assets/products/noir.webp', description: 'A dark, sophisticated signature with smoky warmth and a quietly magnetic finish.' },
  { id: 'amber', label: 'VELORA AMBER', heading: 'Velora <em>Amber</em>', image: 'assets/products/amber.webp', description: 'A luminous amber signature with warmth, depth and an elegant golden character.' },
  { id: 'oud', label: 'VELORA OUD ROYALE', heading: 'Oud <em>Royale</em>', image: 'assets/products/oud-royale.webp', description: 'Deep resinous woods and rich amber-inspired warmth in an elegant composition.' }
];

const $ = selector => document.querySelector(selector);
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
const root = $('#perfume-swiper');
const wrapper = root.querySelector('.swiper-wrapper');
const originals = [...root.querySelectorAll('.perfume-card')];
const prev = $('.collection-prev');
const next = $('.collection-next');
const pager = $('.collection-pagination');
const controller = new AbortController();
const eventOptions = { signal: controller.signal };
const wrapIndex = index => (index % PRODUCTS.length + PRODUCTS.length) % PRODUCTS.length;
let active = 1;
let splits = null;
let disposed = false;

// Three originals have no neighboring slides at either end. Build cloned visual
// neighbors on both sides so NOIR and OUD still have a full, balanced layout.
function makeNeighbors() {
  function duplicate(slide) {
    const copy = slide.cloneNode(true);
    copy.classList.add('is-copy');
    copy.setAttribute('aria-hidden', 'true');
    copy.querySelectorAll('a,button,[tabindex]').forEach(el => el.tabIndex = -1);
    return copy;
  }
  const before = originals.map(duplicate);
  const after = originals.map(duplicate);
  wrapper.prepend(...before);
  wrapper.append(...after);
}
makeNeighbors();

function updateDetails(index) {
  const item = PRODUCTS[index];
  $('#active-fragrance').textContent = item.label;
  const image = $('#selected-product-image');
  image.src = item.image;
  image.alt = `${item.label} perfume bottle`;
  $('#selected-product-heading').innerHTML = item.heading; // Static authored product names, never user HTML.
  $('#selected-product-description').textContent = item.description;
}
function updateVisual(index, currentSlide = null) {
  active = wrapIndex(index);
  const target = currentSlide || originals[active];
  [...wrapper.children].forEach(slide => {
    const selected = slide === target;
    slide.classList.toggle('is-active', selected);
    {
      slide.setAttribute('aria-hidden', selected ? 'false' : 'true');
      slide.querySelectorAll('a,button,[tabindex]').forEach(el => { el.tabIndex = selected ? 0 : -1; });
    }
    if (!slide.classList.contains('is-copy')) {
      slide.setAttribute('aria-label', `${PRODUCTS[wrapIndex(originals.indexOf(slide))].label}${selected ? ' — selected' : ''}`);
    }
  });
  pager.querySelectorAll('button').forEach((button, i) => {
    button.setAttribute('aria-current', i === active ? 'true' : 'false');
    button.setAttribute('aria-label', `${PRODUCTS[i].label}${i === active ? ', selected' : ''}`);
  });
  updateDetails(active);
  prev.disabled = false;
  next.disabled = false;
}

const buttons = PRODUCTS.map((product, index) => {
  const button = document.createElement('button');
  button.type = 'button';
  button.setAttribute('aria-label', `Select ${product.label}`);
  button.addEventListener('click', () => select(index), eventOptions);
  pager.append(button);
  return button;
});

/* ---------- Local drag carousel engine (replaces Swiper/CDN) ----------
 * 9 slides = 3 identical sets. `pos` indexes the 9 slides; after each move that
 * ends outside the middle set we silently re-centre on the identical twin.
 * Pointer Events cover mouse, touch and pen; native image drag is disabled,
 * which was what cancelled mouse drags before. */
const slides = [...wrapper.children];
const SET = PRODUCTS.length;
let pos = 1 + SET;
let drag = null;
let suppressClick = false;
let settleTimer = 0;
const gap = () => parseFloat(getComputedStyle(wrapper).columnGap) || 20;
function offsetFor(i) {
  const s = slides[i];
  return root.clientWidth / 2 - (s.offsetLeft + s.offsetWidth / 2);
}
function setOffset(px, animate) {
  wrapper.style.transition = animate && !reducedMotion.matches ? 'transform .72s cubic-bezier(.22,.75,.24,1)' : 'none';
  wrapper.style.transform = `translate3d(${px}px,0,0)`;
}
function recenter() {
  if (pos >= SET && pos < SET * 2) return;
  pos = SET + wrapIndex(pos);
  root.classList.add('is-recentering');
  setOffset(offsetFor(pos), false);
  updateVisual(wrapIndex(pos), slides[pos]);
  requestAnimationFrame(() => requestAnimationFrame(() => root.classList.remove('is-recentering')));
}
function goTo(i, animate = true) {
  if (disposed) return;
  pos = Math.max(0, Math.min(slides.length - 1, i));
  updateVisual(wrapIndex(pos), slides[pos]);
  setOffset(offsetFor(pos), animate);
  clearTimeout(settleTimer);
  settleTimer = setTimeout(recenter, animate && !reducedMotion.matches ? 760 : 0);
}
function initDrag() {
  wrapper.style.columnGap = '20px';
  slides.forEach(slide => slide.querySelectorAll('img').forEach(img => { img.draggable = false; }));
  root.addEventListener('dragstart', e => e.preventDefault(), eventOptions);
  root.addEventListener('pointerdown', e => {
    if ((e.pointerType === 'mouse' && e.button !== 0) || e.target.closest('a,button')) return;
    clearTimeout(settleTimer); recenter();
    const m = new DOMMatrixReadOnly(getComputedStyle(wrapper).transform);
    drag = { id: e.pointerId, x: e.clientX, y: e.clientY, base: m.m41, moved: false, t: performance.now(), lastX: e.clientX, lastT: performance.now(), v: 0, locked: false };
  }, eventOptions);
  root.addEventListener('pointermove', e => {
    if (!drag || e.pointerId !== drag.id) return;
    const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
    if (!drag.moved) {
      if (Math.abs(dx) < 7 && Math.abs(dy) < 7) return;
      if (Math.abs(dy) > Math.abs(dx) * 1.3 && e.pointerType !== 'mouse') { drag = null; return; }
      drag.moved = true;
      root.setPointerCapture?.(e.pointerId);
      root.classList.add('is-dragging');
    }
    const now = performance.now();
    drag.v = (e.clientX - drag.lastX) / Math.max(1, now - drag.lastT);
    drag.lastX = e.clientX; drag.lastT = now;
    wrapper.style.transition = 'none';
    wrapper.style.transform = `translate3d(${drag.base + dx}px,0,0)`;
    e.preventDefault();
  }, eventOptions);
  const finish = e => {
    if (!drag || e.pointerId !== drag.id) return;
    const d = drag; drag = null;
    root.classList.remove('is-dragging');
    if (!d.moved) return;
    suppressClick = true; setTimeout(() => { suppressClick = false; }, 60);
    const dx = e.clientX - d.x;
    const step = slides[pos].offsetWidth + gap();
    let n = Math.round(-dx / step);
    if (Math.abs(d.v) > .45 && n === 0) n = d.v < 0 ? 1 : -1;      // quick flick
    if (e.type === 'pointercancel') n = 0;
    goTo(pos + Math.max(-2, Math.min(2, n)));
  };
  root.addEventListener('pointerup', finish, eventOptions);
  root.addEventListener('pointercancel', finish, eventOptions);
  // A drag must never trigger the card links / slide-click behind it.
  root.addEventListener('click', e => { if (suppressClick) { e.preventDefault(); e.stopImmediatePropagation(); } }, { ...eventOptions, capture: true });
  wrapper.addEventListener('click', event => {
    if (event.target.closest('a,button')) return;
    const slide = event.target.closest('.perfume-card');
    if (slide) goTo(slides.indexOf(slide));
  }, eventOptions);
  root.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      goTo(pos + (event.key === 'ArrowRight' ? 1 : -1));
    }
  }, eventOptions);
  root.tabIndex = 0;
  new ResizeObserver(() => { setOffset(offsetFor(pos), false); }).observe(root);
  addEventListener('resize', () => setOffset(offsetFor(pos), false), eventOptions);
  requestAnimationFrame(() => { setOffset(offsetFor(pos), false); updateVisual(1, slides[pos]); });
}

function select(index) {
  if (disposed) return;
  const chosen = wrapIndex(index);
  const target = slides.map((s, i) => ({ i, id: s.dataset.id }))
    .filter(item => item.id === PRODUCTS[chosen].id)
    .sort((a, b) => Math.abs(a.i - pos) - Math.abs(b.i - pos))[0];
  if (target) goTo(target.i);
}

/* Smooth scroll helper (local Lenis-style) + local word splitter. */
function initSmooth() {
  if (reducedMotion.matches || matchMedia('(pointer:coarse)').matches || /[?&]smooth=0/.test(location.search)) return;
  const smooth = new VeloraSmooth({ onScroll: () => window.ScrollTrigger?.update() });
  window.__veloraSmooth = smooth;
}
function initSplit() {
  if (reducedMotion.matches || !window.gsap) return;
  const heading = $('.collection-title');
  try {
    splits = new VeloraSplit(heading);
    window.gsap.fromTo(splits.words, { yPercent: 110 }, {
      yPercent: 0, duration: .9, stagger: .09, ease: 'power3.out',
      scrollTrigger: { trigger: heading, start: 'top 88%', toggleActions: 'play none none reverse' }
    });
    document.documentElement.dataset.splitType = 'local';
  } catch (error) { console.warn('Title splitting failed:', error); }
}

function initSearch() {
  const dialog = $('#fragrance-search');
  const input = $('#fragrance-query');
  const results = $('#fragrance-results');
  $('#open-search').addEventListener('click', () => {
    dialog.showModal();
    input.value = '';
    renderResults('');
    input.focus();
  }, eventOptions);
  function renderResults(query) {
    results.replaceChildren();
    const filtered = PRODUCTS.filter(product => product.label.toLowerCase().includes(query.trim().toLowerCase()));
    if (!filtered.length) {
      const empty = document.createElement('p');
      empty.textContent = 'No fragrances found in this collection.';
      results.append(empty);
      return;
    }
    filtered.forEach(product => {
      const button = document.createElement('button');
      button.type = 'button';
      const label = document.createElement('span');
      label.textContent = product.label;
      const icon = document.createElement('span');
      icon.textContent = '↗';
      icon.setAttribute('aria-hidden', 'true');
      button.append(label, icon);
      button.addEventListener('click', () => {
        select(PRODUCTS.indexOf(product));
        dialog.close();
        $('#collection').scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth' });
      }, eventOptions);
      results.append(button);
    });
  }
  input.addEventListener('input', () => renderResults(input.value), eventOptions);
  dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); }, eventOptions);
  $('#view-selection').addEventListener('click', () => {$('#collection-info').close();$('#collection').scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth' });}, eventOptions);
  $('#detail-close').addEventListener('click', () => $('#collection-info').close(), eventOptions);
  // Event delegation keeps "Explore fragrance" working for cloned loop slides.
  root.addEventListener('click', event => {
    const link = event.target.closest('a[data-product]');
    if (!link) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const index = PRODUCTS.findIndex(product => product.id === link.dataset.product);
    if (index >= 0) select(index);
    if (!$('#collection-info').open) $('#collection-info').showModal();
  }, eventOptions);
}

function initReveals() {
  if (reducedMotion.matches || !window.gsap || !window.ScrollTrigger) return;
  document.querySelectorAll('[data-reveal]').forEach(element => {
    window.gsap.fromTo(element, { y: 20, opacity: .5 }, {
      y: 0, opacity: 1, duration: .9, ease: 'power2.out',
      scrollTrigger: { trigger: element, start: 'top 90%', once: true }
    });
  });
}

prev.addEventListener('click', () => goTo(pos - 1), eventOptions);
next.addEventListener('click', () => goTo(pos + 1), eventOptions);
initDrag();
updateVisual(1);
initSearch();
initReveals();
initSplit();
initSmooth();
document.documentElement.dataset.carousel = 'velora-drag';

window.addEventListener('pagehide', () => {
  disposed = true;
  controller.abort();
  clearTimeout(settleTimer);
  window.__veloraSmooth?.destroy();
  splits?.revert();
}, { once: true });
window.__veloraCollection = {
  get active() { return PRODUCTS[active].id; },
  get pos() { return pos; },
  select,
  goTo,
  get engine() { return document.documentElement.dataset.carousel; },
  products: PRODUCTS.map(product => product.label)
};
