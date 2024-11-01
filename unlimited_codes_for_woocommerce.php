<?php
/*
Plugin Name: Unlimited Codes for WooCommerce 
Description: This plugin allows include different code types in your WooCommerce pages.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: unlimited-codes-woocommerce
Version: 0.2
Plugin URI: https://github.com/ovimedia/unlimited-codes-for-woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) exit; 

if ( ! class_exists( 'unlimited_codes_woocommerce' ) ) 
{
	class unlimited_codes_woocommerce 
    {
        public $woocodes = "";
        
        function __construct() 
        {   
            add_action( 'init', array( $this, 'ucw_load_languages') );
            add_action( 'init', array( $this, 'ucw_init_taxonomy') );
            add_action( 'init', array( $this, 'ucw_load_codes') );           
            add_action( 'admin_print_scripts', array( $this, 'ucw_admin_js_css') );
            add_action( 'add_meta_boxes', array( $this, 'ucw_init_metabox') ); 
            add_action( 'save_post', array( $this, 'ucw_save_data_codes') );

            add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'unlimited_codes_woocommerce.php'), array( $this, 'ucw_plugin_settings_link' ) );    
                    
            add_filter( 'manage_edit-woocode_columns', array( $this, 'ucw_edit_code_columns' )) ;
            add_action( 'manage_woocode_posts_custom_column', array( $this, 'ucw_manage_code_columns'), 10, 2 );
            
            add_action( 'template_redirect', array( $this, 'ucw_redirect_post') );   
    
        }

        public function ucw_load_codes()
        {
            $args = array(
                'numberposts' =>   -1,
                'post_type' => "woocode",
                'post_status' => 'publish',
                'meta_key'   => 'ucw_order_code',
                'orderby'    => 'meta_value_num',
                'order'      => 'ASC'
            ); 

            $this->woocodes = get_posts($args); 

            foreach($this->woocodes as $woocode)
            {
                $pagezones = get_post_meta($woocode->ID, "ucw_zone_page_names", true);
                $product_cats = get_post_meta($woocode->ID, "ucw_product_categories", true);
                $product_ids = get_post_meta($woocode->ID, "ucw_product_ids", true);

                $content = $woocode->post_content;

                foreach($pagezones as $zone)
                {
                    add_action($zone,  function() use ($content, $product_cats, $product_ids)
                    {
                        global $post;

                        $terms = wp_get_post_terms( $post->ID, 'product_cat' );

                        $load = false;

                        foreach ( $terms as $term ) 
                        {
                            if(in_array($term->name, $product_cats)) $load = true; 
                        }

                        if($load || in_array($post->ID, $product_ids) || $product_cats == "" && $product_ids == "") 
                            echo do_shortcode($content);     
                        
                    });
                }
            }
        }

        public function ucw_redirect_post() 
        {
            if ( is_single() && 'woocode' ==  get_query_var('post_type') ) 
            {
                wp_redirect( home_url(), 301 );
                exit;
            }
        }
        
        public function ucw_load_languages() 
        {
            load_plugin_textdomain( 'unlimited-codes-woocommerce', false, '/'.basename( dirname( __FILE__ ) ) . '/languages/' ); 
        }

        public function ucw_init_taxonomy()
        {    
            $labels = array(
                'name' => translate( 'Woo Codes', 'unlimited-codes-woocommerce' ),
                'singular_name' => translate( 'Woo Code', 'unlimited-codes-woocommerce' ),
                'add_new' =>  translate( 'Add Woo Code', 'unlimited-codes-woocommerce' ),
                'add_new_item' => translate( 'Add new Woo Code', 'unlimited-codes-woocommerce' ),
                'edit_item' => translate( 'Edit Woo Code', 'unlimited-codes-woocommerce' ),
                'new_item' => translate( 'New Woo Code', 'unlimited-codes-woocommerce' ),
                'view_item' => translate( 'Show Woo Code', 'unlimited-codes-woocommerce' ),
                'search_items' => translate( 'Search Woo Codes', 'unlimited-codes-woocommerce' ),
                'not_found' =>  translate( 'No Woo Codes found', 'unlimited-codes-woocommerce' ),
                'not_found_in_trash' => translate( 'No Woo Codes found in trash', 'unlimited-codes-woocommerce' ),
                'parent_item_colon' => ''
            );

            $args = array( 'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'menu_position' => 51,
                'menu_icon' => 'dashicons-cart',
                'supports' => array( 'title', 'editor', 'revisions')
            );

            register_post_type( 'woocode', $args );
        }


        public function ucw_edit_code_columns( $columns ) {

            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => translate( 'Woo Code', 'unlimited-codes-woocommerce' ),
                'loadin' => translate( 'Load in:', 'unlimited-codes-woocommerce' ),
                'loadcategories' => translate( 'Load in this categories:', 'unlimited-codes-woocommerce' ),
                'loadproducts' => translate( 'Load in this products:', 'unlimited-codes-woocommerce' ),
                'order' =>  translate( 'Order:', 'unlimited-codes-woocommerce' ),
                'date' => __( 'Date' )
            );
            
            return $columns;

        }
        
        public function ucw_manage_code_columns( $column, $post_id ) 
        {
            switch( $column ) 
            {         
                    
                case 'loadin':
                    
                    $values = get_post_meta( $post_id, 'ucw_zone_page_names', true);
                    
                    foreach ($values as $value)
                    {
                        $column_values .= $value.", ";
                    }

                    echo substr($column_values, 0, -2); 

                break;    

                case 'loadcategories':
                    
                    $values = get_post_meta( $post_id, 'ucw_product_categories', true);
                    
                    foreach ($values as $value)
                    {
                        $column_values .= $value.", ";
                    }

                    echo substr($column_values, 0, -2); 

                break;  

                case 'loadproducts':
                    
                    $values = get_post_meta( $post_id, 'ucw_product_ids', true);
                    
                    foreach ($values as $value)
                    {
                        $product = get_post( $value );
                        $column_values .= $product->post_title.", ";
                    }

                    echo substr($column_values, 0, -2); 

                break;  
                                       
                case 'order':

                    echo get_post_meta( $post_id, 'ucw_order_code', true) ;

                    break;    
                    
                default:

                    break;
            }
        }

        public function ucw_admin_js_css() 
        {
            if(get_post_type(get_the_ID()) == "woocode")
            {
                wp_register_style( 'custom_codes_admin_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/style.css', false, '1.0.0' );

                wp_enqueue_style( 'custom_codes_admin_css' );

                wp_register_style( 'codes_select2_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/select2.min.css', false, '1.0.0' );

                wp_enqueue_style( 'codes_select2_css' );

                wp_enqueue_script( 'codes_script', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/scripts.js', array('jquery') );

                wp_enqueue_script( 'codes_select2', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/select2.min.js', array('jquery') );
            }
        }

        public function ucw_init_metabox()
        {
            add_meta_box( 'zone-woocode', translate( 'Woo Code options', 'unlimited-codes-woocommerce' ), 
                         array( $this, 'ucw_meta_options'), 'woocode', 'side', 'default' );
        }
        

        public function ucw_meta_options( $post )
        {
            global $wpdb;
            
            $types = get_post_meta( get_the_ID(), 'ucw_post_type_id');

            ?>
            <div class="meta_div_codes">         
               
                <p>
                    <label for="ucw_zone_page_names">
                        <?php echo translate( 'Load in:', 'unlimited-codes-woocommerce' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple" id="ucw_zone_page_names" name="ucw_zone_page_names[]">
                        <?php

                            $woopages = array(
                                "Shop/Archive/Product category" => array(
                                    "Before main content" => "woocommerce_before_main_content",
                                    "Archive description" => "woocommerce_archive_description",
                                    "Before shop loop" => "woocommerce_before_shop_loop",
                                    "Before shop loop item" => "woocommerce_before_shop_loop_item",
                                    "Before shop loop item title" => "woocommerce_before_shop_loop_item_title",
                                    "Shop loop item title" => "woocommerce_shop_loop_item_title",
                                    "After shop loop item title" => "woocommerce_after_shop_loop_item_title",
                                    "After shop loop item" => "woocommerce_after_shop_loop_item",
                                    "After shop loop" => "woocommerce_after_shop_loop",
                                    "After main content" => "woocommerce_after_main_content"
                                ),
                                "Product" => array(
                                    "Before single product summary" => "woocommerce_before_single_product_summary",
                                    "Single product summary" => "woocommerce_single_product_summary",
                                    "Before add to cart form" => "woocommerce_before_add_to_cart_form",
                                    "Before variations form" => "woocommerce_before_variations_form",
                                    "Before add to cart button" => "woocommerce_before_add_to_cart_button",
                                    "Before single variation" => "woocommerce_before_single_variation",
                                    "Single variation" => "woocommerce_single_variation",
                                    "After single variation" => "woocommerce_after_single_variation",
                                    "After add to cart button" => "woocommerce_after_add_to_cart_button",
                                    "After variations form" => "woocommerce_after_variations_form",
                                    "After add to cart form" => "woocommerce_after_add_to_cart_form",
                                    "Product meta start" => "woocommerce_product_meta_start",
                                    "Product meta end" => "woocommerce_product_meta_end",
                                    "Share" => "woocommerce_share",
                                    "After single product summary" => "woocommerce_after_single_product_summary"
                                ),
                                "Cart" => array(
                                    "Before cart" => "woocommerce_before_cart",
                                    "Before cart table" => "woocommerce_before_cart_table",
                                    "Before cart contents" => "woocommerce_before_cart_contents",
                                    "Cart contents" => "woocommerce_cart_contents",
                                    "Cart coupon" => "	woocommerce_cart_coupon	",
                                    "After cart contents" => "woocommerce_after_cart_contents",
                                    "After cart table" => "woocommerce_after_cart_table",
                                    "Cart collaterals" => "woocommerce_cart_collaterals",
                                    "Before cart totals" => "woocommerce_before_cart_totals",
                                    "Cart totals before shipping" => "woocommerce_cart_totals_before_shipping",
                                    "Before shipping calculator" => "woocommerce_before_shipping_calculator",
                                    "After shipping calculator" => "woocommerce_after_shipping_calculator	",
                                    "Cart totals after shipping" => "woocommerce_cart_totals_after_shipping",
                                    "Cart totals before order total" => "woocommerce_cart_totals_before_order_total",
                                    "Cart totals after order total" => "woocommerce_cart_totals_after_order_total",
                                    "Proceed to checkout" => "woocommerce_proceed_to_checkout",
                                    "After cart totals" => "woocommerce_after_cart_totals",
                                    "After cart" => "woocommerce_after_cart"
                                ),
                                "Checkout" => array(
                                    "Before checkout form" => "woocommerce_before_checkout_form",
                                    "Before customer details" => "woocommerce_checkout_before_customer_details",
                                    "Before checkout billing form" => "woocommerce_before_checkout_billing_form",
                                    "After checkout billing form" => "woocommerce_after_checkout_billing_form",
                                    "Before checkout shipping form" => "woocommerce_before_checkout_shipping_form",
                                    "After checkout shipping form" => "woocommerce_after_checkout_shipping_form",
                                    "Before order notes" => "woocommerce_before_order_notes",
                                    "After order notes" => "woocommerce_after_order_notes",
                                    "Checkout after customer details" => "woocommerce_checkout_after_customer_details",
                                    "Checkout before order review" => "woocommerce_checkout_before_order_review",
                                    "Review order before cart contents" => "woocommerce_review_order_before_cart_contents",
                                    "Review order after cart contents" => "woocommerce_review_order_after_cart_contents",
                                    "Review order before shipping" => "woocommerce_review_order_before_shipping",
                                    "Review order after shipping" => "woocommerce_review_order_after_shipping",
                                    "Review order before order total" => "woocommerce_review_order_before_order_total",
                                    "Review order after order total" => "woocommerce_review_order_after_order_total",
                                    "Review order before payment" => "woocommerce_review_order_before_payment",
                                    "Review order before submit" => "woocommerce_review_order_before_submit", 
                                    "Review order after submit" => "woocommerce_review_order_after_submit",
                                    "Review order after payment" => "woocommerce_review_order_after_payment",
                                    "Checkout after order review" => "woocommerce_checkout_after_order_review",
                                    "After checkout form" => "woocommerce_after_checkout_form"
                                ),
                                "My Account" => array(
                                    "Before customer login form" => "woocommerce_before_customer_login_form",
                                    "Login form start" => "woocommerce_login_form_start",
                                    "Login form" => "woocommerce_login_form",
                                    "Login form end" => "woocommerce_login_form_end",
                                    "Register form start" => "woocommerce_register_form_start",
                                    "Register form" => "woocommerce_register_form",
                                    "Register form" => "register_form",
                                    "Register form end" => "woocommerce_register_form_end",
                                    "After customer login form" => "woocommerce_after_customer_login_form",
                                    "Account content" => "woocommerce_account_content",
                                    "Account dashboard" => "woocommerce_account_dashboard",
                                    "Before account orders" => "woocommerce_before_account_orders",
                                    "Before account orders pagination" => "woocommerce_before_account_orders_pagination",
                                    "After account orders" => "woocommerce_after_account_orders",
                                    "Before account downloads" => "woocommerce_before_account_downloads",
                                    "Before available downloads" => "woocommerce_before_available_downloads",
                                    "After available downloads" => "woocommerce_after_available_downloads",
                                    "After account downloads" => "woocommerce_after_account_downloads",
                                    "Before edit account address form" => "woocommerce_before_edit_account_address_form",
                                    "After edit account address form" => "woocommerce_after_edit_account_address_form",
                                    "Before edit acount address form" => "woocommerce_before_edit_account_address_form",
                                    "After edit account adress form" => "woocommerce_after_edit_account_address_form",
                                    "Before account payment methods" => "woocommerce_before_account_payment_methods",
                                    "After account payment methods" => "woocommerce_after_account_payment_methods",
                                    "Before edit account form" => "woocommerce_before_edit_account_form",
                                    "Edit account form start" => "woocommerce_edit_account_form_start",
                                    "Edit account form" => "woocommerce_edit_account_form",
                                    "Edit account form end" => "woocommerce_edit_account_form_end",
                                    "After edit account form" => "woocommerce_after_edit_account_form"
                                )
                            );

                            $pagezones = get_post_meta(get_the_ID(), "ucw_zone_page_names", true);

                            foreach($woopages as $woopage => $zones)
                            { 
                                echo "<optgroup label='".translate( $woopage, 'unlimited-codes-woocommerce' )."'>";

                                foreach($zones as $name => $zone )
                                {
                                    echo "<option ";

                                    if(in_array($zone, $pagezones))
                                        echo " selected ";

                                    echo " value='".$zone."' >".$name."</option>";
                                }

                                echo "</optgroup>";
                            } 

                            ?>
                    </select>
                </p>
                <p>
                    <label for="ucw_product_categories">
                        <?php echo translate( 'Load in this categories:', 'unlimited-codes-woocommerce' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple" id="ucw_product_categories" name="ucw_product_categories[]">

                    <?php

                        $cats = get_post_meta(get_the_ID(), "ucw_product_categories", true);

                          $taxonomy     = 'product_cat';
                            $orderby      = 'name';  
                            $show_count   = 0;
                            $pad_counts   = 0;      
                            $hierarchical = 1;      
                            $title        = '';  
                            $empty        = 0;

                            $args = array(
                                    'taxonomy'     => $taxonomy,
                                    'orderby'      => $orderby,
                                    'show_count'   => $show_count,
                                    'pad_counts'   => $pad_counts,
                                    'hierarchical' => $hierarchical,
                                    'title_li'     => $title,
                                    'hide_empty'   => $empty
                            );

                        $product_cats = get_categories($args);

                   
                            foreach($product_cats as $cat)
                            {
                                echo "<option ";

                                if(in_array($cat->term_id, $cats))
                                    echo " selected ";

                                echo " value='".$cat->term_id."' >".$cat->name."</option>";
                            }

            
                    ?>
                    </select>
                </p>
                <p>
                    <label for="ucw_product_ids">
                        <?php echo translate( 'Load in this products:', 'unlimited-codes-woocommerce' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple" id="ucw_product_ids" name="ucw_product_ids[]">
                     <?php
                        $args = array(
                        'orderby' => 'title',
                        'order' => 'asc',
                        'numberposts' => -1,
                        'post_type' => "product", 
                        'post_status' => 'publish'
                        ); 

                        $products = get_posts($args); 
        
                        $product_ids = get_post_meta( get_the_ID(), 'ucw_product_ids', true);

                        foreach($products as $product)
                        {
                            echo '<option ';

                                if(in_array($product->ID, $product_ids))
                                    echo ' selected="selected" ';

                            echo ' value="'.$product->ID.'">'.$product->post_title.'</option>';
                        } 

                    ?>
                    </select>
                </p>
                
                <p>
                    <label for="ucw_order_code">
                        <?php echo translate( 'Order:', 'unlimited-codes-woocommerce' ) ?>
                    </label>
                </p>
                <p>
                    <input type="number" value="<?php if(get_post_meta( get_the_ID(), 'ucw_order_code', true) == "") echo "0"; else echo get_post_meta( get_the_ID(), 'ucw_order_code', true) ; ?>" placeholder="<?php echo translate( 'Order:', 'unlimited-codes-woocommerce' ) ?>" name="ucw_order_code" id="ucw_order_code" />
                </p>             
                  
            </div>
        <?php 
        }

        public function ucw_save_data_codes( $post_id )
        {
            if ( "woocode" != get_post_type($post_id) || current_user_can("administrator") != 1 ) return;

            
            $zone_pages = $product_categories = $product_ids = array();

            $validate_zone_pages = $validate_product_categories  = $validate_product_ids = true;

            foreach( $_REQUEST['ucw_zone_page_names'] as $zones)
            {
                if(wp_check_invalid_utf8( $zones, true ) != "")
                    $zone_pages[] = sanitize_text_field($zones);
                else
                    $validate_zone_pages = false;
            }

            foreach( $_REQUEST['ucw_product_categories'] as $categories)
            {
                if(intval($categories))
                    $product_categories[] = intval($categories);
                else
                    $validate_product_categories = false;
            }

            foreach( $_REQUEST['ucw_product_ids'] as $ids)
            {
                if(intval($ids))
                    $product_ids[] = intval($ids);
                else
                    $validate_product_ids = false;
            }

            if($validate_zone_pages )
                update_post_meta( $post_id, 'ucw_zone_page_names', $zone_pages);

            if($validate_product_categories )    
                update_post_meta( $post_id, 'ucw_product_categories',  $product_categories);

            if($validate_product_ids )  
                update_post_meta( $post_id, 'ucw_product_ids',  $product_ids );           
            
            update_post_meta( $post_id, 'ucw_order_code', intval( $_REQUEST['ucw_order_code'] ));
        }

        public function unlimited_codes_woocommerce($zone)
        {
            $result = "";

            foreach($this->codes as $code)
            {
                $post_type = get_post_meta( $code->ID, 'ucw_post_type_id' );
                $post_id = get_post_meta( $code->ID, 'ucw_zone_page_names');
                $exclude_post_id = get_post_meta( $code->ID, 'ucw_exclude_post_code_id'); 
                $post_location = get_post_meta( $code->ID, 'ucw_location_code_page', true );
                
                if($this->check_wpml_languages($code->ID))
                    if(in_array("all", $post_type[0]) || in_array(get_post_type(get_the_id()), $post_type[0]))
                        if( $post_location == $zone)
                            if(in_array(get_the_id(), $post_id[0]) || in_array(-1, $post_id[0]) && !in_array(get_the_id(), $exclude_post_id[0] ))
                                $result .= $code->post_content;
            }	

            return $this->ucw_check_shortcode($result, $code->ID);
        }
                
        public function ucw_plugin_settings_link( $links ) 
        { 
            $settings_link = '<a href="'.admin_url().'/edit.php?post_type=woocode">'.translate( 'Woo Codes', 'unlimited-codes-woocommerce' ).'</a>';
            array_unshift( $links, $settings_link ); 
            return $links; 
        }
    }
}

$GLOBALS['unlimited_codes_woocommerce'] = new unlimited_codes_woocommerce();   
    
?>
