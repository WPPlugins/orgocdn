<?php
/**
* Plugin Name: ORGOTECH
* Plugin URI: https://orgotech.com/
* Description: A fast and simple image optimization plugin that makes your site load faster.
* Version: 2.0.4
* Author: Orgo Tech AB
* Author URI: https://orgotech.com
* License: GPLv2 or later
* Text Domain: orgocdn
**/
# Do not allow anyone to access this file directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ORGOCDN_VERSION', '2.0.4' );
define( 'ORGOCDN__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ORGOCDN__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ORGOCDN__BACKUP_DIR', plugin_dir_path( __FILE__ ) . 'backups' );

require_once(ORGOCDN__PLUGIN_DIR . 'class/admin.php');
require_once(ORGOCDN__PLUGIN_DIR . 'class/image.php');
require_once(ORGOCDN__PLUGIN_DIR . 'class/wp-async-request.php');
require_once(ORGOCDN__PLUGIN_DIR . 'class/wp-background-process.php');
require_once(ORGOCDN__PLUGIN_DIR . 'class/background-process.php');
require_once( ORGOCDN__PLUGIN_DIR . 'class/orgocdn.php' );

register_uninstall_hook(__FILE__, array('Orgocdn', 'uninstall'));
register_activation_hook(__FILE__, array('Orgocdn', 'activate'));
register_deactivation_hook(__FILE__, array('Orgocdn', 'deactivate'));
add_action('init', array('Orgocdn', 'init'));
?>
