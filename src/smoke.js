// WebGL volumetric-style mist: domain-warped fbm density with one-tap self-shadowing.
// Emission is driven by `age` (0..1, tied to scroll), turbulence drifts with time.
const VS='attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}';
const FS=`precision highp float;
uniform vec2 uRes,uNoz;uniform float uScale,uAge,uTime,uFade,uAng;
float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}
float n(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);
 return mix(mix(h(i),h(i+vec2(1,0)),f.x),mix(h(i+vec2(0,1)),h(i+vec2(1,1)),f.x),f.y);}
const mat2 R=mat2(.8,.6,-.6,.8);
float fbm(vec2 p){float a=.5,s=0.;for(int i=0;i<5;i++){s+=a*n(p);p=R*p*2.03;a*=.5;}return s;}
float dens(vec2 q){ // q: x along jet, y across, in plume units
 float front=uAge*1.3;
 float along=(1.-smoothstep(front-.38,front,q.x))*smoothstep(-.01,.04,q.x);
 float w=.025+.42*pow(max(q.x,0.),.75);
 float cr=exp(-pow(q.y/w,2.)*1.1);
 vec2 adv=vec2(-uAge*1.4-uTime*.11,0.);
 vec2 wp=vec2(fbm(q*2.0+adv+3.1),fbm(q*2.0+adv+9.7))-.5;
 vec2 qq=q+wp*(.12+.75*q.x);
 float c=fbm(qq*3.6+adv*1.3);float fine=fbm(qq*9.0+adv*1.9+5.3);
 float jet=exp(-q.x*2.6)*exp(-pow(q.y/(.035+.05*q.x),2.));
 float core=smoothstep(.40,.78,c*.82+fine*.3+cr*.42-.2)+jet*.8;
 return clamp(along*core*cr*1.3,0.,1.)*(1.-smoothstep(.8,1.3,q.x))*(.55+.6*c);}
void main(){
 vec2 d=(gl_FragCoord.xy-uNoz)/uScale;
 float ca=cos(uAng),sa=sin(uAng);vec2 q=vec2(ca*d.x+sa*d.y,-sa*d.x+ca*d.y);
 if(q.x < -.02 || q.x > 1.35 || abs(q.y) > .7){gl_FragColor=vec4(0.);return;}
 float a=dens(q);
 if(a<.004){gl_FragColor=vec4(0.);return;}
 float a2=dens(q+vec2(.06,.09)); // sample toward light (up-right)
 float lit=exp(-1.5*a2);
 vec3 col=mix(vec3(.60,.59,.60),vec3(1.,.98,.95),lit);
 a=clamp(a*.82,0.,.8)*uFade;
 gl_FragColor=vec4(col*a,a);}`;
export function createSmoke(canvas){
  const gl=canvas.getContext('webgl',{premultipliedAlpha:true,alpha:true,antialias:false,powerPreference:'high-performance'});
  if(!gl) return null;
  const sh=(t,s)=>{const o=gl.createShader(t);gl.shaderSource(o,s);gl.compileShader(o);if(!gl.getShaderParameter(o,gl.COMPILE_STATUS)){console.warn(gl.getShaderInfoLog(o));return null}return o};
  const v=sh(gl.VERTEX_SHADER,VS),f=sh(gl.FRAGMENT_SHADER,FS); if(!v||!f) return null;
  const pr=gl.createProgram();gl.attachShader(pr,v);gl.attachShader(pr,f);gl.linkProgram(pr);if(!gl.getProgramParameter(pr,gl.LINK_STATUS))return null;gl.useProgram(pr);
  const b=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,b);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array([-1,-1,3,-1,-1,3]),gl.STATIC_DRAW);
  const loc=gl.getAttribLocation(pr,'p');gl.enableVertexAttribArray(loc);gl.vertexAttribPointer(loc,2,gl.FLOAT,false,0,0);
  const U={};['uRes','uNoz','uScale','uAge','uTime','uFade','uAng'].forEach(k=>U[k]=gl.getUniformLocation(pr,k));
  gl.enable(gl.BLEND);gl.blendFunc(gl.ONE,gl.ONE_MINUS_SRC_ALPHA);
  let w=0,h=0,q=.5,qs=1,cw=1,ch=1;
  return{
    resize(cssW,cssH){cw=cssW;ch=cssH;const dpr=Math.min(devicePixelRatio||1,1.5);q=Math.min((cssW<820?.32:.45)*dpr,720/cssW)*qs;w=canvas.width=Math.max(2,Math.round(cssW*q));h=canvas.height=Math.max(2,Math.round(cssH*q));gl.viewport(0,0,w,h)},
    draw({nx,ny,scale,age,time,fade,ang}){ // nx,ny,scale in CSS px, origin top-left
      gl.clearColor(0,0,0,0);gl.clear(gl.COLOR_BUFFER_BIT);if(age<=0||fade<=0)return;
      gl.uniform2f(U.uRes,w,h);gl.uniform2f(U.uNoz,nx*q,h-ny*q);gl.uniform1f(U.uScale,scale*q);
      gl.uniform1f(U.uAge,age);gl.uniform1f(U.uTime,time);gl.uniform1f(U.uFade,fade);gl.uniform1f(U.uAng,ang);
      gl.drawArrays(gl.TRIANGLES,0,3)},
    lower(){if(qs>.5){qs*=.8;this.resize(cw,ch);return true}return false},
    destroy(){gl.deleteBuffer(b);gl.deleteProgram(pr);gl.deleteShader(v);gl.deleteShader(f)},
    clear(){gl.clearColor(0,0,0,0);gl.clear(gl.COLOR_BUFFER_BIT)}
  };
}

