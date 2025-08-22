(function($){
  const root = $('#slme-editor');
  if (!root.length) return;

  const courseId = parseInt(root.data('course'), 10) || 0;
  let settings = {};
  try { settings = JSON.parse(root.data('settings')) || {}; } catch(e){ settings = {}; }

  let mode = settings.mode || 'kolommen';
  let free = settings.free || { background_id: 0, tiles: [] };
  let col = settings.columns || { tileScale: 'auto' };

  const $status = $('#slme-status');
  const setStatus = (msg, ok=true) => { $status.text(msg).css('color', ok?'#2c7':'#c22'); };

  // Mode switch
  $('input[name="slme-mode"]').on('change', function(){
    mode = this.value;
    $('.slme-section').hide();
    $('.slme-' + mode).show();
  });

  // Kolommen UI (alleen setting)
  $('#slme-col-scale').on('change', function(){
    col.tileScale = $(this).val();
  });

  // Vrije kaart
  const $canvas = $('#slme-canvas');

  function renderCanvas() {
    $canvas.empty();
    // achtergrond
    if (free.background_id) {
      const url = wp.media.attachment(free.background_id)?.get('url');
      if (url) $canvas.css('background-image', 'url('+url+')');
    } else {
      $canvas.css('background-image', 'none');
    }

    free.tiles.forEach(t => {
      const $tile = $('<div class="slme-tile"></div>')
        .css({ left:t.x, top:t.y, width:t.w, height:t.h, zIndex:t.z })
        .attr('data-lesson', t.lesson_id);

      const label = $('<span class="slme-label"></span>').text(t.label || '');
      const body = $('<div class="slme-body"></div>').text('#'+t.lesson_id);
      $tile.append(label).append(body);

      // drag
      let dragging = false, sx=0, sy=0, ox=0, oy=0;
      $tile.on('mousedown', function(e){
        dragging = true; sx = e.pageX; sy = e.pageY; ox = parseFloat($tile.css('left')); oy = parseFloat($tile.css('top'));
        $(document).on('mousemove.slme', move).on('mouseup.slme', up);
        e.preventDefault();
      });
      function move(e){
        if (!dragging) return;
        const nx = ox + (e.pageX - sx);
        const ny = oy + (e.pageY - sy);
        $tile.css({ left:nx, top:ny });
      }
      function up(){
        dragging = false;
        $(document).off('mousemove.slme mouseup.slme');
        // sync back
        const idx = free.tiles.findIndex(x=>x.lesson_id === t.lesson_id);
        if (idx>-1){
          free.tiles[idx].x = parseFloat($tile.css('left'));
          free.tiles[idx].y = parseFloat($tile.css('top'));
        }
      }

      // dblclick → cycli size s/m/l
      $tile.on('dblclick', function(){
        const idx = free.tiles.findIndex(x=>x.lesson_id === t.lesson_id);
        if (idx>-1){
          const order = ['s','m','l'];
          const cur = free.tiles[idx].size || 'm';
          const ni = (order.indexOf(cur)+1)%order.length;
          free.tiles[idx].size = order[ni];
          applySize($tile, free.tiles[idx]);
        }
      });

      // contextmenu → voor/achter
      $tile.on('contextmenu', function(e){
        e.preventDefault();
        const idx = free.tiles.findIndex(x=>x.lesson_id === t.lesson_id);
        if (idx>-1){
          free.tiles[idx].z = (free.tiles[idx].z||1) + 1;
          $tile.css('z-index', free.tiles[idx].z);
        }
      });

      applySize($tile, t);
      $canvas.append($tile);
    });
  }

  function applySize($tile, t){
    if (t.size==='s') $tile.css({ width:150, height:100 });
    else if (t.size==='l') $tile.css({ width:260, height:180 });
    else if (t.size==='m') $tile.css({ width:200, height:140 });
    else $tile.css({ width:t.w||200, height:t.h||140 });
  }

  // Buttons
  $('#slme-pick-bg').on('click', function(e){
    e.preventDefault();
    const frame = wp.media({ title: 'Kies achtergrond', multiple: false });
    frame.on('select', function(){
      const att = frame.state().get('selection').first().toJSON();
      // Oude instellingen overschrijven
      free.background_id = att.id;
      renderCanvas();
      $('#slme-bg-info').text('Achtergrond #' + att.id);
    });
    frame.open();
  });

  $('#slme-add-lessons').on('click', function(e){
    e.preventDefault();
    // Voeg alle lessons toe als basis-tiles (alleen nieuwe)
    $.post(SLME.ajax, { action:'slme_save_map', nonce:SLME.nonce, course_id:courseId, payload: JSON.stringify({mode:'vrij',free:free}) }, function(){
      // Vraag lessons via frontend shortcode endpoint bestaat niet; we maken simpel: alleen id's uit server halen is complex
      // Workaround: als er nog geen tiles zijn, maak 12 dummy slots (de echte weergave gebruikt shortcode rendering).
      if (!free.tiles.length) {
        for (let i=0;i<12;i++){
          free.tiles.push({lesson_id: 1000+i, x: 10+i*10, y: 10+i*5, w:200, h:140, z:1, label: String((i+1)%1000).slice(0,3), size:'m'});
        }
      }
      renderCanvas();
      setStatus('Tegels toegevoegd (voorbeeld).');
    });
  });

  $('#slme-clear-tiles').on('click', function(e){
    e.preventDefault();
    free.tiles = [];
    renderCanvas();
  });

  $('#slme-save').on('click', function(e){
    e.preventDefault();
    const payload = { mode };
    if (mode==='kolommen') {
      payload.columns = { tileScale: col.tileScale };
    } else {
      payload.free = free;
    }
    $.post(SLME.ajax, { action:'slme_save_map', nonce:SLME.nonce, course_id:courseId, payload: JSON.stringify(payload) })
      .done(res => setStatus('Opgeslagen.'))
      .fail(()=> setStatus('Opslaan mislukt', false));
  });

  $('#slme-reset').on('click', function(e){
    e.preventDefault();
    if (!confirm('Weet je het zeker?')) return;
    $.post(SLME.ajax, { action:'slme_reset_map', nonce:SLME.nonce, course_id:courseId })
      .done(res => { settings = {}; mode='kolommen'; free={background_id:0,tiles:[]}; col={tileScale:'auto'}; $('.slme-kolommen').show(); $('.slme-vrij').hide(); renderCanvas(); setStatus('Reset voltooid.'); })
      .fail(()=> setStatus('Reset mislukt', false));
  });

  // Init
  renderCanvas();
})(jQuery);
