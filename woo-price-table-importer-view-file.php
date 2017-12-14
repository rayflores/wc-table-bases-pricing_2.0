<?php
$upload_dir = wp_upload_dir();
$download_dir = wp_upload_dir();
$upload_dir = $upload_dir['basedir'] . '/csv_import';
$download_dir = $download_dir['baseurl'] . '/csv_import';
$uploaded_file_path = "";
$download_file_path = "";
$import_data = array();
$error_messages = array();
$term = array_key_exists('term', $_GET) ? $_GET['term'] : 0;
$addon = array_key_exists('addon', $_GET) ? $_GET['addon'] : '';
$choice = array_key_exists('choice', $_GET) ? $_GET['choice'] : '';
$product_id = array_key_exists('product_id', $_GET) ? $_GET['product_id'] : 0;

if ($term) {
    $uploaded_file_path = $upload_dir . '/' . get_option("wootbp_" . $_GET['term']);
    $download_file_path = $download_dir . '/' . get_option("wootbp_" . $_GET['term']);
    $handle = fopen($uploaded_file_path, 'r');
    if ($handle !== FALSE) {
        while (($line = fgetcsv($handle)) !== FALSE) {
            $import_data[] = $line;
        }
        fclose($handle);
    } else {
        $error_messages[] = 'Could not open file.';
    }
} elseif ($addon && $choice) {
    $uploaded_file_path = $upload_dir . '/' . get_option("wootbp_" . $addon . $choice);
    $download_file_path = $download_dir . '/' . get_option("wootbp_" . $addon . $choice);
    $handle = fopen($uploaded_file_path, 'r');
    if ($handle !== FALSE) {
        while (($line = fgetcsv($handle)) !== FALSE) {
            $import_data[] = $line;
        }
        fclose($handle);
    } else {
        $error_messages[] = 'Could not open file.';
    }
} elseif ( $product_id ) {
	$uploaded_file_path = $upload_dir . '/' . get_option("wootbp_" . $product_id);
    $download_file_path = $download_dir . '/' . get_option("wootbp_" . $product_id);
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
    echo "Invalid File";
}

$header_row = array_shift($import_data);
$header_row2 = array_shift($import_data);
$row_count = sizeof($import_data);

echo render_tabs();
?>
<div class="woo_product_importer_wrapper wrap">
    <div style="margin-bottom:10px;">
        <a target="_blank" class="button-primary" href="<?php echo $download_file_path; ?>">Download</a>
    </div>

    <div id="icon-tools" class="icon32"><br /></div>
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
</div>


