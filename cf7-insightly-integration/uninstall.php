<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'restricted access' );
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

/*
 * Deleted options when plugin uninstall.
 */
delete_option( 'cf7_insightly' );
delete_option( 'cf7_insightly_client_id' );
delete_option( 'cf7_insightly_client_secret' );
delete_option( 'cf7_insightly_modules' );
delete_option( 'cf7_insightly_modules_fields' );