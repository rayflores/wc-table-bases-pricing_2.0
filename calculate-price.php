<?php

//Get prices for product and all addons using measurements
function get_prices($width, $height, $product_id = 0, $options_array) {
	global $wc_price_table, $wpdb;

	$price = 0.0;
	$groupprice = 0.0;
	$result = array('product_price' => null);
	$width = (float) $width;
	$height = (float) $height;
	$result['options_array'] = $options_array;
	if ($width > 0 && $height > 0) {
		$out_of_range_option = get_option(WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_KEY, WC_Price_Table_Hooks::DEFAULT_OUT_OF_RANGE_OPTION);
		//Get product price by measurements
		if ($product_id) {
/*
			$term_id = array();
			$category_terms = wp_get_post_terms($product_id, 'product_cat');
			foreach ($category_terms as $term) {
				$term_id[] = (int) $term->term_id;
			}
*/
			$cost_multiplier_number_field = (int)get_post_meta($product_id, 'cost_multiplier_number_field', true);
			$retail_multiplier_number_field = (int)get_post_meta($product_id, 'retail_multiplier_number_field', true);
			//$msrp_number_field = (int)get_post_meta($product_id, 'msrp_number_field', true);


 			$term_id = WC_Price_Table::get_product_category_id($product_id);
			$category_term_id = (string)implode(', ', $term_id); //$category_terms[0]->term_id;
			$price = $wpdb->get_var($wpdb->prepare(
				"SELECT price FROM {$wc_price_table->cat_price_table_name}
				 WHERE term_id IN ({$category_term_id}) AND width >= %f AND height >= %f
				 ORDER BY width ASC, height ASC",
				$width, $height)
			);
			// RF //
			if (!$price){
				$price = $wpdb->get_var($wpdb->prepare(
					"SELECT price FROM {$wc_price_table->product_price_table_name}
					 WHERE product_id = {$product_id} AND width >= %f AND height >= %f
					 ORDER BY width ASC, height ASC",
					$width, $height)
				);
				$price = $price * ($cost_multiplier_number_field / 100) * ($retail_multiplier_number_field / 100);
				$groupprice = $price + $option_total;
			}

			// RF //
			//If out of range get nearest value
			if (!$price && $out_of_range_option == WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_A) {
				$price = $wpdb->get_var($wpdb->prepare(
					"SELECT price FROM {$wc_price_table->cat_price_table_name}
					 WHERE term_id IN ({$category_term_id})
					 ORDER BY ABS(width - %f) ASC, ABS(height - %f) ASC",
					$width, $height)
				);
			}

		}


		$result['product_price'] = (float) $price;
		$result['group_price'] = (float) $groupprice;
		$result['selected_option_price'] = $option_total;
		//Get prices for addons by measurements
		$result['addon_prices'] = array();
		$addons = WC_Price_Table::get_addons2();
		foreach ($addons as $taxonomy => $addon) {
			foreach ($addon['terms'] as $term) {
				$addon_price = $wpdb->get_var($wpdb->prepare(
					"SELECT price FROM {$wc_price_table->addon_price_table_name}
					 WHERE field_label = %s AND choice = %s AND width >= %f AND height >= %f
					 ORDER BY width ASC, height ASC",
					$taxonomy, $term->slug, $width, $height)
				);
				//If out of range get nearest value
				if (!$addon_price && $out_of_range_option == WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_A) {
					$addon_price = $wpdb->get_var($wpdb->prepare(
						"SELECT price FROM {$wc_price_table->addon_price_table_name}
						 WHERE field_label = %s AND choice = %s
						 ORDER BY ABS(width - %f) ASC, ABS(height - %f) ASC",
						$taxonomy, $term->slug, $width, $height)
					);
				}
				//Check if found a price table for addon
				if ($addon_price) {
					$result['addon_prices'][$taxonomy][$term->slug] = array(
						'name' => $term->name,
						'price' => (float) $addon_price
					);
				}
			}
		}


	}
	return $result;
}
//Get prices for product with options selected
function get_price_with_options($selected_option_price, $matrix_price, $product_id = 0, $options_array) {
    global $wc_price_table, $wpdb;

    $price = 0.0;

    $result = array('product_total' => $price);
    $selected_option_price = (float) $selected_option_price;
    $matrix_price = (float) $matrix_price;

    if ($selected_option_price > 0 ) {

        if ($product_id) {
            $price = (float)$selected_option_price + (float)$matrix_price;
			$result['selected_option_price'] = $selected_option_price;
	        $result['matrix_price'] = $matrix_price;
	        $result['product_total'] = $price;
        }

    } else {
        if ($product_id) {
            $result['selected_option_price'] = $selected_option_price;
	        $result['matrix_price'] = $matrix_price;
	        $result['product_total'] = $matrix_price; // product total
        }
    }

	$result['options_sel_index'] = $options_array['option_sel_index'];
    return $result;
}

function get_field_label_maps($lead, $form) {
	$selected_field_values = array();
	$lead_keys = array();
	foreach ($form["fields"] as $field) {
		$selected_field_value = RGFormsModel::get_lead_field_value($lead, $field);
		if (isset($field['label'])) {
			$selected_field_values[$field['label']] = $selected_field_value;
			$lead_keys[$field['label']] = $field['id'];
		}
	}
	return array(
		'selected_field_values' => $selected_field_values,
		'lead_keys' => $lead_keys
	);
}
	
//Strip out the serialized prices from field choice text
function strip_serialized_price(&$selected_field_value) {
	$text_parts = explode('|', $selected_field_value);
	$selected_field_value = $text_parts[0];
	return $selected_field_value;
}
	
function calculate_cart_price($product_info, $form, $lead, $product_id = 0) {
	if ($product_id != 0) {
		foreach ($product_info['products'] as $product_key => $product) {

			// Create new field value array in format field_label => selected_field_value and lead key array in format field_label => lead_key
			$field_label_maps = get_field_label_maps($lead, $form);
			$selected_field_values = $field_label_maps['selected_field_values'];
			$lead_keys = $field_label_maps['lead_keys'];

			//Strip out the serialized prices from the field choice text
			array_walk($selected_field_values, 'strip_serialized_price');
			// var_dump($selected_field_values);

			//Get form field values
			$width = $selected_field_values['Width:'];
			$height = $selected_field_values['Height:'];

			//Update prices in product info object
			if ($width && $height) {
				//Do database lookup for prices by measurement
				$prices = get_prices($width, $height, $product_id);

				//Get updated product price if have product id
				if ($product_id)
					$product_info['products'][$product_key]['price'] = $prices['product_price'];

				//Get updated addon prices if we have selected an addon whose price is calculated by measurement
				foreach ($selected_field_values as $field_label => $selected_field_value) {
					$addon_price = $prices['addon_prices'][$field_label][$selected_field_value];
					if ($addon_price) {
						$addon_field_key = get_field_key_from_label($product, $field_label);
						$product_info['products'][$product_key]['options'][$addon_field_key]['price'] = $addon_price;
					}
				}
			}
		}
	}
	return $product_info;
}

function get_field_key_from_label($product, $label) {
	foreach ($product['options'] as $key => $field) {
		if ($field['field_label'] == $label)
			return $key;
	}
	return false;
}