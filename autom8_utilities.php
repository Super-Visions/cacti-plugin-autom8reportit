<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013 Super-Visions BVBA                                   |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 */

include_once($config['base_path'].'/plugins/autom8/autom8_utilities.php');

function display_ds_rule_items($title, $rule_id, $rule_type, $module) {
	global $colors, $autom8_op_array, $autom8_oper;

	$items = db_fetch_assoc("SELECT * " .
					"FROM plugin_autom8_report_rule_items " .
					"WHERE rule_id=" . $rule_id .
					" ORDER BY sequence");

	html_start_box("<strong>$title</strong>", "100%", $colors["header"], "3", "center", $module . "?action=item_edit&id=" . $rule_id . "&rule_type=" . $rule_type);

	print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
	DrawMatrixHeaderItem("Item",$colors["header_text"],1);
	DrawMatrixHeaderItem("Sequence",$colors["header_text"],1);
	DrawMatrixHeaderItem("Operation",$colors["header_text"],1);
	DrawMatrixHeaderItem("Field",$colors["header_text"],1);
	DrawMatrixHeaderItem("Operator",$colors["header_text"],1);
	DrawMatrixHeaderItem("Pattern",$colors["header_text"],1);
	DrawMatrixHeaderItem("&nbsp;",$colors["header_text"],2);
	print "</tr>";

	$i = 0;
	if (sizeof($items) > 0) {
		foreach ($items as $item) {
			#print "<pre>"; print_r($item); print "</pre>";
			$operation = ($item["operation"] != 0) ? $autom8_oper{$item["operation"]} : "&nbsp;";

			form_alternate_row_color($colors["alternate"],$colors["light"],$i); $i++;
			$form_data = '<td><a class="linkEditMain" href="' . htmlspecialchars($module . "?action=item_edit&id=" . $rule_id. "&item_id=" . $item["id"] . "&rule_type=" . $rule_type) . '">Item#' . $i . '</a></td>';
			$form_data .= '<td>' . 	$item["sequence"] . '</td>';
			$form_data .= '<td>' . 	$operation . '</td>';
			$form_data .= '<td>' . 	$item["field"] . '</td>';
			$form_data .= '<td>' . 	(($item["operator"] > 0 || $item["operator"] == "") ? $autom8_op_array["display"]{$item["operator"]} : "") . '</td>';
			$form_data .= '<td>' . 	$item["pattern"] . '</td>';
			$form_data .= '<td><a href="' . htmlspecialchars($module . '?action=item_movedown&item_id=' . $item["id"] . '&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/move_down.gif" border="0" alt="Move Down"></a>' .
							'<a	href="' . htmlspecialchars($module . '?action=item_moveup&item_id=' . $item["id"] .	'&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/move_up.gif" border="0" alt="Move Up"></a>' . '</td>';
			$form_data .= '<td align="right"><a href="' . htmlspecialchars($module . '?action=item_remove&item_id=' . $item["id"] .	'&id=' . $rule_id .	'&rule_type=' . $rule_type) .
							'"><img src="../../images/delete_icon.gif" border="0" width="10" height="10" alt="Delete"></a>' . '</td></tr>';
			print $form_data;
		}
	} else {
		print "<tr><td><em>No Rule Items</em></td></tr>\n";
	}

	html_end_box(true);

}

function duplicate_autom8_report_rules($report_ids, $name_format) {
	global $fields_autom8_report_rules_create, $fields_autom8_report_rules_edit;
	
	// find needed fields
	$fields_autom8_report_rules = $fields_autom8_report_rules_create + $fields_autom8_report_rules_edit;
	$save_fields = array();
	foreach($fields_autom8_report_rules as $field => &$array ){
		if (!preg_match('/^hidden/', $array['method'])) {
			$save_fields[] = $field;
		}
	}
	
	// prepare queries
	$rule_sql = 'SELECT ' . implode(', ', $save_fields) . ' FROM plugin_autom8_report_rules WHERE id = %d LIMIT 1;';
	$match_items_sql = 'SELECT * FROM plugin_autom8_match_rule_items WHERE rule_id = %d AND rule_type = %d;';
	$rule_items_sql = 'SELECT * FROM plugin_autom8_report_rule_items WHERE rule_id = %d;';
		
	foreach($report_ids as $id){
		
		// get current rule details
		$rule = db_fetch_row(sprintf($rule_sql, $id));
		$match_items = db_fetch_assoc(sprintf($match_items_sql, $id, AUTOM8_RULE_TYPE_REPORT_MATCH));
		$rule_items = db_fetch_assoc(sprintf($rule_items_sql, $id, AUTOM8_RULE_TYPE_REPORT_ACTION));
		
		// apply some changes
		$rule['name'] = str_replace('<rule_name>', $rule['name'], $name_format);
		$rule['enabled'] = '';
		$rule['id'] = 0;
		
		// save rule
		$rule_id = sql_save($rule, 'plugin_autom8_report_rules');
				
		// save rule match items
		foreach ($match_items as $match_item) {
			$match_item['id'] = 0;
			$match_item['rule_id'] = $rule_id;
			
			sql_save($match_item, 'plugin_autom8_match_rule_items');
		}
		
		// save rule items
		foreach ($rule_items as $rule_item) {
			$rule_item['id'] = 0;
			$rule_item['rule_id'] = $rule_id;
			
			sql_save($rule_item, 'plugin_autom8_report_rule_items');
		}
	}
}

function display_item_edit_form($autom8_rule, $autom8_item, $title, $module, $fields){
	global $colors;

	if (!empty($autom8_item['id'])) {
		$header_label = '[edit rule item for ' . $title . ': ' . $autom8_rule['name'] . ']';
	}else{
		$header_label = '[new rule item for ' . $title . ': ' . $autom8_rule['name'] . ']';
	}

	print '<form method="post" action="' . $module . '" name="form_autom8_global_item_edit">';
	html_start_box('<strong>Rule Item</strong> ' . $header_label, '100%', $colors['header'], 3, 'center', '');
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';
	#print '<pre>'; print_r($_fields_rule_item_edit); print '</pre>';

	draw_edit_form(array(
		'config' => array('no_form_tag' => true),
		'fields' => inject_form_variables($fields, $autom8_item, $autom8_rule),
	));

	html_end_box();
	
	//Now we need some javascript to make it dynamic
?>
<script type="text/javascript">

toggle_operation();
toggle_operator();

function toggle_operation() {
	// right bracket ")" does not come with a field
	if (document.getElementById('operation').value == '<?php print AUTOM8_OPER_RIGHT_BRACKET;?>') {
		//alert("Sequence is '" + document.getElementById('sequence').value + "'");
		document.getElementById('field').value = '';
		document.getElementById('field').disabled='disabled';
		document.getElementById('operator').value = 0;
		document.getElementById('operator').disabled='disabled';
		document.getElementById('pattern').value = '';
		document.getElementById('pattern').disabled='disabled';
	} else {
		document.getElementById('field').disabled='';
		document.getElementById('operator').disabled='';
		document.getElementById('pattern').disabled='';
	}
}

function toggle_operator() {
	// if operator is not "binary", disable the "field" for matching strings
	if (document.getElementById('operator').value == '<?php print AUTOM8_OPER_RIGHT_BRACKET;?>') {
		//alert("Sequence is '" + document.getElementById('sequence').value + "'");
	} else {
	}
}
</script>
<?php
}

?>
