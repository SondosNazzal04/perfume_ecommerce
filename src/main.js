import {createSequence} from './sequence.js';
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const clamp=x=>Math.max(0,Math.min(1,x)), seg=(p,a,b)=>clamp((p-a)/(b-a)), smooth=t=>t*t*(3-2*t), mix=(a,b,t)=>a+(b-a)*t;
const track=$('#hero'), stage=$('.stage'), bottle=$('.bottle'), cap=$('.cap');
const reduced=matchMedia('(prefers-reduced-motion: reduce)'), controller=new AbortController();
const state={p:0};
let W,H,bh,bw,timeline,trigger,smokeSequence,fogSequence,manifest={},disposed=false,renderCount=0,mediaPromise,geometry={};
const opacity=(selector,value)=>$(selector).style.opacity=value;
function interactive(selector,on){$(selector).inert=!on;}
function layout(){W=stage.clientWidth;H=stage.clientHeight;bh=H*(W<820?.40:.62);bw=bh*.5664;bottle.style.width=bw+'px';bottle.style.height=bh+'px';smokeSequence?.resize(W,H);fogSequence?.resize(W,H);render()}
function render(){
 if(disposed||!W)return;renderCount++;
 const p=reduced.matches?.66:state.p,m=W<820,a=smooth(seg(p,.02,.29)),exit=smooth(seg(p,.80,1)),lift=smooth(seg(p,.30,.46))*(1-smooth(seg(p,.79,.89)));
 const scale=mix(m?.80:.93,1,a)*(1-.13*exit),bx=mix(W*(m?.72:.71),W*(m?.58:.60),a),by=mix(H*(m?.70:.74),H*(m?.57:.81),a)-exit*H*.08;
 bottle.style.width=bw*scale+'px';bottle.style.height=bh*scale+'px';
 bottle.style.transform=`translate3d(${bx-bw*scale/2}px,${by-bh*scale}px,0)`;
 bottle.style.opacity=1-exit;
 // Lift vertically first; only then move aside so the cap clears the atomizer.
 cap.style.transform=`translate3d(${-bw*scale*.20*smooth(seg(lift,.72,1))}px,${-bh*scale*.23*Math.min(1,lift/.72)}px,0) rotate(${-17*smooth(seg(lift,.72,1))}deg)`;
 bottle.style.setProperty('--r',a*(1-exit));
 const opening=1-smooth(seg(p,.04,.20));
 opacity('.ed',opening);opacity('.col',opening);interactive('.ed',opening>.5);interactive('.col',opening>.5);
 $('.ed').style.transform=`translate3d(${-32*(1-opening)}px,0,0)`;
 $('.col').style.transform=`translate3d(0,${50*(1-opening)}px,0)`;
 opacity('.editorial-plate',(1-a)*.85);opacity('.halo',1-a*.8);
 opacity('.beams',a*(1-exit));opacity('.floor',a*(1-exit));opacity('.haze',a*(1-exit)*.6);
 const fx=smooth(seg(p,.20,.34))*(1-smooth(seg(p,.78,.91)));opacity('.fx',fx);interactive('.fx',fx>.5);$('.fx').style.pointerEvents=fx>.5?'auto':'none';
 $$('.ln>span').forEach((el,i)=>el.style.transform=`translate3d(0,${108*(1-smooth(seg(p,.22+i*.065,.37+i*.065)))}%,0)`);
 $$('.fx .lead,.fx .act').forEach((el,i)=>el.style.opacity=smooth(seg(p,.38+i*.04,.50+i*.04)));
 opacity('.orb',fx);interactive('.orb',fx>.5);
 const age=seg(p,.47,.78);
 const nozzle={x:bx+.046*bw*scale,y:by-.9724*bh*scale};
 // Source is the full, uncropped 16:9 plate, with emission at (12%,55%).
 const plateW=bh*scale*(m?1.22:1.48),plateH=plateW*9/16;
 const anchor=manifest.smoke?.anchor||[.12,.55];
 const mistRect=[nozzle.x-anchor[0]*plateW,nozzle.y-anchor[1]*plateH,plateW,plateH];
 const effectsReady=smokeSequence?.info.status==='ready'&&fogSequence?.info.status==='ready';
 const mistOpacity=effectsReady&&!reduced.matches&&p>=.47&&p<=.78?1:0;
 smokeSequence?.draw(age,mistRect,mistOpacity);
 // Ambient fog stays behind the product and text; its motion is also scroll-driven.
 const fogH=m?H*.42:W*9/16, fogRect=[0,H-fogH,W,fogH];
 const fogOpacity=reduced.matches||!effectsReady?0:.24*smooth(seg(p,.15,.30))*(1-smooth(seg(p,.78,.95)));
 fogSequence?.draw(seg(p,.15,.95),fogRect,fogOpacity);
 geometry={nozzle,mistRect,sourceAnchor:anchor,mistProgress:age,mistOpacity,fogOpacity,lift,
   emission:{x:mistRect[0]+anchor[0]*plateW,y:mistRect[1]+anchor[1]*plateH}};
 const status=$('.vfx-status');
 status.hidden=!(smokeSequence?.info.status==='error'||fogSequence?.info.status==='error');
 // The preview is physically underneath the fading stage: no empty spacer.
 stage.style.setProperty('--exit',exit);
}
function setup(){
 trigger?.kill();timeline?.kill();
 if(reduced.matches){state.p=.66;render();return;}
 timeline=gsap.timeline({paused:true}).to(state,{p:1,duration:1,ease:'none',onUpdate:render});
 trigger=ScrollTrigger.create({trigger:track,start:'top top',end:'bottom bottom',animation:timeline,scrub:.18,invalidateOnRefresh:true,onRefresh:render});
 document.documentElement.dataset.engine='gsap';
}
const options={signal:controller.signal};
addEventListener('resize',()=>{layout();ScrollTrigger.refresh()},options);
reduced.addEventListener('change',()=>{if(!reduced.matches)loadMedia();layout();setup();ScrollTrigger.refresh()},options);
const burger=$('.burger'),menu=$('#menu');
function closeMenu(){menu.classList.remove('open');burger.setAttribute('aria-expanded','false')}
burger.addEventListener('click',()=>{const open=menu.classList.toggle('open');burger.setAttribute('aria-expanded',String(open))},options);
addEventListener('keydown',e=>{if(e.key==='Escape'&&menu.classList.contains('open')){closeMenu();burger.focus()}},options);
$$('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const target=$(a.hash);if(!target||target.tagName==='DIALOG')return;e.preventDefault();closeMenu();target.scrollIntoView({behavior:reduced.matches?'instant':'smooth'})},options));
if(!window.gsap||!window.ScrollTrigger)throw Error('Local GSAP libraries are missing');
gsap.registerPlugin(ScrollTrigger);layout();setup();
function loadMedia(){
 if(mediaPromise||reduced.matches||disposed)return;
 mediaPromise=fetch('assets/hero/media.json').then(r=>{if(!r.ok)throw Error('Media manifest unavailable');return r.json()}).then(data=>{
  if(disposed)return;manifest=data;
  const profile=W<=820||navigator.connection?.saveData?'mobile':'desktop';
  smokeSequence=createSequence($('.media-smoke'),data.smoke,profile,render);
  fogSequence=createSequence($('.ambient-fog'),data.fog,profile,render);
  layout();return Promise.all([smokeSequence.ready,fogSequence.ready]);
 }).catch(e=>{console.warn(e.message);$('.vfx-status').hidden=false});
}
loadMedia();
function destroy(){disposed=true;controller.abort();trigger?.kill();timeline?.kill();smokeSequence?.destroy();fogSequence?.destroy()}
addEventListener('pagehide',e=>{if(!e.persisted)destroy()},options);
window.__velora={get p(){return state.p},get renders(){return renderCount},engine:()=>document.documentElement.dataset.engine,get media(){return {smoke:smokeSequence?.info,fog:fogSequence?.info}},get geometry(){return geometry},destroy};

