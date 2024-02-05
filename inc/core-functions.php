<?php // Plugin Functions
function WPP_add_website($website_url, $consumer_key, $consumer_secret, $registered_date, $editid){

    // verify website details
	$verification = WPP_website_verify($consumer_key, $consumer_secret, $website_url);
    if($verification){
        global $wpdb;
        $table_name = $wpdb->prefix . 'WPP_websites';
        if( is_numeric($editid) && $editid != '' ) {
            $result = $wpdb->update( $table_name, array(
                'website_url' => $website_url,
                'consumer_key' => $consumer_key,
                'consumer_secret'  => $consumer_secret,
                ),
                array( 'id' => $editid )
            );
        } else {
            $result = $wpdb->insert( $table_name, array(
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
    $table_name = $wpdb->prefix . 'WPP_websites';
    $result = false;
    if( is_numeric($editid) && $editid != '' ) {
        $result = $wpdb->delete( $table_name, array( 'id' => $editid ));
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

// push categories
add_action('wp_ajax_WPP_push_categories', 'WPP_ajax_push_categories');
function WPP_ajax_push_categories(){
   if(isset($_POST['id']) && $_POST['id'] !== ''){
        $website_id = $_POST['id'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'WPP_websites';
        $website_data = $wpdb->get_results( "SELECT * FROM $table_name  where id = $website_id");
        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $category_endpoint = '/wp-json/wc/v3/products/categories?per_page=99';
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

                if (!empty($product_parent_categories) && !is_wp_error($product_parent_categories)) {
                    foreach ($product_parent_categories as $category) {
                        $category_data['create'][] = array(
                            'name' => $category->name,
                            'slug' => $category->slug,
                        );
                    }
                }

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
                        'action_type' => 'push_categories',
                        'category_type' => 'parent'
                    );
                    echo wp_send_json($data);
                }

                // curl_close($ch);
            }
            
        }
   }
    die;
}

// push category
add_action('wp_ajax_WPP_push_category', 'WPP_push_category');
function WPP_push_category(){
   if(isset($_POST['slug']) && $_POST['slug'] !== '' && isset($_POST['website_id']) && $_POST['website_id'] !== ''){
        $website_id = $_POST['website_id'];
        $slug = $_POST['slug'];
        $name = $_POST['name'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'WPP_websites';
        $website_data = $wpdb->get_results( "SELECT * FROM  $table_name  where id = $website_id");
        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $category_endpoint = '/wp-json/wc/v3/products/categories';
            
            $category_url = $website_url . $category_endpoint;

            $category_data = [
                'name' => $name,
                'slug' => $slug,
            ];


            // if (!empty($product_parent_categories) && !is_wp_error($product_parent_categories)) {
            //     foreach ($product_parent_categories as $category) {
            //         $category_data['create'][] = array(
            //             'name' => $category->name,
            //             'slug' => $category->slug,
            //         );
            //     }
            // }

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
                    // 'action_type' => 'push_categories',
                    // 'category_type' => 'parent'
                );
                echo wp_send_json($data);
            }
            
        }
   }
    die;
}

// push child categories ajax
add_action('wp_ajax_WPP_push_child_categories', 'WPP_push_child_categories');
function WPP_push_child_categories(){
    if(isset($_POST['id']) && $_POST['id'] !== ''){
        $website_id = $_POST['id'];
        $index = (int)$_POST['index'];
        $parent_categories = $_POST['parent_categories'];

        if(!empty($parent_categories)){
            global $wpdb;
            $table_name = $wpdb->prefix . 'WPP_websites';
            $website_data = $wpdb->get_results( "SELECT * FROM $table_name where id = $website_id");
            if(!empty($website_data )){
                $consumer_key = $website_data[0]->consumer_key;
                $consumer_secret = $website_data[0]->consumer_secret;
                $website_url = $website_data[0]->website_url;
                $category_endpoint = '/wp-json/wc/v3/products/categories';
                $category_batch_endpoint = '/wp-json/wc/v3/products/categories/batch';
                $category_url = $website_url . $category_batch_endpoint;
                $category_data = [
                    'create' => array()
                ];

                $parent_category_slug = $parent_categories[$index]['slug']; 
                $parent_category = get_term_by('slug', $parent_category_slug, 'product_cat');

                $product_child_categories = get_terms(array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => $parent_category->term_id        
                ));

                if (!empty($product_child_categories) && !is_wp_error($product_child_categories)) {
                    foreach ($product_child_categories as $category) {
                        $category_data['create'][] = array(
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'parent' => $parent_categories[$index]['id']
                        );
                    }

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
                            'index' => $index+1,
                        );
                        echo wp_send_json($data);
                    }
                }else{
                    $data = array(
                        'result' => $product_child_categories,
                        'index' => $index+1,
                    );
                    echo wp_send_json($data);
                }

                

                
            }
        }
    }

    die();
}


// push products
add_action('wp_ajax_WPP_push_products', 'WPP_ajax_push_products');
function WPP_ajax_push_products(){
   if(isset($_POST['id']) && $_POST['id'] !== ''){
        $website_id = $_POST['id'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'WPP_websites';
        $website_data = $wpdb->get_results( "SELECT * FROM $table_name where id = $website_id");
        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $product_endpoint = '/wp-json/wc/v3/products';
            $product_batch_endpoint = '/wp-json/wc/v3/products/batch';
            $product_url = $website_url . $product_endpoint;

            // for fetching products
            if(isset($_POST['action_type']) && $_POST['action_type'] == 'fetch_products'){
                $ch = curl_init($product_url);
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
                        'products' => $decoded_response,
                        'action_type' => 'fetch_products'
                    );
                    echo wp_send_json($data);
                }
            }

            // for pushing products
            if(isset($_POST['action_type']) && $_POST['action_type'] == 'push_products'){
                $product_url = $website_url . $product_batch_endpoint;
                $product_data = [
                    'create' => array()
                ];
                
                $products = wc_get_products(array(
                    'status' => 'publish',
                    'limit' => 1,
                    'type' => 'simple',
                ));

                if (!empty($products) && !is_wp_error($products)) {
                    // echo "<pre>";
                    // print_r($products);
                    foreach ($products as $product) {
                        $product_cats = $product->category_ids;
                        $cats = [];
                        $images = array(
                            [
                                'src' => 'https://www.adilqadri.com/cdn/shop/files/Shanaya12ml.5.jpg'
                            ],
                            [
                                'src' => 'https://www.adilqadri.com/cdn/shop/files/aaShanaya12ml.2.jpg'
                            ],
                            [
                                'src' => 'https://www.adilqadri.com/cdn/shop/products/shanayanewwwaaa.jpg'
                            ],
                        );
                        foreach ($product_cats as $category) {
                            $cats[] = array('id' => $category);
                        }
                        $post = wc_get_product( $product->ID );
                        $product_data['create'][] = array(
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'sku' => $product->sku,
                            'type' => 'simple',
                            'regular_price' => $product->regular_price,
                            'sale_price' => $product->sale_price,
                            'description' => $product->description,
                            'short_description' => $product->short_description,
                            'manage_stock' => $product->manage_stock,
                            'stock_quantity' => $product->stock_quantity,
                            'stock_status' => $product->stock_status,
                            'backorders' => $product->backorders,
                            'weight' => $product->weight,
                            'length' => $product->length,
                            'height' => $product->height,
                            'categories' => $cats,
                            'images' => $images
                        );
                    }

                    // echo "<pre>";
                    // print_r($product_data);
                    // die;
                }

                $post_data = json_encode($product_data);
                $ch = curl_init($product_url);
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
                        'action_type' => 'push_products',
                        'product_type' => 'simple'
                    );
                    echo wp_send_json($data);
                }

                // curl_close($ch);
            }
            
        }
   }
    die;
}


// push single product
add_action('wp_ajax_WPP_push_product', 'WPP_ajax_push_product');
function WPP_ajax_push_product(){
   if(isset($_POST['id']) && $_POST['id'] !== ''){
        $product_id = $_POST['id'];
        $website_id = $_POST['website_id'];
        $slug = $_POST['slug'];
        $name = $_POST['name'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'WPP_websites';
        $website_data = $wpdb->get_results( "SELECT * FROM $table_name where id = $website_id");
        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $product_endpoint = '/wp-json/wc/v3/products';
            $product_url = $website_url . $product_endpoint;
            $product = wc_get_product( $product_id );
            $product_cats = $product->category_ids;
            $images = array(
                [
                    'src' => 'https://www.adilqadri.com/cdn/shop/files/Shanaya12ml.5.jpg'
                ],
            );
            foreach ($product_cats as $category) {
                $cats[] = array('id' => $category);
            }

            $product_data = [
                'name' => $product->get_name(),
                'slug' => $product->get_slug(),
                'sku' => $product->get_sku(),
                'type' => $product->get_type(), // This will return 'simple' or 'variable'
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'description' => $product->get_description(),
                'short_description' => $product->get_short_description(),
                'stock_quantity' => $product->get_stock_quantity(),
                'stock_status' => $product->get_stock_status(),
                'backorders' => $product->get_backorders(),
                'weight' => $product->get_weight(),
                'length' => $product->get_length(),
                'height' => $product->get_height(),
                'categories' => $cats,
                'images' => $images
            ];

            // Include variation data for variable products
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                if (!empty($variations)) {
                    $variation_data = [];
                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        $variation_data[] = [
                            'variation_id' => $variation_id,
                            'regular_price' => $variation->get_regular_price(),
                            'sale_price' => $variation->get_sale_price(),
                            'stock_quantity' => $variation->get_stock_quantity(),
                            'attributes' => $variation->get_variation_attributes(),
                        ];
                    }
                    $product_data['variations'] = $variation_data;
                }
            }

            $post_data = json_encode($product_data);
            $ch = curl_init($product_url);
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
                );
                echo wp_send_json($data);
            }
            
        }
   }
    die;
}


add_action('wp_ajax_testing', 'testing_func');
function testing_func(){
    $website_url = 'https://woo.wordpressdeveloperindia.com';
    $category_endpoint = '/wp-json/wc/v3/products/categories?per_page=99';
    $category_url = $website_url . $category_endpoint;
    $consumer_secret = 'cs_fabe68c84e3bb2a9f04eacea71b3cdcb74e117c8';
    $consumer_key = 'ck_14d8e0ad8861046b5c09f920697b4931156b3f6a';

    $ch = curl_init($category_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$consumer_key:$consumer_secret"),
    ));

    $response = curl_exec($ch);

    echo "<pre>";
    print_r($response);
    die();
}