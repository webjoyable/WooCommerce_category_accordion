<?php

/**
 * Plugin Name: SL Product & Category Accordion
 * Description: Category and product accordion listing with quantities
 * Version: 1.0
 * Author: SemperLabs
 * Author URI: https://semperlabs.com
 * Text Domain: sl_product_accordion
 * Domain Path: /languages/ 
 **/

class SL_Product_Accordion extends WP_Widget {
    
    public function __construct() {
        
        /* ajax calls */

        add_action( 'wp_ajax_sl_get_category_data', array($this, 'sl_get_category_data' ));
        add_action( 'wp_ajax_nopriv_sl_get_category_data', array($this, 'sl_get_category_data' ));

        $widget_ops = array(
            'classname' => 'sl_product_accordion',
            'description' => 'Category and product accordion listing with quantities'
        );

        parent::__construct('sl_product_accordion', 'SL Product & Category Accordion', $widget_ops);
        
    }
   
    public function sl_load_scripts_and_styles() {
        wp_enqueue_style('sl_styles', plugin_dir_url( __FILE__ ) . 'main.css');
        wp_enqueue_script('sl_scripts', plugin_dir_url( __FILE__ ) . '/js/events.js', array('jquery'));
        
        /* localize path for ajax calls */
        
        wp_register_script( 'sl_acc_localize', plugin_dir_url( __FILE__ ) . '/js/vars.js' );
        wp_localize_script( 'sl_acc_localize', 'ajax_url', admin_url( 'admin-ajax.php' ));
        wp_enqueue_script( 'sl_acc_localize');

    }

    /* get stock quantity of regular products */
    
    public function sl_get_stock_quantity( $cat ) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'product_cat' => $cat
        );

        $loop = new WP_Query( $args );

        while ( $loop->have_posts() ) : $loop->the_post();

        global $product;
        $stock += $product->get_stock_quantity();

        endwhile;
        wp_reset_query();

        return $stock ? $stock : 0;
    }

    /* get stock quantity of sale products */

    public function sl_get_stock_quantity_sale_products() {
        
    }
    
    /* ajax */

    public function sl_cat_has_children( $term_id = 0, $taxonomy = 'product_cat' ) {
        $children = get_categories(
            array(
                'child_of' => $term_id,
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids'
            )
        );
        return $children;
    }

    public function sl_get_category_data() {

        if ( isset($_REQUEST) ) {
        
            // get data for selected category - $_POST['category_id']

            $selected_cat = $_POST['category_id'];

            $args = array(
                'taxonomy' => 'product_cat',
                'order_by' => 'name',
                'hierarchical' => 1,
                'title_li' => '',
                'hide_empty' => 0,
                'parent' => $selected_cat,
                'child_of' => 0
            );

            $categories = get_categories( $args );

            $parsed_data = [];

            foreach ( $categories as $cat ) {

                // check if has children

                $has_subcategories = $this->sl_cat_has_children( $cat->term_id ) ? 'fas fa-chevron-down' : false;
                $quantity = $cat->count . "/" . $this->sl_get_stock_quantity($cat_4->name);
                
                $data = array(
                    'id' => $cat->term_id,
                    'slug' => $cat->slug,
                    'name' => $cat->name,
                    'url' => get_category_link( $cat->term_id ),
                    'is_on_sale' => false,
                    'icon' => $has_subcategories,
                    'quantity' => $quantity
                );
                array_push( $parsed_data, $data );
            }
            echo json_encode($parsed_data);
        wp_die();
        }
    }
    
    /* main */
    
    public function widget( $args, $instance ) {
        $this->sl_load_scripts_and_styles();
        $icon = "fas fa-chevron-down";
        ?>
        <!-- structure -->
        <div class="sl-accordion-wrapper">
            <div class="sl-header">
                <i class="fas fa-shopping-cart"></i><span>Online shop</span>
            </div>
            <!-- top level categories -->
            <?php

            $args = array(
                'taxonomy' => 'product_cat',
                'order_by' => 'name',
                'hierarchical' => 1,
                'title_li' => '',
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => 15
            );
            $top_categories = get_categories( $args );
            ?>
            <div class="sl-acc-ul-wrapper">
                <ul>
                    <?php
                    foreach ( $top_categories as $cat_1 ) {
                       ?>
                        <li class="sub">
                            <a href="#" data-sl-category-id="<?php echo $cat_1->term_id ?>">
                                <i class="<?php echo $icon ?>"></i>
                                <span class="category-link" data-slug="<?php echo get_term_link($cat_1->slug, $cat_1->taxonomy) ?>">
                                    <?php echo $cat_1->name ?>
                                </span>
                                <div class="sl-quantities"><?php echo $cat_1->count . "/" . $this->sl_get_stock_quantity($cat_1->name) ?></div>
                            </a>
                        </li>
                       <?php 
                    }
                    ?>
                </ul>
                <?php
                    // products on sale

                    $args_sale = array(
                        'limit' => -1
                    );

                    $sale_products = wc_get_products( $args_sale );
                ?>
                <li class="sub sl-acc-sale-items">
                    <a href="#">
                        <i class="<?php echo $icon ?>"></i>
                        <span class="category-link" data-slug="proizvodi-na-akciji">PROIZVODI NA AKCIJI</span>
                        <div class="sl-quantities"><?php echo count( $sale_products ) ?></div>
                    </a>

                    <?php
                        if ( $sale_products ) {
                            ?>
                            <ul class="sub-categories sl-sale-products">
                            <?php
                            foreach ( $sale_products as $sp ) {
                                if ( $sp->is_on_sale() ) {
                                ?>
                                    <li class="sub">
                                        <a href="#">
                                            <span class="category-link" data-slug="<?php echo get_permalink($sp->id) ?>">
                                                <?php echo $sp->name ?>
                                            </span>
                                        </a>
                                <?php
                            }
                        }
                    }
                    ?>
                </ul></li>
            </div>
        </div>
        <?php
    }
}

 add_action(
    'widgets_init', function() {
        register_widget('SL_Product_Accordion');
    }
);

?>