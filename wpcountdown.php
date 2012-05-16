<?php
/**
 * Plugin Name: WP Countdown
 * Short Name: wp_countdown
 * Description: Show Countdown in a post or page
 * Author: Ivan Kristianto
 * Version: 1.1
 * Requires at least: 2.7
 * Tested up to: 3.1
 * Tags: countdown, timer
 * Contributors: Ivan Kristianto
 *
 *
 * WP Countdown - Simple WordPress Timer and Countdown
 * Copyright (C) 2010	IvanKristianto.com
 *
 * This program is free software - you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */
 
 // exit if add_action or plugins_url functions do not exist
if (!function_exists('add_action') || !function_exists('plugins_url')) exit;

// function to replace wp_die if it doesn't exist
if (!function_exists('wp_die')) : function wp_die ($message = 'wp_die') { die($message); } endif;

// define some definitions if they already are not
!defined('WP_CONTENT_DIR') && define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
!defined('WP_PLUGIN_DIR') && define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
!defined('WP_CONTENT_URL') && define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
!defined('WP_PLUGIN_URL') && define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');
!defined('WPC_PATH') && define('WPC_PATH', dirname( __FILE__ ));


// don't load directly
!defined('ABSPATH') && exit;

/**
 * wp_countdown
 * 
 * @package   
 * @author Ivan Kristianto
 * @version 2011
 * @access public
 */
class wp_countdown{
	var $options = array();	// an array of options and values
	var $plugin = array();	// array to hold plugin information
	
	/**
	 * Defined blank for loading optimization
	 */
	function __construct() {}
	
	//Engine start
	function execute(){		
		register_activation_hook(__FILE__,  array( &$this, 'activationCallback'));
		register_deactivation_hook(__FILE__,  array( &$this, 'deactivationCallback'));
		
		add_action('init', array(&$this, 'init'));
	}
	
	public function activationCallback(){
		$this->default_options();
	}
	
	/**
     * A callback called whenever plugin deactivating
     */
    public function deactivationCallback()
    {
		$this->DeleteOptions();
    }
	
	/**
	 * Loads options named by opts array into correspondingly named class vars
	 */
	function LoadOptions($opts=array('options', 'plugin')){
		foreach ($opts as $pn) $this->{$pn} = get_option("wp_countdown{$pn}");
	}
	
	/**
	 * Saves options from class vars passed in by opts array and the adsense key and api key
	 */
	function SaveOptions($opts=array('options','plugin'))	{
		foreach ($opts as $pn) {
			update_option("wp_countdown{$pn}", $this->{$pn});
		}
	}
	
	/**
	 * Saves options from class vars passed in by opts array and the adsense key and api key
	 */
	function DeleteOptions($opts=array('options','plugin'))	{
		foreach ($opts as $pn) delete_option("wp_countdown{$pn}", $this->{$pn});
	}
	
	/**
	 * Gets and sets the default values for the plugin options, then saves them
	 */
	function default_options()	{
		
		// get all the plugin array data
		$this->plugin = $this->get_plugin_data();	
		
		// default options
		$this->options = array(
			'enabled'			 => 0,
		);
		
		// Save all these variables to database
		$this->SaveOptions();
	}
	
	/**
	 * Loads the options into the class vars.  
	 * Adds this plugins 'load' function to the 'load-plugin' hook.
	 * Adds this plugins 'admin_print_styles' function to the 'admin_print_styles-plugin' hook. 
	 */
	function init()
	{
		$this->LoadOptions();
		
		add_shortcode ( 'wpc_countdown', array(&$this,'show_countdown') );
		
		wp_register_style($this->plugin['pagenice'], plugins_url('/static/css/main.css',__FILE__),NULL,$this->plugin['version']);	
		wp_register_script($this->plugin['pagenice'], plugins_url('/static/js/countdown.js',__FILE__), array('jquery'),$this->plugin['version']);
		
		add_action('wp_print_styles', array(&$this,'print_style'));
		add_action('wp_print_scripts', array(&$this,'print_script'));
	}
	
	function print_style(){
		wp_enqueue_style($this->plugin['pagenice']);
	}
	
	function print_script(){
		wp_enqueue_script($this->plugin['pagenice']);
	}
	
	function show_countdown($attr){
		extract(shortcode_atts(array(
			'before' => '',
			'after' => '',
			'targetdate' => '',
		), $attr));
		
		if(empty($targetdate)){
			$targetdate = date('Y-m-d-H-i-s', strtotime("+7 day"));
		}
		$temp = explode("-",$targetdate);
		$year = $temp[0];
		$month = $temp[1];
		$day = $temp[2];
		$hour = empty($temp[3])? 0 : $temp[3];
		$minutes = empty($temp[4])? 0 : $temp[4];
		$seconds = empty($temp[5])? 0 : $temp[5];
		$output = <<<end
			<div id="CountdownWrapper"></div>
			<script type="text/javascript">
				var endDay = new Date();
				endDay = new Date($year, $month - 1, $day, $hour, $minutes, $seconds);
				 jQuery(document).ready(function() {
				   jQuery('#CountdownWrapper').countdown({until: endDay});
				   
				 });
			</script>
end;

		return $output;
	}
	
	/**
	 * A souped-up function that reads the plugin file __FILE__ and based on the plugin data (commented at very top of file) creates an array of vars
	 *
	 * @return array
	 */
	function get_plugin_data()
	{
		$data = $this->_readfile(__FILE__, 1500);
		$mtx = $plugin = array();
		preg_match_all('/[^a-z0-9]+((?:[a-z0-9]{2,25})(?:\ ?[a-z0-9]{2,25})?(?:\ ?[a-z0-9]{2,25})?)\:[\s\t]*(.+)/i', $data, $mtx, PREG_SET_ORDER);
		foreach ($mtx as $m) $plugin[trim(str_replace(' ', '-', strtolower($m[1])))] = str_replace(array("\r", "\n", "\t"), '', trim($m[2]));

		$plugin['title'] = '<a href="' . $plugin['plugin-uri'] . '" title="' . __('Visit plugin homepage') . '">' . $plugin['plugin-name'] . '</a>';
		$plugin['author'] = '<a href="' . $plugin['author-uri'] . '" title="' . __('Visit author homepage') . '">' . $plugin['author'] . '</a>';
		$plugin['pb'] = preg_replace('|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__);
		$plugin['page'] = basename(__FILE__);
		$plugin['pagenice'] = str_replace('.php', '', $plugin['page']);
		$plugin['nonce'] = 'form_' . $plugin['pagenice'];
		$plugin['hook'] = 'settings_page_' . $plugin['pagenice'];
		$plugin['action'] = 'options-general.php?page=' . $plugin['page'];

		if (preg_match_all('#(?:([^\W_]{1})(?:[^\W_]*?\W+)?)?#i', $plugin['pagenice'] . '.' . $plugin['version'], $m, PREG_SET_ORDER))$plugin['op'] = '';
		foreach($m as $k) sizeof($k == 2) && $plugin['op'] .= $k[1];
		$plugin['op'] = substr($plugin['op'], 0, 3) . '_';

		return $plugin;
	}
	
	/**
	 * Reads a file with fopen and fread for a binary-safe read.  $f is the file and $b is how many bytes to return, useful when you dont want to read the whole file (saving mem)
	 *
	 * @return string - the content of the file or fread return
	 */
	function _readfile($f, $b = false)
	{
		$fp = NULL;
		$d = '';
		!$b && $b = @filesize($f);
		if (!($b > 0) || !file_exists($f) || !false === ($fp = @fopen($f, 'r')) || !is_resource($fp)) return false;
		if ($b > 4096) while (!feof($fp) && strlen($d) < $b)$d .= @fread($fp, 4096);
		else $d = @fread($fp, $b);
		@fclose($fp);
		return $d;
	}
	
	/**
	 * Create a save button
	 */
	function save_button($text='') {
		if($text=='') $text = "Update Settings &raquo;";
		return '<div class="alignright"><input type="submit" class="button-primary" name="submit" value="'.$text.'" /></div><br class="clear"/>';
	}
}

//Let the magic show, shall we?
$wp_countdown = new wp_countdown();
$wp_countdown->execute();