<style>

    ol{
        text-transform: capitalize;
    }

</style>

<?php
//Exit if file called directly
if (! defined( 'ABSPATH' )) {
	exit;
}
global $wpdb;
$table_name = $wpdb->prefix . 'WPP_websites';
$websites = $wpdb->get_results( "SELECT * FROM $table_name");


if(isset($_GET['website_id']) && $_GET['website_id'] !== ""){
    $website_id = $_GET['website_id'];
    $website_url= $wpdb->get_results( "SELECT website_url FROM $table_name where id = $website_id");
    $website_data = $wpdb->get_results( "SELECT * FROM $table_name where id = $website_id");
    ?>
    <div>
        <h3>Website : <a id="website_url" data-id="<?= $website_id?>" href="<?php echo $website_url[0]->website_url?>"><?php echo $website_url[0]->website_url?></a></h3>
        <!-- <button class="button ajax-action-btn" id="" data-id="<?php echo $website_id ?>">Push All Products</button>
        <button class="button ajax-action-btn" id="fetch_products" data-id="<?php echo $website_id ?>">Fetch Products</button>
        <button class="button clear-data">Clear</button>
        <button class="button reload">Reload</button> -->

        <nav class="nav-tab-wrapper wpp-nav-tab-wrapper">
			<a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=1')?>" class="nav-tab nav-tab-active">Simple Products</a>
            <a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=2');?>" class="nav-tab ">Variable Products</a>
            <a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=3');?>" class="nav-tab ">SKU Missing Products</a>
        </nav>

        <div class="result">

        <?php 

        if(!empty($website_data )){
            $consumer_key = $website_data[0]->consumer_key;
            $consumer_secret = $website_data[0]->consumer_secret;
            $website_url = $website_data[0]->website_url;
            $category_endpoint = '/wp-json/wc/v3/products/categories';
            $category_url = $website_url . $category_endpoint . '?per_page=100&parent=0';

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
            }
        }
        
        ?>

        <div style="margin-top: 20px;">
            <select name="" id="website-category">
                <option value="">--Select Category--</option>
                <?php if(!empty($decoded_response)): ?>
                    <?php foreach($decoded_response as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                    <?php endforeach; ?>
                <?php endif;?>
            </select>
            <img class="cat-spinnner" style="display: none;" src="<?= get_admin_url() . 'images/spinner.gif'  ?>" alt="">
        </div>


        <?php 

        if(isset($_GET['tab']) && $_GET['tab'] == '1'){
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => 100,
                'type' => 'simple',
            ));

            if(!empty($products)){
                echo "<ol class='product_list' data-website_id='".$website_id."'>";
                foreach ($products as $product) {
                    $sku = $product->get_sku();
                    if (!empty($sku)) {
                        echo "<li data-id='" . $product->get_id() . "' data-name='" . $product->name . "' data-slug='" . $product->slug . "'>".$product->name.  ": <span class='push_product button button-success'>Sync</span></li>";
                    }
                }
                echo "</ol>";
            }

        }else if(isset($_GET['tab']) && $_GET['tab'] == '2'){
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => 100,
                'type' => 'variable',
            ));

            if(!empty($products)){
                echo "<ol class='product_list' data-website_id='".$website_id."'>";
                foreach ($products as $product) {
                    echo "<li data-id='" . $product->get_id() . "' data-name='" . $product->name . "' data-slug='" . $product->slug . "'>".$product->name.  ": <span class='push_product button button-success'>Sync</span></li>";
                }
                echo "</ol>";
            }
            
        }else if(isset($_GET['tab']) && $_GET['tab'] == '3'){
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => 100,
                'type' => 'simple',
            ));

            if(!empty($products)){
                echo "<ol class='product_list' data-website_id='".$website_id."'>";
                foreach ($products as $product) {
                    $sku = $product->get_sku();
                    if (empty($sku)) {
                        echo "<li data-id='" . $product->get_id() . "' data-name='" . $product->name . "' data-slug='" . $product->slug . "'>".$product->name.  "</li>";
                    }
                }
                echo "</ol>";
            }
        }

        


            
        ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function(){

            // push single product
            jQuery(document).on('click', '.push_product', function(e){
                e.preventDefault();
                let id = jQuery(this).parent().data('id');
                let name = jQuery(this).parent().data('name');
                let slug = jQuery(this).parent().data('slug');
                let website_id = jQuery("#website_url").data('id')
                let $this = jQuery(this);
                let website_categories = [];
                let website_category = jQuery('#website-category').val();
                let website_child_category = jQuery('#website-child-category').val();
                if(website_category == ""){
                    alert("Please select category, where want to push product")
                    return;
                }
                website_categories.push(website_category)
                if(website_child_category !== "" && website_child_category !== "--Select Child Cat--"){
                    website_categories.push(website_child_category)
                }
                jQuery(this).addClass('updating-message disabled')
                jQuery(this).removeClass('push_product')
                jQuery(this).text('Syncing...')
                jQuery.ajax({
                    url: ajaxurl, 
                    type: 'POST',
                    data: {
                        action: 'WPP_push_product',
                        website_id: website_id,
                        name: name,
                        slug: slug,
                        id: id,
                        cat_id: website_categories
                    },
                    success: function(response){
                        console.log(response);
                        if(response !== ""){
                            $this.removeClass('updating-message')
                            const result = response;
                            if(result.hasOwnProperty('message')){
                                $this.text(result.code)
                            }else{
                                $this.text("Success")
                            }
                        }
                    }
                })
            })

            // select category
            jQuery(document).on('change', '#website-category', function(){
                jQuery('.cat-spinnner').show()
                jQuery('#website-child-category').remove()
                let parent_cat_id = jQuery(this).val()
                let website_id = jQuery("#website_url").data('id')
                let $this = jQuery(this);
                if(parent_cat_id){
                    jQuery.ajax({
                        url: ajaxurl, 
                        type: 'POST',
                        data: {
                            action: 'WPP_get_child_cats',
                            cat_id: parent_cat_id,
                            website_id: website_id,
                        },
                        success: function(response){
                            console.log(response);
                            if(response !== ""){
                                let child_cats = response.map((item, index) => {
                                    return "<option value='"+item.id+"'>" + item.name + "</option>";
                                })
                                jQuery('.cat-spinnner').hide()
                                if(child_cats.length !== 0){
                                    let child_cat_html = "<select id='website-child-category'><option>--Select Child Cat--</option></select>";
                                    $this.after("<select id='website-child-category'><option>--Select Child Cat--</option>"+ child_cats + "</select>")
                                }
                               
                            }
                        }
                    })
                }
                
            })
        })
    </script>

    <?php
}else{
    global $wpdb;
    $table_name = $wpdb->prefix . 'WPP_websites';
    $websites = $wpdb->get_results( "SELECT * FROM $table_name");

    if(!empty($websites)): ?>
    <section>
        <h2>Product Push in Website</h2>
        <form action="admin.php?page=product-push" method="get">
        <input type="hidden" name="page" value="product-push">
            <label for="">Select Website</label>
            <select name="website_id" id="">
                <option value="">-- Select Website -- </option>
            <?php foreach ($websites as $website): ?>
                <option value="<?= $website->id?>"><?= $website->website_url?></option> 
            <?php endforeach; ?>
            </select>
            <input type="hidden" name="tab" value="1" />
            <input type="submit" value="Proceed" class="btn button">
        </form>
    <section>
    <?php endif; ?>
    <?php
}

?>
<script>
    jQuery(document).ready(function(){
        var currentURL = window.location.href;
        var urlParams = new URLSearchParams(window.location.search);
        var tabValue = urlParams.get('tab');

        let tabs = jQuery('.wpp-nav-tab-wrapper a');
        tabs.each(function() {
            var tabHref = jQuery(this).attr('href');
            if (tabHref === currentURL) {
                jQuery('.wpp-nav-tab-wrapper a').removeClass('nav-tab-active')
                jQuery(this).addClass('nav-tab-active');
            }
        });
    })
</script>

<?php

