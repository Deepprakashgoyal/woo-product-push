<style>
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 5px;
    }

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
$websites = $wpdb->get_results( "SELECT * FROM WPP_websites");


if(isset($_GET['website_id']) && $_GET['website_id'] !== ""){
    $website_id = $_GET['website_id'];
    $website_url= $wpdb->get_results( "SELECT website_url FROM WPP_websites where id = $website_id");
    ?>
    <div>
        <h3>Website : <a id="website_url" href="<?php echo $website_url[0]->website_url?>"><?php echo $website_url[0]->website_url?></a></h3>
        <button class="button ajax-action-btn" id="push_categories" data-id="<?php echo $website_id ?>">Push Catagories</button>
        <button class="button ajax-action-btn" id="fetch_categories" data-id="<?php echo $website_id ?>">Fetch Catagories</button>
        <button class="button clear-data">Clear</button>

        <div class="result">
        </div>
    </div>

    <script>
        jQuery(document).ready(function(){
            jQuery(document).on('click', '.ajax-action-btn', function(){
                let website_id = jQuery(this).data('id');
                let action_type = jQuery(this).attr('id')
                let website_url = jQuery('#website_url').attr('href')
                var $this = jQuery(this);
                jQuery(this).addClass('disabled')
                jQuery('.result').empty();
                jQuery.ajax({
                    url: ajaxurl, // WordPress AJAX handler
                    type: 'POST',
                    data: {
                        action: 'WPP_push_categories',
                        id: website_id,
                        action_type: action_type
                    },
                    success: function(response) {
                        $this.removeClass('disabled')
                        if(response !== "" && response.action_type == 'fetch_categories'){
                            let categories = response.categories;
                            let data = categories.map((item) => {
                                return `<li>Fetched: <a target="_blank" href="${website_url}/product-category/${item.slug}">${item.name}</a> </li>`;
                            })
                            jQuery('.result').html(`<h3>Fetched Product Categories</h3><ol>${data.join('')}</ol>`)
                            console.log(jQuery(this))
                        }

                        if(response !== "" && response.action_type == 'push_categories'){
                            const result = response.result;
                            // console.log(response.result.data.status)
                            let created_catagories = result.create.map((item) => {
                                return item?.name ? `<li>Created:<a target="_blank" href="${website_url}/product-category/${item.slug}">${item.name}</a></li>` : "";
                            })
                            jQuery('.result').html(`<h3>Product Categories Created</h3><ol>${created_catagories.join('')}</ol>`)
                            console.log(created_catagories)
                        }

                        // console.log(response)
                    },
                    error: function(error) {
                        console.log(error);
                    }
                })
            })

            jQuery(document).on('click', '.clear-data', function(){
                jQuery('.result').empty()
            })
        })
    </script>

    <?php
}else{
    global $wpdb;
    $websites = $wpdb->get_results( "SELECT * FROM WPP_websites");

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

