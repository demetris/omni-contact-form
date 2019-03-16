<?php

namespace OmniContactForm;

class Mail
{
    public function __construct() {
    }

    /**
    *
    *   Sanitizes the request data
    *
    *   @since 0.1.0
    *
    *   TODO
    *   2019-03-09. Maybe remove this method and move all its work to Mail::compose().
    *   2019-03-09. Maybe move just the WARNINGS part to Mail::compose().
    *
    */
    private function sanitize(\WP_REST_Request $request): array {
        $data = [];
        $warnings = [];

        $crypto = new Crypto;
        $main = new Main;

        $data['cc']             = isset($request['cc']) ? explode(',', $crypto->decrypt($request['cc'], $main->password)) : null;
        $data['email']          = sanitize_email($request['email']);
        $data['home']           = esc_url($request['home']);
        $data['message']        = sanitize_textarea_field($request['message']);
        $data['name']           = isset($request['name']) ? sanitize_text_field($request['name']) : null;
        $data['referrer']       = isset($request['referrer']) ? esc_url($request['referrer']) : esc_html__('NONE', 'omni-contact-form');
        $data['subject']        = isset($request['subject']) ? sanitize_text_field($request['subject']) : null;
        $data['to']             = isset($request['to']) ? $crypto->decrypt($request['to'], $$main->password) : null;

        if (isset($request['redirect-warning'])) {
            $redirect = sanitize_text_field($request['redirect']);

            $warnings[] = sprintf(
                esc_html__('Post ID %s given in attribute REDIRECT does not exist. No redirect performed.', 'omni-contact-form'), $redirect
            );
        }

        if (isset($data['to']) && get_userdata($data['to']) === false) {
            $warnings[] = sprintf(
                esc_html__('User ID %s given in attribute TO does not exist. Default used.', 'omni-contact-form'), $data['to']
            );
            unset($data['to']);
        }

        if (isset($data['cc'])) {
            foreach ($data['cc'] as $key => $ID) {
                if (get_userdata((int) $ID) === false) {
                    $warnings[] = sprintf(
                        esc_html__('User ID %s given in attribute CC does not exist. Carbon copy not sent.', 'omni-contact-form'), $ID
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
    *   Composes the email message
    *
    *   @since 0.1.0
    *
    */
    private function compose(array $data, string $type = ''): array {
        $msg = [];

        $msg['to']          = '';
        $msg['subject']     = '';
        $msg['body']        = '';
        $msg['footer']      = '';
        $msg['warnings']    = '';
        $msg['headers']     = [];

        $site               = get_bloginfo('name');
        $sender             = $data['name'] ?? $data['email'];
        $domain             = preg_replace('|https?://|', '', get_home_url());

        /*
        |
        |   Set up TO and SUBJECT for email message
        |
        */
        if ($type === 'copy') {
            $msg['subject'] = sprintf(esc_html__('Copy of your message to %s', 'omni-contact-form'), $site);
        } else {
            $msg['to'] = isset($data['to']) ? get_userdata((int) $data['to'])->user_email : get_option('admin_email');
            $msg['subject'] = sprintf(esc_html__('Message to %s from %s', 'omni-contact-form'), $site, $sender);
        }

        /*
        |
        |   Set up BODY for email message
        |
        */
        if ($type !== 'copy' && $data['warnings']) {
            $heading = count($data['warnings']) > 1 ? esc_html__('WARNINGS', 'omni-contact-form') : esc_html__('WARNING', 'omni-contact-form');

            $msg['body'] .= $heading . "\n";
            $msg['body'] .= "\n";

            foreach ($data['warnings'] as $warning) {
                $msg['body'] .= $warning . "\n";
            }

            $msg['body'] .= "\n";
        }

        $msg['body'] .= esc_html__('SENDER', 'omni-contact-form') . "\n";
        $msg['body'] .= "\n";

        if (isset($data['name'])) {
            $msg['body'] .= sprintf(esc_html__('Name: %s', 'omni-contact-form'), $data['name']) .  "\n";
        }

        $msg['body'] .= sprintf(esc_html__('Email address: %s', 'omni-contact-form'), $data['email']) . "\n";
        $msg['body'] .= "\n";

        if (isset($data['subject'])) {
            $msg['body'] .= esc_html__('SUBJECT', 'omni-contact-form') . "\n";
            $msg['body'] .= "\n";

            $msg['body'] .= $data['subject'] . "\n";
            $msg['body'] .= "\n";
        }

        $msg['body'] .= esc_html__('MESSAGE', 'omni-contact-form') . "\n";
        $msg['body'] .= "\n";

        $msg['body'] .= $data['message'] . "\n";
        $msg['body'] .= "\n";

        $msg['body'] .= esc_html__('FORM INFO', 'omni-contact-form') . "\n";
        $msg['body'] .= "\n";

        $msg['body'] .= sprintf(esc_html__('Form URL: %s', 'omni-contact-form'), $data['home']) .  "\n";

        $msg['body'] .= sprintf(esc_html__('Referrer URL: %s', 'omni-contact-form'), $data['referrer']) .  "\n";

        $msg['body'] .= sprintf(esc_html__('Time: %1$s %2$s (%3$s)', 'omni-contact-form'),
            current_time(get_option('date_format')),
            current_time(get_option('time_format')),
            date('c')
        );

        $msg['body'] .= "\n";

        /*
        |
        |   Set up FOOTER for email message
        |
        */
        $msg['footer'] .= "\n";

        $msg['footer'] .= '~~~~~~~~' . "\n";

        $msg['footer'] .= sprintf(esc_html__('Email sent from %s via the Omni Contact Form', 'omni-contact-form'), $domain);

        /*
        |
        |   Set up the HEADERS for the email message:
        |   1.  Reply-To
        |   2.  CC (may be more than one)
        |
        */
        $msg['headers'][] = sprintf('Reply-To: <%s>', $data['email']);

        if (isset($data['cc'])) {
            foreach ($data['cc'] as $ID) {
                $ID = (int) $ID;

                if (get_userdata($ID) !== false) {
                    $msg['headers'][] = sprintf('CC: %s', get_userdata($ID)->user_email);
                }
            }
        }

        return $msg;
    }

    /**
    *
    *   Sends the email or emails
    *
    *   @since 0.1.0
    *   @return array|bool
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
            $message['body'] . $message['footer'],
            $message['headers']
        )) {
            $success = true;
        }

        /*
        |
        |   If wp_mail() succeeded, prepare printable copy and send it back
        |
        */
        if ($success === true) {
            $printable = [];
            $message = $this->compose($data, 'copy');

            /*
            |
            |   1.  Run body of message through wpautop() to add paragraphs
            |   2.  Add classes to the paragraphs so that they can be targeted in the print CSS
            |
            */
            $body = wpautop($message['body']);
            $body = str_replace('<p>', '<p class="ocf-message-copy-element">', $body);

            $printable['subject'] = $message['subject'];
            $printable['body'] = $body;

            wp_send_json_success($printable);
        }

        wp_send_json_error();
    }
}
