<?php
global $wpdb, $wc_price_table;

$term = array_key_exists('term', $_GET) ? $_GET['term'] : 0;
$product_id = array_key_exists('product_id', $_GET) ? $_GET['product_id'] : 0;
$addon = array_key_exists('addon', $_GET) ? $_GET['addon'] : '';
$choice = array_key_exists('choice', $_GET) ? $_GET['choice'] : '';
if ($term) {
    $table = $wc_price_table->cat_price_table_name;
    $where = array('term_id' => $term);
    $where_format = array('%d');
    $wpdb->delete($table, $where, $where_format);
    delete_option('wootbp_' . $term);
} elseif ($product_id) {
    $table = $wc_price_table->product_price_table_name;
    $where = array('product_id' => $product_id);
    $where_format = array('%d');
    $wpdb->delete($table, $where, $where_format);
    delete_option('wootbp_' . $product_id);
} elseif ($addon && $choice) {
    $table = $wc_price_table->addon_price_table_name;
    $where = array(
        'field_label' => $addon,
        'choice' => $choice
    );
    $where_format = array('%s', '%s');
    $wpdb->delete($table, $where, $where_format);
    delete_option('wootbp_' . $addon . $choice);
} else {
    ?>
    <script>
        alert('Invalid parameters.');
    </script>
    <?php
}
?>
<script>
    window.location.href = '?page=<?= $_REQUEST['page'] ?>&tab=upload';
</script>

