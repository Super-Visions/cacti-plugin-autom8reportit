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
	
	# register all php modules required for this plugin
	api_plugin_register_realm('autom8reportit', 'autom8_report_rules.php', 'Plugin Automate -> Maintain Report Rules', true);
	
	# add plugin_autom8_report_rules table
	$data = array();
	$data['columns'][] = array('name' => 'id', 				'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',	 		'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'report_id',		'type' => 'int(11)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'snmp_query_id', 	'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => true, 'default' => 0);
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

?>
