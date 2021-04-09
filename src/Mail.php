<?php

namespace OmniContactForm;

class Mail
{
    private $site = '';

    public function __construct() {
        $this->site = get_bloginfo('name');
    }

    /**
     *
     *  Sanitizes the request data
     *
     *  @since 0.1.0
     *
     *  TODO
     *  2019-03-09
     *  Maybe move the WARNINGS part to Mail::compose().
     *
     */
    private function sanitize(\WP_REST_Request $request): array {
        $data = [];
        $warnings = [];

        $crypto = new Crypto;
        $main = new Main;

        $data['cc']             = isset($request['cc']) ? array_map('trim', explode(',', $crypto->decrypt($request['cc'], $main->password))) : null;
        $data['email']          = sanitize_email($request['email']);
        $data['home']           = esc_url($request['home']);
        $data['message']        = sanitize_textarea_field($request['message']);
        $data['name']           = isset($request['name']) ? sanitize_text_field($request['name']) : null;
        $data['subject']        = isset($request['subject']) ? sanitize_text_field($request['subject']) : null;
        $data['to']             = isset($request['to']) ? $crypto->decrypt($request['to'], $main->password) : null;

        /* translators: Value for referrer when the form page is accessed directly, that is, without a referrer. */
        $data['referrer']       = isset($request['referrer']) ? esc_url($request['referrer']) : esc_html__('NONE', 'omni-contact-form');

        if (isset($request['redirect-warning'])) {
            $redirect = ($request['redirect']);

            $warnings[] = sprintf(
                esc_html__('Post ID %s given for REDIRECT field in shortcode does not exist. No redirect performed.', 'omni-contact-form'), $redirect
            );
        }

        if (isset($data['to']) && get_userdata($data['to']) === false) {
            $warnings[] = sprintf(
                esc_html__('User ID %s given for TO field in shortcode does not exist. Default used.', 'omni-contact-form'), $data['to']
            );

            unset($data['to']);
        }

        if (isset($data['cc'])) {
            foreach ($data['cc'] as $key => $ID) {
                if (get_userdata((int) $ID) === false) {
                    $warnings[] = sprintf(
                        esc_html__('User ID %s given for CC field in shortcode does not exist. Carbon copy not sent.', 'omni-contact-form'), $ID
                    );

                    unset($data['cc'][$key]);
                }
            }
        }

        $data['warnings'] = $warnings;

        return $data;
    }

    /**
     *
     *  Composes the email message
     *
     *  @since 0.1.0
     *
     */
    private function compose(array $data): array {
        $message = [];

        $message['to']          = '';
        $message['subject']     = '';
        $message['warnings']    = '';
        $message['body']        = '';
        $message['footer']      = '';
        $message['headers']     = [];

        $sender                 = $data['name'] ?? $data['email'];
        $domain                 = preg_replace('|https?://|', '', get_home_url());

        /*
        |
        |   Set up TO for email message
        |
        */
        $message['to'] = isset($data['to']) ? get_userdata((int) $data['to'])->user_email : get_option('admin_email');

        /*
        |
        |   Set up SUBJECT for email message
        |
        */
        $message['subject'] = sprintf(esc_html__('Message to %s from %s', 'omni-contact-form'), $this->site, $sender);

        /*
        |
        |   Set up WARNINGS (if there are any) for email message
        |
        */
        if ($data['warnings']) {
            if (count($data['warnings']) > 1) {
                /* translators: Heading for warnings added to the email message if two or more of TO, CC, REDIRECT shortcode fields have issues. */
                $heading = esc_html__('WARNINGS', 'omni-contact-form');
            } else {
                /* translators: Heading for warning added to the email message if one of TO, CC, REDIRECT shortcode fields has an issue. */
                $heading = esc_html__('WARNING', 'omni-contact-form');
            }

            /*
            |
            |   Add heading for the warning or warnings
            |
            */
            $message['warnings'] .= $heading . "\n";
            $message['warnings'] .= "\n";

            /*
            |
            |   Add the warning or warnings
            |
            */
            foreach ($data['warnings'] as $warning) {
                $message['warnings'] .= $warning . "\n";
            }

            $message['warnings'] .= "\n";
        }

        /*
        |
        |   Set up BODY for email message
        |
        */
        $message['body'] .= esc_html__('SENDER', 'omni-contact-form') . "\n";
        $message['body'] .= "\n";

        if (isset($data['name'])) {
            $message['body'] .= sprintf(esc_html__('Name: %s', 'omni-contact-form'), $data['name']) .  "\n";
        }

        $message['body'] .= sprintf(esc_html__('Email address: %s', 'omni-contact-form'), $data['email']) . "\n";
        $message['body'] .= "\n";

        if (isset($data['subject'])) {
            $message['body'] .= esc_html__('SUBJECT', 'omni-contact-form') . "\n";
            $message['body'] .= "\n";

            $message['body'] .= $data['subject'] . "\n";
            $message['body'] .= "\n";
        }

        $message['body'] .= esc_html__('MESSAGE', 'omni-contact-form') . "\n";
        $message['body'] .= "\n";

        $message['body'] .= $data['message'] . "\n";
        $message['body'] .= "\n";

        $message['body'] .= esc_html__('FORM INFO', 'omni-contact-form') . "\n";
        $message['body'] .= "\n";

        $message['body'] .= sprintf(esc_html__('Form URL: %s', 'omni-contact-form'), $data['home']) .  "\n";

        $message['body'] .= sprintf(esc_html__('Referrer URL: %s', 'omni-contact-form'), $data['referrer']) .  "\n";

        $message['body'] .= sprintf(esc_html__('Time: %1$s %2$s (%3$s)', 'omni-contact-form'),
            current_time(get_option('date_format')),
            current_time(get_option('time_format')),
            date('c')
        );

        $message['body'] .= "\n";

        /*
        |
        |   Set up FOOTER for email message
        |
        */
        $message['footer'] .= "\n";

        $message['footer'] .= '~~~~~~~~' . "\n";

        $message['footer'] .= sprintf(esc_html__('Email sent from %s via the Omni Contact Form', 'omni-contact-form'), $domain);

        /*
        |
        |   Set up HEADERS for email message:
        |   1.  Reply-To
        |   2.  CC (may be more than one)
        |
        */
        $message['headers'][] = sprintf('Reply-To: <%s>', $data['email']);

        if (isset($data['cc'])) {
            foreach ($data['cc'] as $ID) {
                $ID = (int) $ID;

                if (get_userdata($ID) !== false) {
                    $message['headers'][] = sprintf('CC: %s', get_userdata($ID)->user_email);
                }
            }
        }

        return $message;
    }

    /**
     *
     *  Sends the email or emails
     *
     *  @since 0.1.0
     *  @return array|bool
     *
     */
    public function send($request) {
        $success = false;

        $data = $this->sanitize($request);
        $message = $this->compose($data);

        /*
        |
        |   Send the email message
        |
        */
        if (wp_mail(
            $message['to'],
            $message['subject'],
            $message['warnings'] . $message['body'] . $message['footer'],
            $message['headers']
        )) {
            $success = true;
        }

        /*
        |
        |   If wp_mail() succeeded, prepare message copy and send it back
        |
        */
        if ($success === true) {
            $copy = [];
            $body = [];

            /*
            |
            |   1.  Run body of message through wpautop() to add paragraphs
            |   2.  Add classes to the paragraphs so that they can be targeted in the print CSS
            |
            */
            $body = wpautop($message['body']);
            $body = str_replace('<p>', '<p class="ocf-message-copy-element">', $body);

            $copy['heading'] = sprintf(esc_html__('Copy of your message to %s', 'omni-contact-form'), $this->site);
            $copy['body'] = $body;

            wp_send_json_success($copy);
        }

        wp_send_json_error();
    }
}
