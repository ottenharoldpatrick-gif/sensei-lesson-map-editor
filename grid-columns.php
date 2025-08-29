<?php
if (!defined('ABSPATH')) { exit; }
function slge_grid_by_slug($slug){ return do_shortcode('[lesson_grid slug="'.sanitize_title($slug).'"]'); }
