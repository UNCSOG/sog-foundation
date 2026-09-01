// Dropdown menu JS for accessibility and mobile
jQuery(function($) {
  // Open submenu on click for touch devices
  $('.sog-rebrand__nav li.menu-item-has-children > a').on('click', function(e) {
    if ($(this).closest('.sog-rebrand__mobile-nav').length) {
      return;
    }

    var $parent = $(this).parent();
    if ($parent.hasClass('open')) {
      $parent.removeClass('open');
    } else {
      // Close other open submenus at this level
      $parent.siblings('.open').removeClass('open');
      $parent.addClass('open');
    }
    // Only prevent default if submenu exists
    if ($parent.children('ul').length) {
      e.preventDefault();
    }
  });

  // Close submenu when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.sog-rebrand__nav').length) {
      $('.sog-rebrand__nav li.open').removeClass('open');
    }
  });

  // Keyboard navigation: open submenu on Enter/Space
  $('.sog-rebrand__nav li.menu-item-has-children > a').on('keydown', function(e) {
    if ($(this).closest('.sog-rebrand__mobile-nav').length) {
      return;
    }

    if (e.key === 'Enter' || e.key === ' ') {
      $(this).trigger('click');
      e.preventDefault();
    }
  });
});
