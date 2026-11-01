/** DEMO DATA ONLY. Replace this adapter with authenticated PHP endpoints later.
 * Never trust localStorage quantities, prices or availability on a real server.
 * There is intentionally no checkout, payment, order, or customer-data API. */
export const PRODUCTS = Object.freeze([
  {id:'noir',name:'VELORA NOIR',title:'Velora <em>Noir</em>',image:'assets/products/noir.webp',badge:'THE SIGNATURE',notes:'Smoked woods · Warm resin',description:'A dark, sophisticated signature. Smoked woods and warm resin unfold into a quietly magnetic presence.',prices:{50:125,100:185}},
  {id:'amber',name:'VELORA AMBER',title:'Velora <em>Amber</em>',image:'assets/products/amber.webp',badge:'THE ICON',notes:'Amber · Golden warmth',description:'Warm, luminous and beautifully composed. An amber-inspired signature with an enveloping golden character.',prices:{50:135,100:195}},
  {id:'oud',name:'VELORA OUD ROYALE',title:'Oud <em>Royale</em>',image:'assets/products/oud-royale.webp',badge:'THE EXCLUSIVE',notes:'Oud woods · Deep amber',description:'A richer kind of elegance. Deep resinous woods and amber-inspired warmth leave an unforgettable impression.',prices:{50:155,100:225}}
]);
export const product = id => PRODUCTS.find(p=>p.id===id);
export const money = value => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(value);
const KEY='velora-demo-v2';
export const demoStore = {
  load(){try{const d=JSON.parse(localStorage.getItem(KEY)||'{}');const bag=[];for(const x of Array.isArray(d.bag)?d.bag:[]){if(!product(x.id)||![50,100].includes(x.size)||!Number.isInteger(x.qty)||x.qty<1)continue;const same=bag.find(y=>y.id===x.id&&y.size===x.size);if(same)same.qty=Math.min(50,same.qty+x.qty);else bag.push({id:x.id,size:x.size,qty:Math.min(50,x.qty)})}return {bag,saved:[...new Set((Array.isArray(d.saved)?d.saved:[]).filter(id=>product(id)))]}}catch{return {bag:[],saved:[]}}},
  save(state){try{localStorage.setItem(KEY,JSON.stringify(state));return true}catch{return false}}
};
