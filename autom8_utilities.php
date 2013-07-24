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

?>
