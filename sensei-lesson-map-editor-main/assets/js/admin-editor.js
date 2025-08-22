jQuery(function ($) {
  // Helpers
  const $course   = $('#slme-course');
  const $layout   = $('#slme-layout');
  const $bgId     = $('#slme-bg-id');
  const $bgPrev   = $('#slme-bg-preview');
  const $freeOnly = $('#slme-free-only');
  const $preview  = $('#slme-preview');
  const $save     = $('#slme-save');
  const $reset    = $('#slme-reset');
  const $refresh  = $('#slme-preview-btn');

  const $tileSize = $('#slme-tile-size');
  const $tileCustomWrap = $('#slme-tile-custom');
  const $tileW = $('#slme-tile-w');
  const $tileH = $('#slme-tile-h');

  // Media frame
  let frame = null;

  function toggleFreeFields() {
    if ($layout.val() === 'free') {
      $freeOnly.show();
    } else {
      $freeOnly.hide();
    }
  }

  function toggleTileCustom() {
    if ($tileSize.val() === 'custom') {
      $tileCustomWrap.removeClass('hidden').show();
    } else {
      $tileCustomWrap.hide();
    }
  }

  function loadPreview() {
    const courseId = $course.val();
    if (!courseId || courseId === '0') {
      $preview.html('<div style="padding:20px;color:#666;">Kies eerst een cursus…</div>');
      return;
    }
    $.post(slmeEditor.ajaxUrl, {
      action: 'slme_preview',
      nonce: slmeEditor.nonce,
      course_id: courseId
    }, function (res) {
      if (res && res.success) {
        $preview.html(res.data.html || '');

        // Stuur UI velden in sync met serverwaarden
        $layout.val(res.data.layout || 'free');
        if (res.data.layout === 'free') {
          if (res.data.bg_id) {
            $bgId.val(res.data.bg_id);
            renderBgPreview(parseInt(res.data.bg_id, 10));
          } else {
            $bgId.val('');
            $bgPrev.empty();
          }
        } else {
          $bgId.val('');
          $bgPrev.empty();
        }

        // Tegelgrootte
        if (res.data.tileSize) {
          $tileSize.val(res.data.tileSize);
        }
        if ($tileSize.val() === 'custom') {
          $tileW.val(res.data.tile_w || 140);
          $tileH.val(res.data.tile_h || 140);
        }
        toggleFreeFields();
        toggleTileCustom();

        // Draggable alleen in vrije kaart
        if ($layout.val() === 'free') {
          makeTilesDraggable();
        }
      } else {
        $preview.html('<div style="padding:20px;color:#a00;">Fout bij laden van voorbeeld</div>');
      }
    });
  }

  function makeTilesDraggable() {
    // Simpele HTML5 drag / mousedown-move
    const $stage = $('#slme-free-stage');
    if (!$stage.length) return;

    let dragging = null;
    let startX=0, startY=0, origX=0, origY=0;

    $stage.on('mousedown', '.slme-draggable', function (e) {
      e.preventDefault();
      dragging = $(this);
      startX = e.pageX;
      startY = e.pageY;
      origX  = parseInt(dragging.css('left'),10) || 0;
      origY  = parseInt(dragging.css('top'),10) || 0;

      // naar voorgrond met +1 z-index
      let z = parseInt(dragging.css('z-index'), 10) || 1;
      dragging.css('z-index', z + 1);
      dragging.attr('data-z', z+1);
      $(document).on('mousemove.slme', onMove);
      $(document).on('mouseup.slme', onUp);
    });

    function onMove(e) {
      if (!dragging) return;
      const dx = e.pageX - startX;
      const dy = e.pageY - startY;
      dragging.css({ left: (origX + dx) + 'px', top: (origY + dy) + 'px' });
    }

    function onUp() {
      $(document).off('mousemove.slme mouseup.slme');
      dragging = null;
    }
  }

  function collectPositions() {
    const map = {};
    $('#slme-free-stage .slme-tile').each(function () {
      const $t = $(this);
      const id = $t.data('lesson-id');
      if (!id) return;
      const x = parseInt($t.css('left'), 10) || 0;
      const y = parseInt($t.css('top'), 10) || 0;
      const z = parseInt($t.css('z-index'), 10) || 1;
      map[id] = { x:x, y:y, z:z };
    });
    return map;
  }

  function renderBgPreview(attachmentId) {
    if (!attachmentId) {
      $bgPrev.empty();
      return;
    }
    // Vraag de URL via admin-ajax? Niet nodig: we tonen alleen ID.
    // We laten WP zelf in preview laden door <img> met wp ajax-url is lastiger.
    // Simpel: toon ID en hint.
    $bgPrev.html('<em>Achtergrond-ID: '+attachmentId+'</em>');
  }

  // Events
  $layout.on('change', function () {
    toggleFreeFields();
  });

  $tileSize.on('change', function () {
    toggleTileCustom();
  });

  $('#slme-choose-bg').on('click', function (e) {
    e.preventDefault();
    if (!frame) {
      frame = wp.media({
        title: 'Kies achtergrond',
        multiple: false,
        library: { type: 'image' },
        button: { text: 'Gebruik deze afbeelding' }
      });
      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        $bgId.val(attachment.id);
        renderBgPreview(attachment.id);
      });
    }
    frame.open();
  });

  $('#slme-remove-bg').on('click', function (e) {
    e.preventDefault();
    $bgId.val('');
    $bgPrev.empty();
  });

  $save.on('click', function (e) {
    e.preventDefault();
    const courseId = $course.val();
    if (!courseId || courseId === '0') {
      alert('Kies eerst een cursus');
      return;
    }

    const payload = {
      action: 'slme_save_layout',
      nonce: slmeEditor.nonce,
      course_id: courseId,
      layout: $layout.val(),
      tile_size: $tileSize.val(),
      tile_w: parseInt($tileW.val(),10) || 140,
      tile_h: parseInt($tileH.val(),10) || 140
    };

    if ($layout.val() === 'free') {
      payload.bg_id = parseInt($bgId.val(), 10) || 0;
      payload.positions = JSON.stringify(collectPositions());
    }

    $.post(slmeEditor.ajaxUrl, payload, function (res) {
      if (res && res.success) {
        alert(slmeEditor.i18n.saved);
      } else {
        alert(slmeEditor.i18n.error);
      }
    });
  });

  $reset.on('click', function (e) {
    e.preventDefault();
    const courseId = $course.val();
    if (!courseId || courseId === '0') {
      alert('Kies eerst een cursus');
      return;
    }
    if (!confirm('Weet je zeker dat je alle map-instellingen voor deze cursus wilt wissen?')) return;

    $.post(slmeEditor.ajaxUrl, {
      action: 'slme_reset_layout',
      nonce: slmeEditor.nonce,
      course_id: courseId
    }, function (res) {
      if (res && res.success) {
        alert(slmeEditor.i18n.reset_ok);
        loadPreview();
      } else {
        alert(slmeEditor.i18n.error);
      }
    });
  });

  $refresh.on('click', function (e) {
    e.preventDefault();
    loadPreview();
  });

  $course.on('change', function () {
    loadPreview();
  });

  // Initial UI state
  toggleFreeFields();
  toggleTileCustom();

  // Als via query al een course is gekozen, meteen preview laden
  if ($course.val() && $course.val() !== '0') {
    loadPreview();
  }
});
