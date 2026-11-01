/**
 * VELORA cinematic sections 04–08 (final refinement).
 * GSAP + ScrollTrigger drive every scroll-linked motion, all scrubbed or
 * toggle-reversible so forward and backward scrolling reach identical states.
 * Videos are photographic layers; nothing here claims to be real 3D.
 */
import { VeloraSplit } from './lite-libs.js';

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const sections = $$('.cinematic-section, .premium-cta');
const videos = $$('.cinematic-video');
const reduced = matchMedia('(prefers-reduced-motion: reduce)');
const triggers = [];
let splits = [];
let disposed = false;
const track = t => { if (t) triggers.push(t); return t; };
const REVERSE = 'play none none reverse';

/* Small deterministic RNG so every load produces the identical composition. */
function rng(seed) { let s = seed >>> 0; return () => (s = (s * 1664525 + 1013904223) >>> 0) / 4294967296; }

/* ---------- generated decorative elements (cheap DOM, compositor-only motion) ---------- */
function buildSparks() {
  const host = $('.signature-sparks'); if (!host) return;
  const n = Math.min(+host.dataset.sparks || 24, 32), r = rng(7731);
  for (let i = 0; i < n; i++) {
    const el = document.createElement('i');
    el.style.cssText = `--x:${(r() * 94 + 3).toFixed(1)}%;--b:${(r() * 22).toFixed(1)}%;--s:${(2 + r() * 5).toFixed(1)}px;--t:${(7 + r() * 8).toFixed(1)}s;--d:${(-r() * 12).toFixed(1)}s;--dx:${((r() - .5) * 90).toFixed(0)}px`;
    host.append(el);
  }
}
function buildFragments() {
  const host = $('.story-gold-fragments'); if (!host) return;
  const r = rng(4410);
  for (let i = host.children.length; i < 15; i++) {
    const el = document.createElement('i');
    const w = 9 + r() * 20;
    el.style.cssText = `left:${(r() * 92 + 3).toFixed(1)}%;top:${(r() * 86 + 4).toFixed(1)}%;width:${w.toFixed(0)}px;height:${(w * (1.2 + r() * .5)).toFixed(0)}px;opacity:${(.35 + r() * .5).toFixed(2)}`;
    host.append(el);
  }
}
buildSparks(); buildFragments();

/* ---------- section liveness + video playback (pause anything off-screen) ---------- */
function liveness() {
  const io = new IntersectionObserver(entries => entries.forEach(e => e.target.classList.toggle('is-live', e.isIntersecting)), { rootMargin: '10% 0px' });
  sections.forEach(s => io.observe(s));
  const vio = new IntersectionObserver(entries => entries.forEach(({ target: v, isIntersecting }) => {
    if (!isIntersecting || reduced.matches || document.hidden) v.pause();
    else v.play().catch(() => { v.dataset.playback = 'blocked'; });
  }), { threshold: .08, rootMargin: '120px 0px' });
  videos.forEach(v => { v.muted = true; v.loop = true; v.playsInline = true; v.preload = v.classList.contains('story-liquid-film') || v.classList.contains('liquid-film') ? 'auto' : 'metadata'; v.setAttribute('aria-hidden', 'true'); vio.observe(v); });
  const vis = () => videos.forEach(v => { if (document.hidden) v.pause(); });
  document.addEventListener('visibilitychange', vis);
  addEventListener('pagehide', () => { io.disconnect(); vio.disconnect(); videos.forEach(v => { v.pause(); }); }, { once: true });
}

function animate() {
  const { gsap, ScrollTrigger } = window;
  if (!gsap || !ScrollTrigger || reduced.matches) {
    // Static, fully visible fallback.
    $$('.ribbon-strand,.ribbon-glint').forEach(p => { p.style.display = 'none'; });
    return;
  }
  gsap.registerPlugin(ScrollTrigger);

  /* Copy reveals: reversible, never leave content hidden when scrolling back. */
  sections.forEach(section => {
    $$('[data-cinema-reveal]', section).forEach((item, index) => {
      const tween = gsap.fromTo(item, { autoAlpha: 0, y: 34 }, { autoAlpha: 1, y: 0, duration: .85, ease: 'power2.out', delay: index * .08,
        scrollTrigger: { trigger: item, start: 'top 86%', toggleActions: REVERSE } });
      track(tween.scrollTrigger);
    });
  });

  /* Title word reveals (local splitter, <em> preserved). */
  $$('.notes-heading, .premium-cta .cinema-title').forEach(title => {
    const split = new VeloraSplit(title);
    splits.push(split);
    track(gsap.fromTo(split.words, { yPercent: 112 }, { yPercent: 0, duration: .95, stagger: .1, ease: 'power3.out',
      scrollTrigger: { trigger: title, start: 'top 88%', toggleActions: REVERSE } }).scrollTrigger);
    title.removeAttribute('data-cinema-reveal');
  });
  document.documentElement.dataset.split = 'local';

  /* 04 SHOWCASE — scrubbed camera: dolly-in + subtle roll, floor fog and glow follow. */
  const film = $('.showcase-film');
  if (film) {
    gsap.set(film, { x: 0, xPercent: -63 });
    track(gsap.fromTo(film, { scale: 1.0, yPercent: -2, rotation: -.7 }, { scale: 1.17, yPercent: 3, rotation: .7, ease: 'none',
      scrollTrigger: { trigger: '#showcase', start: 'top bottom', end: 'bottom top', scrub: .8, invalidateOnRefresh: true } }).scrollTrigger);
    track(gsap.fromTo('.pedestal-glow', { scaleX: .9, opacity: .6 }, { scaleX: 1.12, opacity: 1, ease: 'none',
      scrollTrigger: { trigger: '#showcase', start: 'top 70%', end: 'bottom 40%', scrub: .8 } }).scrollTrigger);
    track(gsap.fromTo('.showcase-left', { y: 30 }, { y: -30, ease: 'none', scrollTrigger: { trigger: '#showcase', start: 'top bottom', end: 'bottom top', scrub: 1 } }).scrollTrigger);
    track(gsap.fromTo('.showcase-right', { y: 60 }, { y: -50, ease: 'none', scrollTrigger: { trigger: '#showcase', start: 'top bottom', end: 'bottom top', scrub: 1.2 } }).scrollTrigger);
  }

  /* 05 STORY — one pinned scene, two compositions, fully scrub-driven (reversible). */
  const story = $('#brand-story');
  const sFilm = $('.story-film'), lFilm = $('.story-liquid-film');
  if (story && sFilm && lFilm) {
    story.classList.add('is-pinned');
    gsap.set(lFilm, { opacity: 0, xPercent: -4 });
    gsap.set('.story-stage-b .story-second', { autoAlpha: 0, y: 54 });
    gsap.set('.story-b-fragments', { opacity: 0 });
    // Continuous "floating" uses yPercent/rotation; the scrub below uses scale/opacity/xPercent only.
    gsap.to(sFilm, { yPercent: 1.6, rotation: .9, duration: 4.4, ease: 'sine.inOut', yoyo: true, repeat: -1 });
    const tl = gsap.timeline({ defaults: { ease: 'none' }, scrollTrigger: {
      trigger: story, start: 'top top', end: '+=135%', pin: true, scrub: .7, anticipatePin: 1, invalidateOnRefresh: true } });
    tl.fromTo(sFilm, { scale: 1 }, { scale: 1.07, duration: .38 }, 0)
      .to('.story-stage-a .story-copy', { autoAlpha: 0, y: -50, duration: .26 }, .36)
      .to(sFilm, { opacity: 0, xPercent: 7, duration: .3 }, .38)
      .to('.story-orbit,.story-dust,.story-gold-fragments', { opacity: 0, duration: .28 }, .4)
      .to(lFilm, { opacity: 1, xPercent: 0, duration: .32 }, .5)
      .to('.story-b-fragments', { opacity: 1, duration: .25 }, .56)
      .to('.story-stage-b .story-second', { autoAlpha: 1, y: 0, duration: .26 }, .62)
      .to(lFilm, { scale: 1.06, duration: .38, transformOrigin: '0% 30%' }, .62);
    track(tl.scrollTrigger);
    // Decode only the video that is actually visible in the current stage.
    const stageVideo = () => {
      const pr = tl.scrollTrigger.progress;
      const wantA = pr < .55, wantB = pr > .42;
      const set = (v, on) => { if (!v || reduced.matches) return; if (on && v.paused) v.play().catch(() => {}); if (!on && !v.paused) v.pause(); };
      set(sFilm, wantA); set(lFilm, wantB);
    };
    tl.scrollTrigger.vars.onUpdate = stageVideo;
    tl.eventCallback('onUpdate', stageVideo);
    // Recheck the active stage once decoding finishes and when returning to this tab.
    lFilm.addEventListener('canplay', stageVideo);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) stageVideo(); });
    stageVideo();
    // Gold fragments drift forever (cheap transforms) + react to scroll direction.
    const frag = [];
    $$('.story-gold-fragments i, .story-b-fragments i').forEach((el, i) => {
      frag.push(gsap.to(el, { y: (i % 2 ? -1 : 1) * (14 + (i * 7) % 22), x: ((i * 13) % 18) - 9, rotation: `+=${(i % 2 ? 1 : -1) * (18 + (i * 11) % 40)}`,
        duration: 3 + (i % 5) * .7, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: -(i * .37) }));
    });
    const fio = new IntersectionObserver(([e]) => frag.forEach(t => e.isIntersecting ? t.resume() : t.pause()));
    fio.observe(story);
    $$('.story-orbit').forEach((orbit, i) => {
      track(gsap.to(orbit, { rotation: i ? -10 : 24, ease: 'none', scrollTrigger: { trigger: story, start: 'top bottom', end: '+=235%', scrub: .75 } }).scrollTrigger);
    });
  }

  /* 07 SIGNATURE — layered ribbon: rear (soft, dim) -> bottle -> front (crisp) + strands + glint. */
  $$('.ribbon').forEach((svg, group) => {
    const paths = $$('.ribbon-path, .ribbon-strand, .ribbon-glint', svg);
    paths.forEach((path, index) => {
      const len = path.getTotalLength();
      const glint = path.classList.contains('ribbon-glint');
      path.style.strokeDasharray = glint ? `${len * .09} ${len * 2}` : `${len} ${len}`;
      path.style.strokeDashoffset = String(glint ? len * .09 : len);
      track(gsap.to(path, { strokeDashoffset: glint ? -len * 1.05 : 0, ease: 'none', scrollTrigger: {
        trigger: '#signature', start: group ? 'top 88%' : 'top 94%', end: glint ? 'bottom 25%' : 'center 44%', scrub: .5 + index * .08 } }).scrollTrigger);
    });
  });
  const sigBottle = $('.signature-bottle');
  if (sigBottle) {
    gsap.set(sigBottle, { xPercent: -50, yPercent: -50, x: 0, y: 0 });
    track(gsap.fromTo(sigBottle, { y: 26, scale: 1 }, { y: -22, scale: 1.05, ease: 'none',
      scrollTrigger: { trigger: '#signature', start: 'top bottom', end: 'bottom top', scrub: .6, invalidateOnRefresh: true } }).scrollTrigger);
  }

  /* 08 CTA + footer — staged reveal, wordmark rises into place. */
  const cta = $('#premium-cta');
  if (cta) {
    const parts = [$('.eyebrow', cta), $('p', cta), $('.cinema-button', cta)].filter(Boolean);
    gsap.set(parts, { autoAlpha: 0, y: 30 });
    parts.forEach((el, i) => track(gsap.to(el, { autoAlpha: 1, y: 0, duration: .8, ease: 'power2.out', delay: .15 + i * .18,
      scrollTrigger: { trigger: cta, start: 'top 72%', toggleActions: REVERSE } }).scrollTrigger));
    track(gsap.fromTo('.cta-rays', { opacity: 0 }, { opacity: 1, ease: 'none', scrollTrigger: { trigger: cta, start: 'top 90%', end: 'top 35%', scrub: .6 } }).scrollTrigger);
    const cols = $$('.footer-brand, .footer-links > div, .footer-bottom');
    gsap.set(cols, { autoAlpha: 0, y: 24 });
    cols.forEach((el, i) => track(gsap.to(el, { autoAlpha: 1, y: 0, duration: .75, ease: 'power2.out', delay: i * .08,
      scrollTrigger: { trigger: '.cinematic-footer', start: 'top 88%', toggleActions: REVERSE } }).scrollTrigger));
    track(gsap.fromTo('.footer-wordmark', { yPercent: 28, opacity: 0 }, { yPercent: 0, opacity: 1, ease: 'none',
      scrollTrigger: { trigger: '.cinematic-footer', start: 'top bottom', end: 'bottom bottom', scrub: .6 } }).scrollTrigger);
  }

  const refresh = () => { if (!disposed) ScrollTrigger.refresh(); };
  addEventListener('load', refresh, { once: true });
  document.fonts?.ready.then(refresh);
  videos.forEach(v => v.addEventListener('loadedmetadata', () => ScrollTrigger.update(), { once: true }));
  ScrollTrigger.refresh();
}

$('#showcase-detail')?.addEventListener('click', () => { const d = $('#collection-info'); if (d && !d.open) d.showModal(); });

liveness();
animate();
addEventListener('pagehide', () => {
  disposed = true;
  triggers.forEach(t => t?.kill());
  splits.forEach(s => s.revert()); splits = [];
}, { once: true });
window.__veloraCinematic = {
  sections: sections.map(s => s.id),
  get playing() { return videos.filter(v => !v.paused).length; },
  get splitType() { return !!splits.length; },
  get triggers() { return triggers.length; }
};
