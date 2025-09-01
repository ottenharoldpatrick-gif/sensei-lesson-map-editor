<?php
class Handle_Save_Map_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        if ( ! post_type_exists( 'course' ) ) {
            register_post_type( 'course', [ 'public' => true ] );
        }
        $_POST = [];
    }

    public function tearDown(): void {
        $_POST = [];
        parent::tearDown();
    }

    public function test_rejects_without_capabilities() {
        $course_id = self::factory()->post->create( [ 'post_type' => 'course' ] );
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

        $_POST['nonce'] = wp_create_nonce( 'slme_admin' );
        $_POST['course_id'] = $course_id;

        try {
            SLME_Admin::handle_save_map();
            $this->fail( 'Expected wp_die' );
        } catch ( WPDieException $e ) {
            $response = json_decode( $e->getMessage(), true );
            $this->assertFalse( $response['success'] );
            $this->assertSame( 'Geen permissie', $response['data']['message'] );
        }
    }

    public function test_rejects_invalid_course_id() {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $post_id = self::factory()->post->create();

        $_POST['nonce'] = wp_create_nonce( 'slme_admin' );
        $_POST['course_id'] = $post_id;

        try {
            SLME_Admin::handle_save_map();
            $this->fail( 'Expected wp_die' );
        } catch ( WPDieException $e ) {
            $response = json_decode( $e->getMessage(), true );
            $this->assertFalse( $response['success'] );
            $this->assertSame( 'Ongeldige cursus', $response['data']['message'] );
        }
    }

    public function test_sanitizes_tile_size_and_positions() {
        wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
        $course_id = self::factory()->post->create( [ 'post_type' => 'course' ] );

        $_POST = [
            'nonce'         => wp_create_nonce( 'slme_admin' ),
            'course_id'     => $course_id,
            'layout_mode'   => 'free',
            'background_id' => '7',
            'tile_size'     => '120px',
            'positions'     => '{"key":"<script>alert(1)</script>"}',
        ];

        try {
            SLME_Admin::handle_save_map();
            $this->fail( 'Expected wp_die' );
        } catch ( WPDieException $e ) {
            $response = json_decode( $e->getMessage(), true );
            $this->assertTrue( $response['success'] );
            $this->assertSame( '120', $response['data']['saved']['tile_size'] );
            $this->assertSame( '120', get_post_meta( $course_id, '_slme_tile_size', true ) );
            $this->assertSame(
                ['key' => 'alert1'],
                json_decode( get_post_meta( $course_id, '_slme_positions', true ), true )
            );
        }
    }
}
