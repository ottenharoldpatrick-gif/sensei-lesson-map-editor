<?php
/**
 * Frontend weergave template
 * - Desktop/Tablet: Vrije kaart of Kolommen (afhankelijk van cursusinstelling)
 * - Mobiel: altijd terugvallen op standaard Sensei lijst (geen kaart)
 *
 * Vereiste variabelen (worden normaliter door class-slme-frontend.php gezet):
 * $course_id (int)
 * $modules (array, zelfde vorm als in editor)
 * $settings (array met o.a. display_mode, tile_size, tile_custom, bg_id, positions, show_borders)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$course_id    = isset($course_id) ? (int)$course_id : get_the_ID();
$settings     = isset($settings) && is_array($settings) ? $settings : [];
$display_mode = $settings['display_mode'] ?? 'free'; // default = vrije kaart
$tile_size    = $settings['tile_size']   ?? 'md';
$tile_custom  = isset($settings['tile_custom']) ? (int)$settings['tile_custom'] : 140;
$bg_id        = isset($settings['bg_id']) ? (int)$settings['bg_id'] : 0;
$positions    = $settings['positions'] ?? [];
$show_borders = !empty($settings['show_borders']);

$bg_url = '';
if ($bg_id) {
    $img = wp_get_attachment_image_src($bg_id, 'full');
    if ($img && !empty($img[0])) { $bg_url = $img[0]; }
}

// Haal modules + lessen indien niet aangeleverd
if (empty($modules) && function_exists('slme_get_course_modules_with_lessons')) {
    $modules = slme_get_course_modules_with_lessons($course_id, [
        'respect_course_order' => true, // volg Sensei-lesvolgorde
    ]);
} elseif (empty($modules)) {
    $modules = [];
}

// Hulpfunctie voltooiing
if (!function_exists('slme_tpl_is_completed')) {
    function slme_tpl_is_completed($lessonArr) {
        if (isset($lessonArr['completed'])) return (bool)$lessonArr['completed'];
        if (!empty($lessonArr['id']) && class_exists('Sensei_Utils')) {
            return (bool) Sensei_Utils::user_completed_lesson($lessonArr['id'], get_current_user_id());
        }
        return false;
    }
}
?>
<div class="slme-frontend-wrap slme-hidden-on-mobile" data-course-id="<?php echo esc_attr($course_id); ?>">
    <div
        class="slme-front-canvas <?php echo $display_mode==='columns' ? 'is-columns' : 'is-free'; ?> <?php echo $show_borders ? 'has-borders' : ''; ?>"
        data-mode="<?php echo esc_attr($display_mode); ?>"
        data-tile-size="<?php echo esc_attr($tile_size); ?>"
        data-tile-custom="<?php echo esc_attr($tile_custom); ?>"
        <?php if ($display_mode==='free' && $bg_url): ?>
            style="--slme-bg:url('<?php echo esc_url($bg_url); ?>')"
        <?php endif; ?>
    >

        <?php if ($display_mode === 'columns'): ?>
            <div class="slme-columns" role="list" aria-label="<?php esc_attr_e('Modulekolommen', 'slme'); ?>">
                <?php if (empty($modules)): ?>
                    <p class="slme-muted"><?php esc_html_e('Geen modules/lessen gevonden.', 'slme'); ?></p>
                <?php else: ?>
                    <?php foreach ($modules as $m): ?>
                        <section class="slme-col" role="listitem" data-module-id="<?php echo esc_attr($m['id'] ?? 0); ?>">
                            <header class="slme-col-title"><?php echo esc_html($m['title'] ?? ''); ?></header>
                            <div class="slme-col-list">
                                <?php if (!empty($m['lessons'])): foreach ($m['lessons'] as $ls): ?>
                                    <?php
                                        $lid   = (int) ($ls['id'] ?? 0);
                                        $thumb = !empty($ls['thumb_url']) ? $ls['thumb_url'] : (function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($lid,'medium') : '');
                                        $done  = slme_tpl_is_completed($ls);
                                        $short = isset($ls['short_label']) ? $ls['short_label'] : get_post_meta($lid,'_slme_short_label',true);
                                        $url   = $ls['permalink'] ?? get_permalink($lid);
                                    ?>
                                    <a class="slme-tile"
                                       href="<?php echo esc_url($url); ?>"
                                       data-lesson-id="<?php echo esc_attr($lid); ?>">
                                        <span class="slme-tile-img" style="<?php echo $thumb ? 'background-image:url('.esc_url($thumb).')' : ''; ?>"></span>
                                        <span class="slme-status <?php echo $done ? 'is-done':'is-open'; ?>" aria-hidden="true"></span>
                                        <?php if (!empty($short)): ?>
                                            <span class="slme-short-label"><?php echo esc_html(mb_substr((string)$short,0,3)); ?></span>
                                        <?php endif; ?>
                                        <span class="slme-tile-hover">
                                            <span class="slme-tile-title"><?php echo esc_html($ls['title'] ?? ''); ?></span>
                                            <span class="slme-tile-progress"><?php echo $done ? esc_html__('Afgerond','slme') : esc_html__('Nog niet afgerond','slme'); ?></span>
                                        </span>
                                    </a>
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
                    <p class="slme-muted"><?php esc_html_e('Geen modules/lessen gevonden.', 'slme'); ?></p>
                <?php else: ?>
                    <?php foreach ($modules as $m): if (empty($m['lessons'])) continue; ?>
                        <?php foreach ($m['lessons'] as $ls):
                            $lid   = (int) ($ls['id'] ?? 0);
                            $thumb = !empty($ls['thumb_url']) ? $ls['thumb_url'] : (function_exists('get_the_post_thumbnail_url') ? get_the_post_thumbnail_url($lid,'medium') : '');
                            $done  = slme_tpl_is_completed($ls);
                            $short = isset($ls['short_label']) ? $ls['short_label'] : get_post_meta($lid,'_slme_short_label',true);
                            $url   = $ls['permalink'] ?? get_permalink($lid);
                            $xy    = $positions[$m['id']][$lid] ?? ['x'=>0,'y'=>0];
                            $x     = isset($xy['x']) ? (int)$xy['x'] : 0;
                            $y     = isset($xy['y']) ? (int)$xy['y'] : 0;
                        ?>
                            <a class="slme-tile"
                               href="<?php echo esc_url($url); ?>"
                               data-lesson-id="<?php echo esc_attr($lid); ?>"
                               style="left:<?php echo $x; ?>px; top:<?php echo $y; ?>px;">
                                <span class="slme-tile-img" style="<?php echo $thumb ? 'background-image:url('.esc_url($thumb).')' : ''; ?>"></span>
                                <span class="slme-status <?php echo $done ? 'is-done':'is-open'; ?>" aria-hidden="true"></span>
                                <?php if (!empty($short)): ?>
                                    <span class="slme-short-label"><?php echo esc_html(mb_substr((string)$short,0,3)); ?></span>
                                <?php endif; ?>
                                <span class="slme-tile-hover">
                                    <span class="slme-tile-title"><?php echo esc_html($ls['title'] ?? ''); ?></span>
                                    <span class="slme-tile-progress"><?php echo $done ? esc_html__('Afgerond','slme') : esc_html__('Nog niet afgerond','slme'); ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Mobiel fallback: toon standaard Sensei output (lijst) -->
<div class="slme-only-mobile">
    <?php
    // Laat Sensei de normale leslijst tonen (the_content of shortcode / theme template).
    // Als je hier iets specifieks wilt, kun je een shortcode of template-part includen.
    echo do_shortcode('[sensei_course_lessons course="'.intval($course_id).'"]');
    ?>
</div>
