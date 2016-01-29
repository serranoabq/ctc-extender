
jQuery(document).ready( function($) {
	$( ".ctcex-slider" ).on( 'init', function(e,s){
		$( ".ctcex-hidden" ).removeClass( 'ctcex-hidden' );
		console.log('here');
	} );
	$( ".ctcex-slider" ).slick({
		infinite: true,
		slidesToShow: 2,
		autoplaySpeed: 3000,
		autoplay: true,
		prevArrow: '<span class="slick-prev"><i class="fa fa-arrow-circle-left"></i></span>',
		nextArrow: '<span class="slick-next"><i class="fa fa-arrow-circle-right"></i></span>',
		responsive: [
			{
				breakpoint: 600,
				settings: {
					slidesToShow: 1,
				}
			}
		]
	});
})