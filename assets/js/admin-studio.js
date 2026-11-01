/* VELORA Admin Studio — enhancement only, calls existing team API endpoints. */
(() => {
  'use strict';
  const $ = (s, root=document) => root.querySelector(s);
  const $$ = (s, root=document) => [...root.querySelectorAll(s)];
  const esc = value => String(value??'').replace(/[&<>"']/g, ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const request = async (url, options={}) => {
    const res = await fetch(url,{credentials:'same-origin',...options});
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('json')) throw new Error(`Server responded with HTTP ${res.status} instead of JSON. Check PHP/API setup.`);
    const data = await res.json();
    if(!res.ok || data.success === false) throw new Error(data.message || `Request failed (HTTP ${res.status}).`);
    return data;
  };
  const send = (url, method, body) => request(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const message = (selector, text) => {const node=$(selector);if(node){node.textContent=text;node.classList.remove('hidden');}};
  const clear = selector => {const node=$(selector);if(node){node.textContent='';node.classList.add('hidden');}};
  const confirmChange = (text) => window.confirm(text);
  const whole = n => Number.isSafeInteger(Number(n)) && Number(n) >= 0 && Number(n) <= 4294967295;

  function initBulkActions(){
    const table=$('[data-product-rows]'), toolbar=$('[data-bulk-toolbar]');if(!table||!toolbar)return;
    const all=$('[data-select-all-products]'), apply=$('[data-run-bulk]'), choice=$('[data-bulk-action]'), count=$('[data-selected-count]');
    const chosen=()=>$$('[data-select-product]:checked',table);
    const refresh=()=>{
      const visible=$$('[data-select-product]',table);
      const n=chosen().length;
      count.textContent=`${n} selected`;
      apply.disabled=!n||!choice.value;
      all.checked=!!n && n===visible.length;
      all.indeterminate=n>0&&n<visible.length;
      toolbar.classList.toggle('has-selection',n>0);
    };
    table.addEventListener('change',event=>{if(event.target.matches('[data-select-product]'))refresh();});
    all?.addEventListener('change',()=>{$$('[data-select-product]',table).forEach(node=>node.checked=all.checked);refresh();});
    choice.addEventListener('change',refresh);
    $('[data-clear-bulk]')?.addEventListener('click',()=>{$$('[data-select-product]',table).forEach(node=>node.checked=false);refresh();});
    // A new page is rendered by the existing catalog JS: selected records must not carry across pages.
    new MutationObserver(()=>{all.checked=false;all.indeterminate=false;refresh();}).observe(table,{childList:true});
    apply.addEventListener('click',async()=>{
      const ids=chosen().map(input=>Number(input.value)).filter(Number.isSafeInteger);
      const publish=choice.value==='publish';
      if(!ids.length || !['publish','unpublish'].includes(choice.value))return;
      if(!confirmChange(`${publish?'Publish':'Unpublish'} ${ids.length} selected fragrance(s)?\n\nOnly the current page selection will be processed. Updates use the existing product API. Changes can partially complete if the server returns an error.`))return;
      apply.disabled=true;choice.disabled=true;clear('[data-bulk-error]');
      let success=0;const failures=[];
      for(const id of ids){
        try{
          // Fetch ALL existing fields and categories first to avoid overwriting unrelated details.
          const response=await request(`../api/products.php?id=${id}`);
          const p=response.data;
          if(Number(p.is_active)===(publish?1:0)){success++;continue;}
          await send(`../api/products.php?id=${id}`,'PUT',{
            brand_id:Number(p.brand_id),name:p.name,slug:p.slug,
            description:p.description??null,is_active:publish,category_ids:p.category_ids??[]
          });
          success++;
        }catch(error){failures.push(`#${id}: ${error.message}`);break;/* fail fast, never imply atomicity */}
      }
      choice.disabled=false;
      if(failures.length)message('[data-bulk-error]',`${success}/${ids.length} processed. Stopped: ${failures.join('; ')}. Refresh the catalog to inspect changes.`);
      else message('[data-bulk-error]',`Completed ${success} of ${ids.length}. Refreshing catalog…`);
      // Trigger existing trusted catalog loading so counts and statuses are consistent.
      $('[data-reload-products]')?.click();
    });
  }

  function initInventory(){
    const rows=$('[data-inventory-rows]');if(!rows)return;
    const search=$('[data-inventory-search]'),stock=$('[data-inventory-filter]'),pager=$('[data-inventory-pagination]');
    let page=1,seq=0,delay;
    const load=async(nextPage=1)=>{
      page=nextPage;const run=++seq;clear('[data-inventory-error]');rows.innerHTML='<tr><td colspan="5" class="admin-loading">Loading inventory…</td></tr>';
      try{
        const query=new URLSearchParams({page:String(page),search:search.value.trim(),stock:stock.value});
        const payload=await request(`../api/admin/catalog.php?${query}`);if(run!==seq)return;
        const products=payload.data||[],info=payload.pagination||{};
        rows.innerHTML=products.length?products.map(p=>{
          const id=Number(p.id),qty=Number(p.stock_count)||0;
          return `<tr data-inventory-product="${id}"><td><strong>${esc(p.name)}</strong><small class="studio-subline">${esc(p.brand_name)}</small></td><td><strong>${qty}</strong> units</td><td>${Number(p.variant_count)||0} sizes</td><td><span class="admin-status ${qty===0?'cancelled':qty<=5?'pending':'delivered'}">${qty===0?'Out of stock':qty<=5?'Low stock':'In stock'}</span></td><td><button type="button" class="admin-icon-button" data-view-variants="${id}" aria-expanded="false">View sizes</button></td></tr><tr class="studio-detail-row hidden" data-variant-container="${id}"><td colspan="5"><div class="studio-variants" data-variant-content="${id}"></div></td></tr>`;
        }).join(''):'<tr><td colspan="5" class="admin-empty">No fragrances match these inventory filters.</td></tr>';
        const current=Number(info.page)||1,total=Math.max(1,Number(info.pages)||1);
        pager.innerHTML=`<span>${Number(info.total)||0} products · Page ${current} / ${total}</span><div><button type="button" data-inv-page="${Math.max(1,current-1)}" ${current<=1?'disabled':''}>Previous</button><button type="button" data-inv-page="${Math.min(total,current+1)}" ${current>=total?'disabled':''}>Next</button></div>`;
      }catch(e){if(run===seq){message('[data-inventory-error]',e.message);rows.innerHTML='<tr><td colspan="5" class="admin-empty">Could not load stock.</td></tr>';}}
    };
    const variantMarkup=(v,id)=>{
      const qty=Number(v.stock)||0;
      return `<form class="studio-variant-card" data-stock-form="${Number(v.id)}" data-product-id="${id}"><span class="studio-size">${Number(v.size_ml)} ml</span><span class="studio-variant-price">${Number(v.price).toFixed(2)} JOD</span><span class="admin-status ${qty===0?'cancelled':qty<=5?'pending':'delivered'}">${qty===0?'Out':qty<=5?'Low':'Available'}</span><label>Stock <input type="number" name="stock" value="${qty}" min="0" step="1" max="4294967295" required></label><button type="submit" class="admin-button admin-button-soft" disabled>Save stock</button><span class="studio-variant-message" role="status"></span></form>`;
    };
    rows.addEventListener('click',async ev=>{
      const button=ev.target.closest('[data-view-variants]');if(!button)return;
      const id=Number(button.dataset.viewVariants),container=$(`[data-variant-container="${id}"]`),target=$(`[data-variant-content="${id}"]`);
      const opening=container.classList.contains('hidden');container.classList.toggle('hidden',!opening);button.setAttribute('aria-expanded',String(opening));if(!opening||target.dataset.loaded)return;
      target.textContent='Loading individual sizes…';
      try{const data=await request(`../api/product-variant.php?product_id=${id}`);target.dataset.loaded='1';target.innerHTML=(data.data||[]).map(v=>variantMarkup(v,id)).join('')||'<p>No variants assigned.</p>';}catch(e){target.textContent=e.message;}
    });
    rows.addEventListener('input',ev=>{const form=ev.target.closest('[data-stock-form]');if(form&&ev.target.name==='stock'){$('button[type="submit"]',form).disabled=false;}});
    rows.addEventListener('submit',async ev=>{
      const form=ev.target.closest('[data-stock-form]');if(!form)return;ev.preventDefault();const id=Number(form.dataset.stockForm),productId=Number(form.dataset.productId),value=Number(form.elements.stock.value),button=$('button[type="submit"]',form),msg=$('.studio-variant-message',form);
      if(!whole(value)){msg.textContent='Stock must be a whole number (0 or greater).';return;}
      if(!confirmChange(`Set the stock for this bottle size to ${value} units?`))return;
      button.disabled=true;msg.textContent='Saving…';
      try{
        const read=await request(`../api/product-variant.php?id=${id}`),v=read.data;
        await send(`../api/product-variant.php?id=${id}`,'PUT',{
          product_id:productId,size_ml:Number(v.size_ml),price:v.price,
          discount_price:v.discount_price??null,stock:value
        });
        msg.textContent='Saved to inventory.';
        // Updated totals require a refresh; don't falsely display stale stock.
        const parent=$(`[data-inventory-product="${productId}"]`);
        if(parent)parent.classList.add('studio-stale');
      }catch(e){msg.textContent=e.message;button.disabled=false;}
    });
    pager.addEventListener('click',ev=>{const b=ev.target.closest('[data-inv-page]');if(b&&!b.disabled)load(Number(b.dataset.invPage));});
    search.addEventListener('input',()=>{clearTimeout(delay);delay=setTimeout(()=>load(1),280);});
    stock.addEventListener('change',()=>load(1));
    $('[data-inventory-refresh]')?.addEventListener('click',()=>load(page));
    if(new URLSearchParams(location.search).get('stock')==='low')stock.value='low';
    load();
  }

  function initCoupons(){
    const form=$('[data-studio-coupon-form]');if(!form)return;
    const type=$('#offer-type'),value=$('#offer-value'),submit=$('[data-coupon-save]');
    type.addEventListener('change',()=>{value.max=type.value==='percentage'?'100':'99999999.99';});
    form.addEventListener('submit',async ev=>{
      ev.preventDefault();clear('[data-coupon-error]');
      const f=new FormData(form),code=String(f.get('code')||'').trim().toUpperCase();
      if(!/^[A-Z0-9_-]{1,50}$/.test(code)){message('[data-coupon-error]','Only letters, numbers, _ or - are allowed in coupon codes.');return;}
      const start=String(f.get('starts')||''),end=String(f.get('expires')||'');
      if(start&&end&&end<=start){message('[data-coupon-error]','Expiry must be after start time.');return;}
      const amount=String(f.get('value')||'');
      if(!/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/.test(amount)|| Number(amount)<=0){message('[data-coupon-error]','Discount must be a positive amount with at most two decimal places.');return;}
      if(type.value==='percentage'&&Number(amount)>100){message('[data-coupon-error]','Percentage must be at most 100.');return;}
      const limit=String(f.get('limit')||'').trim();
      if(limit&&!/^[1-9][0-9]*$/.test(limit)){message('[data-coupon-error]','Usage limit must be a positive integer.');return;}
      const body={
        'coupon-code':code,'coupon-discount-type':type.value,
        'coupon-discount-value':amount,'coupon-min-order-amount':String(f.get('minimum')||'0'),
        'coupon-usage-limit':limit||null,'coupon-start-at':start||null,
        'coupon-expires-at':end||null,'coupon-is-active':f.has('active')?1:0
      };
      if(!confirmChange(`Create coupon ${code} with a ${type.value==='percentage'?amount+'%':amount+' JOD'} discount? This will save it to your existing database through the team's API.`))return;
      submit.disabled=true;submit.textContent='Creating…';
      try{
        await send('../api/coupons.php','POST',body);
        const target=$('[data-coupon-success]');target.classList.remove('hidden');target.textContent=`Coupon ${code} created successfully. A list is not available from the current backend.`;
        form.reset();value.max='100';
      }catch(e){message('[data-coupon-error]',e.message);}finally{submit.disabled=false;submit.textContent='Create coupon';}
    });
  }

  document.addEventListener('DOMContentLoaded',()=>{initBulkActions();initInventory();initCoupons();});
})();
