jQuery(document).ready(function($){
	// Variables
	var MBP 		 	 = 768; // Temporarily Hardcoded Mobile BreakPoint
	var Window 			 = $(window);
	var Body		 	 = $('body');
	var MainNav 		 = $('.g-main-nav');
	var TopLevel 	 	 = $('.g-toplevel');
	var PageSurround 	 = $('#g-page-surround');	
	var MobileNavOverlay = $('<div/>', { 'class': 'g-nav-overlay' });
	var MobileNav 		 = $('<nav/>', { 'class': 'g-main-nav' }).addClass('g-mobile-nav');
	var MobileNavToggle  = $('<div/>', { 'class': 'g-mobile-nav-toggle' });	

	// Open Dropdown & Overlay
	$('.g-menu-item').children('.g-menu-item-content').on('click', function(){
		var selected = $(this);
		if( selected.next('.g-dropdown').hasClass('g-inactive') ) {
			selected.addClass('g-selected').next('.g-dropdown').removeClass('g-inactive').addClass('g-active').end()
					.parent('li').parent('ul').addClass('g-slide-out').end()
					.closest('.g-block').siblings('.g-block').addClass('g-block-inactive');
			selected.parent('.g-menu-item').siblings('.g-menu-item').children('ul').removeClass('g-active').addClass('g-inactive').end()
					.children('.g-menu-item-content').removeClass('g-selected');
			if( Window.width() > MBP ) {
				Body.addClass('g-nav-overlay-active');
			}
		} else {
			resetSelectedActive();
		}
	});

	// Go Back Link for Level 1
	$('.g-level-1').on('click', function(){
		$(this).closest('.g-dropdown').removeClass('g-active').addClass('g-inactive').closest('.g-toplevel').removeClass('g-slide-out');
	});		
	// Go Back Link for Level 2+
	$('.g-go-back').on('click', function(){
		$(this).closest('.g-dropdown').removeClass('g-active').addClass('g-inactive').closest('.g-sublevel').removeClass('g-slide-out').end()
			   .closest('.g-menu-item').children('.g-menu-item-content').removeClass('g-selected').end()
			   .closest('.g-block').siblings('.g-block').removeClass('g-block-inactive');;
	});		

	// Toggle Class on Mobile
	Body.append(MobileNav);
	MobileNavOverlay.appendTo(PageSurround);
	MobileNavToggle.appendTo(PageSurround);

	Window.bind("load resize",function(e){
		if( Window.width() < MBP ) {
			resetSelectedActive();
			PageSurround.next('.g-mobile-nav').append(TopLevel);
		} else {
			resetSelectedActive();
			PageSurround.find('.g-main-nav').append(TopLevel);
			if( Body.hasClass('g-mobile-nav-active') ) {
				Body.removeClass('g-mobile-nav-active');
			}
		}
	});	

	// Mobile Nav Toggle
    $('.g-mobile-nav-toggle').on('click', function() {
		Body.toggleClass('g-mobile-nav-active');
    });	

    // Page Surround Overlay
    $('.g-nav-overlay').on('click', function() {
    	if( Window.width() < MBP ) {
			Body.toggleClass('g-mobile-nav-active');
		} else {
			Body.toggleClass('g-nav-overlay-active');
			resetSelectedActive();
		}
    });	    

    // Reset Menu Item Selected and SubLevel Active State
	function resetSelectedActive(){
		Body.removeClass('g-nav-overlay-active');
		MainNav.find('.g-selected').removeClass('g-selected').end()
			   .find('.g-active').removeClass('g-active').addClass('g-inactive').end()
			   .find('.g-toplevel').removeClass('g-slide-out').end()
			   .find('.g-sublevel').removeClass('g-slide-out');
	}    

});