<?php
/**
 * Admin Editor Template
 * - Vrije kaart (default) of Kolommen
 * - Achtergrond (alleen bij Vrije kaart)
 * - Tile-grootte (sm/md/lg of custom)
 * - Inline preview + drag & drop (JS regelt het slepen & opslaan)
 *
 * Vereiste variabelen (worden normaliter door class-slme-admin.php gezet):
 * $course_id (int)
 * $modules (array van ['id','title','lessons'=>[['id','title','permalink','thumb_id','thumb_url','completed(bool)','short_label(string)']]])
 * $settings (array: ['display_mode'=>'free|columns','tile_size'=>'sm|md|lg|custom','tile_custom'=>int,'bg_id'=>int,'show_borders'=>bool,'positions'=>[module_id=>[lesson_id=>['x'=>..,'y'=>..]]]])
 * $nonce (string) – wp_create_nonce('slme_save')
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Fallbacks zodat de template zelfstandig blijft.
$course_id   = isset($course_id) ? (int) $course_id : get_the_ID();
$settings    = isset($settings) && is_array($settings) ? $settings : [];
$display_mode= $settings['display_mode'] ?? 'free';
$tile_size   = $settings['tile_size']   ?? 'md';
$tile_custom = isset($settings['tile_custom']) ? (int) $settings['tile_custom'] : 140;
$bg_id       = isset($settings['bg_id']) ? (int) $settings['bg_id'] : 0;
$show_borders= !empty($settings['show_borders']);
$positions   = $settings['positions'] ?? [];
$nonce       = isset($nonce) ? $nonce : wp_create_nonce('slme_save');

// Modules + lessen ophalen indien niet aangeleverd
if (empty($modules) && function_exists('slme_get_course_modules_with_lessons')) {
    $modules = slme_get_course_modules_with_lessons($course_id);
} elseif (empty($modules)) {
    $modules = []; // Laat de placeholder zien als er niets is.
}

// Media URL van achtergrond (alleen voor preview in admin)
$bg_url = '';
if ($bg_id) {
    $img = wp_get_attachment_image_src($bg_id, 'full');
    if ($img && !empty($img[0])) { $bg_url = $img[0]; }
}
?>
<div class="slme-admin-wrap" data-course-id="<?php echo esc_attr($course_id); ?>">
    <h2 class="slme-admin-title"><?php esc_html_e('Lesson Map Editor', 'slme'); ?></h2>

    <div class="slme-controls">
        <div class="slme-control-group">
            <label class="slme-label"><?php esc_html_e('Weergave', 'slme'); ?></label>
            <label><input type="radio" name="slme_display_mode" value="free" <?php checked($display_mode, 'free'); ?>> <?php esc_html_e('Vrije kaart (desktop/tablet)', 'slme'); ?></label>
            <label><input type="radio" name="slme_display_mode" value="columns" <?php checked($display_mode, 'columns'); ?>> <?php esc_html_e('Kolommen (modules)', 'slme'); ?></label>
        </div>

        <div class="slme-control-group">
            <label class="slme-label"><?php esc_html_e('Tile-grootte', 'slme'); ?></label>
            <select id="slme_tile_size" name="slme_tile_size">
                <option value="sm" <?php selected($tile_size,'sm'); ?>><?php esc_html_e('Klein', 'slme'); ?></option>
                <option value="md" <?php selected($tile_size,'md'); ?>><?php esc_html_e('Middel', 'slme'); ?></option>
                <option value="lg" <?php selected($tile_size,'lg'); ?>><?php esc_html_e('Groot', 'slme'); ?></option>
                <option value="custom" <?php selected($tile_size,'custom'); ?>><?php esc_html_e('Eigen (px)', 'slme'); ?></option>
            </select>
            <input type="number" min="64" max="360" step="1" id="slme_tile_custom" name="slme_tile_custom" value="<?php echo esc_attr($tile_custom); ?>" <?php echo ($tile_size==='custom')?'':'disabled'; ?> />
        </div>

        <div class="slme-control-group">
            <label class="slme-label"><?php esc_html_e('Module-grenzen (borders)', 'slme'); ?></label>
            <label><input type="checkbox" id="slme_show_borders" <?php checked($show_borders); ?>> <?php esc_html_e('Toon grenzen', 'slme'); ?></label>
        </div>

        <div class="slme-control-group slme-bg-only-free" <?php echo ($display_mode==='free')?'':'style="display:none"'; ?>>
            <label class="slme-label"><?php esc_html_e('Achtergrond (alleen Vrije kaart)', 'slme'); ?></label>
            <div class="slme-bg-picker">
                <input type="hidden" id="slme_bg_id" value="<?php echo esc_attr($bg_id); ?>">
                <button type="button" class="button" id="slme_bg_select"><?php esc_html_e('Kies afbeelding', 'slme'); ?></button>
                <button type="button" class="button button-link-delete" id="slme_bg_clear" <?php disabled(!$bg_id); ?>><?php esc_html_e('Verwijder', 'slme'); ?></button>
                <span class="slme-bg-thumb"><?php if($bg_url){ echo '<img src="'.esc_url($bg_url).'" alt="">'; } ?></span>
            </div>
            <p class="description"><?php esc_html_e('Wordt 1× gebruikt en visueel in 3 (of meer) kolombreedtes verdeeld; bij nieuwe keuze overschrijft hij de vorige.', 'slme'); ?></p>
        </div>

        <div class="slme-actions">
            <button type="button" class="button button-primary" id="slme_save"
                data-nonce="<?php echo esc_attr($nonce); ?>">
                <?php esc_html_e('Opslaan', 'slme'); ?>
            </button>
            <button type="button" class="button" id="slme_reset"><?php esc_html_e('Reset', 'slme'); ?></button>
            <span class="slme-save-status" aria-live="polite"></span>
        </div>
    </div>

    <div class="slme-editor-preview">
        <div
            id="slme_canvas"
            class="slme-canvas <?php echo $display_mode==='columns' ? 'is-columns' : 'is-free'; ?> <?php echo $show_borders ? 'has-borders' : ''; ?>"
            data-mode="<?php echo esc_attr($display_mode); ?>"
            data-tile-size="<?php echo esc_attr($tile_size); ?>"
            data-tile-custom="<?php echo esc_attr($tile_custom); ?>"
            data-bg-id="<?php echo esc_attr($bg_id); ?>"
            data-bg-url="<?php echo esc_url($bg_url); ?>"
            data-course-id="<?php echo esc_attr($course_id); ?>"
        >

            <?php if ($display_mode === 'columns'): ?>
                <div class="slme-columns" role="list" aria-label="<?php esc_attr_e('Modulekolommen', 'slme'); ?>">
                    <?php if (empty($modules)): ?>
                        <p class="slme-muted"><?php esc_html_e('Geen modules of lessen gevonden.', 'slme'); ?></p>
                    <?php else: ?>
                        <?php foreach ($modules as $m): ?>
                            <section class="slme-col" role="listitem" data-module-id="<?php echo esc_attr($m['id']); ?>">
                                <header class="slme-col-title"><?php echo esc_html($m['title']); ?></header>
                                <div class="slme-col-list" aria-label="<?php echo esc_attr($m['title']); ?>">
                                    <?php if (!empty($m['lessons'])): foreach ($m['lessons'] as $ls): ?>
                                        <?php
                                            $lid   = (int) $ls['id'];
                                            $thumb = !empty($ls['thumb_url']) ? $ls['thumb_url'] : (function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($lid,'medium') : '');
                                            $done  = !empty($ls['completed']);
                                            $short = isset($ls['short_label']) ? $ls['short_label'] : get_post_meta($lid,'_slme_short_label',true);
                                        ?>
                                        <div class="slme-tile"
                                             role="button"
                                             tabindex="0"
                                             draggable="true"
                                             data-lesson-id="<?php echo esc_attr($lid); ?>"
                                             data-module-id="<?php echo esc_attr($m['id']); ?>">
                                            <div class="slme-tile-img" style="<?php echo $thumb ? 'background-image:url('.esc_url($thumb).')' : ''; ?>"></div>
                                            <span class="slme-status <?php echo $done ? 'is-done' : 'is-open'; ?>" aria-hidden="true"></span>
                                            <span class="slme-short-label" contenteditable="true" data-max="3"><?php echo esc_html(mb_substr((string)$short,0,3)); ?></span>
                                            <div class="slme-tile-hover">
                                                <div class="slme-tile-title"><?php echo esc_html($ls['title']); ?></div>
                                                <div class="slme-tile-progress"><?php echo $done ? esc_html__('Afgerond','slme') : esc_html__('Nog niet afgerond','slme'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; else: ?>
                                        <div class="slme-empty"><?php esc_html_e('Geen lessen in deze module.', 'slme'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endforeach; endif; ?>
                </div>
            <?php else: /* FREE MAP */ ?>
                <div class="slme-free"
                     role="region"
                     aria-label="<?php esc_attr_e('Vrije kaart', 'slme'); ?>"
                     style="<?php echo $bg_url ? 'background-image:url('.esc_url($bg_url).')' : ''; ?>">
                    <?php if (empty($modules)): ?>
                        <p class="slme-muted"><?php esc_html_e('Geen modules of lessen gevonden.', 'slme'); ?></p>
                    <?php else: ?>
                        <?php foreach ($modules as $m): if (empty($m['lessons'])) continue; ?>
                            <?php foreach ($m['lessons'] as $ls):
                                $lid   = (int) $ls['id'];
                                $thumb = !empty($ls['thumb_url']) ? $ls['thumb_url'] : (function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($lid,'medium') : '');
                                $done  = !empty($ls['completed']);
                                $short = isset($ls['short_label']) ? $ls['short_label'] : get_post_meta($lid,'_slme_short_label',true);
                                $xy    = $positions[$m['id']][$lid] ?? ['x'=>0,'y'=>0];
                                $x     = isset($xy['x']) ? (int)$xy['x'] : 0;
                                $y     = isset($xy['y']) ? (int)$xy['y'] : 0;
                            ?>
                                <div class="slme-tile"
                                     role="button"
                                     tabindex="0"
                                     draggable="true"
                                     data-lesson-id="<?php echo esc_attr($lid); ?>"
                                     data-module-id="<?php echo esc_attr($m['id']); ?>"
                                     style="left:<?php echo $x; ?>px; top:<?php echo $y; ?>px;">
                                    <div class="slme-tile-img" style="<?php echo $thumb ? 'background-image:url('.esc_url($thumb).')' : ''; ?>"></div>
                                    <span class="slme-status <?php echo $done ? 'is-done' : 'is-open'; ?>" aria-hidden="true"></span>
                                    <span class="slme-short-label" contenteditable="true" data-max="3"><?php echo esc_html(mb_substr((string)$short,0,3)); ?></span>
                                    <div class="slme-tile-hover">
                                        <div class="slme-tile-title"><?php echo esc_html($ls['title']); ?></div>
                                        <div class="slme-tile-progress"><?php echo $done ? esc_html__('Afgerond','slme') : esc_html__('Nog niet afgerond','slme'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <template id="slme-toast">
        <div class="slme-toast" role="status" aria-live="polite"></div>
    </template>
</div>
