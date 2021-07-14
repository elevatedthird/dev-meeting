/**
 * @file
 * Improve the paragraphs admin ui.
 */

/**
 * Format the previews to be expandable.
 *
 * @param paragraphsPreviews
 */
function formatParagraphsPreviews(paragraphsPreviews) {
  paragraphsPreviews.each((i) => {
    const $this = $(paragraphsPreviews[i]);
    if ($this.outerHeight() >= 100) {
      $this.addClass('expandable');
    }
  });
}

Drupal.behaviors.paragraphsPreviewerImprover = {
  attach: (context) => {
    const $previewerButtons = $('.link.paragraphs-previewer', context);

    $previewerButtons.each(() => {
      const $previewerButton = $(this);
      replaceParagraphName($previewerButton);
    });

    // Get paragraphs previews by only targeting ones with the .paragraph-type-top as a sibling
    // so nested paragraphs previews don't break.
    const $paragraphsTopElements = $('.paragraph-type-top', context);
    const $paragraphsPreviews = $paragraphsTopElements.siblings('.paragraph--view-mode--preview');

    formatParagraphsPreviews($paragraphsPreviews);

    // Necessary for paragraphs previews behind tabs.
    $('.vertical-tabs__menu a').on('click', () => {
      formatParagraphsPreviews($paragraphsPreviews);
    });
  },
};

Drupal.behaviors.paragraphsHelperClasses = {
  attach: () => {
    const $confirmRemoveButtons = $('.confirm-remove');

    $confirmRemoveButtons.each(() => {
      $(this).closest('.admin-paragraphs-draggable-item, .admin-paragraphs-single').addClass('paragraph-confirm-delete');
    });
  },
};

// Because drupal behaviors are so annoying, add delegated click
// handler here, couldn't get it to work properly inside the behavior.
$(document).ready(() => {
  $('body').on('click', '.paragraph--view-mode--preview', function () {
    $(this).toggleClass('expanded');
  });
});
