/**
 * @file
 * Virtual Conference, access via email - Element behaviors.
 */
(function ($, Drupal) {
  // WIDGET CLASS DEFINITION
  // =======================
  var VicAccessViaEmail = function (options) {
    this.options = options;
    this.$element = options.selector;
    // Email Selector.
    this.$email = this.$element.find('#email-address');
    // Submit Button selector.
    this.$submi_button = this.$element.find('#submit-button');
    // Get the event info.
    this.$info = this.$element.find('.sessions-access-via-email');
    // Handle Actions.
    this.handleFormSubmit();
  };
  // Helper show loading message.
  VicAccessViaEmail.prototype.showLoadingMessage = function () {
    this.$submi_button.addClass('clicked').html('Validating Access... <div class="loader"></div>');
  };
  // Helper hide loading message.
  VicAccessViaEmail.prototype.hideLoadingMessage = function () {
    this.$submi_button.removeClass('clicked').html('Submit');
  };
  // Helper: Get the Event Code.
  VicAccessViaEmail.prototype.getEventCode = function () {
    return this.$info.attr('data-event-code');
  };
  // Helper: Get the Event Code.
  VicAccessViaEmail.prototype.getEventYear = function () {
    return this.$info.attr('data-event-year');
  };
  // Helper: Get the Email Address.
  VicAccessViaEmail.prototype.getEmailAddress = function () {
    return this.$email.val();
  };
  // Hook 'Register Staff' action.
  VicAccessViaEmail.prototype.handleFormSubmit = function () {
    var that = this;
    this.$submi_button.click(function (event) {
      event.preventDefault();
      event.stopPropagation();
      // Show the loading message.
      that.showLoadingMessage();
      // Get Sessions access.
      that.getSessions(that.getEventCode(), that.getEventYear(), that.getEmailAddress());
    });
  };
  // Helper: 'Add To Cart' action.
  VicAccessViaEmail.prototype.getSessions = function ($event_code, $event_year, $email_address) {
    var that = this;
    $.ajax({
      url: '/ajax-get-access-to-vic-sessions',
      dataType: 'json',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        'event_code': $event_code,
        'event_year': $event_year,
        'email_address': $email_address,
      }),
      processData: false,
      success: function (data, textStatus, jQxhr) {
        if (data.success) {
          jQuery('.vic-access-via-email-container').replaceWith(data.sessions);
        }
        else {
          // Show validation message.
          jQuery('.vic-access-via-email-container .status-messages > div').html(data.messages);
          jQuery('.vic-access-via-email-container #submit-button').removeClass('clicked').html('Submit');
        }
        console.log(data);
      },
      error: function (jqXhr, textStatus, errorThrown) {
        console.log(errorThrown);
      }
    });
  };
  // Define plugin version.
  VicAccessViaEmail.VERSION = '1.0.0';
  // Modal 'Add To Cart tProduct Registration' - Behaviors.
  Drupal.behaviors.VicAccessViaEmail = {
    attach: function (context, settings) {
      // Only allow for one instantiation of this script
      if (typeof $.fn['VicAccessViaEmail'] !== 'undefined') {
        return;
      }
      var widget = new VicAccessViaEmail({
        selector: $('.vic-access-via-email-container', context),
      });
    }
  };
})(jQuery, Drupal);
