<?php
/**
 * Class LazySizesTest
 *
 * @package 
 */


class LazySizesTest extends WP_UnitTestCase {

	private $instance;

	function setUp() {
		$this->instance = LazySizes::get_instance();
	}

	// helper function
	function set_option($setting, $new_value) {
		global $lazySizesDefaults;
		update_option('lazysizes_settings', array_merge($lazySizesDefaults, [$setting => $new_value] ) );
		$this->instance->_get_options();
	}


	function test_plugin_loaded() {
		$this->assertTrue( is_plugin_active('wp-lazysizes/wp-lazysizes.php') );
	}

	function test_get_js_config() {
		global $lazySizesDefaults;

		ob_start();

		$this->set_option('preloadAfterLoad','smart');
		do_action('wp_head');

		$content = ob_get_contents();
		ob_end_clean();

		$this->assertTrue( !empty($content) );
		$this->assertTrue( strpos($content, 'var lazySizesConfig =' ) !== false );
		$this->assertTrue( strpos($content, '"preloadAfterLoad":"smart"' ) !== false );
	}

	function test_should_not_filter_images() {
		$_SERVER['HTTP_USER_AGENT'] = 'Opera Mini';
		$this->assertTrue( $this->instance->should_not_filter_images() === true );
	}

	function test_get_resp_img_replacement() {
		$this->assertSame( $this->instance->get_resp_img_replacement(), 'data-sizes="auto" data-srcset=' );
		$this->set_option('optimumx','auto');
		$this->assertSame( $this->instance->get_resp_img_replacement(), 'data-optimumx="auto" data-sizes="auto" data-srcset=' );
	}

}

