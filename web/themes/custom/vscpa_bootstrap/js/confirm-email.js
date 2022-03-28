/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.confirmEmail = {
    attach: function (context, settings) {

      var emailAddress = $('.form-item-mail .col-sm-9');

      $(emailAddress, context).once().after('<label for="edit-mail" class="col-xs-12 col-sm-3 form-required">Confirm Email Address</label><div class="col-xs-12 col-sm-9"><input class="form-control confirm-email"></div><div class="email-requirement"><div class="description help-block">Please confirm your email address. <span class="no-match">Emails </span></div></div>');
      $('#edit-submit').addClass('email-fail').before('<span class="password-check">Check Requirements</span>');

    }
  };

  Drupal.behaviors.emailMatch = {
    attach: function (context, settings) {

      var confirmEmailInput = $('.confirm-email');
      var emailAddressInput = $('#edit-mail');
      var emailConfirm = $('.email-requirement span');
      var emailInputs = $('.confirm-email, #edit-mail');
      var submit = $('#edit-submit');

      $(confirmEmailInput, context).on('keyup', function () {
        if (emailAddressInput.val() == confirmEmailInput.val()) {
          $(emailConfirm).removeClass('no-match').addClass('ok');
        }

        if (emailAddressInput.val() != confirmEmailInput.val()) {
          $(emailConfirm).addClass('no-match').removeClass('ok');
        }
      });

      $(emailAddressInput, context).on('keyup', function () {
        if (emailAddressInput.val() == confirmEmailInput.val()) {
          $(emailConfirm).removeClass('no-match').addClass('ok');
        }

        if (emailAddressInput.val() != confirmEmailInput.val()) {
          $(emailConfirm).addClass('no-match').removeClass('ok');
        }
      });

      $(emailInputs, context).on('keyup', function () {
        if (emailConfirm.hasClass('ok')) {
          $(submit).removeClass('email-fail').prev().addClass('hide');
        }

        if (emailConfirm.hasClass('no-match')) {
          $(submit).addClass('email-fail').prev().removeClass('hide');
        }
      });

    }
  };

})(jQuery, Drupal);
