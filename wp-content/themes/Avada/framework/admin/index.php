<?php
/*
Title		: SMOF
Description	: Slightly Modified Options Framework
Version		: 1.5.1
Author		: Syamil MJ
Author URI	: http://aquagraphite.com
License		: GPLv3 - http://www.gnu.org/copyleft/gpl.html

Credits		: Thematic Options Panel - http://wptheming.com/2010/11/thematic-options-panel-v2/
		 	  Woo Themes - http://woothemes.com/
		 	  Option Tree - http://wordpress.org/extend/plugins/option-tree/

Contributors: Syamil MJ - http://aquagraphite.com
			  Andrei Surdu - http://smartik.ws/
			  Jonah Dahlquist - http://nucleussystems.com/
			  partnuz - https://github.com/partnuz
			  Alex Poslavsky - https://github.com/plovs
			  Dovy Paukstys - http://simplerain.com
*/

/**
 * Definitions
 *
 * @since 1.4.0
 */
$theme_version = '';
$smof_output = '';

if( function_exists( 'wp_get_theme' ) ) {
	if( is_child_theme() ) {
		$temp_obj = wp_get_theme();
		$theme_obj = wp_get_theme( $temp_obj->get('Template') );
	} else {
		$theme_obj = wp_get_theme();
	}

	$theme_version = $theme_obj->get('Version');
	$theme_name = $theme_obj->get('Name');
	$theme_uri = $theme_obj->get('ThemeURI');
	$author_uri = $theme_obj->get('AuthorURI');
}


define( 'SMOF_VERSION', '1.5.1' );

if( !defined('ADMIN_PATH') )
	define( 'ADMIN_PATH', get_template_directory() . '/framework/admin/' );
if( !defined('ADMIN_DIR') )
	define( 'ADMIN_DIR', get_template_directory_uri() . '/framework/admin/' );

define( 'ADMIN_IMAGES', ADMIN_DIR . 'assets/images/' );

define( 'LAYOUT_PATH', ADMIN_PATH . 'layouts/' );
define( 'THEMENAME', $theme_name );
/* Theme version, uri, and the author uri are not completely necessary, but may be helpful in adding functionality */
define( 'THEMEVERSION', $theme_version );
define( 'THEMEURI', $theme_uri );
define( 'THEMEAUTHORURI', $author_uri );

// Avada Edit
$lang = '';
if ( function_exists( 'pll_define_wpml_constants' ) ) {
	pll_define_wpml_constants();
}
if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		global $sitepress;
	}

	if ( function_exists( 'pll_default_language' ) ) {
		global $polylang;
		$pll_languages_obj = $polylang->model->get_languages_list();
		$pll_languages = array();

		foreach ( $pll_languages_obj as $pll_language_obj ) {
			$pll_languages[] = $pll_language_obj->slug;
		}
	}

	if ( ICL_LANGUAGE_CODE ) {
		$language_exists = true;
		if ( function_exists( 'pll_default_language' ) ) {
			if ( ! in_array( ICL_LANGUAGE_CODE, $pll_languages ) ) {
				$language_exists = false;
			}
		} else {
			if ( ! array_key_exists( ICL_LANGUAGE_CODE, icl_get_languages( 'skip_missing=0' ) ) ) {
				$language_exists = false;
			}
		}
		if ( ! in_array( ICL_LANGUAGE_CODE, array( 'en', 'all' ) ) && $language_exists ) {
			$lang = '_' . ICL_LANGUAGE_CODE;
			if ( ! get_option( $theme_name . '_options' . $lang ) ) {
				update_option( $theme_name . '_options' . $lang, get_option( $theme_name . '_options' ) );
			}
		} elseif ( ! in_array( ICL_LANGUAGE_CODE, array( 'en', 'all' ) ) && function_exists( 'pll_default_language' ) ) {
			if ( in_array( ICL_LANGUAGE_CODE, $pll_languages ) ) {
				$lang = '_' . ICL_LANGUAGE_CODE;
				if ( ! get_option( $theme_name . '_options' . $lang ) ) {
					update_option( $theme_name . '_options' . $lang, get_option( $theme_name . '_options' ) );
				}
			}
		} elseif ( 'all' == ICL_LANGUAGE_CODE  ) {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				$lang = '_' . $sitepress->get_default_language();
				if ( 'en' == $sitepress->get_default_language() ) {
					$lang = '';
				}
			} elseif ( function_exists( 'pll_default_language' ) ) {
				$lang = '_' . pll_default_language( 'slug' );
				if ( 'en' == pll_default_language('slug') ) {
					$lang = '';
				}
			}
		}		
	}
}
// End Avada Edit

define( 'OPTIONS', $theme_name . '_options' . $lang );
define( 'BACKUPS', $theme_name . '_backups' . $lang );

/**
 * Required action filters
 *
 * @uses add_action()
 *
 * @since 1.0.0
 */
//if (is_admin() && isset($_GET['activated'] ) && $pagenow == "themes.php" ) add_action('admin_head','of_option_setup');
add_action('admin_head', 'optionsframework_admin_message');
add_action('admin_init','optionsframework_admin_init');
add_action('admin_menu', 'optionsframework_add_admin');

/**
 * Required Files
 *
 * @since 1.0.0
 */
require_once ( ADMIN_PATH . 'functions/functions.load.php' );
require_once ( ADMIN_PATH . 'classes/class.options_machine.php' );

/**
 * AJAX Saving Options
 *
 * @since 1.0.0
 */
add_action('wp_ajax_of_ajax_post_action', 'of_ajax_callback');
