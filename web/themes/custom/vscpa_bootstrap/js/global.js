/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */

(function ($, Drupal) {

  Drupal.behaviors.mainMenu = {
    attach: function (context, settings) {

      var flyout = '.fly-out';
      var icon = '.nav .icon-arrow-3';
      var menuFlyout = '.fly-out-menu.left';
      var menuTrigger = '.menu-button .btn-header';
      var searchFlyout = '.fly-out-menu.right';
      var searchTrigger = '.search-button .btn-header';
      
      // Open/Close Menu Flyout
      $(context).find(menuTrigger).each(function(){
        $(this).click(function() {
          $(this).toggleClass('on').parents(flyout).addClass('menu-open').siblings(menuFlyout).toggleClass('show').siblings('.right').removeClass('show');
          $(this).parents('.menu-button').siblings('.search-button').children('.btn-header').removeClass('on');
        });
      });

      // Open/Close Search Flyout
      $(context).find(searchTrigger).each(function(){
        $(this).click(function() {
          $(this).toggleClass('on').parents(flyout).addClass('menu-open').siblings(searchFlyout).toggleClass('show').siblings('.left').removeClass('show');
          $(this).parents('.search-button').siblings('.menu-button').children('.btn-header').removeClass('on');
        });
      });

      // Open/Close nested menus
      $(context).find(icon).each(function(){
        $(this).click(function() {
          $(this).toggleClass('active').next('.dropdown-menu').toggleClass('show').parents('.expanded').addClass('clearfix');
          $(this).prev().toggleClass('active');
        });
      });
    }
  };

  Drupal.behaviors.cartButton = {
    attach: function (context, settings) {

      var cartQuantity = $('#block-cart .cart-block--summary__count').text().substr(0, 2);
      var cartMenu = $('.user-menu .cart');

      $(cartMenu, context).append('<span>' + cartQuantity + '</span>');
    }
  };

  Drupal.behaviors.footerMenu = {
    attach: function (context, settings) {
      // Expand and Collapse Footer Menu
      $('.footer-menu', context).on('click', '.block-title', function(){
        $(this).siblings('.menu').toggleClass('show');
      });

    }
  };

  Drupal.behaviors.randomHeaderBK = {
    attach: function (context, settings) {
      // Randomize Hero Region Class
      var heroClass = Array();
      heroClass[0] = "hero-purple";
      heroClass[1] = "hero-grey";
      heroClass[2] = "hero-green";
      heroClass[3] = "hero-teal";
      heroClass[4] = "hero-orange";
      heroClass[5] = "hero-yellow";

      var randomClass = Math.floor(Math.random() * heroClass.length);
      $('#page-hero').addClass(heroClass[randomClass]);
    }
  };

  Drupal.behaviors.backToTop = {
    attach: function (context, settings) {
      // Back To Top Button
      $('.back-to-top').click(function() {
        $('html, body').animate({ scrollTop: 0 }, "slow");
        return false;
      });
    }
  };

  Drupal.behaviors.coverTheming = {
    attach: function (context, settings) {
      var cover = $('article.cover');
      var region = $('.region-cover');

      // H1 Theming
      $(cover).has('.btn-blue').addClass('blue').parents(region).next().addClass('blue');
      $(cover).has('.btn-gold').addClass('gold').parents(region).next().addClass('gold');
      $(cover).has('.btn-green').addClass('green').parents(region).next().addClass('green');
      $(cover).has('.btn-purple').addClass('purple').parents(region).next().addClass('purple');

      // Cover Video Sizing
      $(cover).has('.cover-video').addClass('video');
    }
  };

})(jQuery, Drupal);
