(function(){
  'use strict';

  function positionDownloadMenu(details){
    if(!details || !details.open) return;
    var summary=details.querySelector(':scope > summary');
    var menu=details.querySelector(':scope > .net360-download-options');
    if(!summary || !menu) return;
    menu.classList.add('is-fixed');
    menu.style.visibility='hidden';
    var anchor=summary.getBoundingClientRect();
    var box=menu.getBoundingClientRect();
    var margin=8;
    var left=Math.max(margin,Math.min(anchor.right-box.width,window.innerWidth-box.width-margin));
    var top=anchor.bottom+5;
    if(top+box.height>window.innerHeight-margin && anchor.top-box.height-5>=margin){
      top=anchor.top-box.height-5;
    }
    if(top+box.height>window.innerHeight-margin) top=Math.max(margin,window.innerHeight-box.height-margin);
    menu.style.left=Math.round(left)+'px';
    menu.style.top=Math.round(top)+'px';
    menu.style.visibility='';
  }
  function clearDownloadMenu(details){
    var menu=details && details.querySelector(':scope > .net360-download-options');
    if(!menu) return;
    menu.classList.remove('is-fixed');
    menu.style.left=''; menu.style.top=''; menu.style.visibility='';
  }
  function repositionOpenDownloadMenus(){
    document.querySelectorAll('.net360-download-menu[open]').forEach(positionDownloadMenu);
  }
  document.addEventListener('toggle',function(e){
    var details=e.target;
    if(!details || !details.matches || !details.matches('.net360-download-menu')) return;
    if(details.open) positionDownloadMenu(details); else clearDownloadMenu(details);
  },true);
  window.addEventListener('resize',repositionOpenDownloadMenus);
  window.addEventListener('scroll',repositionOpenDownloadMenus,true);
  function downloadPng(button){
    var b64=button.getAttribute('data-svg');
    if(!b64) return;
    var svgText;
    try{svgText=atob(b64);}catch(e){return;}
    var blob=new Blob([svgText],{type:'image/svg+xml;charset=utf-8'});
    var url=URL.createObjectURL(blob), img=new Image();
    img.onload=function(){
      var canvas=document.createElement('canvas'), size=1200;
      canvas.width=size;canvas.height=size;
      var ctx=canvas.getContext('2d');ctx.fillStyle='#fff';ctx.fillRect(0,0,size,size);ctx.drawImage(img,0,0,size,size);
      URL.revokeObjectURL(url);
      var a=document.createElement('a');a.download=button.getAttribute('data-filename')||'360-qr.png';a.href=canvas.toDataURL('image/png');a.click();
    };
    img.onerror=function(){URL.revokeObjectURL(url);alert('Could not generate the PNG. You can download the SVG instead.');};
    img.src=url;
  }
  function fetchTitle(){
    var input=document.getElementById('net360qr_destination');
    var name=document.getElementById('net360qr_name');
    var status=document.getElementById('net360qr_title_status');
    if(!input||!status||!window.NET360QRAdmin) return;
    var url=(input.value||'').trim();
    if(!/^https?:\/\//i.test(url)){ status.textContent=''; return; }
    status.textContent=NET360QRAdmin.i18n.loading;
    if(name) name.setAttribute('aria-busy','true');
    status.className='three60-title-status loading';
    var fd=new FormData(); fd.append('action','net360qr_fetch_title'); fd.append('nonce',NET360QRAdmin.nonce); fd.append('url',url);
    fetch(NET360QRAdmin.ajaxUrl,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){return r.json();}).then(function(data){
      if(data.success && data.data && data.data.title){
        if(name && !name.value.trim()) name.value=data.data.title;
        status.textContent=NET360QRAdmin.i18n.found+': '+data.data.title;
        status.className='three60-title-status success';
        if(name) name.removeAttribute('aria-busy');
      }else{
        status.textContent=(data.data&&data.data.message)||NET360QRAdmin.i18n.notFound;
        status.className='three60-title-status muted';
        if(name) name.removeAttribute('aria-busy');
      }
    }).catch(function(){if(name) name.removeAttribute('aria-busy');status.textContent=NET360QRAdmin.i18n.error;status.className='three60-title-status error';});
  }

  function openImageModal(trigger){
    var modal=document.getElementById('net360-image-modal');
    var img=document.getElementById('net360-image-modal-img');
    var title=document.getElementById('net360-image-modal-title');
    if(!modal||!img) return;
    var src=trigger.getAttribute('data-image')||'';
    if(!src) return;
    img.src=src;
    img.alt='QR Code ampliado'+(trigger.getAttribute('data-title')?' — '+trigger.getAttribute('data-title'):'');
    if(title) title.textContent=trigger.getAttribute('data-title')||'QR Code';
    modal.hidden=false; modal.setAttribute('aria-hidden','false');
    document.body.classList.add('net360-modal-open');
    var close=modal.querySelector('.net360-image-modal-close');
    if(close) close.focus();
  }
  function closeImageModal(){
    var modal=document.getElementById('net360-image-modal');
    var img=document.getElementById('net360-image-modal-img');
    if(!modal) return;
    modal.hidden=true; modal.setAttribute('aria-hidden','true');
    if(img) img.removeAttribute('src');
    document.body.classList.remove('net360-modal-open');
  }
  var titleTimer=null;
  document.addEventListener('input',function(e){
    if(e.target&&e.target.id==='net360qr_destination'){
      clearTimeout(titleTimer); titleTimer=setTimeout(fetchTitle,750);
    }
  });
  document.addEventListener('click',function(e){
    var png=e.target.closest('.net360-download-png');
    if(png){e.preventDefault();downloadPng(png);return;}
    if(e.target.closest('#net360qr_detect_title')){e.preventDefault();fetchTitle();return;}
    var imageTrigger=e.target.closest('.net360-image-trigger');
    if(imageTrigger){e.preventDefault();openImageModal(imageTrigger);return;}
    if(e.target.closest('[data-modal-close]')){e.preventDefault();closeImageModal();return;}
    var copy=e.target.closest('.net360-copy,.net360-admin-copy');
    if(copy){
      var value=copy.getAttribute('data-copy')||'';
      if(navigator.clipboard){navigator.clipboard.writeText(value).then(function(){var old=copy.textContent;copy.textContent='Copied ✓';setTimeout(function(){copy.textContent=old;},1200);});}
    }
  });
  document.addEventListener('keydown',function(e){if(e.key==='Escape') closeImageModal();});
})();
