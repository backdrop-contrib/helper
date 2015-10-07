(function ($) {

  "use strict";

  Drupal.jsAC.prototype.select = function (node) {
    this.input.value = $(node).data('autocompleteValue');
    $(this.input).trigger('autocompleteSelect', [node]);
    if ($(this.input).hasClass('form-autocomplete-submit')) {
      this.input.form.submit();
    }
  }

})(jQuery);
