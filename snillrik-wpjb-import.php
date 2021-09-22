<?php
/*
 * Plugin Name: Snillrik wpjb AF import
 * Plugin URI: http://snillrik.se
 * Description: An importer plugin for wpjoboard for importning jobs from swedish Arbetsförmedlingen
 * Version: 0.1
 * Author: Mattias Kallio
 * Author URI: http://snillrik.se
 * Text Domain: snillrik-wpjb-import
 * Domain Path: /languages/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

DEFINE("SNIMP_PLUGIN_URL", plugin_dir_url(__FILE__));
DEFINE("SNIMP_DIR", plugin_dir_path(__FILE__));

require_once SNIMP_DIR . 'settings.php';
require_once SNIMP_DIR . 'classes/af-api.php';
require_once SNIMP_DIR . 'classes/importpage.php';

new SNAF_Import();
new SNAF_Settings();
new SNAF_API();
//SNAF_API::get_occupations();

/**
 * Adding scripts
 */
function snillrik_maps_add_admin_scripts(){
    wp_enqueue_style('snillrik-imp-admin-main', SNIMP_PLUGIN_URL . 'css/main.css');
    wp_enqueue_script('snillrik-imp-admin-script', SNIMP_PLUGIN_URL . 'js/snillrik_wpjb_af_import-main.js', array('jquery'));
    wp_localize_script('snillrik-imp-admin-script', 'snillrik_impadmin', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('admin_enqueue_scripts', 'snillrik_maps_add_admin_scripts');

/**
 * Load plugin textdomain.
 */
function wpdocs_load_textdomain() {
  load_plugin_textdomain( 'snillrik-wpjb-import', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'wpdocs_load_textdomain' );