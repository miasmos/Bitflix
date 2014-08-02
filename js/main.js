$(document).ready(function() {
	/*
	
	TO DO
	
	frontend
	fix trailer div freeze on video fail
	on search, animate new row into view and insert below the topmost row that's in view
	default poster art
	trailer full screen
	advanced search?
	finish info details, detailed torrent info, quality pane, change poster click to open quality pane
	long-ass title scrolling
	long-ass overview pagination
	fix wierd row scrolling bugs
	help menus of some kind
	genre, year, actor menus
	program 'featured' row..  info revealed by default, wider, full art
	implement header, same size as featured row, explains what's going on etc.
	fix search icon, change 'play video' icon, change download icon
	minify everything and worry about page size
	analytics?
	
	backend
	force english results?
	account for multiple qualities in data returns (ie. group them together so they can be presented in the ui)
	optimize db queries
	finish search feature
	add more db query combinations
	implement randomization
	fix dailyUpdate and GetTorrentData scripts
	server redundancy

	future
	implement user account system
	email notifications for movie additions
	RPC capability
	categories based on downloaded or liked movies
	taste preferences
	
	*/
	var posterNum=$(window).width()/$('.poster').width();
	var posterWidth=$('.poster').width();
	var animQue=0;
	var appendPosterNum=3;

	$(window).resize(function() {
		posterNum=$(window).width()/$('.poster').width();	//get number of poster that fit across the screen
		posterWidth=$('.poster').width();
	});

	setInterval(function() {
		if ($(animQue).children('.trailer').length == 0) {$(animQue).stop(true).animate({width:posterWidth},200); $(animQue).find('.poster img').stop(true).animate({opacity:0.75},100); animQue=0;}	//reset the movie that just had a trailer
		
		//check for row wrap
		$('.movie-wrapper').each(function(index) {
			if ($(this).children().length >= posterNum) {
				if ($(this).is(':animated')) {
					lastPos = $(this).find('.movie:nth-last-child('+appendPosterNum+')').offset();
					firstPos = $(this).find('.movie:nth-child('+appendPosterNum+')').offset();
					
					if (lastPos.left+posterWidth < $(window).width()) {
						$('#scrolling').animate({left:'+='+(posterWidth)},0,function(){
							$(this).find('.movie:nth-child(1)').appendTo(this);
						});
					}
					if (firstPos.left > 0) {
						$('#scrolling').animate({left:'-='+(posterWidth)},0,function(){
							$(this).find('.movie:nth-last-child(1)').prependTo(this);
						});
					}
				}
			}
		});
	},100);
	
	//remember original accordian direction
	$('#content').on("mouseleave", '.movie-wrapper', function(event) {
		if ($('#accordian-right').length && $('.accordian-left').length) {
			$(this).animate({left:-$('.info').width()},{queue:false});
		}
		$('#accordian-left').attr('id','');
		$('#accordian-right').attr('id','');
	}).on("mouseenter", '.movie-wrapper', function(event) {
		
	});

	//adjust opacity of category titles,poster images on hover
	$('#content').on("mouseenter", '.movie,.category-title', function(event) {
		$(this).closest('.category-title').stop(true).fadeOut(400);
		$(this).find('.poster img').stop(true).animate({opacity:1},100);
	});
	$('#content').on("mouseleave", '.movie,.category-title', function(event) {
		$(this).closest('.category-title').stop(true).fadeTo(400,0.85);
		if ($(this).children('.trailer').length == 0) {$(this).find('.poster img').stop(true).animate({opacity:0.70},100);}
	});
	
	//animate category title opacity on hover
	$('#content').on("mouseenter", '.movie,.category-title', function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.15},400);});
	$('#content').on("mouseleave", '.movie,.category-title', function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.7},400);});
	
	//animate info reveals on cover hover
	$('#content').on("mouseenter", '.movie', function(event) {
		var offset=$(this).offset();
		if (parseFloat(offset.left + $(this).width() + 390) > $(window).width()) { //info will clip outside of window boundaries, open left
			$(this).find('.info').animate({left:"0px"},0);
			$(this).stop(true).animate({width:"554px"},200);
			$(this).find('.poster').animate({left:"400px"},200);
			$(this).addClass('accordian-left');
			if ($(this).prev().width() == posterWidth) {$(this).parent().stop(true).animate({left:"-=400px"},200);}
			else {$(this).parent().stop(true);}

			if (!$('#accordian-left').length && !$('#accordian-right').length) {
				$(this).attr('id','accordian-left');
			}
		}
		else {
			if ($(this).parent().hasClass('closing')) {$(this).parent().stop(true); $(this).parent().removeClass('closing');}
			$(this).addClass('accordian-right');
			$(this).stop(true).animate({width:"554px"}, 200);

			if (!$('#accordian-left').length && !$('#accordian-right').length) {
				$(this).attr('id','accordian-right');
			}
		}
	}).on("mouseleave", '.movie', function() {
		if ($(this).children('.trailer').length == 0) {	//if a trailer isn't playing here
			if ($(this).hasClass('accordian-left')) {	//if accordian has opened to the left
				$(this).find('.poster').animate({left:"0px"},200);
				$(this).parent().addClass('closing').stop(true).animate({left:"+=400px"},200,
					function(){
						$(this).removeClass('closing');
				});
				
			}
			$(this).stop(true).animate({width:posterWidth},200,function(){
				$(this).find('.info').animate({left:posterWidth},0);
				$(this).find('.quality').animate({left:posterWidth+$('.info').width()+30},0);
				$(this).removeClass('accordian-left').removeClass('according-right');
			});
		}
		else {	//if a trailer is playing, que it
			animQue=$(this);
		}
	});

	//animate quality reveal on poster click
	$('#content').on('click', '.poster', function(){
		$(this).closest('.movie').find('.quality').animate({left:posterWidth},200);
	});

	//animate quality reveals on download click
	$('#content').on("click", '.download', function(){
		$(this).closest('.movie').find('.quality').animate({left:posterWidth},200);
	});

	//close quality on quality click
	$('#content').on("click",'.quality', function(){
		$(this).find('.quality').animate({left:posterWidth+$('.info').width()+30},0);
	});
	
	//embed trailer on link click
	$('#content').on("click",'.trailer-link',function(event) {
		event.preventDefault();
		if ($('.trailer').length == 0) {
			$(this).closest('.movie').append("<div class='trailer hidden'></div");
			$('.trailer').youTubeEmbed({
				video:'http://www.youtube.com/watch?v='+$(this).attr('data-href'),
				width:400,
				progressBar:true,
				autoplay:true
			}).fadeIn(400,function(){
				$('.trailer').removeClass('hidden');
			});
		}
		else {
			var newTrailer=$(this);
			$(this).closest('.movie').append("<div class='trailer hidden'></div>");
			$('.trailer').not('.hidden').parent().find('.poster img').stop(true).animate({opacity:0.7},100);
			$('.trailer').not('.hidden').closest('.movie').stop(true).animate({width:posterWidth},200,function(){
				$('.trailer').not('.hidden').remove();
				$('.trailer').youTubeEmbed({
					video:'http://www.youtube.com/watch?v='+$(newTrailer).attr('data-href'),
					progressBar:true,
					autoplay:true
				}).fadeIn(400,function(){
					$('.trailer').removeClass('hidden');
				}); 
			});
		}
	});
	
	//animate movie row scrolling
	$('#content').on("mouseenter", '.move-left', function(dat) {
		var par = $(this).parent().find('.movie-wrapper');
		$(par).attr('id','scrolling');
		if ($(par).children().length >= posterNum) {
			this.iid = setInterval(function() {
				moveIt(0);
			}, 20);
		}
	}).on("mouseleave", '.move-left', function() {
		this.iid && clearInterval(this.iid);
		$('#scrolling').attr('id','');
	});
	
	$('#content').on("mouseenter", '.move-right', function() {
		if ($(this).parent().find('.move-left').hasClass('hidden')) {$(this).parent().find('.move-left').removeClass('hidden');}	//make left arrow useable after moving right

		var par = $(this).parent().find('.movie-wrapper');
		$(par).attr('id','scrolling');

		if ($(par).children().length >= posterNum) {
			this.iid = setInterval(function(dat) {
				moveIt(1);
			}, 25);
		}
	}).on("mouseleave", '.move-right', function() {
		this.iid && clearInterval(this.iid);
		$('#scrolling').attr('id','');
	});
	
	function moveIt(dir) {
		if (dir==1) {$('#scrolling').animate({left:"-=10"},10);}
		else if (dir==0) {$('#scrolling').animate({left:"+=10"},10);}
	};


	//bind search calls
	$('#searchfield').keyup(function(e) {
		if (e.keyCode == 13) {
			doSearch();	
		}
	}).focus(function(){
		$('#searchfield').val('');
	});

	$('#search-icon').click(function(){
		if ($('#searchfield').val() == '') {
			$('#searchfield').focus();
		} else {
			doSearch();
		}
	});

	function doSearch() {
		if ($('#searchfield').val().length >= 2 && !$('.search-'+$('#searchfield').val()).length) {
			$.ajax({
				type: "POST",
				url: "php/search.php",
				data: { query: $('#searchfield').val() },
				cache: false,
				success: function(ret) {
					if (ret == -1) {
						$('#searchfield').val('');
						$('#searchfield,#search-icon').effect("highlight", {color:"#f291af"}, 500);
					} else {
						ret = ret.replace(/w154/g,basePosterURL+"w154");
						ret = ret.replace(/w300/g,basePosterURL+"w300");
						$('#content').prepend(ret);
						$('#content').children().first().addClass('search-'+$('#searchfield').val());
						$('#searchfield').val('').blur();
					}
				}
			});
		} else {
			$('#searchfield').val('');
			$('#searchfield,#search-icon').effect("highlight", {color:"#f291af"}, 500);
		}
		//$('#content').prepend('<div class="category loading" style="height:0px;"></div>');
		//$('.loading').animate({height:$('.poster').height()});
	}
});