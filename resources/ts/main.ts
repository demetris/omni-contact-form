/*
|
| filename: main.ts
|
*/

import DOMPurify from 'dompurify';

/*
|
| Define types for the OCF object
|
*/
interface OCFConfig {
  'hide-after': boolean;
  'mail-error': string;
  'network-error': string;
  'nonce': string;
  'nonce-error': string;
  'old-browser': string;
  'receiver': string;
  'redirect-url': string;
  'thank-you': string;
  [key: string]: string | boolean;
}

/*
|
| Define interface for the response data
|  
*/
interface FormResponse {
  alerts?: Record<string, string>;
  data?: {
    heading: string;
    body: string;
  };
  nonce?: boolean;
  success?: boolean;
}

/*
|
| Define interface for form alerts
|
*/
interface Alerts {
  [key: string]: string;
}

/*
|
| Declare the type of the global OCF variable
|
*/
declare let OCF: OCFConfig;

/*
|
| Set up a few utility constants
|
*/
const formID = 'ocf';
const buttonID = 'ocf-submit';
const answerID = 'ocf-answer';
const messagesID = 'ocf-messages';
const progressID = 'ocf-progress';
const animationDuration = 250;

/*
|
|
| Initializes the form
|
|
*/
function init(): void {
  /*
  | Check browser age using:
  |
  | 1. Object.fromEntries(); and
  | 2. Element.replaceChildren()
  |
  | Both are available since 2020 in most browsers.
  | In Samsung Internet replaceChildren() became available in April 2021.
  */
  if (
    !Object.fromEntries
    || !('replaceChildren' in document.createElement('div'))
  ) {
    window.alert(OCF['old-browser']);

    return;
  }

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById(formID) as HTMLFormElement | null;
    const button = document.getElementById(buttonID) as HTMLButtonElement | null;
    const answer = document.getElementById(answerID) as HTMLInputElement | null;

    if (!form || !button) {
      return;
    }

    /*
    |
    | Handle input in the answer field
    |
    */
    if (answer) {
      answer.addEventListener('input', function (event: Event) {
        const input = event.target as HTMLInputElement;
        
        input.value = removeNonDigits(input.value);
      });
    }

    button.disabled = false;

    submit(form);
  });
}

/*
|
|
| Removes non-digit characters from input
|
|
*/
function removeNonDigits(input: string): string {
  return input.replace(/\D/g, '');
}

/*
|
|
| Adds message to the messages container
|
|
*/
function addMessage(message: string): void {
  const messages = document.getElementById(messagesID);

  if (!messages) {
    return;
  }

  messages.replaceChildren();

  const messageElement = document.createElement('p');

  messageElement.className = 'message ocf-message';
  messageElement.textContent = message;

  messages.appendChild(messageElement);
}

/*
|
|
| Handles alerts for form fields
|
|
*/
function handleAlerts(form: HTMLFormElement, alerts: Alerts): void {
  Object.entries(alerts).forEach(function ([key, value]) {
    const alert = document.getElementById(`ocf-alert-${key}`);
    const field = form.elements.namedItem(key) as HTMLElement;

    if (!alert || !field) {
      return;
    }

    alert.replaceChildren()
    alert.textContent = typeof OCF[value] === 'string' ? OCF[value] as string : value;

    field.classList.add('has-error');
  });

  /*
  |
  | Handle error removal on focus
  |
  */
  function handleFieldFocus(event: FocusEvent): void {
    const target = event.target as HTMLElement;

    if (!(
      ['INPUT', 'TEXTAREA'].includes(target.nodeName)
      && target.classList.contains('has-error')
    )) {
      return;
    }

    const alert = document.getElementById(`ocf-alert-${(target as HTMLInputElement).name}`);
    target.classList.remove('has-error');

    if (alert?.firstChild) {
      alert.classList.add('is-fading-out');

      setTimeout(function () {
        alert.replaceChildren();
        alert.classList.remove('is-fading-out');
      }, animationDuration);
    }
  }

  form.addEventListener('focusin', handleFieldFocus);
}

/*
|
|
| Collects form data
|
|
*/
function getFormData(form: HTMLFormElement) {
  const formData = new FormData(form);

  return Object.fromEntries(formData);
}

/*
|
| Submits the form
|
*/
function submit(form: HTMLFormElement): void {
  const progress = document.getElementById(progressID);
  const button = document.getElementById(buttonID);
  const messages = document.getElementById(messagesID);

  if (!progress || !button || !messages) {
    return;
  }

  form.addEventListener('submit', async function (event: Event) {
    event.preventDefault();

    progress.classList.add('is-visible');

    try {
      const response = await fetch(OCF.receiver, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-WP-Nonce': OCF.nonce,
        },
        body: JSON.stringify(getFormData(form)),
      });

      const data: FormResponse = await response.json();

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      if (data.nonce) {
        addMessage(OCF['nonce-error']);
      } else if (data.success === false) {
        addMessage(OCF['mail-error']);
      } else if (data.alerts) {
        handleAlerts(form, data.alerts);
      } else {
        handleSuccess(form, data, messages);
      }
    } catch (error) {
      console.error('Submission error:', error);
      addMessage(OCF['network-error']);
    } finally {
      button.blur();
      progress.classList.remove('is-visible');
    }
  });
}

/*
|
|
| Handles successful submission
|
|
*/
function handleSuccess(form: HTMLFormElement, data: FormResponse, messages: HTMLElement): void {
  form.reset();
  
  const formHeight = form.scrollHeight;

  if (OCF['hide-after']) {
    form.remove();
  } else if (OCF['redirect-url']) {
    window.location.href = OCF['redirect-url'];
  } else {
    messages.classList.add('ocf-fade-in');
    addMessage(OCF['thank-you']);

    /*
    |
    | Set up printable copy
    |
    */
    form.style.minHeight = `${formHeight}px`;
    form.classList.add('ocf-fade-in');
    form.replaceChildren();

    if (data.data) {
      const { heading, body } = data.data;
      const headingElement = document.createElement('h1');
      
      headingElement.className = 'ocf-message-copy-element';
      headingElement.textContent = heading;
      
      form.appendChild(headingElement);

      const bodyContainer = document.createElement('div');
      
      bodyContainer.innerHTML = DOMPurify.sanitize(body);
      
      form.appendChild(bodyContainer);
    }

    form.classList.add('ocf-message-copy');
  }
}

/*
|
| Initialize the form
|
*/
init();
