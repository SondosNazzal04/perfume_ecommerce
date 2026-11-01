import {PRODUCTS, product} from './commerce-data.js';
const $=(s,r=document)=>r.querySelector(s);
function editorialCard(p){
  return `<div class="card-top"><span class="card-chip">${p.badge}</span></div><div class="card-visual"><img src="${p.image}" alt="${p.name} original VELORA editorial perfume bottle" loading="lazy" width="850" height="1062"></div><div class="card-bottom"><span class="card-category">EAU DE PARFUM · VELORA SIGNATURE</span><h3>${p.title}</h3><p>${p.notes}</p><div class="card-actions"><a class="v2-gold" href="products.php">SHOP LIVE CATALOGUE <span aria-hidden="true">↗</span></a><a class="quick-button" href="#collection-info" data-product="${p.id}">EXPLORE SIGNATURE</a></div></div>`;
}
document.querySelectorAll('.perfume-card[data-id]').forEach(el=>el.innerHTML=editorialCard(product(el.dataset.id)));
const grid=$('.best-grid');
if(grid)grid.innerHTML=[PRODUCTS[1],PRODUCTS[0],PRODUCTS[2]].map(p=>`<article class="perfume-card best-card" data-id="${p.id}" aria-label="${p.name}">${editorialCard(p)}</article>`).join('');
// The quick-view carousel and search still operate through collection.js. No fake checkout or wishlist state.
const detail=$('#collection-info');
if(detail){
  const heading=$('#selected-product-heading'),desc=$('#selected-product-description'),img=$('#selected-product-image');
  document.addEventListener('click',e=>{const a=e.target.closest('a[data-product]');if(!a)return;
   const p=PRODUCTS.find(x=>x.id===a.dataset.product);if(!p)return;
   if(heading)heading.innerHTML=p.title;
   if(desc)desc.textContent=p.description;
   if(img){img.src=p.image;img.alt=p.name+' perfume bottle';}
  },true);
}
