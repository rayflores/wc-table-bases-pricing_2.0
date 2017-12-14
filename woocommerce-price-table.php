<?php

/*
  Plugin Name: Woo Table Based Pricing
  Plugin URI: http://plugins.dvelopit.co.nz/woo-pricing-table-importer/
  Description: Adds price table lookup for products
  Depends: WooCommerce
  Version: 2.0
  Author: DvelopIT
  Author URI: http://www.dvelopit.co.nz/
 */

include_once plugin_dir_path(__FILE__) . 'calculate-price.php';
include_once plugin_dir_path(__FILE__) . 'woocommerce-price-table-hooks.php';

class WC_Price_Table {

    public $cat_price_table_name;
    public $addon_price_table_name;

    public function __construct() {
        global $wpdb;
        $this->cat_price_table_name = $wpdb->prefix . 'woocommerce_cat_price_table';
        $this->addon_price_table_name = $wpdb->prefix . 'woocommerce_addon_price_table';
		$this->product_price_table_name = $wpdb->prefix . 'woocommerce_product_price_table';
		$this->multi_product_price_table_name = $wpdb->prefix . 'woocommerce_multi_product_price_table';
        add_action('admin_menu', array(&$this, 'admin_menu'));  
        add_action(
                'wp_ajax_woo-price-table-importer-ajax', array(&$this, 'render_ajax_action'));
        WC_Price_Table_Hooks::hook();
    }

    public function admin_menu() {
        add_management_page(
                'Woo Table Based Pricing', 'Woo Table Based Pricing', 'manage_options', 'woo-price-table-importer', array('WC_Price_Table', 'render_admin_action'));
    }

    public static function render_admin_action() {
        $DIR = plugin_dir_path(__FILE__);
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'upload';
        $page = $DIR . "woo-price-table-importer-{$action}.php";
        require_once $DIR . 'woo-price-table-importer-common.php';
        if (!file_exists($page) || !is_readable($page)) {
            $page = $DIR . 'woo-price-table-importer-404.php';
        }
        require_once $page;
    }

    public function render_ajax_action() {
        require_once plugin_dir_path(__FILE__)
                . 'woo-price-table-importer-ajax.php';
    }

    //Remove the ':' character from the end of field labels
    public static function optionize_label($field_label) {
        $label_parts = explode(':', $field_label);
        return $label_parts[0];
    }

    public static function normalize_taxonomy_name($name, $prefix = 'pa_') {
        $prefix_length = strlen($prefix);
        if ($prefix_length > 0 && strpos($name, $prefix) == 0) {
            $name = substr($name, $prefix_length);
        }
        return ucwords(str_replace(array('-', '_'), ' ', $name));
    }

    public static function fill_taxonomy_terms($terms = array(), &$target) {
        foreach ($terms as $term) {
            $target[$term->taxonomy]['terms'][] = (object) array(
                        'term_id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
            );
        }
    }
	public static function fill_product_list($products = array(), &$target) {
        foreach ($products as $product) {
			$_product = wc_get_product($product->ID);

            //$target['products'][$_product->get_type()][] = (object) array(
			$target['products'][] = (object) array(
                        'product_id' => $_product->get_ID(),
                        'name' => $_product->get_name(),
						'slug' => $_product->get_slug(),
						'type' => $_product->get_type(),
            );
			
        }
    }
	public static function get_product_list(){
			global $wpdb;
		$full_product_list = array();
		$post_type = 'product';
		$normalized_name = 'Single Product';
		$array = array(
					'normalized_name' => $normalized_name,
					'products' => array()
				);
		
		$products = get_posts([
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			]);
		

		self::fill_product_list($products, $array);
		return $array;
		// foreach( $full_product_list as $single_product ){
			
		// }
	}
	public static function get_multi_product_list(){
			global $wpdb;
		$full_product_list = array();
		$post_type = 'product';
		$normalized_name = 'Multiple Products';
		$array = array(
					'normalized_name' => $normalized_name,
					'products' => array()
				);
		
		$products = get_posts([
			'post_type' => $post_type,
			'post_status' => 'publish',
			]);
		

		self::fill_product_list($products, $array);
		return $array;
		// foreach( $full_product_list as $single_product ){
			
		// }
	}
    public static function get_product_categories() {
        global $woocommerce;
        $taxonomy = 'product_cat';
        $normalized_name = 'Product Category';
        $array = array(
            $taxonomy => array(
                'normalized_name' => $normalized_name,
                'terms' => array()
            )
        );
//1.7        $terms = get_terms($taxonomy);
//2.0 Version

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);
        self::fill_taxonomy_terms($terms, $array);
        return $array;
    }

    public static function get_product_category_id($product_id) {
        $term_id = array();
        $category_terms = wp_get_post_terms($product_id, 'product_cat');
        foreach ($category_terms as $term) {
            $term_id[] = (int) $term->term_id;
        }
        return $term_id;
    }

    public static function get_addons2() {
        global $woocommerce;
        if (!$woocommerce) {
            echo 'Requires WooCommerce';
            return array();
        }
        $addons = array();
        $is_2_1 = version_compare($woocommerce->version, '2.1');
        $has_new_func = function_exists(
                'wc_get_attribute_taxonomy_names');
        $has_old_func = method_exists(
                $woocommerce, 'get_attribute_taxonomy_names');
        if ($is_2_1 >= 0 && $has_new_func) {
            // from 2.1
            $tax = wc_get_attribute_taxonomy_names();
        } elseif ($has_old_func) {
            // pre 2.1
            $tax = $woocommerce->get_attribute_taxonomy_names();
        } else {
            $tax = array();
        }
        foreach ($tax as $taxonomy) {
            $name = self::normalize_taxonomy_name($taxonomy);
            $addons[$taxonomy] = array(
                'normalized_name' => $name,
                'terms' => array()
            );
        }
//  1.7       $terms = get_terms($tax);
//  2.0 Version

        $terms = get_terms([
            'taxonomy' => $tax,
            'hide_empty' => false,
        ]);
        self::fill_taxonomy_terms($terms, $addons);
        return $addons;
    }
	
	

    public function activate() {
        global $wpdb;
        $sql = 'CREATE TABLE IF NOT EXISTS'
                . " {$this->cat_price_table_name} ("
                . ' term_id INT NOT NULL,'
                . ' width FLOAT NOT NULL,'
                . ' height FLOAT NOT NULL,'
                . ' price FLOAT NOT NULL,'
                . ' CONSTRAINT id PRIMARY KEY'
                . ' (term_id, width, height)'
                . ')';
        $wpdb->query($sql);
        $wpdb->query("ALTER TABLE `{$this->cat_price_table_name}`"
                . ' MODIFY COLUMN `width` FLOAT NOT NULL,'
                . ' MODIFY COLUMN `height` FLOAT NOT NULL');
		// SINGLE PRODUCT PRICE
		$sql = 'CREATE TABLE IF NOT EXISTS'
                . " {$this->product_price_table_name} ("
                . ' product_id INT NOT NULL,'
                . ' width FLOAT NOT NULL,'
                . ' height FLOAT NOT NULL,'
                . ' price FLOAT NOT NULL,'
                . ' CONSTRAINT id PRIMARY KEY'
                . ' (product_id, width, height)'
                . ')';
        $wpdb->query($sql);
        $wpdb->query("ALTER TABLE `{$this->product_price_table_name}`"
                . ' MODIFY COLUMN `width` FLOAT NOT NULL,'
                . ' MODIFY COLUMN `height` FLOAT NOT NULL');
		// MULRIPLE PRODUCT PRICE
		$sql = 'CREATE TABLE IF NOT EXISTS'
                . " {$this->multi_product_price_table_name} ("
                . ' product_ids LONGTEXT NOT NULL,'
                . ' width FLOAT NOT NULL,'
                . ' height FLOAT NOT NULL,'
                . ' price FLOAT NOT NULL,'
                . ' CONSTRAINT id PRIMARY KEY'
                . ' (term_id, width, height)'
                . ')';
        $wpdb->query($sql);
        $wpdb->query("ALTER TABLE `{$this->multi_product_price_table_name}`"
                . ' MODIFY COLUMN `width` FLOAT NOT NULL,'
                . ' MODIFY COLUMN `height` FLOAT NOT NULL');
        $sql = 'CREATE TABLE IF NOT EXISTS'
                . " {$this->addon_price_table_name} ("
                . ' field_label VARCHAR(255) NOT NULL,'
                . ' choice VARCHAR(255) NOT NULL,'
                . ' width FLOAT NOT NULL,'
                . ' height FLOAT NOT NULL,'
                . ' price FLOAT NOT NULL,'
                . ' CONSTRAINT id PRIMARY KEY'
                . ' (field_label, choice, width, height)'
                . ')';
        $wpdb->query($sql);
        $wpdb->query("ALTER TABLE `{$this->addon_price_table_name}`"
                . ' MODIFY COLUMN `width` FLOAT NOT NULL,'
                . ' MODIFY COLUMN `height` FLOAT NOT NULL');
    }

    public function deactivate() {
        /*
          // do not remove tables anymore
          global $wpdb;
          $wpdb->query(
          "DROP TABLE IF EXISTS {$this->cat_price_table_name}"
          );
          $wpdb->query(
          "DROP TABLE IF EXISTS {$this->addon_price_table_name}"
          );
         */
    }

}

$GLOBALS['wc_price_table'] = new WC_Price_Table();
register_activation_hook(
        __FILE__, array($GLOBALS['wc_price_table'], 'activate'));
register_deactivation_hook(
        __FILE__, array($GLOBALS['wc_price_table'], 'deactivate'));

