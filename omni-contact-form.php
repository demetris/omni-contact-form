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
    Domain Path:            /public/lang
    Requires PHP:           7.0
    GitHub Plugin URI:      https://github.com/demetris/omni-contact-form
*/

namespace OmniContactForm;

/*
|
|   Define constants
|
|   @since 0.3.0
|
*/
define('OMNI_CONTACT_FORM_DIR', plugin_dir_path(__FILE__));
define('OMNI_CONTACT_FORM_URI', plugin_dir_url(__FILE__));

/*
|
|   Add autoloader
|   Implementation by Justin Tadlock
|
|   @since 0.3.0
|   @see http://justintadlock.com/archives/2018/12/14/php-namespaces-for-wordpress-developers
|
*/
spl_autoload_register(function($class) {
    $namespace = 'OmniContactForm\\';
    $path      = 'src';

    /*
    |
    |   Bail if the class is not in our namespace
    |
    */
    if (strpos($class, $namespace) !== 0) {
        return;
    }

    /*
    |
    |   Remove the namespace
    |
    */
    $class = str_replace($namespace, '', $class);

    /*
    |
    |   Build the filename
    |
    */
    $file = realpath(__DIR__ . "/{$path}");
    $file = $file . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    /*
    |
    |   If the file exists for the class name, load it
    |
    */
    if (file_exists($file)) {
        include($file);
    }
});

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
|   Get the plugin up and running
|
|   @since 0.3.0
|
*/
plugin();
