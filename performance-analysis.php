<?php
/**
 * Plugin Name: Plugin Performance Analysis
 * Description: Track various page and plugin performance metrics over time against previous plugin versions. Useful for monitoring performance of upcoming vs. past releases holistically.
 * Version: 1.2.0
 * Author: The Events Calendar
 * License: GPL2
 */

use PPerf_Analysis\Providers\Activate;
use PPerf_Analysis\StellarWP\DB\DB;
use PPerf_Analysis\StellarWP\DB\Config;


const PPERF_ANALYSIS_VERSION   = '1.2.0';
const PPERF_ANALYSIS_SLUG      = 'plugin-perf';
const PPERF_ANALYSIS_BASE_PATH = __FILE__;
const PPERF_ANALYSIS_BASE_DIR = __DIR__.'/';

require __DIR__ . '/vendor/autoload.php';

function pperf_get_run_table_name() {
	global $wpdb;
	$table_prefix = $wpdb->prefix;

	return "{$table_prefix}pperf_run";
}
function pperf_get_plugin_run_table_name() {
	global $wpdb;
	$table_prefix = $wpdb->prefix;

	return "{$table_prefix}pperf_plugin_run";
}

$act = new Activate();
$act->register();
add_action( 'plugins_loaded', function() {
	Config::setHookPrefix( 'boom-shakalaka' );
	DB::init();
}, 0 );

register_activation_hook( __FILE__, static function () {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$schemas      = [];
	$plugin_run_table = pperf_get_plugin_run_table_name();
	$run_table     = pperf_get_run_table_name();

	$schemas[] = "CREATE TABLE `$run_table` (
`perf_run_id` bigint NOT NULL AUTO_INCREMENT,
`start_time` varchar(50) NOT NULL,
`end_time` varchar(50) NOT NULL,
`request_id` varchar(145) NOT NULL,
`request_uri` varchar(350) NOT NULL,
`num_queries` int(11) DEFAULT NULL,
`active_plugins` text,
`hooks` text,
`plugins_version_hash` varchar(45) NOT NULL,
`created_datetime` datetime DEFAULT NULL,
`total_query_time` varchar(50) NOT NULL,
PRIMARY KEY (`perf_run_id`),
KEY `plugins_version_hash` (`plugins_version_hash`),
KEY `time` (`start_time`,`end_time`)
);";

	$schemas[] = "CREATE TABLE `$plugin_run_table` (
`perf_plugin_run_id` bigint NOT NULL AUTO_INCREMENT,
`perf_run_id` bigint NOT NULL,
`num_queries` int(11) DEFAULT NULL,
`plugin_name` varchar(75) NOT NULL,
`plugin_version` varchar(10) NOT NULL,
`created_datetime` datetime DEFAULT NULL,
`total_query_time` varchar(50) NOT NULL,
PRIMARY KEY (`perf_plugin_run_id`),
KEY (`perf_run_id`),
KEY (`total_query_time`)
);";

	foreach ( $schemas as $schema ) {
		dbDelta( $schema );
	}
} );
