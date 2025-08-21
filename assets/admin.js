(function(){
  const $ = (sel, ctx=document)=>ctx.querySelector(sel);
  const $$ = (sel, ctx=document)=>Array.from(ctx.querySelectorAll(sel));

  const canvas = $('#slme-canvas');
  if (!canvas) return;

  const courseIdEl = $('#slme-course-id');
  const bgIdEl     = $('#slme-background-id');
  const layoutEl   = $('#slme-layout');
  const sizeEl     = $('#slme-tile-size');

  // Drag & drop
  let dragTile=null, startX=0, startY=0, origLeft=0, origTop=0;

  function onDragStart(e){
    dragTile = e.currentTarget;
    dragTile.classList.add('dragging');
    startX = e.clientX;
    startY = e.clientY;
    const rect = dragTile.getBoundingClientRect();
    const parentRect = canvas.getBoundingClientRect();
    origLeft = rect.left - parentRect.left;
    origTop  = rect.top  - parentRect.top;
    e.dataTransfer.setData('text/plain', dragTile.dataset.lessonId);
    e.dataTransfer.effectAllowed = 'move';
  }
  function onDragOver(e){
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
  }
  function onDrop(e){
    e.preventDefault();
    if (!dragTile) return;
    const parentRect = canvas.getBoundingClientRect();
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    const newLeft = Math.max(0, origLeft + dx);
    const newTop  = Math.max(0, origTop + dy);
    dragTile.style.left = newLeft + 'px';
    dragTile.style.top  = newTop  + 'px';
    dragTile.classList.remove('dragging');
    dragTile = null;
  }
  function onDragEnd(){
    if (dragTile) dragTile.classList.remove('dragging');
    dragTile = null;
  }

  $$('.slme-tile', canvas).forEach(t=>{
    t.addEventListener('dragstart', onDragStart);
    t.addEventListener('dragend', onDragEnd);
  });
  canvas.addEventListener('dragover', onDragOver);
  canvas.addEventListener('drop', onDrop);

  // Kolom wisselen (alleen visueel, niet Sensei volgorde)
  $$('.slme-col', canvas).forEach(col=>{
    col.addEventListener('dragover', e=>e.preventDefault());
    col.addEventListener('drop', e=>{
      e.preventDefault();
      const id = e.dataTransfer.getData('text/plain');
      const tile = $('.slme-tile[data-lesson-id="'+id+'"]', canvas);
      if (!tile) return;
      tile.dataset.col = col.dataset.col;
    });
  });

  // Label / Z-index inline snel aanpassen (dubbelklik)
  canvas.addEventListener('dblclick', function(e){
    const tile = e.target.closest('.slme-tile');
    if (!tile) return;
    const curLabel = tile.querySelector('.slme-label')?.textContent || '';
    const curZ     = parseInt(tile.dataset.z||'0',10);
    const label = prompt('3-teken label (bijv. lesnummer):', curLabel).slice(0,3);
    const z     = parseInt(prompt('Z-index (0 = achter, hoger = voor):', String(curZ))||'0',10);
    tile.dataset.z = isNaN(z)?0:z;
    tile.style.zIndex = tile.dataset.z;
    const lbl = tile.querySelector('.slme-label');
    if (lbl) lbl.textContent = label || '';
  });

  // Layout wissel
  layoutEl?.addEventListener('change', ()=>{
    canvas.classList.toggle('slme-free', layoutEl.value==='free');
    canvas.classList.toggle('slme-columns', layoutEl.value==='columns');
  });

  // Achtergrond kiezen via mediabibliotheek
  $('#slme-choose-bg')?.addEventListener('click', ()=>{
    const frame = wp.media({ title: 'Kies achtergrond', button: { text: 'Gebruik als achtergrond' }, multiple: false });
    frame.on('select', ()=>{
      const att = frame.state().get('selection').first().toJSON();
      bgIdEl.value = String(att.id);
      canvas.style.backgroundImage = 'url('+att.url+')';
    });
    frame.open();
  });
  $('#slme-clear-bg')?.addEventListener('click', ()=>{
    bgIdEl.value = '';
    canvas.style.backgroundImage = '';
  });

  // Reset
  $('#slme-reset')?.addEventListener('click', ()=>{
    $$('.slme-tile', canvas).forEach(t=>{
      t.style.left='0px'; t.style.top='0px'; t.dataset.z='0'; t.style.zIndex='0'; t.dataset.col='0';
      const lbl=t.querySelector('.slme-label'); if (lbl) lbl.textContent='';
    });
  });

  // Verzamelen + opslaan
  function collect(){
    const tiles=[];
    $$('.slme-tile', canvas).forEach(t=>{
      tiles.push({
        lesson_id: parseInt(t.dataset.lessonId,10),
        x: parseFloat(t.style.left)||0,
        y: parseFloat(t.style.top)||0,
        col: t.dataset.col?parseInt(t.dataset.col,10):null,
        z: t.dataset.z?parseInt(t.dataset.z,10):0,
        label: (t.querySelector('.slme-label')?.textContent||'').slice(0,3)
      });
    });
    return {
      courseId: parseInt(courseIdEl.value,10),
      layout: layoutEl.value,
      backgroundId: parseInt(bgIdEl.value||'0',10),
      tileSize: sizeEl.value,
      tiles
    };
  }
  function toast(msg){ alert(msg); }

  async function save(){
    const p = collect();
    const body = new URLSearchParams();
    body.append('action','slme_save_map');
    body.append('nonce', SLME.nonce);
    body.append('course_id', String(p.courseId));
    body.append('layout', p.layout);
    body.append('background_id', String(p.backgroundId||''));
    body.append('tile_size', p.tileSize);
    body.append('tiles', JSON.stringify(p.tiles));
    const res = await fetch(SLME.ajaxurl, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
      credentials:'same-origin',
      body: body.toString()
    });
    let json=null; try{ json = await res.json(); }catch(e){}
    if (!res.ok || !json || !json.success) {
      const m = (json && json.data && json.data.message) ? json.data.message : ('HTTP '+res.status);
      throw new Error(m);
    }
    return true;
  }

  $('#slme-save')?.addEventListener('click', async ()=>{
    try {
      await save();
      toast(SLME.i18n.saved+' ✅');
    } catch(err) {
      console.error(err);
      toast(SLME.i18n.failed+' ❌: '+err.message);
    }
  });
})();
