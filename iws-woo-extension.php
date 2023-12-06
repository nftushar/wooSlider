<?php

/**
 * Plugin Name: WS Woo-Slider
 * Description: My Woocommerce Slider
 * Author: NF Tushar
 * Version: 1.0.0
 * Requires at least: 5.7
 * Requires PHP: 7.2
 * Text Domain: ws-woo-slider
 */

// Terminate if accessed directly
if (!defined('ABSPATH')) {
    die();
}

define('WS_WOO_SLID_TXT_DOMAIN', 'woo-slider');
define('WS_WOO_SLID_SLUG', 'woo-slider');
define('WS_WOO_SLID_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WS_WOO_SLID_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Include scripts
 */
function ws_load_scripts()
{
    // Enqueue styles
    wp_enqueue_style('ws-swiper-slider', 'https://unpkg.com/swiper/swiper-bundle.min.css', '', '1.0.0');
    wp_enqueue_style('ws-style', WS_WOO_SLID_PLUGIN_URL . 'assets/css/style.css', 'ws-swiper-slider', filemtime(WS_WOO_SLID_PLUGIN_PATH . 'assets/css/style.css'));

    // Enqueue scripts
    wp_enqueue_script('ws-swiper-slider', 'https://unpkg.com/swiper/swiper-bundle.min.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ws-script', WS_WOO_SLID_PLUGIN_URL . 'assets/js/main.js', array('jquery', 'ws-swiper-slider'), filemtime(WS_WOO_SLID_PLUGIN_PATH . 'assets/js/main.js'), true);
}
add_action('wp_enqueue_scripts', 'ws_load_scripts');

/**
 * Get product discount percentage
 */
function ws_get_product_disc($product)
{
    if ($product->is_type('simple')) {
        $reg_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        // Discount% = (Original Price - Sale price) / Original price * 100
        $disc = round((($reg_price - $sale_price) / $reg_price) * 100);
    } elseif ($product->is_type('variable')) {
        $disc_percentage = [];
        $prices = $product->get_variation_prices();

        foreach ($prices['price'] as $key => $price) {
            if ($prices['regular_price'][$key] !== $price) {
                $disc_percentage[] = round((($prices['regular_price'][$key] - $prices['sale_price'][$key]) / $prices['regular_price'][$key]) * 100);
            }
        }
        $disc = max($disc_percentage);
    } elseif ($product->is_type('grouped')) {
        $child_products = $product->get_children();
        $discounts = [];

        foreach ($child_products as $product_id) {
            $child_product = wc_get_product($product_id);
            if ($child_product) {
                $reg_price = $child_product->get_regular_price();
                $sale_price = $child_product->get_sale_price();
                if (!empty($sale_price) && $sale_price != 0) {
                    $discounts[] = round((($reg_price - $sale_price) / $reg_price) * 100);
                }
            }
        }
        $disc = max($discounts);
    }

    $html = "<span class='variant-offer'>$disc% Off</span>";
    return $html;
}

/**
 * Product slider shortcode
 */
function ws_product_slider($atts)
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
            ob_start();
?>
            <div class="ws-product-slider woocommerce">
                <div class="ws-inner">
                    <div class="ws-swiper">
                        <div class="swiper-wrapper">
                            <?php
                            while ($products->have_posts()) :
                                $products->the_post();
                                $title = get_the_title();
                                $permalink = get_the_permalink();
                                $product = wc_get_product(get_the_id());

                                $img = $product->get_image('woocommerce_thumbnail');
                                $avg_rating = $product->get_average_rating();
                                $rating_percent = ($avg_rating / 5) * 100;
                                $rating_count = $product->get_review_count();
                                $price = $product->get_price_html();
                            ?>
                                <div class="swiper-slide">
                                    <div class="ws-slide-content">
                                        <div class="ws-product-img">
                                            <?php echo $img; ?>
                                            <?php echo do_shortcode('[ti_wishlists_addtowishlist loop=yes]'); ?>
                                        </div>
                                        <div class="ws-product-detail">
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
                                                <?php echo $price;
                                                if ($product->is_on_sale()) :
                                                    echo ws_get_product_disc($product);
                                                endif; ?>
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
            return ob_get_clean();
        endif;
        wp_reset_postdata();
    }
}
add_shortcode('ws-product-slider', 'ws_product_slider');
