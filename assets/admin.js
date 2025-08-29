
(function($){
  function readStructure($wrap){
    var out = [];
    $wrap.find('.slg-module').each(function(){
      var $m = $(this);
      var name = $m.find('.slg-module-name').val() || '';
      var tiles = [];
      $m.find('.slg-tiles > .slg-tile').each(function(){
        var $t = $(this);
        tiles.push({
          title: $t.find('.slg-title').val() || '',
          url: $t.find('.slg-url').val() || '',
          image: parseInt($t.find('.slg-image-id').val() || '0',10) || 0,
          lesson_id: parseInt($t.find('.slg-lesson-id').val() || '0',10) || 0
        });
      });
      out.push({name:name, tiles:tiles});
    });
    return out;
  }
  function writeStructure($wrap, data){ $('#slg-structure').val(JSON.stringify(data)); }

  function wire($root){
    $root.find('#slg-modules').sortable({
      handle: '.slg-handle', items: '> .slg-module', axis: 'y',
      update: function(){ writeStructure($root, readStructure($root)); }
    });
    $root.on('mouseenter','.slg-tiles', function(){
      var $ul = $(this);
      if ($ul.data('wired')) return;
      $ul.data('wired', true);
      $ul.sortable({
        handle: '.slg-handle', items: '> .slg-tile', connectWith: '.slg-tiles',
        update: function(){ writeStructure($root, readStructure($root)); }
      });
    });
    $root.on('click', '#slg-add-module', function(){
      var idx = $root.find('.slg-module').length + 1;
      var html = [
        '<div class="slg-module">',
          '<div class="slg-module-head">',
            '<span class="dashicons dashicons-move slg-handle"></span>',
            '<input type="text" class="slg-module-name" placeholder="Module name" value="Module '+idx+'" />',
            '<button type="button" class="button-link-delete slg-remove-module">&times;</button>',
            '<button type="button" class="button slg-add-tile">Add tile</button>',
          '</div>',
          '<ul class="slg-tiles"></ul>',
        '</div>'
      ].join('');
      $root.find('#slg-modules').append(html);
      writeStructure($root, readStructure($root));
    });
    $root.on('click', '.slg-remove-module', function(){
      $(this).closest('.slg-module').remove();
      writeStructure($root, readStructure($root));
    });
    $root.on('click', '.slg-add-tile', function(){
      var html = [
        '<li class="slg-tile">',
          '<span class="dashicons dashicons-move slg-handle"></span>',
          '<div class="slg-thumb"><img src="'+SLGE.pluginUrl+'assets/placeholder.png" alt="" /></div>',
          '<div class="slg-fields">',
            '<input type="text" class="slg-title" placeholder="Title" />',
            '<input type="url" class="slg-url" placeholder="URL (optional if Sensei lesson set)" />',
            '<div class="slg-row">',
              '<input type="number" class="slg-lesson-id" placeholder="Sensei lesson ID (optional)" min="0" step="1" />',
              '<button type="button" class="button slg-pick-lesson">Pick Sensei lesson</button>',
              '<button type="button" class="button slg-pick-media">Choose image</button>',
              '<input type="hidden" class="slg-image-id" value="0" />',
              '<button type="button" class="button-link-delete slg-remove-tile">Remove</button>',
            '</div>',
          '</div>',
        '</li>'
      ].join('');
      $(this).closest('.slg-module').find('.slg-tiles').append(html);
      writeStructure($root, readStructure($root));
    });
    $root.on('click', '.slg-remove-tile', function(){
      $(this).closest('.slg-tile').remove();
      writeStructure($root, readStructure($root));
    });

    // Media picker
    var frame = null;
    $root.on('click', '.slg-pick-media', function(e){
      e.preventDefault();
      var $btn = $(this);
      if (frame) frame.close();
      frame = wp.media({title: 'Select image', multiple: false});
      frame.on('select', function(){
        var att = frame.state().get('selection').first().toJSON();
        var $tile = $btn.closest('.slg-tile');
        $tile.find('.slg-image-id').val(att.id);
        $tile.find('.slg-thumb img').attr('src', att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
        writeStructure($root, readStructure($root));
      });
      frame.open();
    });

    // Save
    $root.on('click', '#slg-save-structure', function(){
      var payload = readStructure($root);
      writeStructure($root, payload);
      $.post(SLGE.ajax, { action:'slg_save_structure', nonce: SLGE.nonce, post_id: $root.data('post'), structure: JSON.stringify(payload) });
    });

    // Lesson picker modal
    var $modal = $('#slg-lesson-modal'), $results = $('#slg-lesson-results'), $search = $('#slg-lesson-search'), targetTile = null;
    function openModal(){ $modal.show(); $search.focus(); $results.empty(); }
    function closeModal(){ $modal.hide(); targetTile = null; }
    function renderResults(items){
      var html = items.map(function(it){
        var $row = $('<div class="slg-les-row">');
        var thumb = it.thumb ? $('<img class="slg-les-thumb" />').attr('src', it.thumb) : $('<div class="slg-les-thumb slg-les-thumb--ph"></div>');
        var title = $('<div class="slg-les-title">').text(it.title+' (ID '+it.id+')');
        var pick  = $('<button type="button" class="button button-small">Kies</button>').on('click', function(){
          if(!targetTile) return;
          targetTile.find('.slg-lesson-id').val(it.id).trigger('change');
          targetTile.find('.slg-fields').addClass('is-bound');
          if(it.thumb){ targetTile.find('.slg-thumb img').attr('src', it.thumb); }
          if(!targetTile.find('.slg-chip').length){
            var $chip = $('<span class="slg-chip">Sensei les gekoppeld</span>');
            var $unlink = $('<button type="button" class="button button-small slg-unlink">Ontkoppelen</button>');
            $unlink.on('click', function(){
              targetTile.find('.slg-lesson-id').val(0).trigger('change');
              targetTile.find('.slg-fields').removeClass('is-bound');
              $(this).closest('.slg-chip').remove();
            });
            $chip.append($unlink);
            targetTile.find('.slg-row').first().append($chip);
          }
          $('#slg-structure').val(JSON.stringify(readStructure($('#slg-editor'))));
          closeModal();
        });
        $row.append(thumb).append(title).append(pick);
        return $row;
      });
      $results.html(html);
    }
    function searchLessons(q){
      $results.html('<div class="slg-les-loading">Zoeken…</div>');
      $.post(SLGE.ajax, {action:'slg_search_lessons', nonce: SLGE.nonce, s:q||''})
        .done(function(resp){ if(resp && resp.success){ renderResults(resp.data); } else { $results.html('<div class="slg-les-empty">Geen resultaten</div>'); } })
        .fail(function(){ $results.html('<div class="slg-les-empty">Fout bij zoeken</div>'); });
    }
    $(document).on('click', '.slg-pick-lesson', function(){ targetTile = $(this).closest('.slg-tile'); openModal(); });
    $modal.on('click', '.slg-lesson-modal__close, .slg-lesson-modal__backdrop', closeModal);
    $search.on('input', function(){ searchLessons($(this).val()); });

    // Apply bound state for existing tiles
    $('.slg-tile').each(function(){
      var $t = $(this);
      var id = parseInt($t.find('.slg-lesson-id').val()||'0',10);
      if(id>0){
        $t.find('.slg-fields').addClass('is-bound');
        if(!$t.find('.slg-chip').length){
          var $chip = $('<span class="slg-chip">Sensei les gekoppeld</span>');
          var $unlink = $('<button type="button" class="button button-small slg-unlink">Ontkoppelen</button>');
          $unlink.on('click', function(){
            $t.find('.slg-lesson-id').val(0).trigger('change');
            $t.find('.slg-fields').removeClass('is-bound');
            $(this).closest('.slg-chip').remove();
          });
          $chip.append($unlink);
          $t.find('.slg-row').first().append($chip);
        }
      }
    });
  }

  $(function(){
    var $root = $('#slg-editor');
    if ($root.length){ wire($root); }
  });
})(jQuery);
