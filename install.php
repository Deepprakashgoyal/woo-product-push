<?php
//Exit if file called directly
if (! defined( 'ABSPATH' )) { 
    exit; 
}


function WPP_onActivation(){
	global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `WPP_websites` (
        id INTEGER NOT NULL AUTO_INCREMENT,
        website_url TEXT NOT NULL,
        consumer_key TEXT NOT NULL,
        consumer_secret TEXT NOT NULL,
        status TEXT NOT NULL,
        PRIMARY KEY (id)
    )$charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_table_query );
}