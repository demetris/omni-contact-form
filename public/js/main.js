/*
|
|   filename: main.ts
|
*/
/*
|
|    Run initial checks and proceed accordingly
|
*/
if ('classList' in document.documentElement && document.addEventListener) {
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ocf');
        var button = document.getElementById('ocf-submit');
        var answer = document.getElementById('ocf-answer');
        if (answer) {
            answer.addEventListener('keyup', function () {
                var input = answer.value;
                answer.value = removeNonDigits(input);
            });
        }
        if (form && button) {
            button.disabled = false;
            submitForm(form);
        }
    });
}
else {
    window.alert(OCF['old-browser']);
}
/*
|
|   Removes input that is not digits
|
|   @see https://stackoverflow.com/questions/44170430
|
*/
function removeNonDigits(input) {
    return input.replace(/[^0-9]+/g, '');
}
/*
|
|   Removes all children of an element
|
*/
function removeChildren(parent) {
    while (parent.firstChild) {
        parent.removeChild(parent.firstChild);
    }
}
/*
|
|   Adds messages
|
*/
function addMessage(message) {
    var messages = document.getElementById('ocf-messages');
    removeChildren(messages);
    messages.insertAdjacentHTML('beforeend', '<p class="message ofc-message">' + message + '</p>');
}
/*
|
|   Handles the form alerts:
|
|   1.  Attaches alerts and adds CSS class to fields with errors
|   2.  Watches for user input to remove the alerts and the CSS classes
|
*/
function handleFormAlerts(form, alerts) {
    var alert;
    var field;
    var key;
    for (key in alerts) {
        if (alerts.hasOwnProperty(key)) {
            alert = document.getElementById('ocf-alert-' + key);
            field = form.elements[key];
            removeChildren(alert);
            alert.insertAdjacentHTML('beforeend', OCF[alerts[key]]);
            field.classList.add('has-error');
        }
    }
    form.addEventListener('focusin', function (event) {
        var target = event.target;
        if (['INPUT', 'TEXTAREA'].indexOf(target.nodeName) > -1 && target.classList.contains('has-error')) {
            alert = document.getElementById('ocf-alert-' + target.name);
            target.classList.remove('has-error');
            if (alert && alert.lastChild) {
                alert.classList.add('is-fading-out');
                setTimeout(function () {
                    removeChildren(alert);
                    alert.classList.remove('is-fading-out');
                }, 187.5);
            }
        }
    });
}
/*
|
|   Collects the form data and returns them as an object
|
|   NOTE: Looks only at two types of element: TEXTAREA and INPUT
|
*/
function getFormData(form) {
    var data = {};
    var i;
    for (i = 0; i < form.elements.length; i++) {
        if (form.elements[i].tagName === 'TEXTAREA') {
            var textarea = form.elements[i];
            data[textarea.name] = textarea.value;
        }
        if (form.elements[i].tagName === 'INPUT') {
            var input = form.elements[i];
            data[input.name] = input.value;
        }
    }
    return data;
}
/*
|
|    Submits the data and sends back the response
|
*/
function submitForm(form) {
    var formHeight;
    var response;
    var messages = document.getElementById('ocf-messages');
    var progress = document.getElementById('ocf-progress');
    var button = document.getElementById('ocf-submit');
    var xhr = new XMLHttpRequest();
    /*
    |
    |   Set listener and actions for form submission
    |
    */
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        progress.classList.add('is-visible');
        /*
        |
        |    Set listener and actions for XHR loadend
        |
        */
        xhr.addEventListener('loadend', function () {
            button.blur();
            progress.classList.remove('is-visible');
        });
        /*
        |
        |    Set listener and actions for XHR load
        |
        */
        xhr.addEventListener('load', function () {
            response = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && response.nonce) {
                addMessage(OCF['nonce-error']);
            }
            else if (xhr.status === 200 && response.success === false) {
                addMessage(OCF['mail-error']);
            }
            else if (xhr.status === 200 && response.alerts) {
                handleFormAlerts(form, response.alerts);
            }
            else if (xhr.status === 200 && !response.alerts) {
                form.reset();
                formHeight = form.scrollHeight;
                if (OCF['hide-after']) {
                    form.parentNode.removeChild(form);
                }
                else if (OCF['redirect-url']) {
                    window.location = OCF['redirect-url'];
                }
                else {
                    messages.classList.add('ocf-fade-in');
                    addMessage(OCF['thank-you']);
                    /*
                    |
                    |    Set up printable copy of message and remove form
                    |
                    */
                    form.style.minHeight = formHeight + 'px';
                    form.classList.add('ocf-fade-in');
                    removeChildren(form);
                    form.insertAdjacentHTML('beforeend', '<h1 class="ocf-message-copy-element">' + response.data.heading + '</h1>');
                    form.insertAdjacentHTML('beforeend', response.data.body);
                    form.classList.add('ocf-message-copy');
                }
            }
            else {
                addMessage('Submission failed. Status code is ' + xhr.status + '.');
            }
        });
        /*
        |
        |    Set listener and actions for XHR error
        |
        */
        xhr.addEventListener('error', function () {
            addMessage(OCF['network-error']);
        });
        /*
        |
        |   Set up the request
        |
        */
        xhr.open('POST', OCF.receiver);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-WP-Nonce', OCF.nonce);
        /*
        |
        |   Send the request
        |
        */
        xhr.send(JSON.stringify(getFormData(form)));
    });
}
