<?php
namespace SLME;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Utils {

    public static function get_courses(): array {
        // Sensei registreert 'course' als CPT 'course' (Sensei LMS).
        $args = [
            'post_type'      => 'course',
            'posts_per_page' => -1,
            'post_status'    => ['publish','draft','pending','private'],
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ];
        $ids = get_posts( $args );
        $courses = [];
        foreach ( $ids as $id ) {
            $courses[] = [
                'id'    => (int)$id,
                'title' => get_the_title( $id ),
            ];
        }
        return $courses;
    }

    public static function get_modules_for_course( int $course_id ): array {
        // Sensei Modules kunnen module CPT of taxonomy zijn afhankelijk van versie. We proberen via Sensei functies, anders fallback.
        if ( function_exists( 'Sensei' ) && method_exists( \Sensei(), 'modules' ) ) {
            $modules = \Sensei()->modules->get_course_modules( $course_id ); // array of WP_Term
            if ( is_array( $modules ) ) {
                return array_map( function( $m ) {
                    return ['id' => (int)$m->term_id, 'name' => $m->name ];
                }, $modules );
            }
        }
        // Fallback: geen modules
        return [];
    }

    public static function get_lessons_for_course_by_modules( int $course_id ): array {
        $modules = self::get_modules_for_course( $course_id );
        $by_module = [];

        if ( $modules ) {
            foreach ( $modules as $m ) {
                $by_module[$m['id']] = [
                    'module'  => $m,
                    'lessons' => self::get_lessons_for_module( $course_id, $m['id'] ),
                ];
            }
            return $by_module;
        }

        // Fallback: geen modules → alles in één “module”
        $by_module[0] = [
            'module'  => ['id'=>0,'name'=>'Zonder module'],
            'lessons' => self::get_lessons_for_course_simple( $course_id ),
        ];
        return $by_module;
    }

    public static function get_lessons_for_module( int $course_id, int $module_term_id ): array {
        // Probeer via Sensei query utility
        $args = [
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'post_status'    => ['publish','draft','pending','private'],
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'tax_query'      => [
                [
                    'taxonomy' => 'module',
                    'field'    => 'term_id',
                    'terms'    => $module_term_id,
                ]
            ],
            'meta_query'     => [
                [
                    'key'   => '_lesson_course',
                    'value' => $course_id,
                ]
            ],
            'fields'         => 'ids',
        ];
        $ids = get_posts( $args );
        return array_map( fn($id) => self::lesson_payload( $id ), $ids );
    }

    public static function get_lessons_for_course_simple( int $course_id ): array {
        $args = [
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'post_status'    => ['publish','draft','pending','private'],
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_key'       => '_lesson_course',
            'meta_value'     => $course_id,
            'fields'         => 'ids',
        ];
        $ids = get_posts( $args );
        return array_map( fn($id) => self::lesson_payload( $id ), $ids );
    }

    private static function lesson_payload( int $lesson_id ): array {
        return [
            'id'      => $lesson_id,
            'title'   => get_the_title( $lesson_id ),
            'link'    => get_permalink( $lesson_id ),
            'thumb'   => get_the_post_thumbnail_url( $lesson_id, 'medium' ) ?: '',
            'excerpt' => wp_trim_words( strip_tags( get_post_field('post_content', $lesson_id) ), 20 ),
        ];
    }

    public static function option_key( int $course_id ): string {
        return 'slme_course_' . $course_id . '_map';
    }

    public static function get_map_settings( int $course_id ): array {
        $data = get_option( self::option_key( $course_id ), [] );
        return is_array( $data ) ? $data : [];
    }

    public static function save_map_settings( int $course_id, array $data ): bool {
        return update_option( self::option_key( $course_id ), $data, false );
    }

    public static function reset_map_settings( int $course_id ): bool {
        return delete_option( self::option_key( $course_id ) );
    }
}
