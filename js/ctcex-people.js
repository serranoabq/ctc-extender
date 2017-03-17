/*
* People slider
*/
jQuery(document).ready( function($) {
	$( ".ctcex-people-list.ctcex-slider" ).on( 'init', function(e,s){
		$( ".ctcex-people-list.ctcex-hidden" ).removeClass( 'ctcex-hidden' );
	} );
	$( ".ctcex-people-list.ctcex-slider" ).slick({
		infinite: true,
		autoplaySpeed: 3000,
		slidesToShow: 3,
		autoplay: true,
		prevArrow: '<span class="slick-prev"><i class="fa fa-arrow-circle-left"></i></span>',
		nextArrow: '<span class="slick-next"><i class="fa fa-arrow-circle-right"></i></span>',
		responsive: [
			{
				breakpoint: 1024,
				settings: {
					slidesToShow: 2,
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
