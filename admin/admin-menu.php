<?php
//Exit if file called directly
if (! defined( 'ABSPATH' )) {
	exit;
}


// add top-level administrative menu
function WPP_admin_menu() {
	
	/* 
		add_menu_page(
			string   $page_title, 
			string   $menu_title, 
			string   $capability, 
			string   $menu_slug, 
			callable $function = '', 
			string   $icon_url = '', 
			int      $position = null 
		)
	*/
	
	add_menu_page(
		'Manage Sites',
		'Manage Sites',
		'manage_options',
		'woo-product-push',
		'WPP_admin_sites_ui',
		'dashicons-admin-generic',
		null
	);

	// add_submenu_page(
	// 	'woo-product-push', 
	// 	"Product Categories Push", 
	// 	"Product Categories Push", 
	// 	"manage_options", 
	// 	'category-push', 
	// 	"WPP_category_push"
	// );

	add_submenu_page(
		'woo-product-push', 
		"Product Push", 
		"Product Push", 
		"manage_options", 
		'product-push', 
		"WPP_product_push"
	);
	
}
add_action( 'admin_menu', 'WPP_admin_menu' );

function WPP_category_push() {
	require plugin_dir_path( __FILE__ ) . '/category-push.php';
}

function WPP_product_push() {
	require plugin_dir_path( __FILE__ ) . '/product-push.php';
}