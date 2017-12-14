<?php
global $wpdb, $wc_price_table;
$upload_dir = wp_upload_dir();
$upload_dir = $upload_dir['baseurl'] . '/csv_import';
$addons = WC_Price_Table::get_addons2();
$product_categories = WC_Price_Table::get_product_categories();
$product_list = WC_Price_Table::get_product_list();
$multi_products = WC_Price_Table::get_multi_product_list();
//
$term_id = array();
foreach ($product_categories as $key => $category) {
    foreach ($category['terms'] as $term) {
        $term_id[] = $term->term_id;
    }
}
$product_cat_result = array();
if (count($term_id)) {
    $product_cat_sql = 'SELECT DISTINCT(cpt.term_id), wt.name, wt.slug FROM'
            . " {$wc_price_table->cat_price_table_name} cpt"
            . " LEFT OUTER JOIN {$wpdb->prefix}terms wt"
            . ' ON cpt.term_id=wt.term_id'
            . ' WHERE cpt.term_id IN(' . implode(',', $term_id) . ')'
            . ' ORDER BY wt.name';
    $product_cat_result = $wpdb->get_results($product_cat_sql);
}
//
$slugs = array();
foreach ($addons as $slug => $addon) {
    $slugs[] = $slug;
}
$addons_sql = 'SELECT field_label, choice FROM'
        . " {$wc_price_table->addon_price_table_name}"
        . ' WHERE field_label IN (\'' . implode("','", $slugs) . '\')'
        . ' GROUP BY field_label, choice'
        . ' ORDER BY field_label';
$addons_result = $wpdb->get_results($addons_sql);
//
//
$product_ids = array();
foreach ($product_list['products'] as $product) {
    $product_ids[] = $product->product_id;
}

$products_sql = 'SELECT product_id FROM'
        . " {$wc_price_table->product_price_table_name}"
        . ' WHERE product_id IN (\'' . implode("','", $product_ids) . '\')'
        . ' GROUP BY product_id'
        . ' ORDER BY product_id';
$products_result = $wpdb->get_results($products_sql);
//
echo render_tabs();
?>
<div class="woo_product_importer_wrapper wrap">
    <div id="icon-tools" class="icon32"><br /></div>
    <?php echo render_title('Upload') ?>
    <form enctype="multipart/form-data" method="post" action="<?php echo get_admin_url() ?>tools.php?page=woo-price-table-importer&action=preview">
        <p>
            <label for="import_csv">File to Import</label>
            <input type="file" name="import_csv">
        </p>
        <p>
            <button class="button-primary" type="submit">Upload and Preview</button>
        </p>
    </form>
    <?php if ( count($product_cat_result) || count($addons_result) || count($products_result) ): ?>
        <h3>Uploaded pricings</h3>
        <table border="1" cellspaing="0" cellpadding="5" style="border-collapse:collapse;min-width:500px">
            <?php if (count($product_cat_result)): ?>
                <tr>
                    <th colspan=4">Product categories</th>
                </tr>
                <tr>
                    <th colspan="2">Name</th>
                    <th></th>
                    <th></th>
                <?php endif; ?>
                <?php foreach ($product_cat_result as $category): ?>
                <tr>
                    <td colspan="2"><?= $category->name ?></td>
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=remove&term=<?= $category->term_id ?>">remove</a></td>
                    <?php
                    $file_path = "";
                    $file_path = $upload_dir . '/' . get_option("wootbp_" . $category->term_id);
                    ?>
                    <!--<td align="center"><a target="_blank" href="<?php echo $file_path; ?>">Download</a></td>-->
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=view-file&term=<?= $category->term_id; ?>">view</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($addons_result)): ?>
                <tr>
                    <th colspan="4">Addons</th>
                </tr>
                <tr>
                    <th>Name</th>
                    <th>Choice</th>
                    <th></th>
                    <th></th>
                </tr>
            <?php endif; ?>
            <?php foreach ($addons_result as $addon): ?>
                <tr>
                    <td><?= WC_Price_Table::normalize_taxonomy_name($addon->field_label) ?></td>
                    <td><?= WC_Price_Table::normalize_taxonomy_name($addon->choice, '') ?></td>
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=remove&addon=<?= $addon->field_label ?>&choice=<?= $addon->choice ?>">remove</a></td>
                    <?php
                    $add_file_path = "";
                    $add_file_path = $upload_dir . '/' . get_option("wootbp_" . $addon->field_label . $addon->choice);
                    ?>
                    <!--<td><a target="_blank" href="<?php echo $add_file_path; ?>">Download</a></td>-->
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=view-file&addon=<?= $addon->field_label ?>&choice=<?= $addon->choice ?>">view</a></td>
                </tr>
            <?php endforeach; ?>
			<?php if (count($products_result)): ?>
                <tr>
                    <th colspan="4">Single Products</th>
                </tr>
                <tr>
                    <th>Name</th>
                    <th>Product ID</th>
                    <th></th>
                    <th></th>
                </tr>
            <?php endif; ?>
            <?php foreach ($products_result as $key => $product): ?>
                <?php $product_id = $product->product_id;
					$product_name = wc_get_product($product_id)->get_title(); 
					?>
				<tr>
                    <td><?= $product_name;   ?></td>
                    <td><?= $product_id; ?></td>
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=remove&product_id=<?= $product_id ?>">remove</a></td>
                    <?php
                    $add_file_path = "";
                    $add_file_path = $upload_dir . '/' . get_option("wootbp_" . $product_id);
                    ?>
                    <!--<td><a target="_blank" href="<?php echo $add_file_path; ?>">Download</a></td>-->
                    <td align="center"><a href="?page=<?= $_REQUEST['page'] ?>&action=view-file&product_id=<?= $product_id ?>">view</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
