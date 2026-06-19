/**
 * Custom scripts.
 */
(function ($, Drupal) {
  // External links.
  $("a[href^='http']").each(function () {
    var re_matches = /https?:\/\/([^\/]*)/.exec($(this).attr('href'));
    // Check link against the current domain.
    if (re_matches && re_matches[1] && re_matches[1] != location.hostname && re_matches[1] != 'www.' + location.hostname && 'www.' + re_matches[1] != location.hostname) {
      $(this).attr('target', '_blank');
    }
  });

  // I Want To Nav.
  $('.i-want-to-nav__trigger').click(function (e) {
    $(this).parent().find('.i-want-to-nav__options').slideToggle('fast');

    e.preventDefault();
  });

  // Mobile Overlay.
  $('.mobile-overlay').click(function (e) {
    $(this).fadeOut('fast');
    $('.mobile-search-form input.form-search').blur();
  });
  $('.mobile-overlay__content').click(function (e) {
    e.stopPropagation();
  });

  // Mobile Search.
  $('.mobile-control-nav .menu__item--search a').click(function (e) {
    $('.mobile-search-overlay').fadeIn('fast');
    $('.mobile-search-form input.form-search').focus();
    e.preventDefault();
  });
  $('.mobile-search-form__submit').click(function (e) {
    $('.mobile-search-form form').submit();
    e.preventDefault();
  });

  // Mobile Navigation.
  $('.mobile-control-nav .menu__item--menu a').click(function (e) {
    $('.mobile-nav-overlay').fadeIn('fast');
    e.preventDefault();
  });

  // Mobile Dashboard Navigation.
  $('.menu__item--dashboard-quicklinks-mobile a').click(function (e) {
    $('.mobile-dashboard-overlay').fadeIn('fast');
    e.preventDefault();
  });

  $('.mobile-overlay__close').click(function (e) {
    $('.mobile-overlay').fadeOut('fast');
    $('.mobile-search-form input.form-search').blur();
    e.preventDefault();
  });

  // Mobile Navigation - Clone expandable parent into sub-menu.
  $('.mobile-nav nav > ul > li.menu__item--expanded > a').each(function () {
    var $this = $(this);
    var $thisClone = $(this).clone();
    // Change the link text so there's not duplicate links beside each other.
    $thisClone.html(Drupal.t('Overview'));

    $thisClone.wrap('<li class="menu__item menu__item--parent-overview"></li>').parent().prependTo($this.next('ul'));
  });

  // Mobile Navigation - Click on parents to expand.
  $('.mobile-nav nav > ul > li.menu__item--expanded > a').click(function (e) {
    var $this = $(this);
    var $nextMenu = $this.next('ul');

    // Toggle slide animation for sub-menus.
    $nextMenu.slideToggle('fast');
    $('.mobile-nav nav > ul > li.menu__item--expanded > a').next('ul').not($nextMenu).slideUp('fast');

    e.preventDefault();
  });

  // Add search input placeholder.
  $('.block-search input.form-search, .search-page-form input.form-search').attr('placeholder', Drupal.t('Search our site ...'));

  // Primary wrap: when hovering the arrow, show wrap hover state (input can block CSS :hover).
  $(document).on('mouseenter', '.btn--primary-wrap', function () {
    $(this).closest('.btn--primary-wrap').addClass('is-arrow-hovered');
  });
  $(document).on('mouseleave', '.btn--primary-wrap', function () {
    $(this).removeClass('is-arrow-hovered');
  });

  // Banner slider.
  if ($('.banner-slider__slider').length) {
    $('.banner-slider__slider').slick({
      centerMode: true,
      slidesToShow: 3,
      infinite: true,
      dots: true,
      arrows: true,
      autoplay: false,
      variableWidth: true,
      autoplaySpeed: 5000,
      speed: 700,
      responsive: [
        {
          breakpoint: 1200,
          settings: {
            centerMode: false,
            slidesToShow: 1,
            variableWidth: false
          }
        }
      ]
    });
  }

  // Site search submit trigger.
  if ($('.form-search-submit-trigger').length) {
    $('.form-search-submit-trigger').click(function (e) {
      // Submit the parent form.
      $(this).parents('form').submit();
      e.preventDefault();
    });
  }

  // Dashboard.
  $('.menu__item--dashboard-quicklinks > a').click(function (e) {
    $('.dashboard').slideToggle('fast');
    $(this).parent().toggleClass('active');
    $('body').toggleClass('dashboard-open');

    e.preventDefault();
  });

  // Open the dashboard if on the user profile page.
  if ($('body.user-profile').length) {
    if ($('.menu__item--dashboard-quicklinks > a').is(':visible')) {
      $('.menu__item--dashboard-quicklinks > a').click();
    }
  }

  // Partners slider.
  if ($('.partners-slider').length) {
    $('.partners-slider .view-content').slick({
      infinite: true,
      dots: false,
      arrows: true,
      autoplay: false,
      autoplaySpeed: 5000,
      slidesToShow: 5,
      speed: 700,
      responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: 4,
            slidesToScroll: 4
          }
        },
        {
          breakpoint: 992,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2
          }
        },
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1
          }
        }
      ]
    });
  }

  // Used Equipment.
  if ($('.view-used-equipment').length) {
    // Add search input placeholder.
    $('.view-used-equipment .form-item-search .form-text').attr('placeholder', Drupal.t('Search Used Equipment...'));
  }

  // Used Equipment Slider.
  if ($('.used-equipment-slider').length) {
    $('.used-equipment-slider .view-content').slick({
      fade: true,
      infinite: true,
      dots: true,
      arrows: true,
      autoplay: false,
      autoplaySpeed: 5000,
      slidesToShow: 1,
      speed: 700
    });
  }

  // Job Postings
  if ($('.view-job-postings').length) {
    // Add search input placeholder.
    $('.view-job-postings .form-item-search .form-text').attr('placeholder', Drupal.t('Search Job Postings...'));
  }

  // Magnific Popup Gallery.
  if (!!$.prototype.magnificPopup) {
    if ($('.magnific-popup-gallery').length) {
      $('.magnific-popup-gallery').magnificPopup({
        type: 'image',
        gallery: {
          enabled: true
        }
      });
    }
  }

  // Product Slider.
  if ($('.product-slider__main-slider').length) {
    $('.product-slider__main-slider').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      fade: true,
      asNavFor: '.product-slider__nav-slider',
      infinite: false
    });
    $('.product-slider__nav-slider').slick({
      slidesToShow: 3,
      slidesToScroll: 1,
      asNavFor: '.product-slider__main-slider',
      dots: false,
      centerMode: false,
      infinite: false
    });
  }

  // Back button.
  // Go back to previous page if referrer matches site.
  if (document.referrer.indexOf(window.location.hostname) != -1) {
    $('a.js-go-back').click(function () {
      parent.history.back();
      return false;
    });
  }

  // Regional Training Centres.
  // Nav Listing.
  if ($('.view-display-id-nav_listing').length) {
    $('.training-centre__title').each(function () {
      var $navItem = $(this);
      var $navID = $navItem.data('node-id');
      var $nodeID = $('.node').data('history-node-id');

      if ($navID === $nodeID) {
        $navItem.parents('.base-accordion__item').children('.base-accordion__header').click();
        $navItem.addClass('is-active');
      }
    });
  }

  // Events.
  if ($('.view-events.view-display-id-events').length) {
    // Add search input placeholder.
    $('.view-events.view-display-id-events .form-type-textfield .form-text').attr('placeholder', Drupal.t('Search Events...'));
  }

  // Education & Training.
  if ($('.view-education-training.view-display-id-education_training').length) {
    // Add search input placeholder.
    $('.view-education-training.view-display-id-education_training .form-type-textfield .form-text').attr('placeholder', Drupal.t('Search Courses...'));
  }

  // Microsites - Flag nav if register now is not visible.
  if (!$('.microsite-nav .menu--level-0 > .menu__item--register-now').length) {
    $('.microsite-nav').addClass('microsite-nav--register-off');
  }

  // Microsites - Show sidebar Register Now button.
  if ($('.microsite-nav .menu--level-0 > .menu__item--register-now').length) {
    var $micrositeNavRegisterNowLink = $('.microsite-nav .menu--level-0 > .menu__item--register-now .menu__link');
    var $sidebarButton = $('.js-microsite-sidebar-register-now .btn');

    // Attach main nav href to sidebar href.
    $sidebarButton.attr('href', $micrositeNavRegisterNowLink.attr('href'));

    // Show sidebar button.
    $('.js-microsite-sidebar-register-now').show();
  }

})(jQuery, Drupal);
