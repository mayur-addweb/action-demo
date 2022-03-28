(function ($, Drupal) {

  Drupal.behaviors.generateAnnouncement = {
    attach: function(context, settings) {
      for (var id in settings.announcements) {
        var content = settings.announcements[id]
        $(context).find('body').append(content);
      }
    }
  };

  Drupal.behaviors.closeAnnouncement = {
    attach: function(context, settings) {
      $('.announcement-overlay .close div, .announcement-overlay .btn, .announcement-overlay .field--name-field-body a', context).click( function() {
        $(this).parents('.announcement-overlay').hide();

        var announcementID = $('.announcement-overlay').attr('id');

        localStorage.setItem(announcementID, 'dismissed');
      });
    }
  };

  Drupal.behaviors.delayAnnouncement = {
    attach: function(context, settings) {
      var announcement = $('.announcement-overlay');
      var announcementID = $('.announcement-overlay').attr('id');
      var delaySeconds = $('.announcement-overlay').attr('data-delay');
      var delayTimer = parseInt(delaySeconds) * 1000 - 2000;

      if (localStorage.getItem(announcementID) != 'dismissed' ) {
        setTimeout(function () {
          setTimeout(function () {
            $(announcement).show();
          }, delayTimer);
        }, 2000);
      }
    }
  };

})(jQuery, Drupal);


