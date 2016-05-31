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

	function test_get_resp_attrs() {
		$this->assertSame( $this->instance->get_resp_attrs(), 'data-sizes="auto" data-srcset=' );
		$this->set_option('optimumx','auto');
		$this->assertSame( $this->instance->get_resp_attrs(), 'data-optimumx="auto" data-sizes="auto" data-srcset=' );
	}

	function test_apply_responsive_attrs() {
		$altered_string = $this->instance->apply_responsive_attrs('<img srcset="/some/image.jpg">');
		$this->assertSame( $altered_string, '<img data-optimumx="auto" data-sizes="auto" data-srcset="/some/image.jpg">' );
	}

	function test_apply_lazyload_class() {
		$altered_string = $this->instance->apply_lazyload_class('<img class="alignleft wp-image-6312" srcset="/some/image.jpg">');
		$this->assertSame( $altered_string, '<img class="alignleft wp-image-6312 lazyload" srcset="/some/image.jpg">' );
	}

	function test_append_noscript() {
		$original_string   = '<img class="alignleft wp-image-6312" srcset="/some/image.jpg">';
		$responsive_string = '<img data-optimumx="auto" data-sizes="auto" data-srcset="/some/image.jpg">';
		$expected_string = implode(' ', [$responsive_string, '<noscript>', $original_string, '</noscript>']);

		$altered_string = $this->instance->append_noscript($responsive_string, $original_string);
		$this->assertSame( $altered_string, $expected_string );
	}

	function test_do_string_transformations() {
		$fixture_string = '<img class="alignleft wp-image-6312" srcset="/some/image.jpg">';
		$expected_string = '<img class="alignleft wp-image-6312 lazyload" srcset="/some/image.jpg"> <noscript> <img class="alignleft wp-image-6312" srcset="/some/image.jpg"> </noscript>';
		$reduced_string = $this->instance->do_string_transformations(['apply_lazyload_class','append_noscript'], $fixture_string);
		$this->assertSame( $reduced_string, $expected_string);
	}
	
}

