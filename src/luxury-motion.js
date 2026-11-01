import {VeloraSplit} from './lite-libs.js';
/* Reinterprets NOIR #blend and #unbox: one deterministic GSAP timeline per
 * scene; ALL movement depends on scroll progress, including particle orbits.
 * No time-based ingredient loop, random layout, video seek, or extra pin spacer.
 * Packaging uses photographic 2.5D layers, not a claimed real 3D object. */
const {gsap,ScrollTrigger}=window;
const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const media=gsap.matchMedia();
gsap.registerPlugin(ScrollTrigger);
media.add('(prefers-reduced-motion: no-preference)',()=>{
 const stage=$('.fusion-stage'), canvas=$('#fusion-particles'),ctx=canvas.getContext('2d');
 const notes=$$('.fusion-ingredient'), model={entry:0,orbit:0,burst:0};
 let width=1,height=1,dpr=1;
 const particles=Array.from({length:matchMedia('(max-width:820px)').matches?52:92},(_,i)=>({angle:i*2.39996,r:.16+((i*37)%100)/220,s:.6+(i%6)*.2,size:.55+(i%4)*.35}));
 function render(){
   const mobile=width<821,rx=Math.min(width*(mobile?.30:.28),410),ry=Math.min(height*.16,140);
   const p=model.orbit,shrink=Math.pow(1-p,1.35);
   notes.forEach((el,i)=>{const a=[-2.55,-.6,2.55,.6][i]+p*3.5;const x=Math.cos(a)*rx*shrink;const y=Math.sin(a)*ry*shrink+Math.sin(p*7+i)*9*(1-p);gsap.set(el,{xPercent:-50,yPercent:-50,x,y,scale:(.72+.28*model.entry)*(1-p*.88),autoAlpha:model.entry*Math.min(1,(1-p)*7)});gsap.set(el.querySelector('.ingredient-photo'),{rotation:p*260+(i%2?1:-1)*Math.sin(p*5)*12});gsap.set(el.querySelector('figcaption'),{opacity:Math.max(0,1-p*3.2)})});
   ctx.clearRect(0,0,width,height);ctx.globalCompositeOperation='lighter';
   particles.forEach(q=>{const a=q.angle+p*6*q.s+model.burst*1.8,r=q.r*Math.min(width,height)*(Math.pow(1-p,1.3)+model.burst*1.2);ctx.globalAlpha=Math.max(0,Math.min(1,p*2)*(1-model.burst)*.75);ctx.fillStyle='#e9c887';ctx.beginPath();ctx.arc(width/2+Math.cos(a)*r*(mobile?.8:1.55),height*.55+Math.sin(a)*r*.7,q.size,0,Math.PI*2);ctx.fill()});ctx.globalAlpha=1;
 }
 function resize(){width=stage.clientWidth;height=stage.clientHeight;dpr=Math.min(devicePixelRatio||1,1.5);canvas.width=Math.round(width*dpr);canvas.height=Math.round(height*dpr);ctx.setTransform(dpr,0,0,dpr,0,0);render()}
 resize();const observer=new ResizeObserver(resize);observer.observe(stage);
 const fusion=gsap.timeline({defaults:{ease:'none'},onUpdate:render,scrollTrigger:{id:'velora-fusion',trigger:'#showcase',start:'top top',end:'bottom bottom',scrub:.45,invalidateOnRefresh:true,onUpdate:self=>{$('.fusion-phase').textContent=self.progress<.34?'01 — THE RAW ELEMENTS':self.progress<.69?'02 — THE CONVERGENCE':'03 — PURE PRESENCE';$('.fusion-scroll').textContent=self.progress>.93?'THE SIGNATURE, COMPOSED':'SCROLL TO COMPOSE ↓'}}});
 fusion.to(model,{entry:1,duration:.65},0)
 .to(model,{orbit:.25,duration:1.15,ease:'sine.inOut'},.3)
 .to(model,{orbit:1,duration:1.65,ease:'power2.in'},1.45)
 .to('.fusion-heading',{autoAlpha:0,y:-35,duration:.5},1.6)
 .to('.fusion-light',{opacity:1,scale:.65,duration:1.9},.8)
 .fromTo('.fusion-core',{scale:.05,opacity:0},{scale:1.8,opacity:.85,duration:.75,ease:'power2.in'},2.35)
 .to('.fusion-flash',{opacity:.8,duration:.18},3.02)
 .to('.fusion-flash',{opacity:0,duration:.65},3.2)
 .to('.fusion-core',{opacity:0,duration:.5},3.14)
 .fromTo('.fusion-pulse',{scale:.1,opacity:.75},{scale:7,opacity:0,duration:1.05,ease:'power2.out'},3.1)
 .to(model,{burst:1,duration:1.05,ease:'power2.out'},3.1)
 .fromTo('.fusion-product',{autoAlpha:0,y:45,scale:.8},{autoAlpha:1,y:0,scale:1,duration:.95,ease:'power3.out'},3.12)
 .fromTo('.fusion-fog',{opacity:0,xPercent:-6},{opacity:.5,xPercent:4,duration:1.5},3.1)
 .fromTo('.fusion-finish',{autoAlpha:0,y:25},{autoAlpha:1,y:0,duration:.65},3.75)
 .to('.fusion-light',{scale:1.15,opacity:.7,duration:1.1},3.4)
 .to({}, {duration:.35},4.5);
 fusion.fromTo('.fusion-progress i',{scaleX:0},{scaleX:1,duration:fusion.duration()},0);
 const unbox=gsap.timeline({defaults:{ease:'none'},scrollTrigger:{id:'velora-unbox',trigger:'#unbox',start:'top top',end:'bottom bottom',scrub:.55,invalidateOnRefresh:true}});
 // A restrained lift breaks the seal; lid continues away while the fitted bottle rises.
 unbox.to('.unbox-lid',{y:-15,rotationZ:-1,duration:.45,ease:'sine.inOut'},.18)
 .to('.unbox-lid',{yPercent:()=>innerWidth<821?-16:-43,xPercent:()=>innerWidth<821?60:20,rotationX:16,rotationZ:-8,scale:.96,duration:1.0,ease:'power2.inOut'},.63)
 .to('.unbox-lid',{yPercent:()=>innerWidth<821?-28:-65,xPercent:()=>innerWidth<821?100:36,autoAlpha:0,duration:.55},1.5)
 .to('.unbox-innerlight',{opacity:1,duration:.7},.85)
 .to('.unbox-aura',{opacity:1,duration:1.1},.9)
 .to('.unbox-bottle',{yPercent:-18,scale:1.04,duration:1.25,ease:'power2.out'},1.1)
 .to('.unbox-base',{yPercent:23,opacity:0,scale:.96,duration:.8},2.0)
 .to('.unbox-innerlight',{opacity:0,duration:.8},2.05)
 .to('.unbox-bottle',{yPercent:-30,scale:1.09,duration:.85,ease:'power2.out'},2.1)
 .to('.unbox-shadow',{scaleX:.7,opacity:.5,duration:.85},2.1)
 .to({}, {duration:.4},2.95);
 $$('.unbox-motes i').forEach((el,i)=>{unbox.fromTo(el,{x:0,y:0,opacity:0},{x:Math.cos(i*2.399)*(70+(i%3)*25),y:-70-(i*19)%180,opacity:.7,duration:.8},1.05+(i%5)*.055).to(el,{opacity:0,duration:.6},2.0+(i%4)*.05)});
 const splits=$$('[data-editorial]').map(el=>{const split=new VeloraSplit(el);gsap.fromTo(split.words,{yPercent:105},{yPercent:0,duration:.9,stagger:.065,ease:'power3.out',scrollTrigger:{trigger:el,start:'top 89%',toggleActions:'play none none reverse'}});return split});
 gsap.fromTo('.best-heading p',{y:30},{y:-8,ease:'none',scrollTrigger:{trigger:'#best-sellers',start:'top bottom',end:'top 25%',scrub:.7}});
 return ()=>{observer.disconnect();splits.forEach(s=>s.revert());ctx.clearRect(0,0,width,height)};
});
addEventListener('load',()=>ScrollTrigger.refresh(),{once:true});
document.fonts.ready.then(()=>ScrollTrigger.refresh());
