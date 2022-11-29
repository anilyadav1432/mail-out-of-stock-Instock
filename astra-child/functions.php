<?php
/**
 * Anglia Tackle & Gun Bespoke Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Anglia Tackle & Gun Bespoke
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ANGLIA_TACKLE_GUN_BESPOKE_VERSION', '1.0.0' );

/**   
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'anglia-tackle-gun-bespoke-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ANGLIA_TACKLE_GUN_BESPOKE_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

add_filter('woocommerce_product_add_to_cart_text', function ($text) {
     global $product;
     if ($product instanceof WC_Product && $product->is_type('variable')) {
         $text = $product->is_purchasable() ? _('View Options', 'woocommerce')
		 : _('Read more', 'woocommerce');
     }
     return $text;
 }, 10);

function custom_openpos_pos_header_js($handles){
    $handles[] = 'openpos.websql_handle';
    return $handles;
}

add_filter( 'openpos_pos_header_js', 'custom_openpos_pos_header_js' ,10 ,1);
add_action( 'init', 'custom_registerScripts' ,10 );
function custom_registerScripts(){
    wp_register_script( 'openpos.websql_handle', '' );
    wp_enqueue_script('openpos.websql_handle');
    wp_add_inline_script('openpos.websql_handle',"
        if(typeof global == 'undefined')
        {
             var global = global || window;
        }
        global.allow_websql = 'yes';
    ");
}


/******Anil Work */
/**
 * change the “Out Of Stock” text on the Product Catalog (Shop page)
 */
add_filter( 'astra_woo_shop_out_of_stock_string', 'out_of_stock_callback' );
function out_of_stock_callback( $title ) {
    global $product;
    $product = wc_get_product($product->get_id());
    // Get children product variation IDs in an array
    $children_ids = $product->get_children();
    if(!empty($children_ids)){
        foreach($children_ids as $children_id){
            $product = wc_get_product($children_id);
            if($product->get_stock_quantity() > 0 ){
                $title = '';
                break;
            }else{
                $title = 'Call us for lead time';
            }
        }
    }else{
        $title = 'Call us for lead time';
    }
    return $title;
}
/**
 * change the “Out Of Stock” text on the Single Product pages
 */
add_filter( 'woocommerce_get_availability', 'change_out_of_stock_text_woocommerce', 1, 2 );
function change_out_of_stock_text_woocommerce( $availability, $product_to_check ) {
    // Change Out of Stock Text
    if ( ! $product_to_check->is_in_stock() ) {
        $availability['availability'] = '<button id="ced_notify_user">'.__('Call us for lead time', 'woocommerce').'</button>';
    }
    return $availability;
}

add_action('wp_footer', 'ced_notify_popup');
function ced_notify_popup(){
    // popup
    global $product;
    // $id = $product->get_id();
    if(is_product()){
        $product_name = $product->get_name();
    ?>
    <div class="ced-announce pop1Box bottomSlide">
        <div class="container">
            <h2>
                <span class="" aria-hidden="true"></span>&nbsp;
                <span class="h2_txt"></span>
            </h2>
            <div class="content">
                <h5 class="modal-title_">Please Enter Your Details</h5>
                <?php echo do_shortcode('[contact-form-7 id="19692" title="product-out-of-stock-form"]'); ?>
            </div>
            <a href="javascript:void(0)" class="closePopup" title="Close (Esc)"><span class="fa fa-close"></span></a>
        </div> 
    </div>
    <script>
        jQuery(document).on('click','#ced_notify_user',function(event){
            event.preventDefault();
            
            if(jQuery('.elementor-widget-container .elementor-add-to-cart').hasClass('elementor-product-variable')){
                var product_id = jQuery("input[name=variation_id]").val();
                jQuery('#product-stock-id').val(product_id);
                jQuery('#product-stock-title').val('<?php echo $product_name; ?>');
            }else{
                var product_id = jQuery("input[name=queried_id]").val();
                jQuery('#product-stock-id').val(product_id);
                jQuery('#product-stock-title').val('<?php echo $product_name; ?>');
            }
        
            jQuery('.ced-announce').addClass('active');
            jQuery('.ced-announce').attr('id', 'ced-active-popup');
        });
        jQuery(document).on('click','.closePopup',function(event){
            jQuery('#ced-active-popup').removeClass('active');
            jQuery(this).removeAttr('id','ced-active-popup');
        });
    </script>
    <?php
    }
}

add_action('woocommerce_update_product', 'ced_product_update_stock_mail', 10, 2);
function ced_product_update_stock_mail($product_id, $product) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $results = $wpdb->get_results( "SELECT * FROM tks_db7_forms", ARRAY_A );
    $product = wc_get_product($product_id);
    $product_url = get_permalink( $product_id );
    if($product->is_type( 'simple' )){
        // echo "<pre>";print_r($results);die;
         // iterate over results
        foreach ($results as $result) {
            // echo "<pre>";print_r($result);die;
            if($result['form_value']){
                $data = array();
                $data = unserialize($result['form_value']);
                if($product_id == $data['product-stock-id']){
                    if($product->get_stock_quantity() > 0 ){
                        $check_mail =   get_post_meta($result['form_post_id'], '_instock_'.$result['form_id'], true);
                        if( empty($check_mail) || $check_mail==0 ){
                            
                            $html = '<h1>Product has come in stock</h1>'.'<b>Product Url : </b>'.$product_url.'<br/><b>Product Name :</b>'.$data['product-stock-title'];
                            $prepared_html = $html;
                            $email      = $data['user-email'];
                            $to      = $email;
                            $subject ="Product In Stock now";
                            $message = $prepared_html;
                            $headers = "From:Topknotch-Solutions/ <$current_user->user_email>\r\n";
                            $headers.= "Content-Type: text/html; charset=utf-8\r\n";
                            $headers .= "CC:".$data['user-email']."\r\n";
                            wp_mail($to, $subject, $message, $headers);         
                            add_post_meta( $result['form_post_id'], '_instock_'.$result['form_id'], 1 );
                        }
                    }
                }
            }
        }
        
    }elseif($product->is_type( 'variable' )){
        // Get children product variation IDs in an array
        $children_ids = $product->get_children();
        foreach ($results as $result) {
            if($result['form_value']){
                $data = array();
                $data = unserialize($result['form_value']);
                foreach($children_ids as $children_id){
                    $product_var = wc_get_product($children_id);
                    if($children_id == $data['product-stock-id'] && $product_var->get_stock_quantity() > 0 ){
                        $check_mail =   get_post_meta($result['form_post_id'], '_instock_'.$result['form_id'], true);
                        if( empty($check_mail) || $check_mail==0 ){
                            $html = '<h1>Product has come in stock</h1>'.'<b>Product Url : </b>'.$product_url.'<br/><b>Product Name :</b>'.$data['product-stock-title'];
                            $prepared_html = $html;
                            $email      = $data['user-email'];
                            $to      = $email;
                            $subject ="Product In Stock now";
                            $message = $prepared_html;
                            $headers = "From:Topknotch-Solutions/ <$current_user->user_email>\r\n";
                            $headers.= "Content-Type: text/html; charset=utf-8\r\n";
                            $headers .= "CC:".$data['user-email']."\r\n";
                            wp_mail($to, $subject, $message, $headers);
                            add_post_meta( $result['form_post_id'], '_instock_'.$result['form_id'], 1 );
                        }
                    }
                }
            }
        }
    }

}

// function ced_phpmailer_instock( PHPMailer $phpmailer ) {
//     $phpmailer->Host = '78.129.219.58';
//     $phpmailer->Port = 21; // could be different
//     $phpmailer->Username = 'topknotc'; // if required
//     $phpmailer->Password = 'xg~FxK"S9j9yYbME'; // if required
//     $phpmailer->SMTPAuth = true; // if required
//     // $phpmailer->SMTPSecure = 'ssl'; // enable if required, 'tls' is another possible value

//     $phpmailer->IsSMTP();
// }
// add_action('wp_footer', function(){
//     global $wpdb;
//     // $table_name = 'tks_db7_forms';
//     //$query = "SELECT form_value FROM $table_name WHERE form_post_id = 19692";
    
//     $results = $wpdb->get_results( "SELECT form_value FROM tks_db7_forms WHERE form_post_id = 19692", ARRAY_A );
//     // iterate over results
//     foreach ($results as $result) {
//         if($result['form_value']){
//             $data = unserialize($result['form_value']);
//             print_r($data);
//         }
//     }
// });
