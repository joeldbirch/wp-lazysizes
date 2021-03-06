<?php
/**
 * @link              https://github.com/aFarkas/wp-lazysizes
 * @since             2.0.0
 * @package           https://github.com/aFarkas/wp-lazysizes
 *
 * @wordpress-plugin
 */
/*
Plugin Name:       WP LazySizes
Plugin URI:        https://github.com/aFarkas/wp-lazysizes
Description:       Lazyload responsive images with automatic sizes calculation
Version:           0.9.4
Author:            Alexander Farkas
Author URI:        https://github.com/aFarkas/
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

defined('ABSPATH') or die("No script kiddies please!");

if ( ! class_exists( 'LazySizes' ) ) :

$lazySizesDefaults = array(
    'expand' => 359,
    'optimumx' => 'false',
    'intrinsicRatio' => 'false',
    'iframes' => 'false',
    'autosize' => 'true',
    'preloadAfterLoad' => 'false'
);
require_once( plugin_dir_path( __FILE__ ) . 'settings.php' );


class LazySizes {

    const version = '0.9.4';
    private static $options = array();
    private static $instance;

    function __construct() {

        if ( !is_admin() ) {

            add_action( 'wp_enqueue_scripts', array( $this, '_get_options' ), 1 );
            add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ), 1 );
            add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 200 );
            // Run this later, so other content filters have run, including image_add_wh on WP.com
            add_filter( 'the_content', array( $this, 'filter_images'), 200 );
            add_filter( 'post_thumbnail_html', array( $this, 'filter_images'), 200 );
            add_filter( 'widget_text', array( $this, 'filter_images'), 200 );
            if ($this->_get_option('iframes') != 'false') {
                add_filter('oembed_result', array($this, 'filter_iframes'), 200);
                add_filter('embed_oembed_html', array($this, 'filter_iframes'), 200);
            }
            add_filter('get_avatar', array($this, 'filter_avatar'), 200);
        }
    }


    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    public function _get_options() {

        global $lazySizesDefaults;

        self::$options = get_option( 'lazysizes_settings', $lazySizesDefaults);

        if ( is_numeric( $this->_get_option( 'expand' ) ) ) {
            self::$options['expand'] = (float)self::$options['expand'];
        } else {
            self::$options['expand'] = $lazySizesDefaults['expand'];
        }
    }

    private function _get_option( $name ) {

        if ( ! isset( self::$options[$name] ) ) {
            return false;
        }

        return self::$options[$name];
    }

    public function add_styles() {

        wp_enqueue_style( 'lazysizes', $this->get_url( 'css/lazysizes.min.css' ), array(), self::version );
    }

    public function add_scripts() {

        wp_enqueue_script( 'lazysizes', $this->get_url( 'build/wp-lazysizes.min.js' ), array(), self::version, false );

        if ( $this->_get_option( 'optimumx' ) !== 'false' ) {
            wp_enqueue_script( 'lazysizesoptimumx',
                $this->get_url( 'js/lazysizes/plugins/optimumx/ls.optimumx.min.js' ), array(), self::version, false );
        }

        wp_localize_script( 'lazysizes', 'lazySizesConfig', $this->get_js_config() );

    }

    private function get_js_config() {
        return array(
            'expand'           => $this->_get_option('expand'),
            'preloadAfterLoad' => $this->_get_option('preloadAfterLoad')
            );
    }

    public function filter_avatar( $content ) {

        return $this->filter_images( $content, 'noratio' );
    }

    public function filter_iframes( $html ) {

        if ( false === strpos( $html, 'iframe' ) ) {
            return $html;
        }

        return $this->_add_class( $html, 'lazyload' );
    }

    private function should_not_filter_images() {
        return is_feed()
            || intval( get_query_var( 'print' ) ) == 1
            || intval( get_query_var( 'printpage' ) ) == 1
            || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false;
    }

    private function get_resp_attrs() {
        $optimumx_setting = $this->_get_option('optimumx');
        $str = ($optimumx_setting !== 'false' ) ? ["data-optimumx=\"$optimumx_setting\""] : [];
        $str[] = 'data-sizes="auto" data-srcset=';
        return implode(' ', $str);
    }

    private function swap_src($imgHTML) {
        $placeholder_image = apply_filters( 'lazysizes_placeholder_image', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' );
        return preg_replace( '/<img(.*?)src=/i', '<img$1src="' . $placeholder_image . '" data-src=', $imgHTML );
    }

    private function apply_responsive_attrs($imgHTML) {
        return preg_replace( '/srcset=/i', $this->get_resp_attrs(), $imgHTML );
    }

    private function apply_lazyload_class($imgHTML) {
        return $this->_add_class( $imgHTML, 'lazyload' );
    }

    private function append_noscript($imgHTML, $original_imgHTML) {
        return "$imgHTML <noscript> $original_imgHTML </noscript>";
    }

    // NOTE this function requires PHP v5.3 because of the 'use' keyword
    // I guess I should use WordPress's apply_filters for this instead?
    private function do_string_transformations($functions, $original_HTML) {
        return array_reduce($functions, function($altered_HTML, $function) use ($original_HTML) {
            return $this->$function($altered_HTML, $original_HTML);
        }, $original_HTML);
    }

    public function filter_images( $content, $type = 'ratio' ) {

        if ( $this->should_not_filter_images() ) { return $content; }

        $matches = array();
        $skip_images_regex = '/class=".*lazyload.*"/';
        preg_match_all( '/<img\s+.*?>/', $content, $matches );

        $search = array();
        $replace = array();

        foreach ( $matches[0] as $imgHTML ) {

            // Don't do the replacement if a skip class is provided and the image has the class.
            if ( ! ( preg_match( $skip_images_regex, $imgHTML ) ) ) {

                $replaceHTML = $this->do_string_transformations([
                    'swap_src', 'apply_responsive_attrs', 'apply_lazyload_class', 'append_noscript'
                ], $imgHTML);

                if ( $type == 'ratio' && $this->_get_option('intrinsicRatio') != 'false' ) {
                    if ( preg_match( '/width=["|\']*(\d+)["|\']*/', $imgHTML, $width ) == 1
                        && preg_match( '/height=["|\']*(\d+)["|\']*/', $imgHTML, $height ) == 1 ) {

                        $ratioBox = '<span class="intrinsic-ratio-box';
                        if ( preg_match( '/(align(none|left|right|center))/', $imgHTML, $align_class ) == 1 ) {
                            $ratioBox .= ' ' . $align_class[0];
                            $replaceHTML = str_replace( $align_class[0], '', $replaceHTML );
                        }
                        if ( $this->_get_option( 'intrinsicRatio' ) == 'animated' ) {
                            $ratioBox .= ' lazyload" data-expand="-1';
                        }

                        $ratioBox .= '" style="max-width: ' . $width[1] . 'px; max-height: ' . $height[1] . 'px;';

                        $ratioBox .= '"><span class="intrinsic-ratio-helper" style="padding-bottom: ';
                        $replaceHTML = $ratioBox . (($height[1] / $width[1]) * 100) . '%;"></span>'
                            . $replaceHTML . '</span>';
                    }
                }

                array_push( $search, $imgHTML );
                array_push( $replace, $replaceHTML );
            }
        }

        $content = str_replace( $search, $replace, $content );

        return $content;
    }

    function get_url( $path = '' ) {

        return plugins_url( ltrim( $path, '/' ), __FILE__ );
    }

    private function _add_class( $htmlString = '', $newClass ) {

        $pattern = '/class="([^"]*)"/';

        // Class attribute set.
        if ( preg_match( $pattern, $htmlString, $matches ) ) {
            $definedClasses = explode( ' ', $matches[1] );
            if ( ! in_array( $newClass, $definedClasses ) ) {
                $definedClasses[] = $newClass;
                $htmlString = str_replace(
                    $matches[0],
                    sprintf( 'class="%s"', implode( ' ', $definedClasses ) ),
                    $htmlString
                );
            }
        // Class attribute not set.
        } else {
            $htmlString = preg_replace( '/(\<.+\s)/', sprintf( '$1class="%s" ', $newClass ), $htmlString );
        }

        return $htmlString;
    }
}

LazySizes::get_instance();

endif;
