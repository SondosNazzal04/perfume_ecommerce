// Badge shows real team-cart quantities only for signed-in customers.
(async()=>{const badge=document.getElementById('cart-count');if(!badge)return;
  try{const res=await fetch('api/cart.php',{credentials:'same-origin',headers:{Accept:'application/json'}});
    if(!res.ok)return;const body=await res.json();
    const list=Array.isArray(body.data)?body.data:[];
    badge.textContent=String(list.reduce((n,x)=>n+Math.max(0,Number(x.quantity)||0),0));
  }catch{/* Anonymous sessions should not break cinematic rendering. */}
})();
