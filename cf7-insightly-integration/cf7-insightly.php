<?php
/*
Plugin Name: Contact Form 7 - insightly CRM Integration
Description: Contact Form 7 - insightly CRM Integration plugin allows you to connect WordPress Contact Form 7 and insightly CRM.
Version:     2.3.2
Author:      jyoti
Author URI:  https://obtaincode.net/
License:     GPL2
Text Domain: cf7_insightly
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

/*
 * This is a constant variable for plugin path.
 */
define( 'CF7_INSIGHTLY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/*
 * This is a file for includes core functionality.
 */
include_once CF7_INSIGHTLY_PLUGIN_PATH . 'includes/includes.php';
