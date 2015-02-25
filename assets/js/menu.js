jQuery(document).ready(function($){
	
	// Open Dropdown
	$('.g-menu-item').children('.g-menu-item-content').on('click', function(event){
		var selected = $(this);
		if( selected.next('.g-dropdown').hasClass('g-inactive') ) {
			selected.addClass('g-selected').next('.g-dropdown').removeClass('g-inactive').addClass('g-active').end().parent('li').parent('ul').addClass('g-slide-out');
			selected.parent('.g-menu-item').siblings('.g-menu-item').children('ul').removeClass('g-active').addClass('g-inactive').end().children('.g-menu-item-content').removeClass('g-selected');
		} else {
			selected.removeClass('g-selected').next('.g-dropdown').removeClass('g-active').addClass('g-inactive').end().parent('li').parent('ul').removeClass('g-slide-out');
		}
	});

	// SubMenu Item - Go Back Link
	$('.g-go-back').on('click', function(){
		$(this).closest('.g-dropdown').removeClass('g-active').addClass('g-inactive').closest('.g-sublevel').removeClass('g-slide-out');
	});	
});