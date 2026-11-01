/**
 * VELORA local animation helpers.
 * Swiper / Lenis / SplitType could NOT be downloaded while building this package
 * (registry and CDN access were blocked), so these are small first-party
 * implementations with the same purpose. They are NOT the upstream libraries.
 *   VeloraSplit  – word splitter that preserves <em> markup (SplitType role)
 *   VeloraSmooth – wheel-smoothing that drives native scroll (Lenis role)
 * No network access is required for either.
 */
export class VeloraSplit {
  constructor(el) {
    this.el = el;
    this.original = el.innerHTML;
    this.words = [];
    const walk = node => {
      [...node.childNodes].forEach(child => {
        if (child.nodeType === 3) {
          const parts = child.textContent.split(/(\s+)/);
          const frag = document.createDocumentFragment();
          parts.forEach(part => {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.append(document.createTextNode(' ')); return; }
            const outer = document.createElement('span');
            outer.className = 'vw';
            const inner = document.createElement('span');
            inner.className = 'vwi';
            inner.textContent = part;
            outer.append(inner);
            frag.append(outer);
            this.words.push(inner);
          });
          child.replaceWith(frag);
        } else if (child.nodeType === 1 && child.tagName !== 'BR') walk(child);
      });
    };
    walk(el);
    el.dataset.split = 'words';
  }
  revert() { this.el.innerHTML = this.original; delete this.el.dataset.split; this.words = []; }
}

export class VeloraSmooth {
  constructor({ ease = .11, onScroll } = {}) {
    this.ease = ease; this.onScroll = onScroll;
    this.target = scrollY; this.current = scrollY; this.raf = 0; this.internal = false;
    this.enabled = true;
    this.max = () => document.documentElement.scrollHeight - innerHeight;
    this.onWheel = e => {
      if (!this.enabled || e.ctrlKey || e.defaultPrevented || e.deltaX && Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
      if (document.querySelector('dialog[open]')) return;
      // Let genuinely scrollable inner elements keep their own wheel behavior.
      for (let n = e.target; n && n !== document.body; n = n.parentElement) {
        const s = n instanceof Element ? getComputedStyle(n) : null;
        if (s && /(auto|scroll)/.test(s.overflowY) && n.scrollHeight > n.clientHeight + 2) return;
      }
      e.preventDefault();
      const unit = e.deltaMode === 1 ? 32 : e.deltaMode === 2 ? innerHeight : 1;
      this.target = Math.max(0, Math.min(this.max(), this.target + e.deltaY * unit));
      if (!this.raf) { this.current = scrollY; this.raf = requestAnimationFrame(this.tick); }
    };
    this.onNative = () => {
      if (this.internal) return;
      // Anchor clicks, keyboard, scrollbar drags: follow the browser, do not fight it.
      this.current = this.target = scrollY;
    };
    this.tick = () => {
      const d = this.target - this.current;
      if (Math.abs(d) < .35) { this.current = this.target; this.apply(); this.raf = 0; return; }
      this.current += d * this.ease; this.apply();
      this.raf = requestAnimationFrame(this.tick);
    };
    addEventListener('wheel', this.onWheel, { passive: false });
    addEventListener('scroll', this.onNative, { passive: true });
  }
  apply() { this.internal = true; scrollTo(0, this.current); this.onScroll?.(); requestAnimationFrame(() => { this.internal = false; }); }
  destroy() { removeEventListener('wheel', this.onWheel); removeEventListener('scroll', this.onNative); cancelAnimationFrame(this.raf); }
}
window.VeloraSplit = VeloraSplit; window.VeloraSmooth = VeloraSmooth;
