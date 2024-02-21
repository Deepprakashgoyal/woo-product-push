<style>
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 5px;
    }

    ol{
        text-transform: capitalize;
    }
    ol ol{
        list-style: lower-alpha;
    }
    li .push_cat{
        text-decoration: underline;
        cursor: pointer
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
        <button class="button ajax-action-btn disabled" id="push_categories" data-id="<?php echo $website_id ?>">Push All Catagories</button>
        <button class="button ajax-action-btn" id="fetch_categories" data-id="<?php echo $website_id ?>">Fetch Catagories</button>
        <button class="button clear-data">Clear</button>
        <button class="button reload">Reload</button>

        <div class="result">
            <?php 
                $product_categories = get_terms('product_cat', array(
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'hide_empty' => false, // Set to true to hide empty categories
                ));
                
                if(!empty($product_categories)){
                    echo "<ol class='cat_list' data-website_id='".$website_id."'>";
                    foreach ($product_categories as $category) {
                        // print_r($category);
                        echo "<li data-id='" . $category->term_id . "' data-name='" . $category->name . "' data-slug='" . $category->slug . "'>".$category->name.  ": <span class='push_cat'>Push</span></li>";
                    }
                    echo "</ol>";
                }


                
            ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function(){
            jQuery(document).on('click', '.ajax-action-btn', function(e){
                var totalRequests = 4;
                pushCategories(e, 0, totalRequests)
            })

            // push category
            jQuery(document).on('click', '.push_cat', function(e){
                e.preventDefault();
                let cat_id = jQuery(this).parent().data('id');
                let cat_name = jQuery(this).parent().data('name');
                let cat_slug = jQuery(this).parent().data('slug');
                let website_id = jQuery(this).closest('.cat_list').data('website_id');
                let $this = jQuery(this);
                jQuery.ajax({
                    url: ajaxurl, 
                    type: 'POST',
                    data: {
                        action: 'WPP_push_category',
                        website_id: website_id,
                        name: cat_name,
                        slug: cat_slug,
                    },
                    success: function(response){
                        if(response !== ""){
                            const result = response.result;
                            if(result.code == "term_exists"){
                                console.log(result.code)
                                $this.text('Category Exist')
                                $this.removeClass('push_cat')
                            }
                            
                        }

                        
                    }
                })
            })


            function pushCategories(e, index = 0, totalRequests = 0){
                let website_id = jQuery(e.target).data('id');
                let action_type = jQuery(e.target).attr('id')
                let website_url = jQuery('#website_url').attr('href')
                let $this = jQuery(e.target);
                jQuery(e.target).addClass('disabled')
                jQuery('.result').empty();


                jQuery.ajax({
                    url: ajaxurl, 
                    type: 'POST',
                    data: {
                        action: 'WPP_push_categories',
                        id: website_id,
                        action_type: action_type
                    },
                    success: function(response) {
                        if(response !== "" && response.action_type == 'fetch_categories'){
                            let categories = response.categories;
                            let data = categories.map((item) => {
                                return `<li>Fetched: <a target="_blank" href="${website_url}/product-category/${item.slug}">${item.name}</a> </li>`;
                            })
                            jQuery('.result').html(`<h3>Fetched Product Categories</h3><ol>${data.join('')}</ol>`)
                            $this.removeClass('disabled')
                        }

                        if(response !== "" && response.action_type == 'push_categories'){
                            const result = response.result;
                            
                            let created_categories = result.create.map((item) => {
                                return item.id != 0 ? {'id' : item.id, 'name' : item.name, 'slug': item.slug} : null;
                            }).filter(item => item !== null)

                            // console.log(created_categories)

                            if (created_categories.length > 0) {
                                let itemData = created_categories.map(item => {
                                    return `<li data-id="${item.id}"> Created: <a target="_blank" data-slug="${item.slug}" href="${website_url}/product-category/${item.slug}">${item.name}</a></li>`;
                                });

                                jQuery('.result').html(`<h3>Product Categories Created</h3><ol class="parent_categories">${itemData.join('')}</ol>`);

                                pushChildCategories(website_id, 0, created_categories);
                            }else{
                                jQuery('.result').html('<h3>Something went wrong</h3>')
                            }
                        }

                        



                        
                    },
                    error: function(error) {
                        console.log(error);
                    }
                })
            }

            // push child categories
            function pushChildCategories(website_id, index, categories){
                jQuery.ajax({
                    url: ajaxurl, 
                    type: 'POST',
                    data: {
                        action: 'WPP_push_child_categories',
                        id: website_id,
                        index: index,
                        parent_categories: categories
                    },
                    success: function(response){
                        if(response !== ""){
                            const result = response.result;

                            if(result != ""){
                                let created_categories = result.create.map((item) => {
                                    return item.id != 0 ? {'id' : item.id, 'name' : item.name, 'slug': item.slug, 'parent':item.parent } : null;
                                }).filter(item => item !== null)

                                // console.log(created_categories)

                                if (created_categories.length > 0) {
                                    let itemData = created_categories.map(item => {
                                        return `<li> Created: <a target="_blank" data-id="${item.id}" data-slug="${item.slug}" href="${website_url}/product-category/${item.slug}">${item.name}</a></li>`;
                                    });

                                    jQuery('.result .parent_categories > li:nth-child('+response.index+')').append(`<ol>${itemData.join('')}</ol>`);
                                    console.log(response.index)
                                }

                            }


                            if(response.index < categories.length){
                                pushChildCategories(website_id, response.index, categories)
                            }
                        }
                    },
                    error: function(res){
                        console.log(res)
                    }
                })
            }

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

    if(!empty($websites)): ?>
    <section>
        <h2>Product Categories Push in Website</h2>
        <form action="admin.php?page=category-push" method="get">
        <input type="hidden" name="page" value="category-push">
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

