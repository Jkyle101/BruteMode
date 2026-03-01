document.addEventListener('DOMContentLoaded',function(){
  const alerts = document.querySelectorAll('.alert-success');
  alerts.forEach(a=>{
    a.style.transition='transform 0.3s ease, opacity 0.3s ease';
    a.style.transform='scale(1.05)';
    setTimeout(()=>{ a.style.transform='scale(1)'; },200);
    setTimeout(()=>{ a.style.opacity='0'; a.style.transform='translateY(-10px)'; },4000);
    setTimeout(()=>{ a.remove(); },4500);
  });
});
