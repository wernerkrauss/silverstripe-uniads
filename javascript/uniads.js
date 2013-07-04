(function($){
	$().ready(function(){
		/**
		 * process clicks
		 */
		$('body').on('click', 'a[data-adid]', function(e){
			if (e.which < 3) {
				$.post($('base').attr('href') + 'advrt-click/clk', { id: $(this).data('adid') });
			}
			return true;
		});
	});
})(jQuery);