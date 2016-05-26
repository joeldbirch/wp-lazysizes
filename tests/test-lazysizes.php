<?php
/**
 * Class LazySizesTest
 *
 * @package 
 */


class LazySizesTest extends WP_UnitTestCase {


	function test_plugin_loaded() {
		$this->assertTrue( is_plugin_active('wp-lazysizes/wp-lazysizes.php') );
	}


	function test_get_js_config() {
		global $lazySizesDefaults;

		ob_start();

		$changed_option = ['preloadAfterLoad' => 'smart'];
		update_option('lazysizes_settings', array_merge($lazySizesDefaults, $changed_option ) );
		do_action('wp_head');

		$content = ob_get_contents();
		ob_end_clean();

		$this->assertTrue( !empty($content) );
		$this->assertTrue( strpos($content, 'var lazySizesConfig =' ) !== false );

		// var_export converts boolean 1 or 0 to string 'true' or 'false'
		$true_or_false = var_export(!wp_is_mobile(), true );

		$this->assertTrue( strpos($content, '"preloadAfterLoad":"'.$true_or_false.'"' ) !== false );
	}

}

