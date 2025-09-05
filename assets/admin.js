(function($) {
  'use strict';

  // Main plugin object
  const SLGE_Admin = {
    init: function() {
      const $root = $('#slg-editor');
      if (!$root.length) return;
      
      this.wireEvents($root);
      this.initSortables($root);
      this.initModal();
      this.applyBoundStates();
    },

    // Read current structure from DOM (Updated to include columns)
    readStructure: function($wrap) {
      const modules = [];
      
      $wrap.find('.slg-module').each(function() {
        const $module = $(this);
        const name = $.trim($module.find('.slg-module-name').val() || '');
        const columns = parseInt($module.find('.slg-module-columns').val() || '5', 10);
        const tiles = [];
        
        $module.find('.slg-tiles > .slg-tile').each(function() {
          const $tile = $(this);
          tiles.push({
            title: $.trim($tile.find('.slg-title').val() || ''),
            url: $.trim($tile.find('.slg-url').val() || ''),
            image: parseInt($tile.find('.slg-image-id').val() || '0', 10) || 0,
            lesson_id: parseInt($tile.find('.slg-lesson-id').val() || '0', 10) || 0
          });
        });
        
        modules.push({ 
          name: name, 
          columns: columns,
          tiles: tiles 
        });
      });
      
      return modules;
    },

    // Write structure to hidden field
    writeStructure: function($wrap, data) {
      $('#slg-structure').val(JSON.stringify(data));
    },

    // Initialize sortable functionality
    initSortables: function($root) {
      // Module sorting
      $root.find('#slg-modules').sortable({
        handle: '.slg-handle',
        items: '> .slg-module',
        axis: 'y',
        placeholder: 'slg-module-placeholder',
        update: () => {
          this.writeStructure($root, this.readStructure($root));
        }
      });

      // Tile sorting with lazy initialization
      $root.on('mouseenter', '.slg-tiles', (e) => {
        const $tiles = $(e.currentTarget);
        if ($tiles.data('sortable-initialized')) return;
        
        $tiles.data('sortable-initialized', true);
        $tiles.sortable({
          handle: '.slg-handle',
          items: '> .slg-tile',
          connectWith: '.slg-tiles',
          placeholder: 'slg-tile-placeholder',
          update: () => {
            this.writeStructure($root, this.readStructure($root));
          }
        });
      });
    },

    // Wire all event handlers
    wireEvents: function($root) {
      // Add module
      $root.on('click', '#slg-add-module', () => {
        this.addModule($root);
      });

      // Remove module with confirmation
      $root.on('click', '.slg-remove-module', (e) => {
        if (confirm(SLGE.i18n.confirmDelete)) {
          $(e.target).closest('.slg-module').remove();
          this.writeStructure($root, this.readStructure($root));
        }
      });

      // Add tile
      $root.on('click', '.slg-add-tile', (e) => {
        this.addTile($(e.target));
      });

      // Remove tile with confirmation
      $root.on('click', '.slg-remove-tile', (e) => {
        if (confirm(SLGE.i18n.confirmDelete)) {
          $(e.target).closest('.slg-tile').remove();
          this.writeStructure($root, this.readStructure($root));
        }
      });

      // Media picker
      $root.on('click', '.slg-pick-media', (e) => {
        e.preventDefault();
        this.openMediaPicker($(e.target));
      });

      // Lesson picker
      $root.on('click', '.slg-pick-lesson', (e) => {
        this.targetTile = $(e.target).closest('.slg-tile');
        this.openLessonModal();
      });

      // Unlink lesson
      $root.on('click', '.slg-unlink', (e) => {
        this.unlinkLesson($(e.target));
      });

      // Auto-save on input changes (debounced) - Updated to include columns
      $root.on('input change', '.slg-module-name, .slg-module-columns, .slg-title, .slg-url, .slg-lesson-id', 
        this.debounce(() => {
          this.writeStructure($root, this.readStructure($root));
        }, 500)
      );

      // Save structure
      $root.on('click', '#slg-save-structure', () => {
        this.saveStructure($root);
      });
    },

    // Add new module (Updated with columns)
    addModule: function($root) {
      const moduleCount = $root.find('.slg-module').length + 1;
      const html = this.getModuleTemplate(moduleCount);
      
      $root.find('#slg-modules').append(html);
      this.writeStructure($root, this.readStructure($root));
      
      // Focus on the new module name field
      $root.find('.slg-module').last().find('.slg-module-name').focus().select();
    },

    // Add new tile
    addTile: function($button) {
      const html = this.getTileTemplate();
      const $module = $button.closest('.slg-module');
      
      $module.find('.slg-tiles').append(html);
      this.writeStructure($('#slg-editor'), this.readStructure($('#slg-editor')));
      
      // Focus on the new tile title field
      $module.find('.slg-tile').last().find('.slg-title').focus();
    },

    // Templates (Updated with column selector)
    getModuleTemplate: function(index) {
      return [
        '<div class="slg-module">',
          '<div class="slg-module-head">',
            '<span class="dashicons dashicons-move slg-handle" title="Drag to reorder"></span>',
            '<input type="text" class="slg-module-name" placeholder="Module name" value="Module ' + index + '" />',
            '<select class="slg-module-columns" title="Number of columns">',
              '<option value="3">3 columns</option>',
              '<option value="4">4 columns</option>',
              '<option value="5" selected>5 columns</option>',
              '<option value="6">6 columns</option>',
            '</select>',
            '<button type="button" class="button-link-delete slg-remove-module" title="Remove module">&times;</button>',
            '<button type="button" class="button slg-add-tile">Add tile</button>',
          '</div>',
          '<ul class="slg-tiles"></ul>',
        '</div>'
      ].join('');
    },

    getTileTemplate: function() {
      return [
        '<li class="slg-tile">',
          '<span class="dashicons dashicons-move slg-handle" title="Drag to reorder"></span>',
          '<div class="slg-thumb"><img src="' + SLGE.pluginUrl + 'assets/placeholder.png" alt="" /></div>',
          '<div class="slg-fields">',
            '<input type="text" class="slg-title" placeholder="Title" />',
            '<input type="url" class="slg-url" placeholder="URL (optional if Sensei lesson set)" />',
            '<div class="slg-row">',
              '<input type="number" class="slg-lesson-id" placeholder="Sensei lesson ID (optional)" min="0" step="1" />',
              '<button type="button" class="button slg-pick-lesson">Pick Sensei lesson</button>',
              '<button type="button" class="button slg-pick-media">Choose image</button>',
              '<input type="hidden" class="slg-image-id" value="0" />',
              '<button type="button" class="button-link-delete slg-remove-tile" title="Remove tile">Remove</button>',
            '</div>',
          '</div>',
        '</li>'
      ].join('');
    },

    // Media picker functionality
    openMediaPicker: function($button) {
      if (typeof wp === 'undefined' || !wp.media) {
        alert('WordPress media library not available');
        return;
      }

      const frame = wp.media({
        title: 'Select image',
        multiple: false,
        library: { type: 'image' }
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const $tile = $button.closest('.slg-tile');
        
        $tile.find('.slg-image-id').val(attachment.id);
        
        const imageUrl = (attachment.sizes && attachment.sizes.thumbnail) 
          ? attachment.sizes.thumbnail.url 
          : attachment.url;
          
        $tile.find('.slg-thumb img').attr('src', imageUrl);
        
        this.writeStructure($('#slg-editor'), this.readStructure($('#slg-editor')));
      });

      frame.open();
    },

    // Lesson modal functionality
    initModal: function() {
      const $modal = $('#slg-lesson-modal');
      const $results = $('#slg-lesson-results');
      const $search = $('#slg-lesson-search');
      
      this.targetTile = null;

      // Search functionality with debouncing
      $search.on('input', this.debounce((e) => {
        this.searchLessons($(e.target).val());
      }, 300));

      // Modal close handlers
      $modal.on('click', '.slg-lesson-modal__close, .slg-lesson-modal__backdrop', () => {
        this.closeLessonModal();
      });

      // Escape key to close
      $(document).on('keydown', (e) => {
        if (e.key === 'Escape' && $modal.is(':visible')) {
          this.closeLessonModal();
        }
      });
    },

    openLessonModal: function() {
      const $modal = $('#slg-lesson-modal');
      const $search = $('#slg-lesson-search');
      
      $modal.show();
      $search.focus().val('');
      $('#slg-lesson-results').empty();
      
      // Load initial results
      this.searchLessons('');
    },

    closeLessonModal: function() {
      $('#slg-lesson-modal').hide();
      this.targetTile = null;
    },

    // Search lessons via AJAX
    searchLessons: function(query) {
      const $results = $('#slg-lesson-results');
      
      $results.html('<div class="slg-les-loading">Zoeken…</div>');
      
      $.post(SLGE.ajax, {
        action: 'slg_search_lessons',
        nonce: SLGE.nonce,
        s: query || ''
      })
      .done((response) => {
        if (response && response.success && response.data) {
          this.renderLessonResults(response.data);
        } else {
          $results.html('<div class="slg-les-empty">Geen resultaten gevonden</div>');
        }
      })
      .fail(() => {
        $results.html('<div class="slg-les-empty">Fout bij zoeken. Probeer opnieuw.</div>');
      });
    },

    // Render lesson search results
    renderLessonResults: function(lessons) {
      const $results = $('#slg-lesson-results');
      
      if (!lessons.length) {
        $results.html('<div class="slg-les-empty">Geen lessen gevonden</div>');
        return;
      }

      const $items = lessons.map((lesson) => {
        const $row = $('<div class="slg-les-row">');
        
        const $thumb = lesson.thumb 
          ? $('<img class="slg-les-thumb">').attr('src', lesson.thumb)
          : $('<div class="slg-les-thumb slg-les-thumb--ph">IMG</div>');
          
        const $title = $('<div class="slg-les-title">').text(`${lesson.title} (ID ${lesson.id})`);
        
        const $pick = $('<button type="button" class="button button-small">Kies</button>')
          .on('click', () => this.selectLesson(lesson));
        
        return $row.append($thumb, $title, $pick);
      });
      
      $results.empty().append($items);
    },

    // Select a lesson from the modal
    selectLesson: function(lesson) {
      if (!this.targetTile) return;

      const $tile = this.targetTile;
      
      // Set lesson ID and mark as bound
      $tile.find('.slg-lesson-id').val(lesson.id);
      $tile.find('.slg-image-id').val(0); // Reset custom image when linking lesson
      $tile.find('.slg-fields').addClass('is-bound');
      
      // Update title and URL from lesson data (visual feedback only)
      $tile.find('.slg-title').val(lesson.title);
      if (lesson.permalink) {
        $tile.find('.slg-url').val(lesson.permalink);
      }
      
      // Update thumbnail if available
      if (lesson.thumb) {
        $tile.find('.slg-thumb img').attr('src', lesson.thumb);
      }
      
      // Add linked indicator if not present
      if (!$tile.find('.slg-chip').length) {
        this.addLinkedChip($tile);
      }
      
      // Update structure and close modal
      this.writeStructure($('#slg-editor'), this.readStructure($('#slg-editor')));
      this.closeLessonModal();
      
      this.showNotice('Sensei lesson linked successfully', 'success');
    },

    // Add linked lesson indicator
    addLinkedChip: function($tile) {
      const $chip = $('<span class="slg-chip">Sensei les gekoppeld </span>');
      const $unlink = $('<button type="button" class="button button-small slg-unlink">Ontkoppelen</button>');
      
      $unlink.on('click', (e) => {
        e.preventDefault();
        this.unlinkLesson($unlink);
      });
      
      $chip.append($unlink);
      $tile.find('.slg-row').first().append($chip);
    },

    // Unlink lesson
    unlinkLesson: function($button) {
      const $tile = $button.closest('.slg-tile');
      
      // Reset lesson ID and remove bound state
      $tile.find('.slg-lesson-id').val(0);
      $tile.find('.slg-fields').removeClass('is-bound');
      $button.closest('.slg-chip').remove();
      
      // Clear title and URL if they were auto-filled
      if ($tile.find('.slg-title').prop('readonly') || $tile.find('.slg-url').prop('readonly')) {
        $tile.find('.slg-title').val('').prop('readonly', false);
        $tile.find('.slg-url').val('').prop('readonly', false);
      }
      
      // Reset to placeholder image
      $tile.find('.slg-thumb img').attr('src', SLGE.pluginUrl + 'assets/placeholder.png');
      
      this.writeStructure($('#slg-editor'), this.readStructure($('#slg-editor')));
      this.showNotice('Lesson unlinked', 'success');
    },

    // Apply bound states for existing tiles on page load
    applyBoundStates: function() {
      $('.slg-tile').each((index, tile) => {
        const $tile = $(tile);
        const lessonId = parseInt($tile.find('.slg-lesson-id').val() || '0', 10);
        
        if (lessonId > 0) {
          $tile.find('.slg-fields').addClass('is-bound');
          
          if (!$tile.find('.slg-chip').length) {
            this.addLinkedChip($tile);
          }
          
          // Load lesson data to update fields
          this.loadLessonData(lessonId, $tile);
        }
      });
    },

    // Load lesson data via AJAX
    loadLessonData: function(lessonId, $tile) {
      if (!lessonId || lessonId <= 0) return;
      
      $.post(SLGE.ajax, {
        action: 'slg_get_lesson_data',
        nonce: SLGE.nonce,
        lesson_id: lessonId
      })
      .done((response) => {
        if (response.success && response.data) {
          const lesson = response.data;
          
          // Update visual fields (but don't overwrite saved values)
          if (lesson.title && !$tile.find('.slg-title').val()) {
            $tile.find('.slg-title').val(lesson.title);
          }
          
          if (lesson.permalink && !$tile.find('.slg-url').val()) {
            $tile.find('.slg-url').val(lesson.permalink);
          }
          
          if (lesson.thumb) {
            $tile.find('.slg-thumb img').attr('src', lesson.thumb);
          }
        }
      })
      .fail(() => {
        console.warn('Could not load lesson data for ID:', lessonId);
      });
    },

    // Save structure via AJAX
    saveStructure: function($root) {
      const $button = $('#slg-save-structure');
      const originalText = $button.text();
      
      $button.prop('disabled', true).text('Saving...');
      
      const payload = this.readStructure($root);
      this.writeStructure($root, payload);
      
      $.post(SLGE.ajax, {
        action: 'slg_save_structure',
        nonce: SLGE.nonce,
        post_id: $root.data('post'),
        structure: JSON.stringify(payload)
      })
      .done((response) => {
        if (response.success) {
          this.showNotice(SLGE.i18n.saveSuccess || 'Structure saved successfully', 'success');
        } else {
          this.showNotice(response.data?.message || SLGE.i18n.saveError || 'Error saving structure', 'error');
        }
      })
      .fail(() => {
        this.showNotice(SLGE.i18n.saveError || 'Network error while saving', 'error');
      })
      .always(() => {
        $button.prop('disabled', false).text(originalText);
      });
    },

    // Show admin notice
    showNotice: function(message, type = 'info') {
      const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
      
      // Insert after h1 or at top of content
      const $target = $('.wrap h1').first();
      if ($target.length) {
        $target.after($notice);
      } else {
        $('.wrap').prepend($notice);
      }
      
      // Auto-dismiss after 3 seconds
      setTimeout(() => {
        $notice.fadeOut(() => $notice.remove());
      }, 3000);
    },

    // Utility: Debounce function
    debounce: function(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },

    // Error handling
    handleError: function(error, context = '') {
      console.error('SLGE Error' + (context ? ` (${context})` : '') + ':', error);
      this.showNotice('An error occurred. Please try again.', 'error');
    }
  };

  // Initialize when DOM is ready
  $(document).ready(() => {
    try {
      SLGE_Admin.init();
    } catch (error) {
      SLGE_Admin.handleError(error, 'initialization');
    }
  });

  // Expose for debugging in development
  if (window.console && console.log) {
    window.SLGE_Admin = SLGE_Admin;
  }

})(jQuery);