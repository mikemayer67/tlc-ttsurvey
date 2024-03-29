<?php
namespace TLC\TTSurvey;

/**
 * Plugin Name: TLC Time and Talent Survey
 * Plugin URI: https://github.com/mikemayer67/tlc-ttsurvey
 * Description: Time and Talent Survey WP Plugin
 * Version: 0.0.2
 * Author: Michael A. Mayer
 * Requires PHP: 7.3.0
 * License: GPLv3
 * License URL: https://www.gnu.org/licenses/gpl-3.0.html
 */

if( ! defined('WPINC') ) { die; }

/**
 * scope the specified string to the plugin namespace
 */
function ns($s) { return __NAMESPACE__.'\\'.$s; }

/**
 * name of the plugin
 */
function plugin_name() { return plugin_basename(__FILE__); }

/**
 * absolute path to the plugin file
 */
function plugin_file() { return __FILE__; }

/**
 * absolute path to the plugin directory
 */
function plugin_dir() { return plugin_dir_path(__FILE__); }

/**
 * convert path relative to the plugin directory to an absoute path
 */
function plugin_path($path) { return plugin_dir() . '/' . $path; }

/**
 * convert path relative to the plugin directory to a URL
 */
function plugin_url($rel_url='') { return plugin_dir_url(__FILE__).'/'.$rel_url; }

/**
 * returns base64 encoded svg icon
 */
function plugin_icon() {
  static $icon = null;
  if(!$icon) {
    $svg = file_get_contents(plugin_path('img/trinity_logo.svg'));
    $icon = 'data:image/svg+xml;base64,' . base64_encode($svg);
  }
  return $icon;
}

/**
 * Determine level of admin access
 *   returns associative array of accesses
 **/
function plugin_admin_access()
{
  $access = array();
  $caps = array('view','manage','content','responses','tech','data');
  foreach($caps as $cap) {
    $access[$cap] = current_user_can("tlc-ttsurvey-$cap");
  }
  if(current_user_can('manage_options')) {
    $access['view'] = 1;
    $access['manage'] = 1;
    $access['data'] = 1;
  }
  return $access;
}

function plugin_admin_can($cap) { 
  return plugin_admin_access()[$cap] ?? false;
}

/**
 * plugin activation hooks
 */

register_activation_hook(   __FILE__, ns('handle_activate') );
register_deactivation_hook( __FILE__, ns('handle_deactivate') );
register_uninstall_hook(    __FILE__, ns('handle_uninstall') );

function handle_activate()
{
  require_once plugin_path('include/const.php');
  require_once plugin_path('include/logger.php');
  require_once plugin_path('include/surveys.php');
  require_once plugin_path('include/users.php');
  log_info('activate: '.__NAMESPACE__);

  $cat_id = category_exists(POST_CATEGORY_NAME);
  if(!$cat_id) {
    $cat_id = wp_insert_category(
      array(
        'cat_name' => POST_CATEGORY_NAME,
        'category_nicename' => POST_CATEGORY_SLUG,
        'category_description' => 'Post entries created and used by the Time & Talent plugin',
      )
    );
  }

  users_activate();
  surveys_activate();

  $admin = get_role('administrator');
  $admin->add_cap('tlc-ttsurvey-view');
}

function handle_deactivate()
{
  require_once plugin_path('include/const.php');
  require_once plugin_path('include/logger.php');
  require_once plugin_path('include/surveys.php');
  require_once plugin_path('include/users.php');
  log_info('deactivate: '.__NAMESPACE__);
  users_deactivate();
  surveys_deactivate();

  $cat_id = category_exists(POST_CATEGORY_NAME);
  if($cat_id) { wp_delete_category($cat_id); }

  $admin = get_role('administrator');
  $admin->remove_cap('tlc-ttsurvey-view');
}

function handle_uninstall()
{
  require_once plugin_path('include/logger.php');
  require_once plugin_path('include/settings.php');
  log_info('uninstall: '.__NAMESPACE__);
  uninstall_options();
}

/**
 * Ajax support
 **/

require_once plugin_path('ajax.php');
add_action('wp_ajax_nopriv_tlc_ttsurvey', ns('ajax_wrapper'));
add_action('wp_ajax_tlc_ttsurvey', ns('ajax_wrapper'));

/**
 * Module support
 **/

function enqueue_javascript_as_module($tag,$handle,$src)
{
  if(str_starts_with($src,plugin_url(''))) {
    $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
  }
  return $tag;
}
add_filter('script_loader_tag',ns('enqueue_javascript_as_module'),10,3);

/**
 * Import admin/shortcode specific functions
 **/

if( is_admin() ) /* Admin setup */
{
  require_once plugin_path('admin/setup.php');
}
else /* Non-admin setup */
{
  require_once plugin_path('include/login.php');
  require_once plugin_path('shortcode/setup.php');
}


