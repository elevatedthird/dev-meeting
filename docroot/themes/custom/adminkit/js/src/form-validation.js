/**
 * @file
 * Add class to form items when active to animate the label.
 */

Drupal.behaviors.formValidation = {
  attach(context) {
    const inputs = [
      '.js-form-wrapper',
    ];
    const $input = $(inputs.join(', '), context);
    if (!$input.length) return;

    // If the required attribute gets added in, make sure the necessary markup is updated.
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.attributeName === 'required') {
          const $target = $(mutation.target);
          const attributeValue = $target.attr(mutation.attributeName);
          if (attributeValue === 'required') {
            $target.find('input').prop('required', true);
            $target.find('textarea').prop('required', true);
            $target.find('label').addClass('js-form-required form-required');
          } else {
            $target.find('input').prop('required', false);
            $target.find('textarea').prop('required', false);
            $target.find('label').removeClass('js-form-required form-required');
          }
        }
      });
    });

    // Loop through all the form wrapper elements.
    $input.each(function () {
      observer.observe($(this)[0], {
        attributes: true,
      });
    });
  },
};
