<?php

namespace OmniContactForm;

class Main
{
    public $password = '';

    public function __construct() {
        if ($this->password !== '') {
            $this->password = wp_generate_password(16);
        }
    }

    /**
    *
    *   Boots the plugin
    *
    *   @since 0.3.0
    *   @return void
    *
    */
    public function boot() {
        add_action('init', [$this, 'register_shortcode']);
        add_action('plugins_loaded', [$this, 'load_text_domain']);
        add_action('rest_api_init', [$this, 'register_route']);
        add_action('wp_enqueue_scripts', [$this, 'register_js']);
    }

    /**
    *
    *   Registers a REST route
    *
    *   @wp-action rest_api_init
    *
    *   @since 0.3.0
    *   @return void
    *
    */
    public function register_route() {
        $handler = new Handler;

        register_rest_route('omni/v1', '/post', [
            'methods' => 'POST',
            'callback' => [$handler, 'dispatch']
        ]);
    }

    /**
    *
    *   Registers the plugin textdomain
    *
    *   @wp-action plugins_loaded
    *
    *   @since 0.1.0
    *   @return void
    *
    */
    public function load_text_domain() {
        load_plugin_textdomain('omni-contact-form', false, OMNI_CONTACT_FORM_DIR . 'public/lang/');
    }

    /**
    *
    *   Registers the plugin shortcode
    *
    *   @wp-action init
    *
    *   @since 0.1.0
    *   @see https://developer.wordpress.org/plugins/shortcodes/basic-shortcodes/
    *   @return void
    *
    */
    public function register_shortcode() {
        $form = new Form;

        add_shortcode('omni-contact-form', [$form, 'render']);
    }

    /**
    *   Registers the plugin JavaScript
    *
    *   @wp-action wp_enqueue_scripts
    *
    *   @since 0.1.0
    *   @return void
    *
    */
    public function register_js() {
        wp_register_script('ocf-main', OMNI_CONTACT_FORM_URI . 'public/js/main.js');
    }

    /**
    *
    *   Returns inline CSS for the form
    *
    *   @since 0.1.0
    *
    */
    public function css(string $what = null): string {
        $style = '';

        $style .= '<style>' . "\n";

        if ($what === 'all') {
            $style .= file_get_contents(OMNI_CONTACT_FORM_DIR . 'public/css/optional.css', true);
        }

        $style .= file_get_contents(OMNI_CONTACT_FORM_DIR . 'public/css/main.css', true);

        $style .= '</style>' . "\n";

        return $style;
    }
}
