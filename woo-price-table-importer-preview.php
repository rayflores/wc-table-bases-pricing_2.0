<?php
if (isset($_FILES['import_csv']['tmp_name'])) {
    $import_data = array();
    $error_messages = array();

    if (function_exists('wp_upload_dir')) {
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'] . '/csv_import';
    } else {
        $upload_dir = dirname(__FILE__) . '/uploads';
    }

    if (!file_exists($upload_dir)) {
        $old_umask = umask(0);
        mkdir($upload_dir, 0755, true);
        umask($old_umask);
    }

    if (!file_exists($upload_dir)) {
        $error_messages[] = "Could not create upload directory '{$upload_dir}'";
    }

    //gets uploaded file extension for security check.
    $uploaded_file_ext = strtolower(pathinfo($_FILES['import_csv']['name'], PATHINFO_EXTENSION));

    //full path to uploaded file. slugifys the file name in case there are weird characters present.
    $new_file_name = uniqid();
    $sanitized_title = sanitize_title(basename($new_file_name, '.' . $uploaded_file_ext));
    $uplodedfile = $new_file_name . '.' . $uploaded_file_ext;
    $uploaded_file_path = "{$upload_dir}/{$sanitized_title}.{$uploaded_file_ext}";


    if ($uploaded_file_ext != 'csv') {
        $error_messages[] = "The file extension '{$uploaded_file_ext}' is not allowed.";
    } else if (move_uploaded_file($_FILES['import_csv']['tmp_name'], $uploaded_file_path)) {
        //now that we have the file, grab contents
        $handle = fopen($uploaded_file_path, 'r');

        if ($handle !== FALSE) {
            while (($line = fgetcsv($handle)) !== FALSE) {
                $import_data[] = $line;
            }

            fclose($handle);
        } else {
            $error_messages[] = 'Could not open file.';
        }
    } else {
        $error_messages[] = 'move_uploaded_file() returned false.';
    }

    if (sizeof($import_data) == 0) {
        $error_messages[] = 'No data to import.';
    }

    $header_row = array_shift($import_data);
    $header_row2 = array_shift($import_data);
    $row_count = sizeof($import_data);
}

//Get product categories and addon field choices to map price table to
$product_list = WC_Price_Table::get_product_list();
$multi_products = WC_Price_Table::get_multi_product_list();
$addons = WC_Price_Table::get_addons2();
$product_categories = WC_Price_Table::get_product_categories();

echo render_tabs();
?>
<div class="woo_product_importer_wrapper wrap">
    <div id="icon-tools" class="icon32"><br /></div>
    <?php echo render_title('Preview') ?>
    <?php if (sizeof($error_messages) > 0): ?>
        <ul class="import_error_messages">
            <?php foreach ($error_messages as $message): ?>
                <li><?php echo $message; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form enctype="multipart/form-data" method="post" action="<?php echo get_admin_url() . 'tools.php?page=woo-price-table-importer&action=result'; ?>">
        <input type="hidden" name="uploaded_file_path" value="<?php echo htmlspecialchars($uploaded_file_path); ?>">
        <input type="hidden" name="row_count" value="<?php echo $row_count; ?>">
        <input type="hidden" name="limit" value="5">
        <input type="hidden" name="uploded_file" value="<?php echo $uplodedfile; ?>">
        <p>
            <button class="button-primary" type="submit">Import</button>
        </p>
        <p>
            <label>Type:</label>
            <select id="type" name="type">
				<option value="product_cat">Product Category</option>
				<option value="multi_products">Multiple Products</option>
				<option value="product_list">Single Product</option>
                
                <?php foreach ($addons as $taxonomy => $addon): ?>
                    <option value="<?php echo $taxonomy ?>"><?php echo $addon['normalized_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p id="replaceable"></p>
        <table class="wp-list-table widefat fixed pages" cellspacing="0">
            <tr class="header_row">
                <?php if (is_array($header_row)) foreach ($header_row as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
            </tr>
            <tr class="header_row">
                <?php if (is_array($header_row2)) foreach ($header_row2 as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
            </tr>
            <tbody>
                <?php foreach ($import_data as $row_id => $row): ?>
                    <tr class="header_row">
                        <th><?php echo htmlspecialchars($row[0]); ?></th>
                        <?php for ($c = 1; $c < count($row); ++$c): ?>
                            <td><?php echo htmlspecialchars($row[$c]); ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>
<script type="text/javascript">
	var product_list = <?php echo json_encode($product_list); ?>,
	multi_list = <?php echo json_encode($multi_products); ?>,
     product_categories = <?php echo json_encode($product_categories) ?>,
            addons = <?php echo json_encode($addons) ?>;
    function update_options(select_name, data) {
        var $ = jQuery, select = $("<select></select>").attr({name: select_name})
                .addClass("map_to"), value;
		if (select_name === 'term_id') {
			data.terms.forEach(function(term) {
				value = select_name === 'term_id' ? term.term_id : term.slug;
				select.append($("<option></option>").append(term.name).val(value));
			});
		}
		if (select_name === 'product_id'){
			data.products.forEach(function(product) {
					value = select_name === 'product_id' ? product.product_id : product.slug;
					select.append($("<option></option>").append(product.name).val(value));
			});
		}
		if (select_name === 'multi_product_ids[]'){
			select.attr({multiple: 'multiple'});
			data.products.forEach(function(product) {
					value = select_name === 'product_id' ? product.product_id : product.slug;
					select.append($("<option></option>").append(product.name).val(value));
			});
		}
		
		console.log(data);
		
			$("#replaceable").empty()
					.append($("<label></label>").append(data.normalized_name + ":"))
					.append(select);
					
			$('[name="multi_product_ids[]"]').select2();
		} // end update_options function


    jQuery(document).ready(function($) {

        $("#type").change(function() {
            $("#type option:selected").each(function() {
                var select_name, data, type = $(this).val();
				console.log(type);
				if (type === 'product_list'){
					select_name = 'product_id';
					data = product_list;
				} else
                if (type === 'product_cat') {
                    select_name = 'term_id';
                    data = product_categories[type];
                } else
                if (type === 'multi_products') {
                    select_name = 'multi_product_ids[]';
                    data = multi_list;
                }
                else {
                    select_name = 'field_choice';
                    data = addons[type];
                }

                update_options(select_name, data);
            });
        });
		
		
		
		if (type === 'product_cat') {
			update_options('term_id', product_categories['product_cat']);
		} else if (type === 'product_list') {
			update_options('product_id', product_list['product_list']);
		}
		
		
        $("#type").trigger("change");
    });
</script>
