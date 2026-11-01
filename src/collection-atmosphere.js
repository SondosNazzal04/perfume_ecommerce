/* WebGL atmosphere using locally bundled Three.js — intentionally subtle and cheap.
   The *Hero bottle* remains the approved V3 art layers, not an unrelated model. */
import * as THREE from '../assets/vendor/three/three.module.js';
const canvas=document.getElementById('collection-atmosphere');
if(canvas && !matchMedia('(prefers-reduced-motion: reduce)').matches){
 try{
  const section=canvas.closest('.collection-section');
  const renderer=new THREE.WebGLRenderer({canvas,alpha:true,antialias:false,powerPreference:'low-power'});
  renderer.setPixelRatio(Math.min(devicePixelRatio||1,1.3));renderer.setClearColor(0x000000,0);
  const scene=new THREE.Scene(),camera=new THREE.PerspectiveCamera(46,1,.1,50);
  camera.position.z=7;
  const positions=new Float32Array(120*3);
  let seed=38170;const rnd=()=>{seed=(seed*1664525+1013904223)>>>0;return seed/4294967296};
  for(let i=0;i<120;i++){positions[i*3]=(rnd()-.5)*14;positions[i*3+1]=(rnd()-.5)*7;positions[i*3+2]=(rnd()-.5)*3}
  const geometry=new THREE.BufferGeometry();geometry.setAttribute('position',new THREE.BufferAttribute(positions,3));
  const material=new THREE.PointsMaterial({color:0xd1aa70,size:.025,transparent:true,opacity:.65,depthWrite:false,sizeAttenuation:true});
  const points=new THREE.Points(geometry,material);scene.add(points);
  let visible=false,raf=0;
  function resize(){const w=section.clientWidth,h=section.clientHeight;renderer.setSize(w,h,false);camera.aspect=w/h;camera.updateProjectionMatrix()}
  function animate(){if(!visible)return;points.rotation.y+=.00018;points.rotation.z+=.000045;renderer.render(scene,camera);raf=requestAnimationFrame(animate)}
  const observer=new IntersectionObserver(entries=>{visible=entries[0]?.isIntersecting||false;cancelAnimationFrame(raf);if(visible){resize();animate()}},{threshold:.015});observer.observe(section);
  const ro=new ResizeObserver(resize);ro.observe(section);
  addEventListener('pagehide',()=>{cancelAnimationFrame(raf);observer.disconnect();ro.disconnect();geometry.dispose();material.dispose();renderer.dispose()},{once:true});
  window.__veloraAtmosphere={engine:'Three.js',get active(){return visible}};
 }catch(error){console.warn('Optional Three.js atmosphere unavailable:',error.message);canvas.style.display='none'}
}
