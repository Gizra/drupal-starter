/**
 * @file
 * Client-side file size validation to prevent upload errors.
 *
 * Validates file size before upload to show a user-friendly error message
 * instead of cryptic AJAX errors when files exceed PHP limits.
 */
(function ($, Drupal, once) {

  /**
   * Validates file size before upload.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.fileSizeValidation = {
    attach: function (context, settings) {
      const maxFileSize = settings.fileSizeValidation?.maxFileSize;
      if (!maxFileSize) {
        return;
      }

      const $fileInputs = $(
        once('file-size-validate', 'input[type="file"]', context)
      );

      if (!$fileInputs.length) {
        return;
      }

      $fileInputs.on('change.fileSizeValidation', function (event) {
        const input = this;
        const files = input.files;

        if (!files || !files.length) {
          return;
        }

        // Remove any previous file size errors.
        $(input)
          .closest('div.js-form-managed-file, .form-item')
          .find('.file-size-error')
          .remove();

        // Check each file's size.
        for (let i = 0; i < files.length; i++) {
          const file = files[i];
          if (file.size > maxFileSize) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            const maxSizeMB = (maxFileSize / (1024 * 1024)).toFixed(0);

            const error = Drupal.t(
              'The file "@filename" (@filesize MB) exceeds the maximum upload size of @maxsize MB. Please choose a smaller file.',
              {
                '@filename': file.name,
                '@filesize': fileSizeMB,
                '@maxsize': maxSizeMB,
              }
            );

            $(input)
              .closest('div.js-form-managed-file, .form-item')
              .prepend(
                '<div class="messages messages--error file-size-error" aria-live="polite">' + error + '</div>'
              );

            // Clear the file input to prevent upload attempt.
            input.value = '';

            // Stop processing and prevent upload.
            event.stopImmediatePropagation();
            return;
          }
        }
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(once.remove('file-size-validate', 'input[type="file"]', context)).off(
          '.fileSizeValidation'
        );
      }
    },
  };

})(jQuery, Drupal, once);
