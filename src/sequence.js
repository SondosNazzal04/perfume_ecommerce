// Transparent frame atlases derived from the supplied VP9-alpha sources.
// All sheets are decoded before use: reverse scrubbing performs no video seeks
// or image network requests. Adjacent source frames are alpha-interpolated.
export function createSequence(canvas, config, profile, invalidate) {
  const variant=config.variants[profile], perSheet=6;
  const ctx=canvas.getContext('2d',{alpha:true});
  const sheets=new Array(variant.sheets);
  let status='loading', disposed=false, next=0, loaded=0, key='',draws=0;
  let width=1,height=1,dpr=1;
  async function worker(){
    while(next<sheets.length&&!disposed){
      const index=next++,img=new Image();
      img.decoding='async';
      img.src=variant.pattern.replace('{sheet}',String(index).padStart(3,'0'));
      await img.decode();
      if(disposed){img.src='';return;}
      sheets[index]=img;loaded++;
    }
  }
  const ready=Promise.all([worker(),worker()]).then(()=>{
    if(disposed)return;status='ready';invalidate();
  }).catch(error=>{if(disposed)return;status='error';console.warn('VFX media could not load',error.message);invalidate()});
  function clear(){ctx.clearRect(0,0,width,height)}
  function frame(index,rect,alpha){
    const sheet=sheets[Math.floor(index/perSheet)],sx=(index%perSheet)*variant.width;
    ctx.globalAlpha=alpha;
    ctx.drawImage(sheet,sx,0,variant.width,variant.height,...rect);
  }
  return {
    ready,
    get info(){return {status,loaded,sheets:sheets.length,draws,profile,decodedBytes:sheets.length*6*variant.width*variant.height*4}},
    resize(w,h){width=w;height=h;dpr=Math.min(devicePixelRatio||1,profile==='mobile'?1.25:1.5);canvas.width=Math.round(w*dpr);canvas.height=Math.round(h*dpr);ctx.setTransform(dpr,0,0,dpr,0,0);key=''},
    draw(progress,rect,opacity=1){
      const position=Math.max(0,Math.min(1,progress))*(config.frames-1),index=Math.floor(position),blend=Math.round((position-index)*256)/256;
      const nextKey=[status,index,blend,...rect,opacity].join('|');
      if(disposed||key===nextKey)return status==='ready';key=nextKey;
      clear();if(status!=='ready'||opacity<=0)return false;
      ctx.globalCompositeOperation='source-over';
      frame(index,rect,opacity*(1-blend));
      if(blend>0&&index<config.frames-1){ctx.globalCompositeOperation='lighter';frame(index+1,rect,opacity*blend)}
      ctx.globalCompositeOperation='source-over';ctx.globalAlpha=1;draws++;
      return true;
    },
    destroy(){disposed=true;sheets.forEach(img=>{if(img)img.src=''});sheets.length=0;clear()}
  };
}
