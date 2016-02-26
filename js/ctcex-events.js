/*
* Events Slider
*/
jQuery(document).ready( function($) {
	$( ".ctcex-events-list.ctcex-slider" ).on( 'init', function(e,s){
		$( ".ctcex-events-list.ctcex-hidden" ).removeClass( 'ctcex-hidden' );
	} );
	$( ".ctcex-events-list.ctcex-slider" ).slick({
		infinite: true,
		autoplay: true,
		autoplaySpeed: 3000,
		prevArrow: '<span class="slick-prev"><i class="fa fa-arrow-circle-left"></i></span>',
		nextArrow: '<span class="slick-next"><i class="fa fa-arrow-circle-right"></i></span>'
	});
})

