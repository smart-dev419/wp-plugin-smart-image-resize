// Utils

var WP_SIR_UTIL = {
  setCookie: function (cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  }
};

(function ($) {
  'use strict';

  // ELEMENTS
  var $colorPicker = $('#wpSirColorPicker'),
    $sizesSelect = $('#wpSirResizeSizes'),
    $compressImageSlider = $('.wpSirSlider');

  // ------------------------------------------------------------------------------------------
  // INITILIAZE COLOR PICKER
  // ------------------------------------------------------------------------------------------

  $colorPicker.wpColorPicker();

  // ------------------------------------------------------------------------------------------
  // INITILIAZE MULTI SELECT
  // ------------------------------------------------------------------------------------------

  $sizesSelect.multipleSelect();

  // ------------------------------------------------------------------------------------------
  // INITILIAZE COMPRESSION SLIDER.
  // ------------------------------------------------------------------------------------------

  $compressImageSlider.each(function () {
    var handle = $(this).find('.wpSirSliderHandler');
    var inputElement = $('.' + $(this).data('input'));
    $(this).slider({
      create: function () {
        $(this).slider('value', inputElement.val());
        handle.text($(this).slider('value') + '%');
      },
      slide: function (event, ui) {
        handle.text(ui.value + '%');
        inputElement.val(ui.value);
      },
      change: function (event, ui) {
        handle.text(ui.value + '%');
      },
    });
  });

  // we'll wait until the box is rendered, so we can move it to the top.
  var wpsirLoadIntervalId = setInterval(() => {
    if ($('.wpsirProcessMediaLibraryImageWraper').length) {
      clearInterval(wpsirLoadIntervalId);

      $('.wpsirProcessMediaLibraryImageWraper')
        .insertBefore($('#wp-media-grid > .media-frame'));

      handleProcessMediaLibraryChange($('#processMediaLibraryImage'));

      $(document).on('change', '#processMediaLibraryImage', function () {
        handleProcessMediaLibraryChange($(this));
      });

    }
  }, 100);


  /**
   * Allow user to decide whether to process image being uploaded.
   * We'll place a checkbox input where we cannot determine image attachment parent
   * under "Media > Library" and "Media > Add" new pages.
   */


  function handleProcessMediaLibraryChange($input) {
    var isProcessable = $input.is(':checked');

    WP_SIR_UTIL.setCookie(wp_sir_object.process_ml_upload_cookie, isProcessable.toString(), 365);
    // Normal HTML uploader.
    if ($('#html-upload-ui').length) {
      var $htmlProcessableInput = $('input[name="_processable_image"]');

      if ($htmlProcessableInput.length === 0) {
        $('#html-upload-ui').append(
          '<input type="hidden"  name="_processable_image" >'
        );
        $htmlProcessableInput = $($htmlProcessableInput.selector);
      }
      $htmlProcessableInput.val(isProcessable);
    }

    // Drag-and-drop uploader box.
    if (
      typeof wpUploaderInit === 'object' &&
      wpUploaderInit.hasOwnProperty('multipart_params')
    ) {
      wpUploaderInit.multipart_params._processable_image = isProcessable;
    }

    // Media library modal.
    if (
      wp.media &&
      wp.media.frame &&
      wp.media.frame.uploader &&
      wp.media.frame.uploader.uploader
    ) {
      wp.media.frame.uploader.uploader.param('_processable_image', isProcessable);
    }
  }


  // Toggle the Trim option settings.
  $('#wp-sir-enable-trim').on('change', function () {
    if ($(this).is(':checked')) {
      $('#wp-sir-trim-feather-wrap').removeClass('hidden');
      $('#wp-sir-trim-tolerance-wrap').removeClass('hidden');
    } else {
      $('#wp-sir-trim-feather-wrap').addClass('hidden');
      $('#wp-sir-trim-tolerance-wrap').addClass('hidden');
    }
  }).change();


  // Reset "Image sizes" to default ones.
  $(document).on('click', '#wpsirResetDefaultSizes', function () {
    var $sizesSelect = $('#wpSirResizeSizes');
    $sizesSelect.multipleSelect('setSelects', $sizesSelect.data('defaults').split(','));
  });


  // Add filter to Media Library (grid view)

  if (typeof sir_vars != 'undefined') {
    var SIR_MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
      id: 'media-attachment-sir-filter',
      createFilters() {
        this.filters = {
          all: {
            text: sir_vars.filter_strings.all,
            props: { _filter: 'all' },
            priority: 10,
          },
          processed: {
            text: sir_vars.filter_strings.processed,
            props: { _filter: 'processed' },
            priority: 20,
          },
          unprocessed: {
            text: sir_vars.filter_strings.unprocessed,
            props: { _filter: 'unprocessed' },
            priority: 30,
          },
        };
      },
    });

    var SIR_AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
    wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
      createToolbar() {
        // Make sure to load the original toolbar
        SIR_AttachmentsBrowser.prototype.createToolbar.call(this);
        this.toolbar.set(
          'SIR_MediaLibraryTaxonomyFilter',
          new SIR_MediaLibraryTaxonomyFilter({
            controller: this.controller,
            model: this.collection.props,
            priority: -75,
          }).render()
        );
      },
    });

  }

  // Handle the "Clear" button display.
  $('#wp-sir-clear-bg-color').on('click', function (e) {
    $colorPicker.find('.wp-picker-clear').click();
    $colorPicker.val('').trigger('change');
    $colorPicker.find('.wp-color-result').css({'background-color': '' });
    e.preventDefault();
    e.stopPropagation();
    $(this).hide();
  });

  if(!$colorPicker.val()){
    $('#wp-sir-clear-bg-color').hide();
  }

  $('.wp-picker-container').on('click', function () {
    if ($(this).hasClass('wp-picker-active')) {
      $('#wp-sir-clear-bg-color').hide();
    } else if ($colorPicker.val()) {
      $('#wp-sir-clear-bg-color').show();
    }
  });
  

  $(document).on('click', function (e) {
    if ($colorPicker.val()) {
      $('#wp-sir-clear-bg-color').show();
    }
  });


})(jQuery);


