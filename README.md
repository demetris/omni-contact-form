
Omni Contact Form
================================================================================

Omni Contact Form is a WordPress plugin for people who want a basic contact form. It aims to be light and simple to use.



Features
--------------------------------------------------------------------------------

-   Uses shortcode attributes for configuration (no settings page)
-   Has required fields only (if a field is in the form, it’s required)
-   It is light
-   It is discreet (only adds JavaScript and CSS to its own page)
-   It Is ready for translation
-   It displays a printable copy of the message after submission



Requirements
--------------------------------------------------------------------------------

-   Server
    -   PHP 7.0 or newer
    -   WordPress REST API
    -   WordPress 4.9.9 or newer
-   Browser
    -   JavaScript (no fallback for browsers without JavaScript)
    -   Version released after 2012



Installation
--------------------------------------------------------------------------------

### Manual

Get the latest release from the [Releases page](https://github.com/demetris/omni-contact-form/releases) and install it with your preferred method.

### Automatic

Use [GitHub Updater](https://github.com/afragen/github-updater).



Usage
--------------------------------------------------------------------------------

Insert the shortcode `[omni-contact-form]` into a page or post to display the form.

`[omni-contact-form]` generates the default form: two fields (email address and message) and a button.

To customize the form’s appearance and behaviour, you can use a number of shortcode attributes (see tables below). Here are two examples:

`[omni-contact-form name="yes" quiz="yes" cc="10"]`

The above shortcode generates a form that, in addition to the defaults, has a name field and a quiz field, and also sends a carbon copy to the WordPress user with ID 10.

`[omni-contact-form quiz="yes" message-label="Your complaint"]`

The above shortcode generates a form with a multiplication quiz and with the message label *Your complaint* (instead of the default *Message*).



Options
--------------------------------------------------------------------------------

The basic attributes you can use to customize the behaviour of the form are:

| Attribute    | Value         | Default     | Description                                                             |
|:-------------|:--------------|:------------|:------------------------------------------------------------------------|
| `name`       | yes/no        | no          | Include a name field in the form                                        |
| `subject`    | yes/no        | no          | Include a subject field in the form                                     |
| `quiz`       | yes/no        | no          | Include a multiplication quiz as a basic antispam measure               |
| `css`        | yes/no        | yes         | Include basic styling                                                   |
| `cc`         | number        | not set     | ID or IDs (comma-separated) of users to send carbon copies to           |
| `to`         | number        | not set     | ID of user to send the messages to (overrides WordPress default)        |
| `redirect`   | number        | not set     | ID of page or post to redirect to after successful submission           |


### Notes

If you give an invalid ID for `cc`, `to` or `redirect`, you will see a warning at the top of the messages sent by the form.

If the `to` ID is invalid, the messages are sent to the default address (email address for admin puproses in the General Settings of WordPress).

If the `redirect` ID is invalid, there will be no redirection after successful submission.



Custom form labels
--------------------------------------------------------------------------------

There are also shortcode attributes to change the default labels:

| Attribute                    | Default                                                           |
|:-----------------------------|:------------------------------------------------------------------|
| `email-label`                | Email address                                                     |
| `message-label`              | Message                                                           |
| `name-label`                 | Name                                                              |
| `progress-text`              | Sending...                                                        |
| `submit-label`               | Send                                                              |
| `subject-label`              | Subject                                                           |



Custom form messages and alerts
--------------------------------------------------------------------------------

You can also change the default messages and alerts using the following attributes:

| Attribute                    | Default                                                           |
|:-----------------------------|:------------------------------------------------------------------|
| `answer-empty`               | The answer is missing!                                            |
| `answer-wrong`               | The answer is wrong!                                              |
| `email-invalid`              | The email address is not valid!                                   |
| `email-empty`                | The email address is missing!                                     |
| `message-empty`              | The message is missing!                                           |
| `message-short`              | The message must have at least 12 characters!                     |
| `name-empty`                 | The name is missing!                                              |
| `name-short`                 | The name must have at least 4 characters!                         |
| `subject-empty`              | The subject is missing!                                           |
| `subject-short`              | The subject must have at least 4 characters!                      |
| `mail-error`                 | Mail error: Could not send the message.                           |
| `network-error`              | Network error: Could not connect to the server.                   |
| `old-browser`                | To use this form, please visit the page with a newer browser.     |
| `prompt-text`                | Send a message to get in touch!                                   |
| `thank-you`                  | Thank you for your message!                                       |



WordPress themes
--------------------------------------------------------------------------------

Omni Contact Form should work with any WordPress theme. It has been tested with several, including:

-   Checathlon
-   GeneratePress
-   Independent Publisher
-   Shoreditch
-   Twenty Fifteen
-   Twenty Nineteen
-   Twenty Seventeen



Limitations
--------------------------------------------------------------------------------

-   Only one form can be added to any page or post.



Issues
--------------------------------------------------------------------------------

-   It seems many themes provide styling for `input[type="submit"]` but not `button`.



Acknowledgements
--------------------------------------------------------------------------------

-   [Justin Tadlock](https://github.com/justintadlock) for his review of an early version of Omni Contact Form.
