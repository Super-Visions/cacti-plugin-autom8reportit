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

define("AUTOM8_RULE_TYPE_REPORT_MATCH", 5);
define("AUTOM8_RULE_TYPE_REPORT_ACTION", 6);

define("AUTOM8_ACTION_REPORT_DUPLICATE", 1);
define("AUTOM8_ACTION_REPORT_ENABLE", 2);
define("AUTOM8_ACTION_REPORT_DISABLE", 3);
define("AUTOM8_ACTION_REPORT_DELETE", 99);

define("AUTOM8_REPORT_ACTION_MERGE", 0);
define("AUTOM8_REPORT_ACTION_OVERWRITE", 1);
define("AUTOM8_REPORT_ACTION_DELETE", 2);

# non-gw-cacti compatibility
global $database_idquote;
if(empty($database_idquote)) $database_idquote = '`';

/**
 * plugin_autom8reportit_install    - Initialize the plugin and setup all hooks
 */
function plugin_autom8reportit_install() {

	#api_plugin_register_hook('PLUGINNAME', 'HOOKNAME', 'CALLBACKFUNCTION', 'FILENAME');
	#api_plugin_register_realm('PLUGINNAME', 'FILENAMETORESTRICT', 'DISPLAYTEXT', true);

	# setup all arrays needed
	api_plugin_register_hook('autom8reportit', 'config_arrays', 'autom8reportit_config_arrays', 'setup.php');
	# setup all forms needed
	api_plugin_register_hook('autom8reportit', 'config_settings', 'autom8reportit_config_settings', 'setup.php');
	api_plugin_register_hook('autom8reportit', 'config_form', 'autom8reportit_config_form', 'setup.php');
	# graph provide navigation texts
	api_plugin_register_hook('autom8reportit', 'draw_navigation_text', 'autom8reportit_draw_navigation_text', 'setup.php');
	# setup actions
	api_plugin_register_hook('autom8reportit', 'autom8_data_source_action', 'autom8reportit_data_source_action', 'setup.php');
	api_plugin_register_hook('autom8reportit', 'reportit_autorrdlist', 'autom8reportit_report_action_add', 'setup.php');
	
	# register all php modules required for this plugin
	api_plugin_register_realm('autom8reportit', 'autom8_report_rules.php', 'Plugin Automate -> Maintain Report Rules', true);
	
	# add plugin_autom8_report_rules table
	$data = array();
	$data['columns'][] = array('name' => 'id', 				'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',	 		'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'report_id',		'type' => 'int(11)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'snmp_query_id', 	'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => true, 'default' => 0);
	$data['columns'][] = array('name' => 'action',			'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'enabled', 		'type' => 'char(2)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name'=> 'report_id', 'columns' => 'report_id');
	$data['keys'][] = array('name'=> 'snmp_query_id', 'columns' => 'snmp_query_id');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Autom8 Report Rules';
	api_plugin_db_table_create ('autom8reportit', 'plugin_autom8_report_rules', $data);
	
	# add plugin_autom8_report_rule_items table
	$data = array();
	$data['columns'][] = array('name' => 'id', 			'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'rule_id',		'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'sequence',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'operation',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'field',		'type' => 'varchar(255)', 'NULL' => true,  'default' => '');
	$data['columns'][] = array('name' => 'operator',	'type' => 'smallint(3)', 'unsigned' => 'unsigned', 'NULL' => true, 'default' => 0);
	$data['columns'][] = array('name' => 'pattern', 	'type' => 'varchar(255)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name'=> 'rule_id', 'columns' => 'rule_id');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Autom8 Report Rule Items';
	api_plugin_db_table_create ('autom8reportit', 'plugin_autom8_report_rule_items', $data);
	
}

/**
 * plugin_autom8reportit_check_config - Check if dependencies are met
 * 
 * @return boolean
 */
function plugin_autom8reportit_check_config(){
	return db_fetch_cell("SELECT status FROM plugin_config WHERE directory = 'autom8reportit';") == 0 || api_plugin_is_enabled('autom8');
}

/**
 * plugin_autom8reportit_version    - define version information
 */
function plugin_autom8reportit_version() {
	return autom8reportit_version();
}

/**
 * autom8reportit_version    - Version information (used by update plugin)
 */
function autom8reportit_version() {
    return array(
    	'name'		=> 'Autom8-Reportit',
		'version'	=> '0.01',
		'longname'	=> 'Automate add/remove DS on reports',
		'author'	=> 'Thomas Casteleyn',
		'email'		=> 'thomas.casteleyn@super-visions.com',
		'homepage'	=> 'https://super-visions.com/redmine/projects/groundwork/wiki/Autom8Reportit'
    );
}

/**
 * autom8reportit_draw_navigation_text    - Draw navigation texts
 * @param array $nav            - all current navigation texts
 * returns array                - updated navigation texts
 */
function autom8reportit_draw_navigation_text($nav) {
	// Displayed navigation text under the blue tabs of Cacti
	$nav["autom8_report_rules.php:"] 			= array("title" => "Report Rules", "mapping" => "index.php:", "url" => "autom8_report_rules.php", "level" => "1");
	$nav["autom8_report_rules.php:edit"] 		= array("title" => "(Edit)", "mapping" => "index.php:,autom8_report_rules.php:", "url" => "", "level" => "2");
	$nav["autom8_report_rules.php:actions"] 	= array("title" => "Actions", "mapping" => "index.php:,autom8_report_rules.php:", "url" => "", "level" => "2");
	$nav["autom8_report_rules.php:item_edit"]	= array("title" => "Report Rule Items", "mapping" => "index.php:,autom8_report_rules.php:,autom8_report_rules.php:edit", "url" => "", "level" => "3");
	
    return $nav;
}

/**
 * autom8reportit_config_arrays    - Setup arrays needed for this plugin
 */
function autom8reportit_config_arrays() {
	
	# menu titles
	global $menu;
	$menu["Templates"]['plugins/autom8reportit/autom8_report_rules.php'] = "Report Rules";

}

/**
 * autom8reportit_config_form	- Setup forms needed for this plugin
 */
function autom8reportit_config_form () {
	
	global $fields_autom8_report_rules_create, $fields_autom8_report_rules_edit;
	
	$fields_autom8_report_rules_create = array(
		"name" => array(
			"method" => "textbox",
			"friendly_name" => "Name",
			"description" => "A useful name for this Rule.",
			"value" => "|arg1:name|",
			"max_length" => "255",
			"size" => "60"
		),
		"report_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "REQUIRED: Report",
			"description" => "Choose a Report to apply to this rule.",
			"value" => "|arg1:report_id|",
			"on_change" => "applyReportIdChange(document.form_autom8_rule_edit)",
			"sql" => "SELECT id, description AS name FROM reportit_reports ORDER BY description;"
		),
	);

	$fields_autom8_report_rules_edit = array(
		"snmp_query_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "Data Query",
			"description" => "Choose a Data Query to apply to this rule.",
			"value" => "|arg1:snmp_query_id|",
			"none_value" => "None",
			"on_change" => "applySNMPQueryIdChange(document.form_autom8_rule_edit)",
			"sql" => "SELECT 
	sq.id, 
	sq.name 
FROM snmp_query sq 
JOIN snmp_query_graph sqg 
	ON (sqg.snmp_query_id = sq.id) 
JOIN snmp_query_graph_rrd_sv sqgrs 
	ON (sqgrs.snmp_query_graph_id = sqg.id ) 
JOIN reportit_templates rt 
	USING(data_template_id) 
JOIN reportit_reports rr 
	ON (rr.template_id = rt.id) 
WHERE 
	rr.id = |arg1:report_id| 
GROUP BY sq.id, sq.name 
ORDER BY sq.name;"
		),
		"ds_action" => array(
			"method" => "drop_array",
			"friendly_name" => "Rule Action",
			"description" => "What action needs to be taken with the list of matched data sources.",
			"value" => "|arg1:action|",
			"array" => array(
				AUTOM8_REPORT_ACTION_MERGE => "Merge", 
				AUTOM8_REPORT_ACTION_OVERWRITE => "Overwrite",
			),
			"default" => "0",
		),
		"enabled" => array(
			"method" => "checkbox",
			"friendly_name" => "Enable Rule",
			"description" => "Check this box to enable this rule.",
			"value" => "|arg1:enabled|",
			"default" => "",
			"form_id" => false
		),
	);
}

/**
 * autom8reportit_config_settings    - configuration settings for this plugin
 */
function autom8reportit_config_settings() {
    global $tabs, $settings, $plugins;

    if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) != 'settings.php' || isset($plugins['autom8']))
        return;
	
    $temp = array(
        "autom8_reports_enabled" => array(
			"method" => "checkbox",
			"friendly_name" => "Enable Autom8 generated report Data Items",
			"description" => "When disabled, Autom8 will not actively add or remove any Data Items from reports.<br>" .
				"This will be useful when fiddeling around with Reports to avoid changing Data Items each time you run a report",
			"default" => "",
		),
    );

    /* create a new Settings Tab, if not already in place */
    if (!isset($tabs["misc"])) {
        $tabs["misc"] = "Misc";
    }

    /* and merge own settings into it */
    if (isset($settings["misc"]))
        $settings["misc"] = array_merge($settings["misc"], $temp);
    else
        $settings["misc"] = $temp;
}

/**
 * Perform rule actions to selected data sources
 * 
 * @param array $selected_items
 * @return array
 */
function autom8reportit_data_source_action($selected_items){
	global $database_idquote, $autom8_op_array;
	
	$id_list = implode(',', $selected_items);
	
	// return if we have wrong data
	if(!preg_match('#^([0-9]+,)*[0-9]+$#', $id_list)) return $selected_items;
	
	// find possibly matching report rules
	$report_rule_settings_sql = sprintf("SELECT DISTINCT 
	report_rule.id AS rule_id, 
	report_rule.*, 
	presets.*, 
	data_template_id 
FROM data_template_data 
JOIN reportit_templates report_template 
	USING(data_template_id) 
JOIN reportit_reports report 
	ON(report.template_id = report_template.id) 
JOIN reportit_presets presets 
	ON(presets.id = report.id) 
JOIN plugin_autom8_report_rules report_rule 
	ON(report_rule.report_id = report.id) 
WHERE report_rule.enabled = 'on' 
	AND local_data_id IN(%s);", $id_list );
	$report_rule_settings = db_fetch_assoc($report_rule_settings_sql);
	
	// execute every rule to find matching DS
	foreach ($report_rule_settings as &$report_rule) {
		unset($report_rule['id']);
		
		// get all used data query fields
		$dq_fields_sql = sprintf('SELECT DISTINCT field FROM plugin_autom8_report_rule_items WHERE rule_id = %d AND CHAR_LENGTH(field) > 0 ORDER BY field;', $report_rule['rule_id']);
		$dq_fields = db_fetch_assoc($dq_fields_sql);
		
		// get rule items
		$rule_items_sql = sprintf("SELECT 
	operation, 
	IF(field='',field,CONCAT('hsc_',field,'.field_value')) AS field, 
	operator, 
	pattern 
FROM plugin_autom8_report_rule_items 
WHERE rule_id = %d 
ORDER BY sequence;",  $report_rule['rule_id']);
		$rule_items = db_fetch_assoc($rule_items_sql);
		$rule_items_where = build_rule_item_filter($rule_items);
		
		// get match items
		$match_items_sql = sprintf('SELECT * FROM plugin_autom8_match_rule_items WHERE rule_id = %d AND rule_type = %d ORDER BY sequence;', $report_rule['rule_id'], AUTOM8_RULE_TYPE_REPORT_MATCH);
		$match_items = db_fetch_assoc($match_items_sql);
		$match_items_where = build_rule_item_filter($match_items);
		
		// build SQL query WHERE part
		$sql_where = sprintf('dl.id IN(%s) ' . PHP_EOL, $id_list);
		$sql_where .= empty($match_items_where)? '	AND (1 ' . $autom8_op_array['op'][AUTOM8_OP_MATCHES_NOT] . ' 1) ' . PHP_EOL : '	AND ( ' . $match_items_where . ' ) '.PHP_EOL;
		$sql_where .= empty($rule_items_where)? '	AND (1 ' . $autom8_op_array['op'][AUTOM8_OP_MATCHES_NOT] . ' 1) ' . PHP_EOL : '	AND ( ' . $rule_items_where . ' ) '.PHP_EOL;
		if($report_rule['action'] == AUTOM8_REPORT_ACTION_MERGE) $sql_where .= '	AND rdi.id IS NULL '.PHP_EOL;
		if($report_rule['action'] == AUTOM8_REPORT_ACTION_DELETE) $sql_where .= '	AND rdi.id IS NOT NULL '.PHP_EOL;

		// build SQL query FROM part
		$sql_from = sprintf('data_template_data AS dtd 
LEFT JOIN reportit_data_items AS rdi 
	ON( dtd.local_data_id = rdi.id AND rdi.report_id = %d ) 
JOIN data_local AS dl 
	ON( dl.id = dtd.local_data_id ) 
JOIN ' . $database_idquote . 'host' . $database_idquote .' 
	ON( host.id = dl.host_id ) 
JOIN host_template 
	ON ( host.host_template_id = host_template.id )	
', $report_rule['report_id'] );
	
		// build SQL query SELECT part
		$sql_select = '
	dl.id, 
	IFNULL(rdi.id = dl.id, 0) AS present, 
	dtd.name_cache '.PHP_EOL;
	
		// add some dynamical fields
		foreach ($dq_fields as $dq_field){

			$sql_from .= sprintf('
LEFT JOIN host_snmp_cache AS hsc_%1$s 
	ON( 
		hsc_%1$s.host_id = dl.host_id AND 
		hsc_%1$s.snmp_query_id = dl.snmp_query_id AND 
		hsc_%1$s.snmp_index =  dl.snmp_index AND 
		hsc_%1$s.field_name = \'%1$s\' 
	) ' . PHP_EOL, $dq_field['field']);
		
		}
		
		// find matching DS
		$data_item_list_sql = 'SELECT ' . $sql_select . 'FROM ' . $sql_from . 'WHERE ' . $sql_where . 'ORDER BY dtd.name_cache ASC;';
		$data_item_list = db_fetch_assoc($data_item_list_sql);
		
		// do action with data items
		switch ($report_rule['action']){
			case AUTOM8_REPORT_ACTION_OVERWRITE:
				// remove currently existing data items
				$data_items_clean_sql = sprintf('DELETE FROM reportit_data_items WHERE report_id = %d AND id IN(%s);', $report_rule['report_id'], $id_list);
				db_execute($data_items_clean_sql);
			
			case AUTOM8_REPORT_ACTION_MERGE:
				// prepare adding data items
				$values = array();
				foreach($data_item_list as $data_item){
					$values[] = sprintf("(%d, %d, '%s', '%s', '%s', '%s', '%s', '%s')", 
							$data_item['id'], $report_rule['report_id'], 
							$report_rule['description'], $report_rule['start_day'], 
							$report_rule['end_day'], $report_rule['start_time'], 
							$report_rule['end_time'], $report_rule['timezone']);
				}
				// run query
				if(!empty($values)){
					$data_items_save_sql = sprintf('INSERT INTO reportit_data_items VALUES %s;', implode(', ', $values));
					db_execute($data_items_save_sql);
				}
				
				break;
				
			case AUTOM8_REPORT_ACTION_DELETE:
				// prepare existing data items
				$data_item_ids = array();
				foreach($data_item_list as $data_item){
					$data_item_ids[] = $data_item['id'];
				}
				// delete data items
				$data_items_delete_sql = sprintf('DELETE FROM reportit_data_items WHERE report_id = %d AND id IN(%s);', $report_rule['report_id'], implode(',', $data_item_ids));
				db_execute($data_items_delete_sql);
				
				break;
		}
	}
	
	return $selected_items;
}

/**
 * 
 * @param array $report_settings
 * @return array
 */
function autom8reportit_report_action_add($report_settings){
	
	return $report_settings;
}

?>
