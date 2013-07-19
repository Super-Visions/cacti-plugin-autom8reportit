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

chdir('../../');
include('./include/auth.php');
include_once('./lib/data_query.php');

define('MAX_DISPLAY_PAGES', 21);

$script_url = $config['url_path'].'plugins/acceptance/acceptance_report.php';

$report_rule_actions = array(
AUTOM8_ACTION_REPORT_DUPLICATE => 'Duplicate',
AUTOM8_ACTION_REPORT_ENABLE => 'Enable',
AUTOM8_ACTION_REPORT_DISABLE => 'Disable',
AUTOM8_ACTION_REPORT_DELETE => 'Delete',
);

/* set default action */
if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = ''; }

switch ($_REQUEST['action']) {
	default:
		include_once($config['include_path'] . "/top_header.php");

		autom8_report_rules();

		include_once($config['include_path'] . "/bottom_footer.php");
		break;
}

function autom8_report_rules() {
	global $colors, $report_rule_actions, $script_url, $item_rows;
	#print '<pre>'; print_r($_POST); print_r($_GET); print_r($_REQUEST); print '</pre>';

	$sort_options = array(
		'name' 				=> array('Rule Title', 'ASC'),
		'id' 				=> array('Rule Id', 'ASC'),
		'report_name'		=> array('Report Name', 'ASC'),
		'snmp_query_name' 	=> array('Data Query', 'ASC'),
		'enabled' 			=> array('Enabled', 'ASC'),
	);

	/* if the user pushed the 'clear' button */
	if (get_request_var_request('clear_x')) {
		kill_session_var('sess_autom8_report_rules_filter');
		kill_session_var('sess_autom8_report_rules_sort_column');
		kill_session_var('sess_autom8_report_rules_sort_direction');
		kill_session_var('sess_autom8_report_rules_status');
		kill_session_var('sess_autom8_report_rules_rows');
		kill_session_var('sess_autom8_report_rules_snmp_query_id');

		unset($_REQUEST['filter']);
		unset($_REQUEST['sort_column']);
		unset($_REQUEST['sort_direction']);
		unset($_REQUEST['rule_status']);
		unset($_REQUEST['per_page']);
		unset($_REQUEST['snmp_query_id']);

	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('per_page', 'sess_autom8_report_rules_rows', read_config_option('num_rows_device'));
	load_current_session_value('filter', 'sess_autom8_report_rules_filter', '');
	load_current_session_value('sort_column', 'sess_autom8_report_rules_sort_column', 'name');
	load_current_session_value('sort_direction', 'sess_autom8_report_rules_sort_direction', 'ASC');
	load_current_session_value('rule_status', 'sess_autom8_report_rules_status', -1);
	load_current_session_value('snmp_query_id', 'sess_autom8_report_rules_snmp_query_id', 0);
	
	
	// load page and sort settings
	$page = (int) get_request_var_request('page', 1);
	$per_page = (int) get_request_var_request('per_page');
	if(isset($sort_options[get_request_var_request('sort_column')])) $sort_column = get_request_var_request('sort_column');
	if(in_array(get_request_var_request('sort_direction'), array('ASC','DESC'))) $sort_direction = get_request_var_request('sort_direction');
	
	// filter settings
	$filter = sanitize_search_string(get_request_var_request('filter'));
	$preg_pattern = '#' . str_replace(array('%','_'), array('.*','.'), preg_quote($filter)) . '#i';
	$preg_replace = '<span style="background-color: #F8D93D;">\\1</span>';
	
	$rule_status = (int) get_request_var_request('rule_status');
	$snmp_query_id = (int) get_request_var_request('snmp_query_id');
	
	
	// extra validation
	if($page < 1) $page = 1;
	
	$available_data_queries = db_fetch_assoc("SELECT DISTINCT " .
		"plugin_autom8_report_rules.snmp_query_id, " .
		"snmp_query.name " .
		"FROM plugin_autom8_report_rules " .
		"LEFT JOIN snmp_query ON (plugin_autom8_report_rules.snmp_query_id=snmp_query.id) " .
		"order by snmp_query.name");
	
	
	/* form the 'where' clause for our main sql query */
	$sql_where = "rule.name LIKE '%" . $filter . "%'";

	if($rule_status == -2) {
		$sql_where .= " AND rule.enabled = 'on'";
	}elseif($rule_status == -3) {
		$sql_where .= " AND rule.enabled <> 'on'";
	}

	if (!empty($snmp_query_id)) {
		$sql_where .= ' AND rule.snmp_query_id = ' . $snmp_query_id;
	}
	
	$total_rows_sql = sprintf('SELECT COUNT(*) FROM plugin_autom8_report_rules rule WHERE %s;',$sql_where);
	$total_rows = db_fetch_cell($total_rows_sql);

	$rules_list_sql = sprintf('SELECT 
	rule.id, 
	rule.name, 
	rule.enabled, 
	report.description AS report_name, 
	snmp_query.name AS snmp_query_name 
FROM plugin_autom8_report_rules rule 
LEFT JOIN reportit_reports report 
	ON( report.id = report_id ) 
LEFT JOIN snmp_query 
	ON (snmp_query.id = snmp_query_id ) 
WHERE %s 
ORDER BY %s %s 
LIMIT %d OFFSET %d;',
		$sql_where ,
		$sort_column,
		$sort_direction,
		$per_page, ($page-1)*$per_page);
	
	$rules_list = db_fetch_assoc($rules_list_sql);
	
	
	# filter box

	print ('<form name="form_autom8_report_rules" method="post" action="autom8_report_rules.php">');

	html_start_box('<strong>Report Rules</strong>', '100%', $colors['header'], '3', 'center', 'autom8_report_rules.php?action=edit');

	$filter_html = '<tr bgcolor="' . $colors['panel'] . '">
					<td>
					<table width="100%" cellpadding="0" cellspacing="0">
						<tr>
							<td nowrap style="white-space: nowrap;" width="50">
								Search:&nbsp;
							</td>
							<td width="1"><input type="text" name="filter" size="40" onChange="applyViewRuleFilterChange(document.form_autom8_report_rules)" value="' . $filter . '">
							</td>
							<td nowrap style="white-space: nowrap;" width="50">
								&nbsp;Status:&nbsp;
							</td>
							<td width="1">
								<select name="rule_status" onChange="applyViewRuleFilterChange(document.form_autom8_report_rules)">
									<option value="-1"'.($rule_status == -1 ?' selected':'').'>Any</option>
									<option value="-2"'.($rule_status == -2 ?' selected':'').'>Enabled</option>
									<option value="-3"'.($rule_status == -3 ?' selected':'').'>Disabled</option>
								</select>
							</td>
							<td nowrap style="white-space: nowrap;" width="50">
								&nbsp;Rows per Page:&nbsp;
							</td>
							<td width="1">
								<select name="per_page" onChange="applyViewRuleFilterChange(document.form_autom8_report_rules)">
									<option value="'. read_config_option('num_rows_device') .'">Default</option>';
	foreach ($item_rows as $key => $value) {
		$filter_html .= PHP_EOL.'<option value="' . $key . '"'.($per_page == $key ? ' selected':'').'>' . $value . '</option>';
	}
	$filter_html .= '
								</select>
							</td>
							<td nowrap style="white-space: nowrap;">&nbsp;<input type="submit"
								name"go" value="Go"><input type="button"
								name="clear_x" value="Clear"></td>
						</tr>
						<tr>
							<td nowrap style="white-space: nowrap;" width="50">
								Data Query:&nbsp;
							</td>
							<td width="1">
								<select name="snmp_query_id" onChange="applyViewRuleFilterChange(document.form_autom8_report_rules)">
									<option value="0"'.(empty($snmp_query_id) ? ' selected' : '' ).'>Any</option>';
	foreach ($available_data_queries as $data_query) {
		$filter_html .= PHP_EOL.'<option value="' . $data_query['snmp_query_id'] . '"'.($snmp_query_id == $data_query['snmp_query_id'] ? ' selected':'').'>' . $data_query['name'] . '</option>';
	}
	$filter_html .= '
								</select>
							</td>
						</tr>
					</table>
					</td>
					<td><input type="hidden" name="page" value="1"></td>
				</tr>';

	print $filter_html;

	html_end_box();

	print "</form>\n";

	
	print '<form name="chk" method="post" action="autom8_report_rules.php">';
	html_start_box('', '100%', $colors['header'], '3', 'center', '');
	
	
	/* generate page list */
	$url_page_select = get_page_list($page, ACCEPTANCE_MAX_DISPLAY_PAGES, $per_page, $total_rows, $script_url.'?');

	$nav = '<tr bgcolor="#' . $colors["header"] . '">
		<td colspan="11">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td align="left" class="textHeaderDark">
						<strong>&lt;&lt; ';
	// previous page
	if ($page > 1) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page-1) . '">';
	$nav .= 'Previous'; 
	if ($page > 1) $nav .= '</a>';

	$nav .= '</strong>
					</td>
					<td align="center" class="textHeaderDark">
						Showing Rows ' . (($per_page*($page-1))+1) .' to '. ((($total_rows < $per_page) || ($total_rows < ($per_page*$page))) ? $total_rows : ($per_page*$page)) .' of '. $total_rows .' ['. $url_page_select .']
					</td>
					<td align="right" class="textHeaderDark">
						<strong>'; 
	// next page
	if (($page * $per_page) < $total_rows) $nav .= '<a class="linkOverDark" href="'.$script_url.'?page=' . ($page+1) . '">';
	$nav .= 'Next'; 
	if (($page * $per_page) < $total_rows) $nav .= '</a>';

	$nav .= ' &gt;&gt;</strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>';

	print $nav;

	
	// display column names
	html_header_sort_checkbox($sort_options, $sort_column, $sort_direction, false);

	$i = 0;
	if (sizeof($rules_list) > 0) {
		foreach ($rules_list as $report_rule) {
			
			form_alternate_row_color($colors['alternate'], $colors['light'], $i, 'line' . $report_rule['id']); $i++;
			
			// rule name
			$report_rule_name = title_trim($report_rule['name'], read_config_option('max_title_graph'));
			$report_rule_title = '<a class="linkEditMain" href="' . htmlspecialchars($script_url.'?action=edit&id=' . $report_rule['id'] . '&page=1" title="' . $report_rule['name']) . '">' . 
					(!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $report_rule_name) : $report_rule_name) . 
					'</a>';
			form_selectable_cell($report_rule_title, $report_rule['id']);
			
			form_selectable_cell($report_rule['id'], $report_rule['id']);
			form_selectable_cell((!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $report_rule['report_name']) : $report_rule['report_name']), $report_rule['id']);
			
			$snmp_query_name = empty($report_rule['snmp_query_name']) ? '<em>None</em>' : $report_rule['snmp_query_name'];			
			form_selectable_cell((!empty($filter) ? preg_replace($preg_pattern, $preg_replace, $snmp_query_name) : $snmp_query_name), $report_rule['id']);
			form_selectable_cell($report_rule['enabled'] == 'on' ? 'Enabled' : 'Disabled', $report_rule['id']);
			form_checkbox_cell($report_rule['name'], $report_rule['id']);

			form_end_row();
		}
		print $nav;
	}else{
		print '<tr><td><em>No Report Rules</em></td></tr>';
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($report_rule_actions);

	print "</form>\n";
	?>
	<script type="text/javascript">
	<!--

	function applyViewRuleFilterChange(objForm) {
		strURL = 'autom8_report_rules.php?rule_status=' + objForm.rule_status.value;
		strURL = strURL + '&rule_rows=' + objForm.rule_rows.value;
		strURL = strURL + '&snmp_query_id=' + objForm.snmp_query_id.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}

	-->
	</script>
	<?php
}

?>
