/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.passwordRequirements = {
    attach: function (context, settings) {

      var passwordDescription = $('#edit-pass--description');
      var passwordField = $('#edit-pass-pass1');

      $(passwordDescription).once().after('<div class="password-requirements">Requirements: <span class="uppercase">1 uppercase character</span>, <span class="special">1 special character</span>, and <span class="length">minimum length (10)</span>.</div>');


      $(passwordField, context).on('keyup', function () {

        var length = $('.length');
        var password = $('#edit-pass-pass1').val();
        var special = $('.special');
        var uppercase = $('.uppercase');

        if (/[A-Z]/.test(password)) {
          $(uppercase).addClass('ok').removeClass('no-match');
        } else {
          $(uppercase).addClass('no-match').removeClass('ok');
        }

        if (/[!@#$%^&*(),.?":;{}|<>`'~/[\]\{}+=_\\]/.test(password)) {
          $(special).addClass('ok').removeClass('no-match');
        } else {
          $(special).addClass('no-match').removeClass('ok');
        }

        if (password.length > 9) {
          $(length).addClass('ok').removeClass('no-match');
        } else {
          $(length).addClass('no-match').removeClass('ok');
        }
      });
    }
  };

  Drupal.behaviors.passwordMatch = {
    attach: function (context, settings) {

      var confirm = $('#edit-pass-pass2');
      var description = $('#edit-pass--description');
      var password   = $('#edit-pass-pass1');
      var passwordInputs = $('#edit-pass-pass1, #edit-pass-pass2');
      var gatekeeper = $('.length, .special, .uppercase, #edit-pass--description');
      var submit = $('#edit-submit');

      $(password, context).on('keyup', function () {
        if (password.val() != confirm.val()) {
          $(description).addClass('no-match').removeClass('ok');
        }

        if (password.val() == confirm.val()) {
          $(description).addClass('ok').removeClass('no-match');
        }
      });

      $(confirm, context).on('keyup', function () {
        if (password.val() != confirm.val()) {
          $(description).addClass('no-match').removeClass('ok');
        }

        if (password.val() == confirm.val()) {
          $(description).addClass('ok').removeClass('no-match');
        }
      });

      $(passwordInputs, context).on('keyup', function () {
        if (gatekeeper.hasClass('ok')) {
          $(submit).removeClass('password-fail').prev().addClass('hide');
        }

        if (gatekeeper.hasClass('no-match')) {
          $(submit).addClass('password-fail').prev().removeClass('hide');
        }
      });

      $(passwordInputs, context).on('blur', function () {
        if (!$('.email-requirement span').hasClass('no-match') && !$('#edit-pass--description').hasClass('no-match') && !$('.uppercase, .special, .length').hasClass('no-match')) {
          $(submit).prev().addClass('hide');
        }
      });
    }
  };

  Drupal.behaviors.emptyPasswordFields = {
    attach: function (context, settings) {
      $('#edit-pass-pass1, #edit-pass-pass2', context).on('keyup', function () {

      if ($('#edit-pass-pass1').val() === "" && $('#edit-pass-pass2').val() === "") {
        $('#edit-submit').removeClass('password-fail');
        $('.password-requirements span').removeClass('no-match');
      }

      });
    }
  };

})(jQuery, Drupal);