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
    ?>
    <div>
        <h3>Website : <a id="website_url" href="<?php echo $website_url[0]->website_url?>"><?php echo $website_url[0]->website_url?></a></h3>
        <!-- <button class="button ajax-action-btn" id="" data-id="<?php echo $website_id ?>">Push All Products</button>
        <button class="button ajax-action-btn" id="fetch_products" data-id="<?php echo $website_id ?>">Fetch Products</button>
        <button class="button clear-data">Clear</button>
        <button class="button reload">Reload</button> -->

        <nav class="nav-tab-wrapper wpp-nav-tab-wrapper">
			<a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=simple')?>" class="nav-tab nav-tab-active">Simple Products</a>
			<a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=sku-missing');?>" class="nav-tab ">SKU Missing Products</a>
            <a href="<?php echo  admin_url('admin.php?page=product-push&website_id='.$website_id.'&tab=variable');?>" class="nav-tab ">Variable Products</a>
        </nav>

        <div class="result">
        <?php 

        if(isset($_GET['tab']) && $_GET['tab'] == 'simple'){
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

        }else if(isset($_GET['tab']) && $_GET['tab'] == 'variable'){
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
            
        }else if(isset($_GET['tab']) && $_GET['tab'] == 'sku-missing'){
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
                        echo "<li data-id='" . $product->get_id() . "' data-name='" . $product->name . "' data-slug='" . $product->slug . "'>".$product->name.  ": <span class='push_product button button-success'>Sync</span></li>";
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

            jQuery('.wpp-nav-tab-wrapper')

            jQuery(document).on('click', '.ajax-action-btn', function(){
                let website_id = jQuery(this).data('id');
                let action_type = jQuery(this).attr('id')
                let website_url = jQuery('#website_url').attr('href')
                $this = jQuery(this);
                jQuery(this).addClass('disabled')
                jQuery('.result').empty();
                jQuery.ajax({
                    url: ajaxurl, // WordPress AJAX handler
                    type: 'POST',
                    data: {
                        action: 'WPP_push_products',
                        id: website_id,
                        action_type: action_type
                    },
                    success: function(response) {
                        $this.removeClass('disabled')
                        if(response !== "" && response.action_type == 'fetch_products'){
                            let products = response.products;
                            if(products != ""){
                                let data = products.map((item) => {
                                    return `<li>Fetched: <a target="_blank" href="${website_url}/product/${item.slug}">${item.name}</a> </li>`;
                                })
                                jQuery('.result').html(`<h3>Fetched Products</h3><ol>${data.join('')}</ol>`)
                            }else{
                                jQuery('.result').html(`<h3>No Product Found</h3>`)
                            }
                            
                        }

                        if(response !== "" && response.action_type == 'push_products'){
                            const result = response.result;
                            let created_products = result.create.map((item) => {
                                return item.id != 0 ? {'id' : item.id, 'name' : item.name, 'slug': item.slug} : null;
                            }).filter(item => item !== null)

                            if (created_products.length > 0) {
                                let itemData = created_products.map(item => {
                                    return `<li data-id="${item.id}"> Created: <a target="_blank" data-slug="${item.slug}" href="${website_url}/product/${item.slug}">${item.name}</a></li>`;
                                });

                                jQuery('.result').html(`<h3>Products Created</h3><ol>${itemData.join('')}</ol>`);

                                // pushChildCategories(website_id, 0, created_categories);
                            }else{
                                jQuery('.result').html('<h3>Something went wrong</h3>')
                            }
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                })
            })

            // push single product
            jQuery(document).on('click', '.push_product', function(e){
                e.preventDefault();
                let id = jQuery(this).parent().data('id');
                let name = jQuery(this).parent().data('name');
                let slug = jQuery(this).parent().data('slug');
                let website_id = jQuery(this).closest('.product_list').data('website_id');
                let $this = jQuery(this);
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
                    },
                    success: function(response){
                        console.log(response);
                        if(response !== ""){
                            $this.removeClass('updating-message')
                            const result = response.result;
                            if(result.hasOwnProperty('message')){
                                $this.text(result.code)
                            }else{
                                $this.text("Synced")
                            }
                        }
                    }
                })
            })

            jQuery(document).on('click', '.clear-data', function(){
                jQuery('.result').empty()
            })

            jQuery(document).on('click', '.reload', function(){
                location.reload(true);
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

