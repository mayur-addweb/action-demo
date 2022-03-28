/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.disableClick = {
    attach: function (context, settings) {
      $(context).find('.product-variations-price-fields-wrapper .btn').each(function() {
        $(this).click(function() {
          $(this).addClass('clicked');
        });
      });
    }
  };

  Drupal.behaviors.eventSharing = {
    attach: function (context, settings) {

      var event = $('article.event');
      var parent = $('.main-content');
      var share = $('.block-addtoany');

      $(context).find(event).each(function() {
        if ( $(event).hasClass('Conference') )
        {
          $(this).parents(parent).prev().find(share).addClass('conference');
        }
        if ( $(event).hasClass('Course') )
        {
          $(this).parents(parent).prev().find(share).addClass('course');
        }
        if ( $(event).hasClass('Webinar') )
        {
          $(this).parents(parent).prev().find(share).addClass('webinar');
        }
      });
    }
  };

  Drupal.behaviors.openMenu = {
    attach: function (context, settings) {

      var items = $('.event-menu .items');
      var links = $('.event-menu .items a');
      var title = $('.event-menu .title');

      $(context).find(title).each(function() {
        $(this).click(function() {
          $(this).siblings(items).toggleClass('active');
        });
        $(this).siblings(items).children(links).click(function() {
          $(this).parents(items).removeClass('active');
        });
      });
    }
  };

  Drupal.behaviors.displayLinks = {
    attach: function (context, settings) {

      var advancedPrepField = $('#advanced-prep');
      var advancedPrepMenu = $('.advanced-prep');
      var fields = $('.menu-fields');
      var highlightsField = $('#highlights');
      var highlightsMenu = $('.highlights');
      var leadersField = $('#leaders');
      var leadersMenu = $('.leaders');
      var locationField = $('#location');
      var locationMenu = $('.location');
      var materialsField = $('#materials');
      var materialsMenu = $('.materials');
      var mainContent = $('.main-content');
      var objectivesField = $('#objectives');
      var objectivesMenu = $('.objectives');
      var scheduleField = $('#schedule');
      var scheduleMenu = $('.schedule');
      var sessionsField = $('#sessions');
      var sessionsMenu = $('.sessions');
      var sidebarTop = $('.sidebar-top');
      var syllabusField = $('#syllabus');
      var syllabusMenu = $('.syllabus');
      var vendorsField = $('#vendors');
      var vendorsMenu = $('.vendors');

      $(context).find(fields).each(function() {
        if ( $(highlightsField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(highlightsMenu).removeClass('hide');
        }
        if ( $(objectivesField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(objectivesMenu).removeClass('hide');
        }
        if ( $(sessionsField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(sessionsMenu).removeClass('hide');
        }
        if ( $(syllabusField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(syllabusMenu).removeClass('hide');
        }
        if ( $(advancedPrepField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(advancedPrepMenu).removeClass('hide');
        }
        if ( $(locationField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(locationMenu).removeClass('hide');
        }
        if ( $(materialsField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(materialsMenu).removeClass('hide');
        }
        if ( $(vendorsField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(vendorsMenu).removeClass('hide');
        }
        if ( $(scheduleField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(scheduleMenu).removeClass('hide');
        }
        if ( $(leadersField).length )
        {
          $(this).parents(mainContent).siblings(sidebarTop).find(leadersMenu).removeClass('hide');
        }
      });
    }
  };

})(jQuery, Drupal);
