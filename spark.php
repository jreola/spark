<?php

/**
 * Plugin Name: Spark
 * Plugin URI: http://ciscospark.com
 * Description: This plugin allows you to send notifications to spark chat room when certain events in WordPress occur.
 * Version: 0.0.1
 * Author: Cisco Spark
 * Author URI: http://ciscospark.com
 */
require_once __DIR__ . '/includes/autoload.php';

// Register the autoloader.
WP_Spark_Autoload::register('WP_Spark', trailingslashit(plugin_dir_path(__FILE__)) . '/includes/');

// Runs this plugin.
$GLOBALS['wp_spark'] = new WP_Spark_Plugin();
$GLOBALS['wp_spark']->run(__FILE__);
