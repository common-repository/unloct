<?php
/*
Plugin Name:       Unloct
Plugin URI:        https://unloct.com
Description:       Unloct is a microsubscription platform. Subscribers pay one monthly fee for unlimited access to an entire network of creators. When creators install this plugin, Unloct subscribers can log into the creators website. When they do, the creator gets paid. Anything that can fit on a website will work on the Unloct platform. Creators can choose how they use the log in system to engage with their fans. Put content behind the paywall, lock down comments so that only subscribers can post, etc.
Version:           3.1.0
Requires at least: 5.4.2
Requires PHP:      7.2
Author:            Unloct
Author URI:        https://unloct.com/about
License:           GPL3
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:       unloct-plugin
Domain Path:       /wordpress
*/

if (class_exists('core_unloct_login')) {
	global $unloct_core_already_exists;
	$unloct_core_already_exists = true;
}
else {
	require_once( plugin_dir_path( __FILE__ ) . '/core/core_unloct_login.php' );
}

if (class_exists('unloct_shortcode')) {
	global $unloct_shortcode;
	$unloct_shortcode = true;
}
else {
	require 'unloct_short_code.php';
}

if (class_exists('visitor_shortcode')) {
	global $visitor_shortcode;
	$visitor_shortcode = true;
}
else {
	require 'visitor_short_code.php';
}

class basic_unloct_login extends core_unloct_login {
	
	protected $PLUGIN_VERSION = '2.0.0';
	
	// Singleton
	private static $instance = null;
	
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function ga_activation_hook($network_wide) {
		parent::ga_activation_hook($network_wide);
	
		$old_options = get_site_option($this->get_options_name());
	
		if (!$old_options) {
			$new_options = $this->get_option_galogin();
			$this->save_option_galogin($new_option);
		}
	}

    protected function add_actions() {
        parent::add_actions();
        
    }
		
	protected function unloct_section_text_end() {
	?>
		<p><b><?php _e( 'For more support on how to use Unloct to monetize your website please visit:' , 'unloct-login'); ?>
		<a href="https://www.unloct.net/" target="_blank">https://www.unloct.net/</a></b>
		</p>
	<?php
	}
	
	
	public function my_plugin_basename() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			$basename = basename(dirname(__FILE__)).'/'.basename(__FILE__);
		}
		return $basename;
	}
	
	protected function my_plugin_url() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			return plugins_url().'/'.basename(dirname(__FILE__)).'/';
		}
		// Normal case (non symlink)
		return plugin_dir_url( __FILE__ );
	}

}

// Global accessor function to singleton
function basicunloctLogin() {
	return basic_unloct_login::get_instance();
}

// Initialise at least once
basicunloctLogin();

if (!function_exists('unloctLogin')) {
	function unloctLogin() {
		return basicunloctLogin();
	}
}

function unloct_setup_widget() {
    if (!class_exists('Unloct_Widget')) {
        require 'unloct_widget.php';
        register_widget('Unloct_Widget');
    }
}
add_action('widgets_init', 'unloct_setup_widget');

?>
