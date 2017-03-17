/*
* Events Slider
*/
jQuery(document).ready( function($) {
	$( ".ctcex-events-list.ctcex-slider" ).on( 'init', function(e,s){
		$( ".ctcex-events-list.ctcex-hidden" ).removeClass( 'ctcex-hidden' );
	} );
	var ctcex_event_slides = ( typeof ctcex_events === 'undefined' ? 1 : ctcex_events.slides );
	$( ".ctcex-events-list.ctcex-slider" ).slick({
		infinite: true,
		autoplay: true,
		slidesToShow: ctcex_event_slides,
		autoplaySpeed: 3000,
		prevArrow: '<span class="slick-prev"><i class="fa fa-arrow-circle-left"></i></span>',
		nextArrow: '<span class="slick-next"><i class="fa fa-arrow-circle-right"></i></span>',
		responsive: [
			{
				breakpoint: 1024,
				settings: {
					slidesToShow: ctcex_event_slides > 2 ? 2 : 1,
				}
			},{
				breakpoint: 600,
				settings: {
					slidesToShow: 1,
				}
			}
		]
	});
	
	
})

