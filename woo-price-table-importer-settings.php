<?php echo render_tabs() ?>
<style>
tr {
	border-bottom: 1px solid #aaa;
}
td label, td input {
	vertical-align: middle;
}
td label {
	display: inline-block;
	width: 150px;
}
</style>
<div class="woo_product_importer_wrapper wrap">
	<div id="icon-tools" class="icon32"><br /></div>
	<?php echo render_title('Settings') ?>
	<form method="post" action="options.php">
		<input type="hidden" name="<?php echo WC_Price_Table_Hooks::ENABLE_KEY ?>" value="<?php echo get_option(WC_Price_Table_Hooks::ENABLE_KEY, WC_Price_Table_Hooks::DEFAULT_ENABLE) ?>" id="<?php echo WC_Price_Table_Hooks::ENABLE_KEY?>">
		<input type="hidden" name="<?php echo WC_Price_Table_Hooks::USE_TWO_FIELDS_KEY ?>" value="<?php echo get_option(WC_Price_Table_Hooks::USE_TWO_FIELDS_KEY, WC_Price_Table_Hooks::DEFAULT_USE_TWO_FIELDS) ?>" id="<?php echo WC_Price_Table_Hooks::USE_TWO_FIELDS_KEY ?>">
<?php

settings_fields('wpti-settings');
do_settings_sections('wpti-settings');

?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Enable</th>
				<td>
					<input type="checkbox"<?php echo get_option(WC_Price_Table_Hooks::ENABLE_KEY, WC_Price_Table_Hooks::DEFAULT_ENABLE) == 1 ? ' checked="checked"' : '' ?> id="wpti-enable-checkbox">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Use two fields</th>
				<td>
					<input type="checkbox"<?php echo get_option(WC_Price_Table_Hooks::USE_TWO_FIELDS_KEY, WC_Price_Table_Hooks::DEFAULT_USE_TWO_FIELDS) == 1 ? ' checked="checked"' : '' ?> id="wpti-use-two-fields-checkbox">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">X</th>
				<td>
					<div>
						<label>Name</label>
						<input type="text" name="wpti_x_name" placeholder="Name" value="<?php echo get_option(WC_Price_Table_Hooks::X_NAME_KEY, WC_Price_Table_Hooks::DEFAULT_X_NAME) ?>">
					</div>
					<div>
						<label>Metric</label>
						<input type="text" name="wpti_x_metric" placeholder="Metric" value="<?php echo get_option(WC_Price_Table_Hooks::X_METRIC_KEY, WC_Price_Table_Hooks::DEFAULT_X_METRIC) ?>">
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Y</th>
				<td>
					<div>
						<label>Name</label>
						<input type="text" name="wpti_y_name" placeholder="Name" value="<?php echo get_option(WC_Price_Table_Hooks::Y_NAME_KEY, WC_Price_Table_Hooks::DEFAULT_Y_NAME) ?>">
					</div>
					<div>
						<label>Metric</label>
						<input type="text" name="wpti_y_metric" placeholder="Metric" value="<?php echo get_option(WC_Price_Table_Hooks::Y_METRIC_KEY, WC_Price_Table_Hooks::DEFAULT_Y_METRIC) ?>">
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Out of range option</th>
				<td>
					<div>
						<label>Select nearest value</label>
						<input type="radio" name="<?php echo WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_KEY ?>" value="<?php echo WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_A ?>"<?php echo (get_option(WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_KEY, WC_Price_Table_Hooks::DEFAULT_OUT_OF_RANGE_OPTION)==WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_A)?' checked="checked"':'' ?>>
					</div>
					<div>
						<label>Block order</label>
						<input type="radio" name="<?php echo WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_KEY ?>" value="<?php echo WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_B ?>"<?php echo (get_option(WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_KEY, WC_Price_Table_Hooks::DEFAULT_OUT_OF_RANGE_OPTION)==WC_Price_Table_Hooks::OUT_OF_RANGE_OPTION_B)?' checked="checked"':'' ?>>
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">X/Y field type</th>
				<td>
					<div>
						<label>Text box</label>
						<input type="radio" name="<?php echo WC_Price_Table_Hooks::FIELD_TYPE_KEY ?>" value="<?php echo WC_Price_Table_Hooks::FIELD_TYPE_TEXTBOX ?>"<?php echo (get_option(WC_Price_Table_Hooks::FIELD_TYPE_KEY,  WC_Price_Table_Hooks::DEFAULT_FIELD_TYPE)==WC_Price_Table_Hooks::FIELD_TYPE_TEXTBOX)?' checked="checked"':'' ?>>
					</div>
					<div>
						<label>Select box</label>
						<input type="radio" name="<?php echo WC_Price_Table_Hooks::FIELD_TYPE_KEY ?>" value="<?php echo WC_Price_Table_Hooks::FIELD_TYPE_SELECTBOX ?>"<?php echo (get_option(WC_Price_Table_Hooks::FIELD_TYPE_KEY,  WC_Price_Table_Hooks::DEFAULT_FIELD_TYPE)==WC_Price_Table_Hooks::FIELD_TYPE_SELECTBOX)?' checked="checked"':'' ?>>
					</div>
				</td>
			</tr>
		</table>
<?php submit_button() ?>
	</form>
<div>
<script type="text/javascript">
jQuery(document).ready(function($){
	$("#wpti-enable-checkbox").click(function(e){
		$("#<?php echo WC_Price_Table_Hooks::ENABLE_KEY ?>").val($(this).is(":checked") ? 1 : 0);
	});
	$("#wpti-use-two-fields-checkbox").click(function(e){
		$("#<?php echo WC_Price_Table_Hooks::USE_TWO_FIELDS_KEY ?>").val($(this).is(":checked") ? 1 : 0);
	});

});
</script>
