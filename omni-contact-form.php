<?php

/*
	Omni Contact Form plugin for WordPress

	Plugin Name:            Omni Contact Form
	Plugin URI:             https://github.com/demetris/omni-contact-form
	Description:            A simple contact form with simple shortcode settings
	Version:                0.4.5
	Author:                 Demetris Kikizas
	Author URI:             https://kikizas.com/
	License:                GPL-2.0
	License URI:            https://opensource.org/licenses/GPL-2.0
	Text Domain:            omni-contact-form
	Domain Path:            /lang
	Requires PHP:           7.0
	GitHub Plugin URI:      https://github.com/demetris/omni-contact-form
*/

namespace OmniContactForm;

/*
|
| Add autoloader
|
| @since 0.5.0
|
*/
require __DIR__ . '/vendor/autoload.php';

/*
|
| Define constants
|
| @since 0.3.0
|
*/
define('OMNI_CONTACT_FORM_DIR', plugin_dir_path(__FILE__));
define('OMNI_CONTACT_FORM_URI', plugin_dir_url(__FILE__));

/**
 *
 *  Gets the plugin up and running
 *
 *  @since 0.3.0
 *  @return object
 *
 */
function plugin() {
	static $instance = null;

	if (is_null($instance)) {
		$plugin = new Main();

		$plugin->boot();
	}

	return $instance;
}

/*
|
| Get the plugin up and running
|
| @since 0.3.0
|
*/
plugin();
