if($) {
	$(document).ready(function() {

		if($.cookie) {
			var page_y = $.cookie("scrollTo_page_y");
			var page_url = $.cookie("scrollTo_url");
			//console.log(page_y,page_url);

			if(page_y>0 && document.location.href.indexOf(page_url)>-1 ) {
				$(document).scrollTop(page_y);
			}


		}

	});

	$(window).on('unload', function() {

		var page_y = $(document).scrollTop();
		//console.log(page_y);
		if($.cookie) {

			$.cookie("scrollTo_page_y", page_y, { expires: 1, path: '/' });
			$.cookie("scrollTo_url", document.location.href.split('?')[0], { expires: 1, path: '/' });

		}
	});

}
