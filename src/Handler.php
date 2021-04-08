<?php

namespace OmniContactForm;

class Handler
{
    public $message_min_len = 12;
    public $name_min_len = 4;
    public $subject_min_len = 4;

    public function __construct() {
    }

    /**
     *
     *  Handles the data of the form that is submitted to the REST endpoint
     *
     *  NOTE
     *  There is no need to return after wp_send_json():
     *  wp_send_json() concludes with wp_die() when doing Ajax.
     *
     *  @since 0.1.0
     *  @return void
     *
     */
    public function dispatch(\WP_REST_Request $request) {
        if ($this->validate($request) !== true) {
            wp_send_json($this->validate($request));
        }

        $mail = new Mail;

        $mail->send($request);
    }

    /**
     *
     *  Validates the form data
     *
     *  @since 0.1.0
     *  @return array|bool Bool if successful, array containing feedback if not.
     *
     */
    private function validate(\WP_REST_Request $request) {
        $alerts = [];
        $feedback = [];
        $malefactors = [];
        $nonce = [];

        /*
        |
        |   NOTE
        |   The automatic nonce verification by the WordPress REST API relies on cookies.
        |   It works for logged-in users but not for visitors.
        |
        */
        if (!isset($request['ocf-nonce']) || !wp_verify_nonce($request['ocf-nonce'], 'ocf')) {
            $nonce[] = 'invalid';
            $feedback['nonce'] = $nonce;

            return $feedback;
        }

        if (
            !empty($request['phone'])
            ||  mb_strlen($request['message']) > 2048
            ||  mb_strlen($request['subject']) > 128
            ||  mb_strlen($request['email']) > 254
            ||  mb_strlen($request['name']) > 128
        ) {
            sleep(1);

            $malefactors[] = 'malefactor';
            $feedback['malefactors'] = $malefactors;

            return $feedback;
        }

        if (empty($request['email'])) {
            $alerts['email'] = 'email-empty';
        } elseif (!is_email($request['email'])) {
            $alerts['email'] = 'email-invalid';
        }

        if (empty($request['message'])) {
            $alerts['message'] = 'message-empty';
        } elseif (mb_strlen($request['message']) - mb_substr_count($request['message'], ' ') < $this->message_min_len) {
            $alerts['message'] = 'message-short';
        }

        if (isset($request['name'])) {
            if (empty($request['name'])) {
                $alerts['name'] = 'name-empty';
            } elseif (mb_strlen($request['name']) - mb_substr_count($request['name'], ' ') < $this->name_min_len) {
                $alerts['name'] = 'name-short';
            }
        }

        if (isset($request['subject'])) {
            if (empty($request['subject'])) {
                $alerts['subject'] = 'subject-empty';
            } elseif (mb_strlen($request['subject']) - mb_substr_count($request['subject'], ' ') < $this->subject_min_len) {
                $alerts['subject'] = 'subject-short';
            }
        }

        if (isset($request['product'])) {
            if (empty($request['answer'])) {
                $alerts['answer'] = 'answer-empty';
            } elseif (intval($request['answer']) !== intval($request['product'])) {
                $alerts['answer'] = 'answer-wrong';
            }
        }

        if (!empty($alerts)) {
            $feedback['alerts'] = $alerts;

            return $feedback;
        }

        return true;
    }
}
