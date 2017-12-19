<?php

/*
  File: woocommerce-price-table-hooks.php
  Woocommerce hooks and helper methods for Price Table Importer
  by Terra
 */

include_once plugin_dir_path(__FILE__) . 'calculate-price.php';

class WC_Price_Table_Hooks
{

    const X_KEY = 'wpti_x';
    const Y_KEY = 'wpti_y';
    const X = 'x';
    const Y = 'y';
    const WFRACT = 'wfract';
    const HFRACT = 'hfract';
    const OPTION_TOTAL = 'option_total';
    const MATRIX_PRICE = 'matrix_price';
    const PRODUCT_TOTAL = 'product_total';
    const ENABLE_KEY = 'wpti_enable';
    const USE_TWO_FIELDS_KEY = 'wpti_use_two_fields';
    const X_NAME_KEY = 'wpti_x_name';
    const Y_NAME_KEY = 'wpti_y_name';
    const X_METRIC_KEY = 'wpti_x_metric';
    const Y_METRIC_KEY = 'wpti_y_metric';
    const OUT_OF_RANGE_OPTION_KEY = 'wpti_out_of_range_option';
    const FIELD_TYPE_KEY = 'wpti_field_type';
    const DEFAULT_ENABLE = 1;
    const DEFAULT_USE_TWO_FIELDS = 1;
    const DEFAULT_X_NAME = 'Width';
    const DEFAULT_Y_NAME = 'Height';
    const DEFAULT_X_METRIC = 'mm';
    const DEFAULT_Y_METRIC = 'mm';
    const DEFAULT_OUT_OF_RANGE_OPTION = 'nearest';
    const DEFAULT_FIELD_TYPE = 'textbox';
    const OUT_OF_RANGE_OPTION_A = 'nearest';
    const OUT_OF_RANGE_OPTION_B = 'block';
    const FIELD_TYPE_TEXTBOX = 'textbox';
    const FIELD_TYPE_SELECTBOX = 'selectbox';
    const WPTI_KEY = 'wpti_options';
    const WPTI_SETTINGS = 'wpti-settings';

    // singleton
    protected static $instance = null;

    private function construct()
    {

    }

    public static function hook()
    {
        // hooking should only be done once
        if (self::$instance === null) {
            self::$instance = new WC_Price_Table_Hooks;
            self::$instance->__hook();
        }
    }

    protected function __hook()
    {
        add_action('admin_init', array(&$this, 'register_settings'));
        add_action('wp_enqueue_scripts',array(&$this,'enqueue_scripts'));
        add_action('wp_ajax_wpti-calculation', array(&$this, 'handle_calculation_ajax'));
        add_action('wp_ajax_nopriv_wpti-calculation', array(&$this, 'handle_calculation_ajax'));
        add_action('wp_ajax_wpti-options-calculation', array(&$this, 'handle_options_calculation_ajax'));
        add_action('wp_ajax_nopriv_wpti-options-calculation', array(&$this, 'handle_options_calculation_ajax'));
        add_action('woocommerce_get_price_html', array(&$this, 'woocommerce_get_price_html'), 10, 2);
        add_action('woocommerce_before_add_to_cart_button', array(&$this, 'woocommerce_before_add_to_cart_button1'), 0);
        add_action('woocommerce_before_add_to_cart_button', array(&$this, 'woocommerce_before_add_to_cart_button2'),
            22);
        add_filter('woocommerce_add_cart_item_data', array(&$this, 'woocommerce_add_cart_item_data'), 50, 3);
        add_filter('woocommerce_get_cart_item_from_session', array(&$this, 'woocommerce_get_cart_item_from_session'),
            10, 2);
        add_filter('woocommerce_get_item_data', array(&$this, 'woocommerce_get_item_data'), 10, 2);
        add_filter('woocommerce_get_item_data', array(&$this, 'woocommerce_get_option_data_item_data'), 15, 2);
        add_filter('woocommerce_get_item_data', array(&$this, 'woocommerce_get_color_data_item_data'), 20, 2);
        add_filter('woocommerce_add_cart_item', array(&$this, 'woocommerce_add_cart_item'), 50, 2);
        add_action('woocommerce_add_order_item_meta', array(&$this, 'woocommerce_add_order_item_meta'), 10, 2);
        add_action('woocommerce_order_item_meta', array(&$this, 'woocommerce_order_item_meta'), 10, 2);
       //add_action('woocommerce_before_main_content', array(&$this, 'show_custom_fields'));

    }
	public function enqueue_scripts(){
    	wp_enqueue_script('formatter-script','//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js',array('jquery'),'', false);
	}
    public function show_custom_fields()
    {
        global $woocommerce, $post;

        $prod = wc_get_product($post->ID);
		echo '<pre>';
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
			print_r( $cart_item['wpti_options'] );
			print_r($cart_item['data']);
        }
        echo '</pre>';


    }

    // options in Settings tab
    public function register_settings()
    {
        register_setting(self::WPTI_SETTINGS, self::ENABLE_KEY, 'intval');
        register_setting(self::WPTI_SETTINGS, self::USE_TWO_FIELDS_KEY, 'intval');
        register_setting(self::WPTI_SETTINGS, self::X_NAME_KEY);
        register_setting(self::WPTI_SETTINGS, self::X_METRIC_KEY);
        register_setting(self::WPTI_SETTINGS, self::Y_NAME_KEY);
        register_setting(self::WPTI_SETTINGS, self::Y_METRIC_KEY);
        register_setting(self::WPTI_SETTINGS, self::OUT_OF_RANGE_OPTION_KEY);
        register_setting(self::WPTI_SETTINGS, self::FIELD_TYPE_KEY);
    }

    // return prices by specified width and height
    public function handle_calculation_ajax()
    {
        $width = (float)array_key_exists(self::X, $_REQUEST) ? $_REQUEST[self::X] : 1;
        $height = (float)array_key_exists(self::Y, $_REQUEST) ? $_REQUEST[self::Y] : 1;
        $product_id = (int)array('product_id', $_REQUEST) ? $_REQUEST['product_id'] : 0;
        $widthfraction = $_REQUEST[self::WFRACT] ? $_REQUEST[self::WFRACT] : 0;
        $heightfraction = $_REQUEST[self::HFRACT] ? $_REQUEST[self::HFRACT] : 0;
        $matrix_price = $_REQUEST[self::MATRIX_PRICE] ? $_REQUEST[self::MATRIX_PRICE] : 0;
        $option_sel_index = $_REQUEST['option_sel_index'] ? $_REQUEST['option_sel_index'] : null;
        $prices = array(
            'product_price' => 0,
            'addon_prices'  => array()
        );
	    $fraction_array = array();
	    $fraction_array[] = array(
		    'width_fraction' => (float)$widthfraction,
		    'height_fraction' => (float)$heightfraction,
		    'width' => (float)$width,
		    'height' => (float)$height,
	    );

	    $options_array = array();
	    if (have_rows('addon', $product_id)) :
		    while (have_rows('addon', $product_id)) : the_row();
			    $addon_index = get_row_index();
			    $name = get_sub_field('addon_name', $product_id);
			    $which = get_sub_field('addon_price', $product_id);
			    $fixed = get_sub_field('addon_fixed_price', $product_id);
			    $percentage = get_sub_field('addon_fixed_percentage', $product_id);
			    $addon_price = $which != 'fixed' ? $percentage : $fixed;

			    $options_array[] = array(
				    'addon_name'  => $name,
				    'addon_which' => $which,
				    'addon_price' => $addon_price,
				    'addon_index' => $addon_index,
				    'matrix_price' => $matrix_price,
				    'option_sel_index' => $option_sel_index
			    );

		    endwhile;
	    endif;

        if ( $widthfraction !== 0 ) {
            $width = $width + 1;
        } else {
            $width = $width;
        }
        if ( $heightfraction !== 0 ) {
            $height = $height + 1;
        } else {
            $height = $height;
        }
        if ($width && $height && $product_id) {
            $prices = get_prices($width, $height, $product_id, $options_array);
        }



        $prices['addons'] = $matrix_array;

        $prices['fractions'] = $fraction_array;
        $prices['currency'] = get_woocommerce_currency();
        $prices['currency_symbol'] = get_woocommerce_currency_symbol();
        die(json_encode($prices));
    }

    // return prices by specified options selection
    public function handle_options_calculation_ajax()
    {
        $product_id = (int)array('product_id', $_REQUEST) ? $_REQUEST['product_id'] : 0;
        $matrix_price = $_REQUEST['matrix_price'] ? (float)$_REQUEST['matrix_price'] : 0;
        $selected_option_price = $_REQUEST['option_price'] ? (float)$_REQUEST['option_price'] : 0;
        $option_sel_index = $_REQUEST['option_sel_index'] ? $_REQUEST['option_sel_index'] : 1 ;

        $prices = array(
            'product_total' => 0,
            'matrix_price'  => 0,
            'selected_option_price' => 0,
        );
        $options_array = array(
	        'option_sel_index' => $option_sel_index,
        );
        if ( $matrix_price && $product_id ){
            $prices = get_price_with_options($selected_option_price, $matrix_price, $product_id, $options_array);
        }


        $prices['currency'] = get_woocommerce_currency();
        $prices['currency_symbol'] = get_woocommerce_currency_symbol();
        die(json_encode($prices));
    }

    // check if product's category has price table attached
    protected function use_price_table($product_id)
    {
        global $wc_price_table, $wpdb;
        if (get_option(self::ENABLE_KEY, self::DEFAULT_ENABLE) == 0) {
            return false;
        }
        $count = 0;
        $term_id = array();
        $category_terms = wp_get_post_terms($product_id, 'product_cat');
        foreach ($category_terms as $term) {
            $term_id[] = (int)$term->term_id;
        }
        if (count($term_id)) {
            $category_term_id = (string)implode(', ', $term_id);
            $table = $wc_price_table->cat_price_table_name;
            $sql = "SELECT COUNT(term_id) count FROM {$table} WHERE term_id IN ({$category_term_id})";
            $count = (int)$wpdb->get_var($sql);
            if ($count > 0) {
                return $count;
            } else {
                $table = $wc_price_table->product_price_table_name;
                $sql = "SELECT COUNT(product_id) count FROM {$table} WHERE product_id = {$product_id}";
                $count = (int)$wpdb->get_var($sql);
                if ($count > 0) {
                    return $count;
                }
            }
        }
        return ($count > 0);


    }

    public function woocommerce_get_price_html($price, $instance)
    {
        return $this->use_price_table($instance->id) ? '' : $price;
    }

    public function woocommerce_before_add_to_cart_button1()
    {
        global $woocommerce, $wpdb, $post, $wc_price_table;

        if ($this->use_price_table($post->ID)) {
            $product = get_product($post->ID);
            $cost_multiplier_number_field = get_post_meta($post->ID, 'cost_multiplier_number_field', true);
            $retail_multiplier_number_field = get_post_meta($post->ID, 'retail_multiplier_number_field', true);
            $msrp_number_field = get_post_meta($post->ID, 'msrp_number_field', true);
            $x_input_name = self::X_KEY;
            $y_input_name = self::Y_KEY;
            $x_name = get_option(self::X_NAME_KEY, self::DEFAULT_X_NAME);
            $x_metric = get_option(self::X_METRIC_KEY, self::DEFAULT_X_METRIC);
            $y_name = get_option(self::Y_NAME_KEY, self::DEFAULT_Y_NAME);
            $y_metric = get_option(self::Y_METRIC_KEY, self::DEFAULT_Y_METRIC);
            $out_of_range_option = get_option(self::OUT_OF_RANGE_OPTION_KEY, self::DEFAULT_OUT_OF_RANGE_OPTION);
            $field_type = get_option(self::FIELD_TYPE_KEY, self::DEFAULT_FIELD_TYPE);
            $currency = get_woocommerce_currency();
            $stock = ($product->is_in_stock() ? 'InStock' : 'OutOfStock');
            $site_url = get_site_url();
            $update_event = 'input';
            if ($field_type == self::FIELD_TYPE_SELECTBOX) {
                $term_id = WC_Price_Table::get_product_category_id($post->ID);
                $product_id = $product->id;
                $category_term_id = (string)implode(', ', $term_id);
                $update_event = 'change';
            }
            $output = <<<END
<div id="wptiaddons">
END;
            if ($field_type == self::FIELD_TYPE_TEXTBOX) {
                $output .= <<<END
	<input type="text" name="{$x_input_name}" placeholder="{$x_name}" class="wpti-product-size" id="wpti-product-x">
END;
            } else if ($field_type == self::FIELD_TYPE_SELECTBOX) {
                $single_product_widths = $wpdb->get_results("SELECT DISTINCT(width) FROM {$wc_price_table->product_price_table_name} WHERE product_id ={$product_id}");
                $widths = $wpdb->get_results("SELECT DISTINCT(width) FROM {$wc_price_table->cat_price_table_name} WHERE term_id IN({$category_term_id})");
                $output .= "\t".'<div style="width: 50%; float: left;"><select name="'.$x_input_name.'" class="wpti-product-size " id="wpti-product-x">'
                    .'<option value="">'.$x_name.'</option>';
                if ($single_product_widths) {
                    foreach ($single_product_widths as $index => $entry) {
                        if ($entry === reset($single_product_widths))
                            $first = $entry->width;
                       // $output .= '<option data-first="first" value="'.$entry->width.'">'.$entry->width.'-'.$index.'</option>';

                        if ($entry === end($single_product_widths))
                            $last = $entry->width;
                           // $output .= '<option data-last="last" value="'.$entry->width.'">'.$entry->width.'-'.$index.'</option>';
                    }
                    for ($i = $first; $i <= $last; $i++) {
                        $output .= '<option value="' . $i . '">' . $i . '</option>';
                    }
                } else {
                    foreach ($widths as $entry) {
                        $output .= '<option value="'.$entry->width.'">'.$entry->width.'</option>';
                    }
                }
                $output .= '</select>';
                $output .= '<select id="WidthFraction" name="WidthFraction">';
                $output .= '<option selected="selected" value="0">0/0</option>';
                $output .= '<option value="1">1/8</option>';
                $output .= '<option value="2">1/4</option>';
                $output .= '<option value="3">3/8</option>';
                $output .= '<option value="4">1/2</option>';
                $output .= '<option value="5">5/8</option>';
                $output .= '<option value="6">3/4</option>';
                $output .= '<option value="7">7/8</option>';
                $output .= '</select>';
            }
            $output .= "\t<label style='display: block; clear: both;'>{$x_metric}</label></div>\n";
            if (get_option(self::USE_TWO_FIELDS_KEY, self::DEFAULT_USE_TWO_FIELDS) == 1) {
                //$output .= "\tx";
                if ($field_type == self::FIELD_TYPE_TEXTBOX) {
                    $output .= <<<END
	<input type="text" name="{$y_input_name}" placeholder="{$y_name}" class="wpti-product-size" id="wpti-product-y">
END;
                } else if ($field_type == self::FIELD_TYPE_SELECTBOX) {
                    $single_product_heights = $wpdb->get_results("SELECT DISTINCT(height) FROM {$wc_price_table->product_price_table_name} WHERE product_id ={$product_id}");
                    $heights = $wpdb->get_results("SELECT DISTINCT(height) FROM {$wc_price_table->cat_price_table_name} WHERE term_id IN({$category_term_id})");
                    $output .= "\t".'<div style="width: 50%; float: left;"><select name="'.$y_input_name.'" class="wpti-product-size 321Y" id="wpti-product-y">'
                        .'<option value="">'.$y_name.'</option>';
                    if ($single_product_heights) {
                        foreach ($single_product_heights as $index => $entry) {
                            if ($entry === reset($single_product_heights))
                                $firstH = $entry->height;
                            // $output .= '<option data-first="first" value="'.$entry->width.'">'.$entry->width.'-'.$index.'</option>';

                            if ($entry === end($single_product_heights))
                                $lastH = $entry->height;
                            // $output .= '<option data-last="last" value="'.$entry->width.'">'.$entry->width.'-'.$index.'</option>';
                        }
                        for ($i = $firstH; $i <= $lastH; $i++) {
                            $output .= '<option value="' . $i . '">' . $i . '</option>';
                        }
                    } else {
                        foreach ($heights as $entry) {
                            $output .= '<option value="'.$entry->height.'">'.$entry->height.'</option>';
                        }
                    }
                    $output .= '</select>';
                    $output .= '<select id="HeightFraction" name="HeightFraction">';
                    $output .= '<option selected="selected" value="0">0/0</option>';
                    $output .= '<option value="1">1/8</option>';
                    $output .= '<option value="2">1/4</option>';
                    $output .= '<option value="3">3/8</option>';
                    $output .= '<option value="4">1/2</option>';
                    $output .= '<option value="5">5/8</option>';
                    $output .= '<option value="6">3/4</option>';
                    $output .= '<option value="7">7/8</option>';
                    $output .= '</select>';
                }
                $output .= " <label style='display: block; clear: both;'>{$y_metric}</label></div>";
            }
            $output .= <<<END
</div>
<script type="text/javascript">
var single_add_to_cart_button, add_to_cart_button_container;
function addons_all_selected() {
	var $ = jQuery, selected_count = 0, selections = $("table.variations select");
	selections.each(function(key, el){
		if ($(el).find("option:selected").val()) {
			selected_count++;
		}
	});
	return (selected_count == selections.length);
}
function update_price(current_prices, status, jqXHR, undefined) {
	var $ = jQuery, price = '', product_price = '', selected_option_price = '', 
		option_sel_index = $('.option_sel_index').val(),
		terms, slug, selected, term_price, matrix_addons, new_option_price = 0,
		variations, addon, i, swatch, selector, selected_swatch, dataprice = '';

	if (typeof current_prices == 'string') {
		current_prices = JSON.parse(current_prices);
	}
	//console.log(current_prices);

	if (current_prices.product_price) {
	    price = current_prices.product_price;
		product_price = current_prices.product_price;
		selected_option_price = current_prices.selected_option_price;
		price = price + selected_option_price;
		matrix_addons = current_prices.options_array;
		$.each(matrix_addons,function(index, maddon) {
			
			var addon_price = maddon.addon_price;
			var addon_index = maddon.addon_index;
			var addon_which = maddon.addon_which;
			var addon_name = maddon.addon_name;
			var this_addon = $('#select_product_addons option[data-addon-index="' + addon_index + '"]');
			 if (addon_which !== 'fixed' ){
    			var temp_text = $(this_addon).text().split(' + ');
    		    addon_price = current_prices.product_price * (maddon.addon_price/100);
				addon_price = parseFloat((Math.round(addon_price*100)/100).toFixed(2))
				$(this_addon).attr('data-addon-new-price', addon_price);
				$(this_addon).text(temp_text[0] + ' + ' + numeral(addon_price).format('$0,0.00'));
				$(this_addon).val(addon_price);

			 } else {
				var temp_text = $(this_addon).text().split(' + ');
				if (addon_price > 0) {
				    addon_price = parseFloat((Math.round(addon_price*100)/100).toFixed(4));
                    $(this_addon).attr('data-addon-new-price', addon_price);
                    $(this_addon).text(temp_text[0] + ' + ' + numeral(addon_price).format('$0,0.00'));
                    $(this_addon).val(addon_price);
			    }   else {
				    addon_price = addon_price*100;
				    $(this_addon).attr('data-addon-new-price', addon_price);
				    $(this_addon).text(temp_text[0]);
				    $(this_addon).val(addon_price);
			    }
			}
			
			if (option_sel_index == addon_index ){
			     new_option_price = $('#select_product_addons option[data-addon-index="' + addon_index + '"]').val();
			     $('.addons_table_group_option_total .price.amount').text(numeral(new_option_price).format('$0,0.00'));
			     $('.selected_option_meta').val(addon_name);
			     $('.addons_table_group_option_total').attr('data-grouptotal',new_option_price); 
			      						} 
	});
		//price = price + new_option_price;
		if ( new_option_price > 0 ){
		    price = price + parseFloat(new_option_price);
		}

	
		
		
/*
		$("table.variations select").each(function(key, el){
			terms = addons[$(el).attr("id")];
			if (terms === undefined) return;
			$(this).children().each(function(i, entry){
				slug = $(entry).val();
				if (terms[slug] !== undefined) {
					term_price = current_prices.currency_symbol + terms[slug].price;
					$(entry).html(terms[slug].name + " (" + term_price + ")");
				}
			});
			selected = $(this).children("option:selected");
			if (selected) {
				slug = $(selected[0]).val();
				if (terms[slug] === undefined) return;
				price += terms[slug].price;
			}
		});
*/
		variations = $("table.variations select");
		if (variations.size()) {
			variations.each(function(key, el){
				terms = addons[$(el).attr("id")];
				if (terms === undefined) return;
				$(this).children().each(function(i, entry){
					slug = $(entry).val();
					if (terms[slug] !== undefined) {
						term_price = current_prices.currency_symbol + terms[slug].price;
						$(entry).html(terms[slug].name + " (" + term_price + ")");
					}
				});
				selected = $(this).children("option:selected");
				if (selected) {
					slug = $(selected[0]).val();
					if (terms[slug] === undefined) return;
					price += terms[slug].price;
				}
			});
		}
		// compatibility with WooCommerce Color or Image Variation Select addon
		if ($('div.wcvaswatch').size()) {
			for (i in addons) {
				addon = addons[i];
				selector = '.wcvaswatch > .swatchinput > input[name="attribute_' + i + '"]';
				swatch = $(selector);
				if (swatch.size()) {
					selected_swatch = $(selector + ':checked');
					if (addon[selected_swatch.val()]) {
						price += addon[selected_swatch.val()].price;
					}
				}
			}
		}
		dataprice = parseFloat(price.toFixed(3));
		dataprice = parseFloat((Math.round(dataprice*100)/100).toFixed(2));
		
		price = current_prices.currency_symbol + dataprice;
	}

	//$("#wpti-product-price").html(price);
	
	//$("#wpti-product-price").hide();
	$("#addons-wpti-product-price").html(numeral(product_price).format('$0,0.00'));
	$("#addons-wpti-product-price").attr('data-wptiprice', numeral(product_price).format('0.00'));
	$("#addons").attr('data-wptiprice', numeral(dataprice).format('0.00') );
	$('.matrix_price_total').val(numeral(dataprice).format('0.00'));

	$( ".addons_table_tr_order_totals span.price" ).html( numeral(dataprice).format('$0,0.00') );
	$('.total_order_total').val(dataprice);

	if (current_prices.product_price) { 
        $('#select_product_addons').prop('disabled', false);
        $("select.color_option").prop('disabled', false);		
		add_to_cart_button_container.show();
		single_add_to_cart_button.prop('disabled', false);
	}
	else {
		add_to_cart_button_container.hide();
		single_add_to_cart_button.prop('disabled', true);
		$('#select_product_addons').prop('disabled', true);
		$("select.color_option").prop('disabled', true);
	}
	
}

function retrieve_price(e) {
	var $ = jQuery,
		x = $('#wpti-product-x').val(), y = $('#wpti-product-y').val(),
		matrix_price = $('#addons-wpti-product-price').attr('data-wptiprice'),
		option_sel_index = $('.option_sel_index').val(), 
		wfract = $('#WidthFraction').val(), hfract = $('#HeightFraction').val(),
		options = {x: x, y: y, option_sel_index: option_sel_index, matrix_price: matrix_price, wfract: wfract, hfract: hfract, action: 'wpti-calculation', product_id: {$post->ID}};
	add_to_cart_button_container.hide();
	single_add_to_cart_button.prop('disabled', true);
	$.get('{$site_url}/wp-admin/admin-ajax.php', options, update_price);
}
function update_price_with_options(current_all_prices, status, jqXHR, undefined) {
    
    var $ = jQuery, price = '', option_price = '', product_total = '', option_name;

	if (typeof current_all_prices == 'string') {
		current_all_prices = JSON.parse(current_all_prices);
	}
	option_price = numeral(current_all_prices.selected_option_price).format('$0,0.00');
	
	$('.addons_table_group_option_total').attr('data-grouptotal',current_all_prices.selected_option_price);
	$('.addons_table_group_option_total .price.amount').html(option_price);
	product_total = numeral(current_all_prices.product_total).format('$0,0.00');
	$('.addons_table_group_final_total .price.amount').html(product_total);
	$('.total_order_total').val(current_all_prices.product_total);
	//console.log(current_all_prices);
	$('.option_sel_index').val(current_all_prices.options_sel_index);
	var temp_text = $('#select_product_addons option[data-addon-index="' + current_all_prices.options_sel_index + '"]').text().split(' + ');
	option_name = temp_text[0];
	$('.selected_option_meta').val(option_name);
	$('select.color_option').prop('disabled', false);
}
function retrieve_options_price(e){ 
    var $ = jQuery,
		option_price = $('#select_product_addons').find(':selected').data('addon-new-price'),
		option_sel_index = $('#select_product_addons').find(':selected').data('addon-index'),
		matrix_price = $('#addons-wpti-product-price').attr('data-wptiprice'),
		options = {option_sel_index: option_sel_index, option_price: option_price, matrix_price: matrix_price, action: 'wpti-options-calculation', product_id: {$post->ID}};
		console.log(matrix_price);
	    $.get('{$site_url}/wp-admin/admin-ajax.php', options, update_price_with_options);
}

jQuery(document).ready(function($){
	if (  $('#select_product_addons').length ) {
		$('#select_product_addons').prop('disabled','disabled');
	}
	$("select.color_option").prop('disabled','disabled');
	
	single_add_to_cart_button = $('.single_add_to_cart_button');
	add_to_cart_button_container = $('.single_variation_wrap');
	$('.wpti-product-size').on('{$update_event}', retrieve_price);
	
	
	$('#select_product_addons').on('change', function(){
	    var $ = jQuery,
		option_price = $(this).find(':selected').attr('data-addon-new-price'),
		matrix_price = $('#addons').data('wptiprice');
		options = {option_price: option_price, matrix_price: matrix_price, action: 'wpti-options-calculation', product_id: {$post->ID}};
	    $.get('{$site_url}/wp-admin/admin-ajax.php', options, retrieve_options_price);
		
	});
	
	$('table.variations').on('change', retrieve_price);
	// compatibility with WooCommerce Color or Image Variation Select addon
	//$('.wcvaswatch > .swatchinput > input').on('click', retrieve_price);
	single_add_to_cart_button.prop('disabled', true);

    // copy selected value of product image when color dropdown is selected
    $("select.color_option").change(function(){
	$("#selected-color").empty();
        var value = $(this).find("option:selected").attr("data-id");
        //alert(value);
        value = value.toString();
        //console.log(value);
        var img_src = $('.single-product .color-group .collection .sample img[data-id*="'+ value +'"]').attr('src');
        //console.log(img_src);
        var img = $('<img>');
        img.attr('src', img_src);
        $("#selected-color").append(img);
        
        var color_price = $(this).find("option:selected").attr('data-color-price'),
        option_price = $('.addons_table_group_option_total').attr('data-grouptotal'),
        product_price = $('#addons-wpti-product-price').attr('data-wptiprice'),
        color_total = '', 
        all_total = '', 
        color_percentage = '', 
        options_total = '',
        product_with_options_total = '';
        
        console.log(option_price);
        color_percentage = parseFloat(color_price) / 100;
        if(typeof option_price != 'undefined') {
	        product_with_options_total = parseFloat(option_price) + parseFloat(product_price);
	        color_total = color_percentage * product_with_options_total;
	        all_total = parseFloat(color_total) + parseFloat(product_with_options_total);
        } else {
            product_with_options_total = parseFloat(product_price);
	        color_total = color_percentage * product_with_options_total;
	        all_total = parseFloat(color_total) + parseFloat(product_with_options_total);
        }
        $(".addons_table_group_color_total .price").text( numeral(color_total).format("$0,0.00") );
        $('.total_order_total').val(all_total);
        $('.addons_table_group_final_total .price.amount').text(numeral(all_total).format('$0,0.00'));
		var name = $(this).find("option:selected").val();
	    $('.selected_color_meta').val(name);
        

    });

    // copy selected value of product image to color dropdown
    $(".single-product .color-group .collection .sample img").on("click", function(e) {
        $("#selected-color").empty();
        $("select.color_option").val($(this).data("value"));
        var img = $(this).clone();
        $("#selected-color").append(img);
        $("select.color_option").focus();
        $('select.color_option').trigger('change');
    });

	

});
</script>
END;
            echo $output;
        }
    }

    public function woocommerce_before_add_to_cart_button2()
    {
        global $woocommerce, $post;
        $product = wc_get_product($post->ID);
        $x_input_name = self::X_KEY;
        $y_input_name = self::Y_KEY;
        $option_total_name = self::OPTION_TOTAL;
        $matrix_price_name = self::MATRIX_PRICE;
		$product_total_name = self::PRODUCT_TOTAL;
        $x_name = get_option(self::X_NAME_KEY, self::DEFAULT_X_NAME);
        $x_metric = get_option(self::X_METRIC_KEY, self::DEFAULT_X_METRIC);
        $y_name = get_option(self::Y_NAME_KEY, self::DEFAULT_Y_NAME);
        $y_metric = get_option(self::Y_METRIC_KEY, self::DEFAULT_Y_METRIC);
        $currency = get_woocommerce_currency();
        $stock = ($product->is_in_stock() ? 'InStock' : 'OutOfStock');
	    $title = get_field('addon_title');
	    $addons_output = '<div id="addons">';

        if (have_rows('addon', $post->ID)):

	        $addons_output .= '<h3>'.$title.'</h3><p>Please choose size before selecting options</p>';
            $addons_output .= '<select id="select_product_addons" name="'.$option_total_name.'">';
            $addons_output .= '<option value="0" selected="selected">Choose Option</option>';
            // loop through rows (parent repeater)
            while (have_rows('addon', $post->ID)): the_row();
                $index = get_row_index();
                $name = get_sub_field('addon_name');
                $which = get_sub_field('addon_price');
                $fixed = get_sub_field('addon_fixed_price');
                $percentage = get_sub_field('addon_fixed_percentage');
                $addon_price = $which != 'fixed' ? $percentage : $fixed;
                $plus = (int)$addon_price > 0 ? ' + ' : '';
                $addons_output .= '<option data-addon-index="'.$index.'" data-addon-price="'.$addon_price.'" data-addon-type="'.$which.'" value="">'.$name.' + </option>';

            endwhile; // end of the loop.
            $addons_output .= '</select>';
            $addons_output .= '<input type="hidden" class="selected_option_meta" name="selected_option_meta" value=""/>';
        endif; // if have_rows

        // Start Color ACF
        if ( have_rows('collections', $post->ID ) ):
            $addons_output .= '<h3><a href="#ftc-acc-1123280537">Choose Color</a></h3><p>Please choose size before selecting color</p>';
            $addons_output .='<span id="selected-color"><img src="https://placehold.it/75x75"/></span>';
            $addons_output .= '<select name="color_option" class="color_option">';
            $addons_output .= '<option value="0" data-color-price="0" data-price="0" data-id="0" selected="selected">Choose Color</option>';
            $index = 1;
            while ( have_rows('collections', $post->ID )) : the_row();
                $colors = get_sub_field('colors');
                foreach( $colors as $color ):
                    $addons_output .= '<option data-color-index="'.$index.'" data-color-price="'.get_field('price', $color).'" data-id="'.$color.'" data-price="'.get_field('price', $color).'" value="'.get_the_title( $color ).'">'.get_the_title( $color ).'</option>';
                $index++;
                endforeach;
                //$addons_output .= '<option value="'.the_sub_field("collection_title").'">'.the_sub_field("collection_title").'</option>';
            endwhile;
            $addons_output .= '</select>';
            $addons_output .= '<input type="hidden" class="selected_color_meta" name="selected_color_meta" value=""/>';
            $addons_output .= '<br><br>';
        endif;
        // start addons ACF
        $addons_output .= '<div class="addons_table_group_total " data-product-price="" data-product-id="'.$post->ID.'" style="display: block;">';
        $addons_output .= '<table><tbody>';
        $addons_output .= '<tr class="addons_table_tr_product_base_price"><td>Product price:</td>';
        $addons_output .= '<td><div class="addons_table_group_product_price_total"><span id="addons-wpti-product-price" class="price amount ">$0.00</span></div>';
        $addons_output .= '<input type="hidden" class="matrix_price_total" name="'.$matrix_price_name.'" value=""/></td></tr>';
        $addons_output .= '<tr class="addons_table_tr_additional_options"><td>Additional options total:</td><td><div class="addons_table_group_option_total"><span class="price amount">$0.00</span></div><input type="hidden" class="option_sel_index" name="option_sel_index" value=""/></td></tr>';
        $addons_output .= '<tr class="addons_table_tr_color_total"><td>Color total:</td><td><div class="addons_table_group_color_total"><span class="price">$0.00</span></div><input type="hidden" class="color_sel_index" name="option_sel_index" value=""/></td></tr>';

        $addons_output .= '<tr class="addons_table_tr_order_totals"><td>Order total:</td><td><div class="addons_table_group_final_total" data-grouptotal="0"><span class="price amount">$0.00</span></div>';
        $addons_output .= '<input type="hidden" class="total_order_total" name="'.$product_total_name.'" value=""/></td></tr>';
        $addons_output .= '</tbody></table>';
        $addons_output .= '</div>';

        $addons_output .= '</div>';
        echo <<<END
		{$addons_output}
<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
	<p itemprop="price" class="price">
		<span class="amount" id="wpti-product-price"></span>
	</p>
	<meta itemprop="priceCurrency" content="{$currency}" />
	<link itemprop="availability" href="http://schema.org/{$stock}" />
</div>
<style>
.addons_table_group_total {margin-top:2%;}
.addons_table_group_total table td {text-align:right;}
</style>
END;
    }

    public function woocommerce_add_cart_item_data($cart_item = array(), $product_id = 0, $variation_id = 0)
    {
		
		
        if ($this->use_price_table($product_id) || $this->use_price_table($variation_id)) {
            $cart_item[self::WPTI_KEY] = array(
                'x' => array_key_exists(self::X_KEY, $_REQUEST) ? $_REQUEST[self::X_KEY] : 0.01,
                'y' => array_key_exists(self::Y_KEY, $_REQUEST) ? $_REQUEST[self::Y_KEY] : 0.01,
				'width_fraction' => $_REQUEST['WidthFraction'] ? $_REQUEST['WidthFraction'] : 0,
				'height_fraction' => $_REQUEST['HeightFraction'] ? $_REQUEST['HeightFraction'] : 0,
	            'option_total' =>  $_REQUEST[self::OPTION_TOTAL] ? $_REQUEST[self::OPTION_TOTAL] : 0,
	            'product_total' => $_REQUEST[self::PRODUCT_TOTAL] ? $_REQUEST[self::PRODUCT_TOTAL]: 0,
	            'matrix_price' => $_REQUEST[self::MATRIX_PRICE] ? $_REQUEST[self::MATRIX_PRICE]: 0,
                'selected_option_meta' => $_REQUEST['selected_option_meta'] ? $_REQUEST['selected_option_meta'] : '',
	            'selected_color_meta' => $_REQUEST['selected_color_meta'] ? $_REQUEST['selected_color_meta'] : '',
            );
        }
        return $cart_item;
    }

    // set price on cart item if it uses price table
    protected function set_price(&$cart_item)
    {


        if ($this->use_price_table($cart_item['product_id']) || $this->use_price_table($cart_item['variation_id'])) {
            $wpti = &$cart_item[self::WPTI_KEY];
            /*$prices = get_prices($wpti[self::X], $wpti[self::Y], $cart_item['product_id'], $wpti[self::OPTION_TOTAL], $wpti[self::MATRIX_PRICE]);
            $price = $prices['product_price'];*/
            $prices = get_price_with_options(0, $wpti[self::PRODUCT_TOTAL], $cart_item['product_id'], $wpti[self::PRODUCT_TOTAL]);
            $price = $prices['product_total'];
            /*if (array_key_exists('variation',
                    $cart_item) && is_array($cart_item['variation']) && count($cart_item['variation'])) {
                $variations = $cart_item['variation'];
                $addon_prices = &$prices['addon_prices'];
                foreach ($variations as $name => $variation) {
                    // remove 'attribute_' prefix if exists. should start with 'pa_'
                    if (strpos($name, 'attribute_') !== false) {
                        $name = substr($name, strpos($name, 'pa_'));
                    }
                    if (is_array($addon_prices) && array_key_exists($name,
                            $addon_prices) && is_array($addon_prices[$name]) && array_key_exists($variation,
                            $addon_prices[$name])) {
                        $price += $addon_prices[$name][$variation]['price'];
                    }
                }
            }*/
            $cart_item['data']->set_price($price);
        }
    }

    // helper method to retrieve order size in string
    protected function get_size_label()
    {
        $x_name = get_option(self::X_NAME_KEY, self::DEFAULT_X_NAME);
        $y_name = get_option(self::Y_NAME_KEY, self::DEFAULT_Y_NAME);
        $label = $x_name;
        if (get_option(self::USE_TWO_FIELDS_KEY, self::DEFAULT_USE_TWO_FIELDS) == 1) {
            $label .= " x {$y_name}";
        }
        return $label;
    }

    // helper method to retrieve size information in string
    protected function get_size_string(&$cart_item) {
		$cart_item_width_fraction = '';
	if ($cart_item[self::WPTI_KEY]['width_fraction'] === '1'){
		$cart_item_width_fraction = '1/8';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '2'){
		$cart_item_width_fraction = '1/4';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '3'){
		$cart_item_width_fraction = '3/8';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '4'){
		$cart_item_width_fraction = '1/2';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '5'){
		$cart_item_width_fraction = '5/8';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '6'){
		$cart_item_width_fraction = '3/4';
	} elseif ($cart_item[self::WPTI_KEY]['width_fraction'] === '7'){
		$cart_item_width_fraction = '7/8';
	}
	
	$cart_item_height_fraction = '';
	if ($cart_item[self::WPTI_KEY]['height_fraction'] === '1'){
		$cart_item_height_fraction = '1/8';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '2'){
		$cart_item_height_fraction = '1/4';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '3'){
		$cart_item_height_fraction = '3/8';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '4'){
		$cart_item_height_fraction = '1/2';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '5'){
		$cart_item_height_fraction = '5/8';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '6'){
		$cart_item_height_fraction = '3/4';
	} elseif ($cart_item[self::WPTI_KEY]['height_fraction'] === '7'){
		$cart_item_height_fraction = '7/8';
	}
		
		
        $x_value = $cart_item[self::WPTI_KEY][self::X].' '.$cart_item_width_fraction;
        $y_value = $cart_item[self::WPTI_KEY][self::Y].' '.$cart_item_height_fraction;
        $x_metric = get_option(self::X_METRIC_KEY, self::DEFAULT_X_METRIC);
        $y_metric = get_option(self::Y_METRIC_KEY, self::DEFAULT_Y_METRIC);
        $string = "{$x_value} {$x_metric}";
        if (get_option(self::USE_TWO_FIELDS_KEY, self::DEFAULT_USE_TWO_FIELDS) == 1) {
            $string .= " x {$y_value} {$y_metric}";
        }
        return $string;
    }

    public function woocommerce_add_cart_item($cart_item, $cart_item_key)
    {
        $this->set_price($cart_item);
        return $cart_item;
    }

    public function woocommerce_get_cart_item_from_session($cart_item, $cart_item_data)
    {
        if (isset($cart_item_data[self::WPTI_KEY])) {
            $cart_item[self::WPTI_KEY] = $cart_item_data[self::WPTI_KEY];
            $this->set_price($cart_item);
        }
        return $cart_item;
    }

    public function woocommerce_get_item_data($item_data, $cart_item)
    {
        if ($this->use_price_table($cart_item['product_id'])) {
            $item_data[] = array(
                'name'  => $this->get_size_label(),
                'value' => $this->get_size_string($cart_item)
            );

        }
        return $item_data;
    }
    public function woocommerce_get_option_data_item_data($item_data, $cart_item){
        if ($this->use_price_table($cart_item['product_id'])) {
            $item_data[] = array(
                'name'  => 'Option',
                'value'  => $cart_item[self::WPTI_KEY]['selected_option_meta'] . ' - $' . number_format($cart_item[self::WPTI_KEY][self::OPTION_TOTAL], 2),
            );

        }
        return $item_data;
    }
	public function woocommerce_get_color_data_item_data($item_data, $cart_item){
		if ($this->use_price_table($cart_item['product_id'])) {
			$item_data[] = array(
				'name'  => 'Color',
				'value'  => $cart_item[self::WPTI_KEY]['selected_color_meta'],
			);

		}
		return $item_data;
	}

    public function woocommerce_order_item_meta($item_meta, $cart_item)
    {
        if ($this->use_price_table($cart_item['product_id'])) {
            $item_meta->add($this->get_size_label(), $this->get_size_string($cart_item));
            $item_meta->add('Option', $cart_item[self::WPTI_KEY]['selected_option_meta'].' - $'.number_format($cart_item[self::WPTI_KEY][self::OPTION_TOTAL],2));
            $item_meta->add('Color', $cart_item[self::WPTI_KEY]['selected_color_meta']);
        }
    }

    public function woocommerce_add_order_item_meta($item_id, $values) {
        if ($this->use_price_table($values['product_id'])) {
            woocommerce_add_order_item_meta($item_id, $this->get_size_label(), $this->get_size_string($values));
            wc_add_order_item_meta( $item_id, 'Option', $values[self::WPTI_KEY]['selected_option_meta'] . ' - $' . number_format($values[self::WPTI_KEY][self::OPTION_TOTAL],2), false );
            wc_add_order_item_meta( $item_id, 'Color', $values[self::WPTI_KEY]['selected_color_meta']);
        }
    }




}
       