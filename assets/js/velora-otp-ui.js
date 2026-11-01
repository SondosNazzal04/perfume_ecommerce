// Presentation enhancement layered over the original working 6-digit OTP POST form.
(()=>{
  const field=document.querySelector('.velora-otp-field');if(!field)return;
  const input=field.querySelector('#otp'),slots=[...field.querySelectorAll('.velora-otp-slot')];
  const update=()=>{input.value=input.value.replace(/\D/g,'').slice(0,6);
    slots.forEach((el,i)=>{const filled=input.value.length>i;el.textContent=filled?'•':'·';el.classList.toggle('filled',filled);el.classList.toggle('current',input.value.length===i)});
  };
  field.querySelector('.velora-otp-slots')?.addEventListener('click',()=>input.focus());
  input.addEventListener('input',update);input.addEventListener('paste',e=>{e.preventDefault();input.value=(e.clipboardData?.getData('text')||'').replace(/\D/g,'').slice(0,6);update()});
  input.addEventListener('focus',()=>field.classList.add('focused'));input.addEventListener('blur',()=>field.classList.remove('focused'));
  update();
  const message=document.querySelector('#verify-form .form-message');
  if(message)new MutationObserver(()=>{
    if(message.classList.contains('success') && !field.classList.contains('is-verified')){
      field.classList.add('is-verified');input.readOnly=true;
      slots.forEach(x=>x.classList.remove('current'));
    }
  }).observe(message,{childList:true,characterData:true,attributes:true,subtree:true});
})();
