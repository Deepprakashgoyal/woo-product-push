<?php
/**
 * Plugin Name: Woo Product Push
 * version: 1.0
 * Author: Deep P. Goyal
 * Author URI: https://wpexpertdeep.com
 * Description: This is a custom plugin to push product in woocommerce websites
 */


 if (! defined( 'ABSPATH' )) {
	exit;
}


function WPP_plugin_styles_scripts() {
    wp_register_style('dataTable-css', plugin_dir_url(__FILE__).'assets/css/jquery.dataTables.css');
    wp_enqueue_style('dataTable-css');
    wp_register_script( 'dataTable-js', plugin_dir_url(__FILE__).'assets/js/jquery.dataTables.js');
    wp_enqueue_script('dataTable-js');
}
add_action('admin_enqueue_scripts', 'WPP_plugin_styles_scripts');

function WPP_include_bs_datatables() {
	wp_enqueue_script('jquery');
    wp_enqueue_style( 'datepicker-css', plugin_dir_url(__FILE__).'assets/css/jquery-ui.css' );
    //wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script( 'jquery-ui-datepicker' );//, plugin_dir_url(__FILE__).'assets/js/datepicker.js' );
	wp_enqueue_script( 'admin-bs', plugin_dir_url(__FILE__).'assets/js/bootstrap.min.js' );
    wp_enqueue_style( 'admin-css', plugin_dir_url(__FILE__).'assets/css/bootstrap.min.css' );
}

if( isset($_GET['page']) && $_GET['page'] == 'woo-product-push' ){
	add_action('admin_enqueue_scripts', 'WPP_include_bs_datatables');
}

function WPP_include_bootsrap(){ ?>
	<style type="text/css">
		.cf-search {
		    width: 700px;
		    margin: 50px auto !important;
		    background: #f7f8fd;
		    border: 3px solid #eceefb;
		    padding: 30px;
		    border-radius: 10px;
		}
		.cf-search form {
		    display: inline-flex;
    		width: 100%;
		}
		.cf-field {
			display: inline-block !important;
		    border: 1px solid #000 !important;
		    margin-bottom: 0px !important;
		    width: 90%;
		    padding-left: 16px;
		    height: 47px;
		}
		.cf-btn {
			display: inline-block;
			border: none;
		    height: 47px !important;
		    width: 200px;
		    background: #000 !important;
		    color: #fff !important;
	        min-height: 47px;
    		border-radius: 0 !important;
		}
		.success {
			color: #155724;
		    background-color: #d4edda;
		    position: relative;
		    padding: .75rem 1.25rem;
		    margin-bottom: 1rem;
		    border: 1px solid #c3e6cb;
		    border-radius: .25rem;
		}
		.danger {
		    color: #721c24;
		    background-color: #f8d7da;
	        position: relative;
		    padding: .75rem 1.25rem;
		    margin-bottom: 1rem;
		    border: 1px solid #f5c6cb;
		    border-radius: .25rem;
		}

		@media screen and ( max-width: 768px ){
			.cf-search{ width: 90%; }
		}
		@media screen and ( max-width: 480px ){
			.cf-search form { display: initial; }
			.cf-field, .cf-btn {
				display: block !important;
				width: 100%;
			}
		}
	</style>
<?php }
add_action('wp_head', 'WPP_include_bootsrap');


if ( is_admin() ) {

    // Include dependencies
    require_once plugin_dir_path( __file__ ).'install.php';
    require_once plugin_dir_path( __file__ ).'uninstall.php';
    require_once plugin_dir_path( __file__ ).'inc/core-functions.php';
    require_once plugin_dir_path( __file__ ).'admin/admin-menu.php'; 
    require_once plugin_dir_path( __file__ ).'admin/settings-page.php';
}

register_activation_hook( __FILE__, 'WPP_onActivation' );
// register_deactivation_hook( __FILE__, 'course_certificate_segwitz_certificate_onDeactivation' );


function connected_sites_func(){

    global $wpdb;
    $table_name = 'WPP_websites';

    $sql_query = "SELECT * FROM $table_name";

    // Run the query
    $results = $wpdb->get_results($sql_query);

    print_r($results);

    echo '<div class="wrap"><h2>Woocommerce Connected Websites</h2>';

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Website</th><th>key</th><th>secret</th><th>status</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $result) {
        echo '<tr>';
        echo '<td> '.$result->website_url.'</td>';
        echo '<td> '.$result->consumer_key.'</td>';
        echo '<td> '.$result->consumer_secret.'</td>';
        echo '<td> '.$result->status.'</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}






add_action('wp_ajax_product_insert', 'product_insert');
function product_insert(){
    $key = 'ck_33bf8ea95638955a3d94ffb693a7cfe9838c633a';
    $secret = 'cs_b851411a2392cc1cd2d8122d58979c7a444fb372';

    $ch = curl_init('https://woo.wordpressdeveloperindia.com/wp-json/wc/v3/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_encoded);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode("$key:$secret"),
    ));

    // Execute cURL session
    $response = curl_exec($ch);

    // $credentials = base64_encode("$key:$secret");
    // $headers = array(
    //     'Authorization: Basic ' . $credentials,
    //     'Content-Type: application/json', // Add any other headers you need
    // );

    // $response = wp_remote_post( 
    //     'https://woo.wordpressdeveloperindia.com/wp-json/wc/v3/products', 
    //     array(
    //         'method'=> 'POST',
    //          'headers' => $headers,
    //         'body' => array(
    //             'name' => 'My test product', // product title
    //             'status' => 'draft', // product status, default: publish
    //             'regular_price' => '9.99' // product price
    //         )
    //     )
    // );

    echo "<pre>";
    print_r($response);


    die();
}


