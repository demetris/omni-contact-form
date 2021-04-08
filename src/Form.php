<?php

namespace OmniContactForm;

class Form
{
    private $defaults = [];

    public function __construct() {
        $handler = new Handler;

        $this->defaults = [
            'cc'                    => false,
            'hide-after'            => false,
            'name'                  => false,
            'prompt'                => false,
            'quiz'                  => false,
            'redirect'              => false,
            'subject'               => false,
            'to'                    => false,

            'css'                   => true,

            'required-text'         => '',

            'prompt-text'           => __('Send a message to get in touch!', 'omni-contact-form'),

            'email-label'           => __('Email address', 'omni-contact-form'),
            'message-label'         => __('Message', 'omni-contact-form'),
            'name-label'            => __('Name', 'omni-contact-form'),
            'progress-text'         => __('Sending...', 'omni-contact-form'),
            'subject-label'         => __('Subject', 'omni-contact-form'),
            'submit-label'          => __('Send', 'omni-contact-form'),

            'answer-empty'          => esc_html__('The answer is missing!', 'omni-contact-form'),
            'answer-wrong'          => esc_html__('The answer is wrong!', 'omni-contact-form'),
            'email-empty'           => esc_html__('The email address is missing!', 'omni-contact-form'),
            'email-invalid'         => esc_html__('The email address is not valid!', 'omni-contact-form'),
            'message-empty'         => esc_html__('The message is missing!', 'omni-contact-form'),
            'name-empty'            => esc_html__('The name is missing!', 'omni-contact-form'),
            'subject-empty'         => esc_html__('The subject is missing!', 'omni-contact-form'),
            'message-short'         => sprintf(esc_html__('The message must have at least %d characters!', 'omni-contact-form'), $handler->message_min_len),
            'name-short'            => sprintf(esc_html__('The name must have at least %d characters!', 'omni-contact-form'), $handler->name_min_len),
            'subject-short'         => sprintf(esc_html__('The subject must have at least %d characters!', 'omni-contact-form'), $handler->subject_min_len),

            'mail-error'            => esc_html__('Mail error: Could not send the message.', 'omni-contact-form'),
            'network-error'         => esc_html__('Network error: Could not connect to the server.', 'omni-contact-form'),
            'nonce-error'           => esc_html__('Something went wrong! Please refresh the page and try again!', 'omni-contact-form'),
            'old-browser'           => esc_html__('To use this form, please visit the page with a newer browser.', 'omni-contact-form'),

            'thank-you'             => esc_html__('Thank you for your message!', 'omni-contact-form'),

            'nonce'                 => wp_create_nonce('wp_rest'),
            'receiver'              => rest_url('omni/v1/post')
        ];
    }

    /**
     *
     *  Sets up the form configuration
     *
     *  1.  Processes the user settings defined via the shortcode
     *  2.  Combines the user settings with the defaults
     *  3.  Sends the result to JavaScript
     *  4.  Returns the result as array
     *
     *  @since 0.3.4
     *  @param array|string $atts Array if the shortcode has attributes, string if not.
     *
     */
    private function config($atts): array {
        /*
        |
        |   Normalize attribute keys supplied via the shortcode
        |
        */
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        /*
        |
        |   Escape HTML in values supplied via the shortcode
        |
        */
        $atts = array_map('esc_html', $atts);

        /*
        |
        |   Trim the values
        |
        */
        $atts = array_map('trim', $atts);

        /*
        |
        |   Convert certain user supplied values to FALSE
        |
        */
        $atts = array_map(function ($value) {
            return (in_array($value, ['', 'no', 'No', 'NO'])) ? false : $value;
        }, $atts);

        /*
        |
        |   Unset attributes not supposed to be set by the user
        |
        */
        foreach (['nonce', 'receiver'] as $key) {
            if (isset($atts[$key])) {
                unset($atts[$key]);
            }
        }

        /*
        |
        |   Combine default values with values set by the user
        |
        */
        $atts = shortcode_atts($this->defaults, $atts, 'ocf');

        /*
        |
        |
        |
        */
        if ($atts['redirect']) {
            if (get_the_permalink((int) ($atts['redirect']))) {
                $atts['redirect-url'] = get_the_permalink((int) ($atts['redirect']));
            } else {
                $atts['redirect-warning'] = true;
            }
        }

        /*
        |
        |   Make a compact copy of the attributes to send to JavaScript
        |
        */
        $atts_compact = array_filter($atts);

        /*
        |
        |   Send the compact copy of the attributes to JavaScript as an object
        |
        */
        wp_localize_script('ocf-main', 'OCF', $atts_compact);

        /*
        |
        |   Return
        |
        */
        return $atts;
    }

    /**
     *
     *  Renders the form and displays the form messages
     *
     *  @wp-caller add_shortcode()
     *
     *  @since 0.1.0
     *  @param array|string $atts Array if the shortcode has attributes, string if not.
     *
     */
    public function render($atts): string {
        $atts = $this->config($atts);

        if ($atts['css']) {
            wp_enqueue_style('ocf-all');
        } else {
            wp_enqueue_style('ocf-required');
        }

        wp_enqueue_script('ocf-main');

        $main           = new Main;
        $quiz           = new Quiz;
        $crypto         = new Crypto;

        $alert          = '';
        $form           = '';
        $messages       = '';
        $copy           = '';

        global $wp;
        $home = user_trailingslashit(home_url($wp->request));

        $referrer = wp_get_referer();

        /*
        |
        |   Assemble messages
        |
        */
        $messages .= '<div id="ocf-messages" class="ocf-messages">' . "\n";

        $messages .= '<noscript>';
        $messages .= 'This contact form requires JavaScript.';
        $messages .= '</noscript>' . "\n";

        if ($atts['prompt']) {
            $messages .= '<p id="ocf-prompt" class="ocf-prompt">';
            $messages .= esc_html($atts['prompt-text']);
            $messages .= '</p>' . "\n";
        }

        $messages .= '</div>' . "\n\n";

        /*
        |
        |   Add element to use for printable message copy after successful submission
        |
        */
        $copy .= '<article id="ocf-message-copy" class="ocf-message-copy ocf-message-copy-element">';
        $copy .= '</article>' . "\n\n";

        /*
        |
        |   Assemble the form
        |
        */
        $form .= '<form id="ocf" method="post" action="#" class="ocf ocf-form omni-contact-form" novalidate="novalidate">' . "\n";

        $form .= '<input type="hidden" name="action" value="ocf">' . "\n";

        $form .= wp_nonce_field('ocf', 'ocf-nonce', true, false) . "\n";

        if ($atts['cc']) {
            /*
            |
            |   Put the cc IDs in a hidden input in encrypted form
            |
            */
            $form .= '<input type="hidden" name="cc" value="' . esc_attr($crypto->encrypt($atts['cc'], $main->password)) . '" />' . "\n";
        }

        if ($atts['to']) {
            /*
            |
            |   Put the to ID in a hidden input in encrypted form
            |
            */
            $form .= '<input type="hidden" name="to" value="' . esc_attr($crypto->encrypt($atts['to'], $main->password)) . '" />' . "\n";
        }

        if ($atts['redirect']) {
            /*
            |
            |   Put the redirect ID in a hidden input
            |
            */
            $form .= '<input type="hidden" name="redirect" value="' . esc_attr($atts['redirect']) . '" />' . "\n";
        }

        if (isset($atts['redirect-warning'])) {
            $form .= '<input type="hidden" name="redirect-warning" value="1" />' . "\n";
        }

        if ($atts['required-text']) {
            $alert .= $atts['required-text'];
        }

        if ($atts['name']) {
            $form .= '<p class="ocf-field ocf-field-name">' . "\n";
            $form .= '<label for="ocf-name">';
            $form .= '<span class="label-text">' . esc_html($atts['name-label'])  . '</span>';
            $form .= '<span id="ocf-alert-name" class="ocf-alert">' . esc_html($alert) . '</span>';
            $form .= '</label>' . "\n";
            $form .= '<input id="ocf-name" type="text" name="name" maxlength="128" value="">' . "\n";
            $form .= '</p>' . "\n";
        }

        $form .= '<p class="ocf-field ocf-field-email">' . "\n";
        $form .= '<label for="ocf-email">';
        $form .= '<span class="label-text">' . esc_html($atts['email-label']) . '</span>';
        $form .= '<span id="ocf-alert-email" class="ocf-alert">' . esc_html($alert) . '</span>';
        $form .= '</label>' . "\n";
        $form .= '<input id="ocf-email" type="email" name="email" maxlength="254" value="">' . "\n";
        $form .= '</p>' . "\n";

        $form .= '<label for="ocf-phone" style="display: none !important">';
        $form .= '<span class="label-text">' . 'Phone number' . '</span>';
        $form .= '<span class="ocf-alert">' . esc_html($alert) . '</span>';
        $form .= '</label>' . "\n";
        $form .= '<input id="ocf-phone" style="display: none !important" type="tel" name="phone" tabindex="-1" autocomplete="off" value="">' . "\n";

        if ($atts['subject']) {
            $form .= '<p class="ocf-field ocf-field-subject">' . "\n";
            $form .= '<label for="ocf-subject">';
            $form .= '<span class="label-text">' . esc_html($atts['subject-label']) . '</span>';
            $form .= '<span id="ocf-alert-subject" class="ocf-alert">' . esc_html($alert) . '</span>';
            $form .= '</label>' . "\n";
            $form .= '<input id="ocf-subject" type="text" name="subject" maxlength="128" value="">' . "\n";
            $form .= '</p>' . "\n";
        }

        $form .= '<p class="ocf-field ocf-field-message">' . "\n";
        $form .= '<label for="ocf-message">';
        $form .= '<span class="label-text">' . esc_html($atts['message-label']) . '</span>';
        $form .= '<span id="ocf-alert-message" class="ocf-alert">' . esc_html($alert) . '</span>';
        $form .= '</label>' . "\n";
        $form .= '<textarea id="ocf-message" name="message" rows="4" maxlength="2048">' . '</textarea>' . "\n";
        $form .= '</p>' . "\n";

        if ($atts['quiz']) {
            $question = sprintf(__('What is %d × %d?', 'omni-contact-form'), $quiz->getA(), $quiz->getB());

            $form .= '<p class="ocf-field ocf-field-answer">' . "\n";
            $form .= '<label for="ocf-answer">';
            $form .= '<span class="label-text">' . esc_html($question) . '</span>';
            $form .= '<span id="ocf-alert-answer" class="ocf-alert">' . esc_html($alert) . '</span>';
            $form .= '</label>' . "\n";
            $form .= '<input id="ocf-answer" type="number" name="answer" value="" max="20">' . "\n";
            $form .= '<input type="hidden" name="product" value="' . esc_attr((string) $quiz->getProduct()) . '" />' . "\n";
            $form .= '</p>' . "\n";
        }

        if ($referrer) {
            $form .= '<input type="hidden" name="referrer" value="' . esc_url($referrer) . '" />' . "\n";
        }

        $form .= '<input type="hidden" name="home" value="' . esc_url($home) . '" />' . "\n";

        $form .= '<div class="form-group form-group-submit">' . "\n";

        $form .= '<button id="ocf-submit" name="submit" class="button btn" disabled="disabled">' . esc_html($atts['submit-label']) . '</button>' . "\n";
        $form .= '<span id="ocf-progress" class="ocf-progress" style="display: none">' . esc_html($atts['progress-text']) . '</span>' . "\n";

        $form .= '</div>' . "\n";

        $form .= '</form>' . "\n";

        /*
        |
        |   Return the form along with the copy container and any messages
        |
        */
        return $messages . $copy . $form;
    }
}
