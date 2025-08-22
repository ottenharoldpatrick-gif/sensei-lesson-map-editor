(function($){
  const SLME = window.SLME || (window.SLME = {});
  SLME.state = { layout:'free', tileSize:'md', bgId:0, positions:{}, tags:{} };

  function initMediaPicker(){
    if (!wp || !wp.media) return;
    const btn = $('#slme-pick-bg'), input = $('#slme-bg-id');
    btn.on('click', function(e){
      e.preventDefault();
      const frame = wp.media({ title:'Kies achtergrond', multiple:false, library:{type:'image'} });
      frame.on('select', function(){
        const img = frame.state().get('selection').first().toJSON();
        input.val(img.id).trigger('change');
        $('#slme-bg-preview').css('background-image', 'url('+img.url+')');
      });
      frame.open();
    });
    $('#slme-clear-bg').on('click', function(){ input.val('0').trigger('change'); $('#slme-bg-preview').css('background-image','none'); });
  }

  function initDrag(){
    const $canvas = $('.slme-free-canvas');
    $('.slme-free-canvas .slme-tile').each(function(){
      const $t=$(this); $t.attr('tabindex','0');
      let isDown=false, startX=0,startY=0, origX=0,origY=0;
      $t.on('mousedown touchstart', function(e){
        isDown=true; const p=e.touches?e.touches[0]:e;
        startX=p.pageX; startY=p.pageY; const cur=$t.position(); origX=cur.left; origY=cur.top; $t.addClass('slme-moving');
      });
      $(document).on('mousemove touchmove', function(e){
        if(!isDown) return; const p=e.touches?e.touches[0]:e;
        let nx=origX+(p.pageX-startX), ny=origY+(p.pageY-startY);
        const maxX=$canvas.width()-$t.outerWidth(), maxY=$canvas.height()-$t.outerHeight();
        nx=Math.max(0,Math.min(nx,maxX)); ny=Math.max(0,Math.min(ny,maxY)); $t.css({left:nx, top:ny});
      });
      $(document).on('mouseup touchend', function(){
        if(!isDown) return; isDown=false; $t.removeClass('slme-moving');
        const id=$t.data('lesson'), pos=$t.position();
        SLME.state.positions[id]=SLME.state.positions[id]||{};
        SLME.state.positions[id].x=Math.round(pos.left); SLME.state.positions[id].y=Math.round(pos.top);
        $('#slme-state').val(JSON.stringify(SLME.state));
      });
    });
  }
  function initZIndex(){
    $('.slme-free-canvas .slme-tile').each(function(){
      const $t=$(this), id=$t.data('lesson');
      $t.find('.slme-z-up').on('click',function(e){e.preventDefault(); let z=parseInt($t.css('z-index'))||1; z++; $t.css('z-index',z); SLME.state.positions[id]=SLME.state.positions[id]||{}; SLME.state.positions[id].z=z; $('#slme-state').val(JSON.stringify(SLME.state));});
      $t.find('.slme-z-down').on('click',function(e){e.preventDefault(); let z=parseInt($t.css('z-index'))||1; z=Math.max(1,z-1); $t.css('z-index',z); SLME.state.positions[id]=SLME.state.positions[id]||{}; SLME.state.positions[id].z=z; $('#slme-state').val(JSON.stringify(SLME.state));});
    });
  }
  function initTileSize(){
    $('#slme-size').on('change', function(){ const v=$(this).val(); SLME.state.tileSize=v;
      $('.slme-free-canvas .slme-tile').removeClass('slme-tile--sm slme-tile--md slme-tile--lg');
      if(['sm','md','lg'].includes(v)) $('.slme-free-canvas .slme-tile').addClass('slme-tile--'+v);
      $('#slme-state').val(JSON.stringify(SLME.state));
    });
  }
  function initTagInputs(){
    $('.slme-tag-input').on('input', function(){ const id=$(this).data('lesson'); const val=($(this).val()||'').toString().slice(0,3); SLME.state.tags[id]=val; $('.slme-tile[data-lesson="'+id+'"] .slme-tag').text(val); $('#slme-state').val(JSON.stringify(SLME.state)); });
  }
  function initLayoutSwitch(){
    $('input[name="slme_layout"]').on('change', function(){ const v=$(this).val(); SLME.state.layout=v; $('#slme-state').val(JSON.stringify(SLME.state)); $('.slme-layout').hide(); $('.slme-layout--'+v).show(); });
  }
  function initAjaxSave(){
    $('#slme-save').on('click', function(e){ e.preventDefault();
      $.post(ajaxurl, { action:'slme_save', _wpnonce:$('#slme-nonce').val(), course_id:$('#slme-course').val(), state:$('#slme-state').val() })
      .done(function(resp){ alert(resp && resp.message ? resp.message : 'Opgeslagen'); })
      .fail(function(){ alert('Fout bij opslaan'); });
    });
    $('#slme-reset').on('click', function(e){ e.preventDefault(); if(!confirm('Resetten?')) return;
      $.post(ajaxurl, { action:'slme_reset', _wpnonce:$('#slme-nonce').val(), course_id:$('#slme-course').val() })
      .done(function(){ location.reload(); })
      .fail(function(){ alert('Fout bij reset'); });
    });
  }
  $(function(){ initMediaPicker(); initDrag(); initZIndex(); initTileSize(); initTagInputs(); initLayoutSwitch(); initAjaxSave(); });
})(jQuery);
