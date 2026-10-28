/**
 * @file
 * Message template overrides.
 */

((Drupal) => {
  /**
   * Overrides message theme function.
   *
   * @param {object} message
   *   The message object.
   * @param {string} message.text
   *   The message text.
   * @param {object} options
   *   The message context.
   * @param {string} options.type
   *   The message type.
   * @param {string} options.id
   *   ID of the message, for reference.
   *
   * @return {HTMLElement}
   *   A DOM Node.
   */
  Drupal.theme.message = ({ text }, { type, id }) => {
    const messagesTypes = Drupal.Message.getMessageTypeLabels();
    const messageWrapper = document.createElement('div');

    if (type === 'error') {
      message_class = 'bg-red-400';  
    } else if (type === 'warning') {
      message_class = 'bg-yellow-500';
    } else if (type === 'status') {
      message_class = 'bg-green-600';
    }
    
    messageWrapper.setAttribute(
      'class',
      `messages messages--${type} ${message_class} messages-list__item bg-none shadow-none border-0 text-white py-2 px-4 rounded rounded-large text-left mt-4 [&_a]:underline`,

    );
    messageWrapper.setAttribute(
      'role',
      type === 'error' || type === 'warning' ? 'alert' : 'status',
    );
    messageWrapper.setAttribute('aria-labelledby', `${id}-title`);
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
    <div class="messages__container" data-drupal-selector="messages-container">
      <div class="messages__header">
        <h2 id="${id}-title" class="visually-hidden messages__title">${messagesTypes[type]}</h2>
      </div>
      <div class="messages__content">
        ${text}
      </div>
    </div>
  `;

    return messageWrapper;
  };
})(Drupal);
