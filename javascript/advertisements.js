(function($){
	$().ready(function(){

		var base = $('base').attr('href');

		/**
		 * process clicks
		 */
		$('.adlink').livequery(function(){
			$(this).mouseup(function(b){
				if (b.which < 3) {
					$.post(base + 'adclick/clk', { id: $(this).data('adid') });
				}
				return true;
			})
		});

	});
})(jQuery);