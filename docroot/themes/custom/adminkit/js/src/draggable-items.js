/**
 * @file
 * Custom dragging for paragraphs.
 */

import dragula from 'dragula';
import autoScroll from 'dom-autoscroller';

// Make sure this WAS a wysiwyg initially, not any textarea, maybe selectors or something.
function initCkeditorFromSavedStatus(el, draggedItems) {
  $.each(draggedItems, (i, value) => {
    if ($(el).find(`#${value.id}`).length && value.config) {
      const newEditor = CKEDITOR.replace(value.id, value.config);
      newEditor.on('instanceReady', () => {
        newEditor.setData(value.content);
      });
    }
  });
}

function adjustOrder(dragulaObject) {
  const $draggableItems = $(dragulaObject.containers[0]).children();
  $draggableItems.each(function (i) {
    // Because drupal has no useful selectors on the admin side and adds wrappers for
    // newly created paragraphs, we need to do this hanky panky to make sure we are only
    // adjusting the weights of the currently adjusted items.
    const $weightSelect = $(this).children('div').children('div').children('.form-type-select')
      .children('select');

    /* @todo with clao update, this selector does not select anything.
    *    However, this still seems to be functioning. Is it needed? */
    const $weightSelectAjax = $(this).children('.ajax-new-content').children('div').children('div')
      .children('.form-type-select')
      .children('select');

    if ($weightSelect.length > 0) {
      $weightSelect.val(i);
    } else if ($weightSelectAjax.length > 0) {
      $weightSelectAjax.val(i);
    } else {
      console.log('Error: Cannot find valid paragraph weight to adjust!'); // eslint-disable-line no-console
    }
  });
}

function initDraggableItems(draggableItemContainers, context) {
  // Declare variables for the currently dragged item so they can be accessed in any even handler.
  let draggedItems = [];
  const item = context.querySelector('.draggable-items-container');

  // Initialize dragula on draggable containers.
  const drake = dragula([item], {
    // Only handle drags items.
    moves: (el, container, handle) => $(el).children('.dragula-handle')[0] === $(handle)[0],
    // Drop can only happen in source element.
    accepts: (el, target, source) => target === source,
  });

  // On drop we need to recreate the editor from saved config.
  drake.on('drop', (el) => {
    adjustOrder(drake);
    initCkeditorFromSavedStatus(el, draggedItems);
  });

  // On cancel we need to recreate the editor from saved config.
  drake.on('cancel', (el) => {
    initCkeditorFromSavedStatus(el, draggedItems);
  });

  // On drag start we need to save the config from the ckeditor instance and destroy it.
  drake.on('drag', (el) => {
    // On drag start, reset the array to empty so you don't try to
    // initialize the same element multiple times.
    draggedItems = [];
    // Get id from textarea.
    const $wysiwygs = $(el).find('.cke').siblings('textarea');
    $wysiwygs.each(function () { // eslint-disable-line object-shorthand
      const draggedItemId = $(this).attr('id');
      if (CKEDITOR.instances[draggedItemId]) {
        const draggedItemInstance = CKEDITOR.instances[draggedItemId];
        const draggedItemConfig = draggedItemInstance.config;
        const draggedItemContent = draggedItemInstance.getData();
        draggedItems.push({
          id: draggedItemId,
          instance: draggedItemInstance,
          config: draggedItemConfig,
          content: draggedItemContent,
        });
        if (draggedItemInstance) {
          draggedItemInstance.destroy(true);
        }
      }
    });
  });

  // Init dom-autoscroller for each drake instance.
  autoScroll([
    window,
  ], {
    margin: 70,
    maxSpeed: 14,
    autoScroll: function () { // eslint-disable-line object-shorthand
      return this.down && drake.dragging;
    },
  });
}

Drupal.behaviors.dragula = {
  attach(context) {
    const drag = context.querySelectorAll('.draggable-items-container');
    if (drag.length === 0) return;
    drag.forEach(() => {
      initDraggableItems(this, context);
    });
  },
};
