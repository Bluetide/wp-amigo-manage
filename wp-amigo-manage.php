<?php

/**
 * Plugin Name:       WP Amigo Manage
 * Plugin URI:        https://wpamigo.com/
 * Description:       Auditor de inventario y vulnerabilidades para el core, plugins y temas de WordPress
 * Version:           1.0.0
 * Requires PHP:      8.0
 * Requires at least: 6.0
 * Author:            BlueTide
 * Author URI:        https://bluetide.dev
 * Text Domain:       wp-amigo-manage
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use WPAmigoManage\Core\Plugin;
use WPAmigoManage\Core\Scheduler;

define('WP_AMIGO_MANAGE_VERSION', '1.0.0');
define('WP_AMIGO_MANAGE_FILE', __FILE__);
define('WP_AMIGO_MANAGE_URL', plugin_dir_url(__FILE__));
define('WP_AMIGO_MANAGE_DIR', plugin_dir_path(__FILE__));
define('WP_AMIGO_MANAGE_WEBHOOK_URL', 'https://bluetide.app.n8n.cloud/webhook/wp-amigo-manage/audit');
define('WP_AMIGO_MANAGE_ASSETS_DIR', WP_AMIGO_MANAGE_DIR . 'assets/');
define('WP_AMIGO_MANAGE_ASSETS_URL', WP_AMIGO_MANAGE_URL . 'assets/');

/**
 * Directories folders
 */
define('WP_AMIGO_MANAGE_CORE_DIR', WP_AMIGO_MANAGE_DIR . 'core/');
define('WP_AMIGO_MANAGE_INCLUDES_DIR', WP_AMIGO_MANAGE_DIR . 'includes/');
define('WP_AMIGO_MANAGE_PROVIDERS_DIR', WP_AMIGO_MANAGE_DIR . 'providers/');


function wp_amigo_manage_init()
{
  Plugin::instance()->run();
}
add_action('plugins_loaded', 'wp_amigo_manage_init', 20);

function wp_amigo_manage_activate()
{
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_amigo_manage_activate');


function wp_amigo_manage_deactivate()
{
  Scheduler::clear_schedule();

  flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wp_amigo_manage_deactivate');
