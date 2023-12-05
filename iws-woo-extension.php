<?php

/**
 * Plugin Name: woo-slider
 * Description: All the code in this plugin is for learning purposes only. It is recommended not to use this on live projects.
 * Author: Tanuj Patra
 * Author URI: https://www.youtube.com/channel/UChvgNtbMI8Pnan7R7FrSIng
 * Version: 1.0.0
 * Requires at least: 5.7
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/tanujpatra228/youtube/tree/iws-woo-extension
 * Text Domain: iws-woo-extension
 */

// Terminate if accessed directly
if (!defined('ABSPATH')) {
    die();
}

define('IWS_WOO_EXT_TXT_DOMAIN', 'iws-geo-form-fields');
define('IWS_WOO_EXT_SLUG', 'iws-geo-form-fields');
define('IWS_WOO_EXT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IWS_WOO_EXT_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Include scripts
 */
function iws_load_scripts()
{
    $style_src = 'https://unpkg.com/swiper/swiper-bundle.min.css';
    $style_ver = '1.0.0';
    wp_enqueue_style('iws-swiper-slider', $style_src, '', $style_ver);

    $style_src = IWS_WOO_EXT_PLUGIN_URL . 'assets/css/style.css';
    $style_ver = filemtime(IWS_WOO_EXT_PLUGIN_PATH . 'assets/css/style.css');
    wp_enqueue_style('iws-style', $style_src, 'iws-swiper-slider', $style_ver);

    $script_src = 'https://unpkg.com/swiper/swiper-bundle.min.js';
    $script_ver = '1.0.0';
    wp_enqueue_script('iws-swiper-slider', $script_src, array('jquery'), $script_ver, true);

    $script_src = IWS_WOO_EXT_PLUGIN_URL . 'assets/js/main.js';
    $script_ver = filemtime(IWS_WOO_EXT_PLUGIN_PATH . 'assets/js/main.js');
    wp_enqueue_script('iws-script', $script_src, array('jquery', 'iws-swiper-slider'), $script_ver, true);
}
add_action('wp_enqueue_scripts', 'iws_load_scripts');

function iws_product_slider($atts)
{
    // Check if WooCommerce is active
    if (class_exists('WooCommerce')) {
        $atts = shortcode_atts(
            array(
                'tag' => 'hoodies',
                'count' => 20,
            ),
            $atts
        );


        $tag = explode(',', sanitize_text_field($atts['tag']));
        $count = sanitize_text_field($atts['count']);

        $query = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $tag,
                )
            ),
        );

        $products = new WP_Query($query);

        if ($products->have_posts()) :
            while ($products->have_posts()) :
                $products->the_post();
                echo get_the_title();

            endwhile;
        endif;


        ob_start();
        if ($products->have_posts()) :
?>
            <div class="iws-product-slider woocommerce">
                <div class="iws-inner">
                    <div class="iws-swiper">
                        <div class="swiper-wrapper">
                            <?php
                            while ($products->have_posts()) :
                                $products->the_post();
                                $title = get_the_title();
                                $permalink = get_the_permalink();
                                // $product = WC_Product(get_the_id());
                                $product = wc_get_product(get_the_id());
                                
                                $img = $product->get_image('woocommerce_thumbnail');
                                $avg_rating = $product->get_average_rating();
                                $rating_percent = ($avg_rating / 5) * 100;
                                $rating_count = $product->get_review_count();

                            ?>
                                <div class="swiper-slide">
                                    <div class="iws-slide-content">
                                        <div class="iws-product-img">
                                            <!-- <img src="http://localhost/youtube/wp-content/uploads/2022/02/cap-2-300x300.jpg" alt="product"> -->
                                            <?php echo $img; ?>
                                            <i class="far fa-heart"></i>
                                        </div>
                                        <div class="iws-product-detail">
                                            <p><a href="<?php echo $permalink; ?>"><?php echo $title; ?></a></p>
                                            <div class="star-rating-wrap">
                                                <div class="star-rating">
                                                    <span style="width:<?php echo $rating_percent; ?>%">
                                                        Rated <strong class="rating"><?php echo $avg_rating; ?></strong> out of 5
                                                    </span>
                                                </div>
                                                <span class="total-review">( <?php echo $rating_count; ?> Reviews )</span>
                                            </div>
                                            <div class="price-wrap">
                                                <span class="amount">Rs.7,500</span>
                                                <del class="amount">Rs.12,400</del>
                                                <span class="variant-offer">30% Off</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php
                            endwhile;
                            ?>
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>
<?php
        endif;
        wp_reset_postdata();
        return ob_get_clean();
    }
}
add_shortcode('iws-product-slider', 'iws_product_slider');