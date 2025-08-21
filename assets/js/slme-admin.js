(function($){
	'use strict';

	let state = {
		course: 0,
		mode: 'free',
		bg_id: 0,
		bg_url: '',
		tile_size: 'm',
		tile_w: 180,
		tile_h: 120,
		borders: false,
		positions: {}, // lesson_id => {x,y,w,h,z,label,module}
		modules: {},   // moduleName => [lesson_ids]
	};

	const $canvas = $('#slme-canvas');

	function setCanvasBg(url){
		$canvas.css({
			'background-image': url ? 'url('+url+')' : 'none',
			'background-size':'cover',
			'background-repeat':'no-repeat',
			'background-position':'center'
		});
	}

	// -------- Courses laden (via select) ----------
	$('#slme-course').on('change', function(){
		const id = parseInt($(this).val()||0,10);
		state.course = id;
		if(!id){
			$canvas.html('<div class="slme-editor-hint">Kies eerst een cursus hierboven.</div>');
			return;
		}
		// Haal meta + lessen op via een kleine inline endpoint (we gebruiken de frontend shortcode output niet in admin)
		loadCourseData(id);
	});

	function loadCourseData(courseId){
		// Eenvoudig: minimal fetch via REST bestaat niet standaard → we renderen on the fly:
		// Voor nu: reset canvas; user past posities handmatig aan.
		state.positions = {};
		renderEditor();
	}

	// -------- Mode / UI instellen ----------
	$('input[name="slme_mode"]').on('change', function(){
		state.mode = this.value;
		renderEditor();
	});

	$('#slme-tile-size').on('change', function(){
		state.tile_size = this.value;
		if (state.tile_size === 's'){ state.tile_w = 140; state.tile_h = 90; }
		else if(state.tile_size === 'm'){ state.tile_w = 180; state.tile_h = 120; }
		else if(state.tile_size === 'l'){ state.tile_w = 220; state.tile_h = 140; }
		$('#slme-tile-w').val(state.tile_w);
		$('#slme-tile-h').val(state.tile_h);
		sizeTiles();
	});

	$('#slme-tile-w,#slme-tile-h').on('input', function(){
		state.tile_size = 'custom';
		$('#slme-tile-size').val('custom');
		state.tile_w = Math.max(40, parseInt($('#slme-tile-w').val()||0,10));
		state.tile_h = Math.max(40, parseInt($('#slme-tile-h').val()||0,10));
		sizeTiles();
	});

	$('#slme-borders').on('change', function(){
		state.borders = $(this).is(':checked');
		$canvas.toggleClass('slme-has-borders', state.borders);
	});

	// -------- Media bibliotheek ----------
	$('#slme-bg-choose').on('click', function(e){
		e.preventDefault();
		const frame = wp.media({ title:'Kies achtergrond', button:{text:'Gebruik deze afbeelding'}, multiple:false });
		frame.on('select', function(){
			const att = frame.state().get('selection').first().toJSON();
			state.bg_id = att.id;
			state.bg_url = att.url;
			$('#slme-bg-id').val(att.id);
			$('#slme-bg-preview').html('<img src="'+att.url+'" alt="">');
			setCanvasBg(att.url);
		});
		frame.open();
	});

	$('#slme-bg-clear').on('click', function(e){
		e.preventDefault();
		state.bg_id = 0; state.bg_url = '';
		$('#slme-bg-id').val('');
		$('#slme-bg-preview').empty();
		setCanvasBg('');
	});

	// -------- Renderen ----------
	function renderEditor(){
		$canvas.empty().removeClass('slme-has-borders').toggleClass('slme-has-borders', !!state.borders);
		setCanvasBg(state.bg_url);

		if (state.mode === 'columns') {
			const $wrap = $('<div class="slme-colwrap"></div>');
			for (let i=1;i<=3;i++){
				const $col = $(`
					<div class="slme-col" data-col="${i}">
						<div class="slme-col-title">Module ${i}</div>
						<div class="slme-col-inner"></div>
					</div>`);
				$wrap.append($col);
			}
			$canvas.append($wrap);
			// demo tegels als placeholder totdat we echte lessen inladen:
			spawnDemoTiles(6, false);
		} else {
			// free canvas
			spawnDemoTiles(8, true);
		}
		sizeTiles();
		enableDragging();
	}

	function spawnDemoTiles(n=6, free=true){
		for(let i=1;i<=n;i++){
			const id = 10000+i; // dummy id voor editor; in echte opzet zou dit lesson_id zijn
			if (!state.positions[id]){
				state.positions[id] = { x: 20+(i*10), y: 20+(i*6), w: state.tile_w, h: state.tile_h, z: 1, label: '' };
			}
			const pos = state.positions[id];

			const $tile = $(`
				<div class="slme-tile" tabindex="0" data-lesson="${id}" style="left:${pos.x}px;top:${pos.y}px;width:${pos.w}px;height:${pos.h}px;z-index:${pos.z};">
					<span class="slme-status" aria-hidden="true">✖</span>
					<span class="slme-label">${pos.label||''}</span>
					<div class="slme-img" aria-hidden="true"></div>
				</div>
			`);

			if (free) {
				$canvas.append($tile);
			} else {
				$canvas.find('.slme-col-inner').eq((i-1)%3).append($tile.css({position:'relative', left:'auto', top:'auto'}));
			}
		}
	}

	function sizeTiles(){
		$canvas.find('.slme-tile').each(function(){
			const id = $(this).data('lesson');
			const pos = state.positions[id] || {};
			const w = pos.w || state.tile_w;
			const h = pos.h || state.tile_h;
			$(this).css({ width:w, height:h });
		});
	}

	// label instellen (dubbelklik)
	$canvas.on('dblclick', '.slme-tile', function(e){
		const id = $(this).data('lesson');
		const current = (state.positions[id] && state.positions[id].label) || '';
		const val = prompt('Label (max 3 tekens):', current || '');
		if (val === null) return;
		const label = (val||'').toString().trim().slice(0,3);
		state.positions[id] = state.positions[id] || {};
		state.positions[id].label = label;
		$(this).find('.slme-label').text(label);
	});

	// z-index wijzigen met [ en ]
	$(document).on('keydown', function(e){
		const $focus = $('.slme-tile:focus');
		if (!$focus.length) return;
		const id = $focus.data('lesson');
		state.positions[id] = state.positions[id] || { x:0,y:0,w:state.tile_w,h:state.tile_h,z:1,label:'' };
		if (e.key === '['){
			state.positions[id].z = Math.max(1, (state.positions[id].z||1) - 1);
			$focus.css('z-index', state.positions[id].z);
			e.preventDefault();
		} else if (e.key === ']'){
			state.positions[id].z = (state.positions[id].z||1) + 1;
			$focus.css('z-index', state.positions[id].z);
			e.preventDefault();
		}
	});

	// Simpele dragging (muishandlers)
	function enableDragging(){
		let dragging = null;
		let start = { x:0, y:0, left:0, top:0 };

		$canvas.on('mousedown', '.slme-tile', function(e){
			dragging = this;
			dragging.classList.add('slme-dragging');
			start.x = e.pageX;
			start.y = e.pageY;
			const off = $(this).position();
			start.left = off.left;
			start.top  = off.top;
			e.preventDefault();
		});

		$(document).on('mousemove.slme', function(e){
			if(!dragging) return;
			const dx = e.pageX - start.x;
			const dy = e.pageY - start.y;
			const left = Math.max(0, start.left + dx);
			const top  = Math.max(0, start.top + dy);
			$(dragging).css({ left, top });
		});

		$(document).on('mouseup.slme', function(){
			if(!dragging) return;
			const $t = $(dragging);
			const id = $t.data('lesson');
			const off = $t.position();
			const w = $t.outerWidth();
			const h = $t.outerHeight();
			const z = parseInt($t.css('z-index')||1,10);
			state.positions[id] = state.positions[id] || {};
			state.positions[id].x = Math.round(off.left);
			state.positions[id].y = Math.round(off.top);
			state.positions[id].w = Math.round(w);
			state.positions[id].h = Math.round(h);
			state.positions[id].z = z;
			dragging.classList.remove('slme-dragging');
			dragging = null;
		});
	}

	// Reset
	$('#slme-reset').on('click', function(e){
		e.preventDefault();
		if (!confirm('Weet je zeker dat je alle posities wilt resetten?')) return;
		state.positions = {};
		renderEditor();
	});

	// Opslaan
	$('#slme-save').on('click', function(e){
		e.preventDefault();
		if (!state.course){ alert('Kies eerst een cursus.'); return; }

		$.post(SLME.ajax, {
			action: 'slme_save',
			nonce: SLME.nonce,
			course: state.course,
			mode: state.mode,
			bg_id: state.bg_id,
			tile_size: state.tile_size,
			tile_w: state.tile_w,
			tile_h: state.tile_h,
			borders: state.borders ? 1 : 0,
			positions: state.positions
		}).done(function(resp){
			if (resp && resp.success) {
				alert('Opgeslagen');
			} else {
				alert('Fout bij opslaan');
			}
		}).fail(function(){
			alert('Fout bij opslaan (server)');
		});
	});

	// Initial
	// Canvas leeg tot cursus gekozen is
})(jQuery);
