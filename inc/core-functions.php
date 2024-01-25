<?php // Plugin Functions
function WPP_add_website($website_url, $consumer_key, $consumer_secret, $registered_date, $editid){

    // verify website details
	$verification = WPP_website_verify($consumer_key, $consumer_secret, $website_url);
    if($verification){
        global $wpdb;
        if( is_numeric($editid) && $editid != '' ) {
            $result = $wpdb->update('WPP_websites', array(
                'website_url' => $website_url,
                'consumer_key' => $consumer_key,
                'consumer_secret'  => $consumer_secret,
                ),
                array( 'id' => $editid )
            );
        } else {
            $result = $wpdb->insert('WPP_websites', array(
                'website_url' => $website_url,
                'consumer_key' => $consumer_key,
                'consumer_secret'  => $consumer_secret,
                'register_date'  => $registered_date,
                )
            );
        }
        return $result;
    }else{
        return "Something went wrong";
    }
}

function WPP_delete_website($editid) {
    global $wpdb;
    $result = false;
    if( is_numeric($editid) && $editid != '' ) {
        $result = $wpdb->delete('WPP_websites', array( 'id' => $editid ));
    }
    return $result;
}


function WPP_website_verify($consumer_key, $consumer_secret, $base_url){
    if(isset($consumer_key) && isset($consumer_secret) && isset($base_url)){
        $api_endpoint = '/wp-json/wc/v3/products';

        $url = $base_url . $api_endpoint;

        $response = wp_remote_get(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                ),
            )
        );

        // Check if the request was successful
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if($response_code === 200){
                return true;
            }
        } else {
            return false;
        }
    }else{
        return false;
    }   
}

add_action('wp_ajax_WPP_push_categories', 'WPP_ajax_push_categories');
function WPP_ajax_push_categories(){
   if(isset($_POST['id']) && $_POST['id'] !== ''){
        $website_id = $_POST['id'];

        global $wpdb;
        $website_data = $wpdb->get_results( "SELECT * FROM WPP_websites where id = $website_id");
        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $category_endpoint = '/wp-json/wc/v3/products/categories';
            $category_batch_endpoint = '/wp-json/wc/v3/products/categories/batch';
            $category_url = $website_url . $category_endpoint;

            // for fetching product categories
            if(isset($_POST['action_type']) && $_POST['action_type'] == 'fetch_categories'){
                $ch = curl_init($category_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode("$consumer_key:$consumer_secret"),
                ));

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if (!curl_errno($ch) && $http_code === 200) {
                    $decoded_response = json_decode($response, true);
                    $data = array(
                        'categories' => $decoded_response,
                        'action_type' => 'fetch_categories'
                    );
                    echo wp_send_json($data);
                }
            }

            // for pushing product categories
            if(isset($_POST['action_type']) && $_POST['action_type'] == 'push_categories'){
                $category_url = $website_url . $category_batch_endpoint;
                $category_data = [
                    'create' => array()
                ];
                $product_parent_categories = get_terms(array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => 0         
                ));

                $product_parent_categories = get_terms(array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => 0         
                ));

                if (!empty($product_parent_categories) && !is_wp_error($product_parent_categories)) {
                    foreach ($product_parent_categories as $category) {
                        $category_data['create'][] = array(
                            'name' => $category->name,
                            'slug' => $category->slug,
                        );
                    }
                }

                // print_r($category_data);
                // die();

                $post_data = json_encode($category_data);
                $ch = curl_init($category_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode("$consumer_key:$consumer_secret"),
                ));

                $response = curl_exec($ch);
                if (!curl_errno($ch)) {
                    $decoded_response = json_decode($response, true);
                    $data = array(
                        'result' => $decoded_response,
                        'action_type' => 'push_categories'
                    );
                    echo wp_send_json($data);
                }

                // curl_close($ch);
            }
            
        }
   }
    die;
}


add_action('wp_ajax_testing', 'testing_func');
function testing_func(){
    $product_categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,         
    ));

    echo "<pre>";
    print_r($product_categories);
    die();
}