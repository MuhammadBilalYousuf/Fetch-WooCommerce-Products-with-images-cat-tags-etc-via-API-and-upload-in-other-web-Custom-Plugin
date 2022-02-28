<?php
/*
Plugin Name: Fetch WooCommerce Products
Description: Fetch WooCommerce Products with images cat tags etc via API and upload in other web platform of woocommerce
Author: Muhammad Bilal Yousuf
*/

require __DIR__ . '/vendor/autoload.php';
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

add_action('manage_posts_extra_tablenav', 'admin_order_list_top_bar_button', 20, 1);
function admin_order_list_top_bar_button($which)
{
    global $typenow;
    if ('product' === $typenow && 'top' === $which) {
        echo '<a href="#fetching" class="myajax button" style="margin-left: -10px;">Import</a>';
        echo '<span class="spinner"></span>';
    }
}

add_action('admin_head', 'my_action_javascript');

function my_action_javascript()
{
    ?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
    var currentdate = new Date(); 
    var datetime = "on: " + currentdate.getDate() + "/"
                + (currentdate.getMonth()+1)  + "/" 
                + currentdate.getFullYear() + " @ "  
                + currentdate.getHours() + ":"  
                + currentdate.getMinutes() + ":" 
                + currentdate.getSeconds();
    $('.myajax').click(function(){
        $(".spinner").addClass("is-active");
        var data = {
            action: 'my_action',
            whatever: datetime
        };
        $.post(ajaxurl, data, function(response) {
            $(".spinner").removeClass("is-active");
            alert('Data is upto date on: ' + datetime);
            location.reload();
        });
    });
});
</script>
<?php
}

add_action('wp_ajax_my_action', 'my_action_callback');
function my_action_callback()
{   

    $woocommerce = new Client(
        'http://localhost/2ndbazaarmainwp', 
        'ck_b8ebd04b0a88df879f68fcf4b567c62bc0b80354', 
        'cs_7b18b6df1acd44eebe456eb7f23e4869f4980014',
        [
            'version' => 'wc/v3',
        ]
    );
  
    global $wpdb;
    $second = $wpdb->get_col( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_type IN ('product')");
    $first = $woocommerce->get( 'products', array('per_page'=>100) );

    $final_array = array();
    foreach($first as $key=>$data) {
        $array_o[$key] = $data->slug;
        $array_all[$key] = $data;
    }
    
    foreach(array_combine($array_o, $array_all) as $already => $all) {
        $tags            =         $all->tags;
        $laptopFilter    =         $all->laptop_filter;
        $categories      =         $all->categories;
        $final_array[] = array(
            'sid'            =>        $all->id,
            'name'           =>        $all->name,
            'slug'           =>        $all->slug,
            'type'           =>        $all->type,
            'status'         =>        $all->status,
            'description'    =>        $all->description,
            'sku'            =>        $all->sku,
            'regular_price'  =>        $all->regular_price,
            'sale_price'     =>        $all->sale_price,
            'stock_quantity' =>        $all->stock_quantity,
            'manage_stock'   =>        $all->manage_stock,
            'stock_status'   =>        $all->stock_status,
            'status'         =>        $all->status,
            'accessories'    =>        $all->meta_data,
            'laptopFilter'   =>        $all->laptop_filter,
            'categories'     =>        $all->categories,
            'tags'           =>        $all->tags,
            'tagsResult'     =>        array_column($tags, 'name'),
            'categoriesResult'     =>  array_column($categories, 'name'),
            'laptopFilterResult'   =>  array_column($laptopFilter, 'name'),
            'image'          =>        $all->images[0]->src,
            'photos'         =>        array_column($all->images, 'src'),
            'check' => in_array($already, $second) ? 1 : 0);
    }
    
    foreach($final_array as $key => $tag_name){
        if($tag_name['check'] == 1) {
            unset($final_array[$key]);
        }
        $sid            =        $final_array[$key]['id'];
        $name           =        $final_array[$key]['name'];
        $slug           =        $final_array[$key]['slug'];
        $type           =        $final_array[$key]['type'];
        $status         =        $final_array[$key]['status'];
        $description    =        $final_array[$key]['description'];
        $sku            =        $final_array[$key]['sku'];
        $regularPrice   =        $final_array[$key]['regular_price'];
        $salePrice      =        $final_array[$key]['sale_price'];
        $quantity       =        $final_array[$key]['stock_quantity'];
        $manageStock    =        $final_array[$key]['manage_stock'];
        $stockStatus    =        $final_array[$key]['stock_status'];
        $status         =        $final_array[$key]['status'];
        $accessories    =        $final_array[$key]['accessories'];
        $laptopFilter   =        $final_array[$key]['laptop_filter'];
        $categories     =        $final_array[$key]['categories'];
        $tags           =        $final_array[$key]['tags'];
        $tagsResult     =        $final_array[$key]['tagsResult'];
        $categoriesResult     =  $final_array[$key]['categoriesResult'];
        $laptopFilterResult   =  $final_array[$key]['laptopFilterResult'];
        $image          =        $final_array[$key]['image'];
        $photos         =        $final_array[$key]['photos'];


        $post_id = wp_insert_post(array(
            'post_title'    => $name,
            'post_name'     => $slug,
            'post_type'     => 'product',
            'post_status'   => 'draft',
            'post_content'  => $description,
            'post_author'   => 1
		));

        //get Accessories
        foreach($accessories as $accProduct)
        {
            if ( 'accessories_operating_system_0_name' === $accProduct->key ){
                $os             =        $accProduct->value;
            }
            if ( 'accessories_ram_0_name' === $accProduct->key ){
                $ram            =        $accProduct->value;
            }
            if ( 'accessories_hard_drive_0_name' === $accProduct->key ){
                $storage        =        $accProduct->value;
            }
            if ( '_conditions' === $accProduct->key ){
                $conditions     =        $accProduct->value;
            }
        }

        // set sku, price, sale price, slug
        update_post_meta($post_id,'_sku',$sku);
        update_post_meta($post_id,'_price',$regularPrice);
        update_post_meta($post_id,'_regular_price',$regularPrice);
        update_post_meta($post_id,'_sale_price',$salePrice);
        
        //set Accessories
        $values = array(
            'operating_system'	=>	array(
                array(
                'name' => $os,
                'price' => '0'
                )
            ),
            'ram'	=>	array(
                array(
                'name' => $ram,
                'price' => '0'
                )
            ),
            'hard_drive'	=>	array(
                array(
                'name' => $storage,
                'price' => '0'
                )
            )
        );
        update_field('accessories', $values, $post_id);

        //set Product Tags
        wp_set_object_terms($post_id, $tagsResult, 'product_tag');

        //set Product Category
        wp_set_object_terms($post_id, $categoriesResult, 'product_cat');

        //set Product Type
        wp_set_object_terms($post_id, $type, 'product_type');

        //set Laptop Filter
        wp_set_object_terms($post_id, $laptopFilterResult, 'laptop_filter');

        //set Product Condition
        update_post_meta($post_id, '_conditions', $conditions);
        update_post_meta($post_id, '_stock_status', $stockStatus);
        update_post_meta($post_id, '_manage_stock', $manageStock);
        update_post_meta($post_id, '_stock', $quantity);
        update_post_meta($post_id, 'image', $image);
        update_post_meta($post_id, 'photos', $photos);

        // Featured Image
        include_once(ABSPATH . 'wp-admin/includes/image.php');
        $imageurl = $image;
        $imagetype = end(explode('/', getimagesize($imageurl)['mime']));
        $uniq_name = $name;
        $filename = $uniq_name.'.'.$imagetype;
        $uploaddir = wp_upload_dir();
        $uploadfile = $uploaddir['path'] . '/' . $filename;
        $contents= file_get_contents($imageurl);
        $savefile = fopen($uploadfile, 'w');
        fwrite($savefile, $contents);
        fclose($savefile);

        $wp_filetype = wp_check_filetype(basename($filename), null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $filename,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $uploadfile, $post_id);
        $imagenew = get_post($attach_id);
        $fullsizepath = get_attached_file($imagenew->ID);
        $attach_data = wp_generate_attachment_metadata($attach_id, $fullsizepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($post_id, $attach_id);

        // Product Images
        foreach (get_post_meta($post_id, 'photos') as $image) {
            $i=1;
            $vv = '';
            foreach ($image as $val) {
                include_once(ABSPATH . 'wp-admin/includes/image.php');
                $imageurl = $val;
                $imagetype = end(explode('/', getimagesize($imageurl)['mime']));
                $uniq_name = basename($imageurl).'_'.$post_id;
                $filename = $uniq_name.'.'.$imagetype;
                $uploaddir = wp_upload_dir();
                $uploadfile = $uploaddir['path'] . '/' . $filename;
                $contents= file_get_contents($imageurl);
                $savefile = fopen($uploadfile, 'w');
                fwrite($savefile, $contents);
                fclose($savefile);
                $wp_filetype = wp_check_filetype(basename($filename), null);
                $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => $filename,
                'post_content' => basename($imageurl),
                'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $uploadfile, $post_id);
                $imagenew = get_post($attach_id);
                $fullsizepath = get_attached_file($imagenew->ID);
                $attach_data = wp_generate_attachment_metadata($attach_id, $fullsizepath);
                wp_update_attachment_metadata($attach_id, $attach_data);
                $vv .= $imagenew->ID . ",";
                $i++;
            }
            update_post_meta($post_id, '_product_image_gallery', $vv);
        }
    }
}