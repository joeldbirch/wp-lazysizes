<?php
/**
 * Class LazySizesTest
 *
 * @package 
 */


class LazySizesTest extends WP_UnitTestCase {

	private $instance;
	private $fixture_img;

	function setUp() {
		$this->instance = LazySizes::get_instance();
		$this->fixture_img = '<img class="alignleft wp-image-6312" srcset="/some/image.jpg">';
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
		$method = new ReflectionMethod(
			'LazySizes', 'should_not_filter_images'
		);
		$method->setAccessible(true);

		$_SERVER['HTTP_USER_AGENT'] = 'Opera Mini';
		$this->assertTrue( $method->invoke($this->instance) === true );
	}

	function test_get_resp_attrs() {
		$method = new ReflectionMethod(
			'LazySizes', 'get_resp_attrs'
		);
		$method->setAccessible(true);

		$this->assertSame( $method->invoke($this->instance), 'data-sizes="auto" data-srcset=' );
		$this->set_option('optimumx','auto');
		$this->assertSame( $method->invoke($this->instance), 'data-optimumx="auto" data-sizes="auto" data-srcset=' );
	}

	function test_apply_responsive_attrs() {
		$method = new ReflectionMethod(
			'LazySizes', 'apply_responsive_attrs'
		);
		$method->setAccessible(true);

		$altered_string = $method->invoke($this->instance, '<img srcset="/some/image.jpg">');
		$this->assertSame( $altered_string, '<img data-optimumx="auto" data-sizes="auto" data-srcset="/some/image.jpg">' );
	}

	function test_apply_lazyload_class() {
		$method = new ReflectionMethod(
			'LazySizes', 'apply_lazyload_class'
		);
		$method->setAccessible(true);

		$altered_string = $method->invoke($this->instance, $this->fixture_img);
		$this->assertSame( $altered_string, '<img class="alignleft wp-image-6312 lazyload" srcset="/some/image.jpg">' );
	}

	function test_append_noscript() {
		$original_string   = $this->fixture_img;
		$responsive_string = '<img data-optimumx="auto" data-sizes="auto" data-srcset="/some/image.jpg">';
		$expected_string = implode(' ', [$responsive_string, '<noscript>', $original_string, '</noscript>']);

		$method = new ReflectionMethod(
			'LazySizes', 'append_noscript'
		);

		$method->setAccessible(true);

		$altered_string = $method->invoke($this->instance, $responsive_string, $original_string);
		$this->assertSame( $altered_string, $expected_string );
	}

	function test_do_string_transformations() {
		$method = new ReflectionMethod(
			'LazySizes', 'do_string_transformations'
		);
		$method->setAccessible(true);

		$fixture_string = $this->fixture_img;
		$expected_string = '<img class="alignleft wp-image-6312 lazyload" srcset="/some/image.jpg"> <noscript> '.$fixture_string.' </noscript>';
		$reduced_string = $method->invoke($this->instance, ['apply_lazyload_class','append_noscript'], $fixture_string);
		$this->assertSame( $reduced_string, $expected_string);
	}
	
}

