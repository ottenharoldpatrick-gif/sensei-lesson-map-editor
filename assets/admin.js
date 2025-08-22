/* ===== Sensei Lesson Map Editor – ADMIN JS ===== */
(function(){
  const $ = (s, c=document)=>c.querySelector(s);
  const $$ = (s, c=document)=>Array.from(c.querySelectorAll(s));
  function ready(fn){ document.readyState!=='loading'?fn():document.addEventListener('DOMContentLoaded',fn); }

  ready(function(){
    const wrap=$('.slme-admin-wrap'); if(!wrap) return;
    const courseId=parseInt(wrap.dataset.courseId,10);
    const canvas=$('#slme_canvas',wrap);
    const saveBtn=$('#slme_save',wrap);
    const resetBtn=$('#slme_reset',wrap);
    const statusEl=$('.slme-save-status',wrap);
    const modeRadios=$$('input[name="slme_display_mode"]',wrap);
    const tileSizeSel=$('#slme_tile_size',wrap);
    const tileCustom=$('#slme_tile_custom',wrap);
    const showBorders=$('#slme_show_borders',wrap);
    const bgWrap=$('.slme-bg-only-free',wrap);
    const bgIdFld=$('#slme_bg_id',wrap);
    const bgPick=$('#slme_bg_select',wrap);
    const bgClear=$('#slme_bg_clear',wrap);
    const bgThumb=$('.slme-bg-thumb',wrap);

    // Max 3 tekens in label
    $$('.slme-short-label',canvas).forEach(el=>{
      el.addEventListener('input',()=>{ if(el.textContent.length>3) el.textContent=el.textContent.slice(0,3); });
    });

    // Toggle free/columns
    modeRadios.forEach(r=>r.addEventListener('change',()=>{
      const v=r.value;
      canvas.dataset.mode=v;
      canvas.classList.toggle('is-columns',v==='columns');
      canvas.classList.toggle('is-free',v==='free');
      bgWrap.style.display=(v==='free')?'':'none';
    }));

    // Tile size
    tileSizeSel.addEventListener('change',()=>{ tileCustom.disabled=(tileSizeSel.value!=='custom'); });

    // Media picker
    let frame=null;
    bgPick?.addEventListener('click',e=>{
      e.preventDefault();
      if(!wp?.media){ alert('Media niet beschikbaar'); return; }
      frame=wp.media({title:'Kies achtergrond',multiple:false,library:{type:'image'}});
      frame.on('select',()=>{
        const f=frame.state().get('selection').first().toJSON();
        bgIdFld.value=f.id;
        bgThumb.innerHTML='<img src="'+f.url+'">';
        const free=$('.slme-free',canvas);
        if(free) free.style.backgroundImage='url('+f.url+')';
        bgClear.disabled=false;
      });
      frame.open();
    });
    bgClear?.addEventListener('click',()=>{
      bgIdFld.value=''; bgThumb.innerHTML='';
      const free=$('.slme-free',canvas); if(free) free.style.backgroundImage='';
      bgClear.disabled=true;
    });

    // Opslaan
    saveBtn?.addEventListener('click',()=>{
      const data={display_mode:canvas.dataset.mode,tile_size:tileSizeSel.value,tile_custom:parseInt(tileCustom.value||'140',10),show_borders:showBorders.checked?1:0,bg_id:parseInt(bgIdFld.value||'0',10)};
      const body=new URLSearchParams({action:'slme_save_settings',nonce:saveBtn.dataset.nonce||'',course_id:String(courseId),data:JSON.stringify(data)});
      fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
        .then(r=>r.json()).then(j=>{ statusEl.textContent=j.success?'Opgeslagen':'Fout'; });
    });

    // Reset
    resetBtn?.addEventListener('click',()=>{
      if(!confirm('Resetten?')) return;
      const body=new URLSearchParams({action:'slme_reset_settings',course_id:String(courseId)});
      fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
        .then(r=>r.json()).then(j=>{ if(j.success) location.reload(); });
    });
  });
})();
