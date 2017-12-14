<script type="text/javascript">
    jQuery(document).ready(function($){

    $("#show_debug").click(function(){
    $("#debug").show();
            $(this).hide();
    });
            doAjaxImport(<?php echo intval($_POST['limit']); ?>, 0);
            function doAjaxImport(limit, offset) {
            var data = {
            "action"             : "woo-price-table-importer-ajax",
                    "uploaded_file_path" : <?php echo json_encode($_POST['uploaded_file_path']); ?>,
                    "uploded_file" : <?php echo json_encode($_POST['uploded_file']); ?>,
                    "limit"              : limit,
                    "offset"             : offset,
                    'type'               : "<?php echo $_POST['type']; ?>",
<?php if (isset($_POST['term_id'])): ?>
                'term_id'           : "<?php echo $_POST['term_id']; ?>",
<?php elseif (isset($_POST['product_id'])): ?>
                'product_id'      : "<?php echo $_POST['product_id']; ?>",
<?php elseif (isset($_POST['field_choice'])): ?>
                'field_choice'      : "<?php echo $_POST['field_choice']; ?>",
<?php endif; ?>
            };
                    //ajaxurl is defined by WordPress
                    $.post(ajaxurl, data, ajaxImportCallback);
            }

    function ajaxImportCallback(response_text) {

    $("#debug").append($(document.createElement("p")).text(response_text));
            var response = jQuery.parseJSON(response_text);
            $("#insert_count").text(response.insert_count + " (" + response.insert_percent + "%)");
            $("#remaining_count").text(response.remaining_count);
            $("#row_count").text(response.row_count);
            //show inserted rows
            for (var row_num in response.inserted_rows) {
    var tr = $(document.createElement("tr"));
            if (response.inserted_rows[row_num]['success'] == true) {
    if (response.inserted_rows[row_num]['has_errors'] == true) {
    tr.addClass("error");
    } else {
    tr.addClass("success");
    }
    } else {
    tr.addClass("fail");
    }

    tr.append($(document.createElement("td")).append($(document.createElement("span")).addClass("icon")));
            tr.append($(document.createElement("td")).text(response.inserted_rows[row_num]['row_id']));
<?php if (isset($_POST['term_id'])): ?>
        tr.append($(document.createElement("td")).text(response.inserted_rows[row_num]['term_id']));
                tr.append($(document.createElement("td")).text(response.inserted_rows[row_num]['category_name']));
<?php else: ?>
        tr.append($(document.createElement("td")).text(response.inserted_rows[row_num]['field_label']));
                tr.append($(document.createElement("td")).text(response.inserted_rows[row_num]['field_choice']));
<?php endif; ?>


    var result_messages = "";
            if (response.inserted_rows[row_num]['has_errors'] == true) {
    result_messages += response.inserted_rows[row_num]['errors'].join("\n") + "\n";
    } else {
    result_messages += "No errors.";
    }
    tr.append($(document.createElement("td")).text(result_messages));
            tr.appendTo("#inserted_rows tbody");
    }

    //show error messages
    for (var message in response.error_messages) {
    $(document.createElement("li")).text(response.error_messages[message]).appendTo(".import_error_messages");
    }

    //move on to the next set!
    if (parseInt(response.remaining_count) > 0) {
    doAjaxImport(response.limit, response.new_offset);
    } else {
    $("#import_status").addClass("complete");
    }
    }
    });
</script>
<?php echo render_tabs() ?>
<div class="woo_product_importer_wrapper wrap">
    <div id="icon-tools" class="icon32"><br /></div>
    <?php echo render_title('Results') ?>

    <ul class="import_error_messages"></ul>

    <div id="import_status">
        <div id="import_in_progress">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>img/ajax-loader.gif"
                 alt="Importing. Please do not close this window or click your browser's stop button."
                 title="Importing. Please do not close this window or click your browser's stop button.">

            <strong>Importing. Please do not close this window or click your browser's stop button.</strong>
        </div>
        <div id="import_complete">
            <img src="<?php echo plugin_dir_url(__FILE__) ?>img/complete.png"
                 alt="Import complete!"
                 title="Import complete!">
            <strong>Import Complete! Results below.</strong>
        </div>

        <table>
            <tbody>
                <tr>
                    <th>Processed</th>
                    <td id="insert_count">0</td>
                </tr>
                <tr>
                    <th>Remaining</th>
                    <td id="remaining_count"><?php echo $_POST['row_count'] ?></td>
                </tr>
                <tr>
                    <th>Total</th>
                    <td id="row_count"><?php echo $_POST['row_count'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <table id="inserted_rows" class="wp-list-table widefat fixed pages" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th style="width: 80px;">CSV Row</th>
                <?php if (isset($_POST['term_id'])): ?>
                    <th style="width: 150px;">Term ID</th>
                    <th style="width: 500px;">Category</th>
                <?php elseif (isset($_POST['product_id'])): ?>
					<th style="width: 150px;">Product ID</th>
                    <th style="width: 500px;">Single Product</th>
				<?php else : ?>	
                    <th style="width: 150px;">Addon Type</th>
                    <th style="width: 500px;">Addon</th>
                <?php endif; ?>
                <th>Result</th>
            </tr>
        </thead>
        <tbody><!-- rows inserted via AJAX --></tbody>
    </table>

    <p><a id="show_debug" href="#" class="button">Show Raw AJAX Responses</a></p>
    <div id="debug"><!-- server responses get logged here --></div>

    <p id="credits">
        ajax-loader.gif courtesy of <a href="http://ajaxload.info">ajaxload.info</a>. The gigantic checkmark is public domain. All other icons are from the <a href="http://www.famfamfam.com/lab/icons/silk/">Silk icon set</a> by Mark James.
    </p>
</div>