$(document).ready(function() {
	/*
	
	TO DO
	
	fix trailer div freeze on video fail
	row wrapping
	trailer full screen
	finish info details, detailed torrent info
	long-ass title scrolling
	long-ass overview pagination
	fix category titles messing with movie focus
	
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
	$('.movie-wrapper').on("mouseleave", function(event) {
		if ($('#accordian-right').length) {
			$(this).animate({left:-$('.info').width()},{queue:false});
		}
		$('#accordian-left').attr('id','');
		$('#accordian-right').attr('id','');
	}).bind("mouseenter", function(event) {
		
	});

	//adjust opacity of category titles,poster images on hover
	$('.movie,.category-title').on("mouseenter", function(event) {
		$(this).closest('.category-title').stop(true).fadeOut(400);
		$(this).find('.poster img').stop(true).animate({opacity:1},100);
	});
	$('.movie,.category-title').on("mouseleave", function(event) {
		$(this).closest('.category-title').stop(true).fadeTo(400,0.85);
		if ($(this).children('.trailer').length == 0) {$(this).find('.poster img').stop(true).animate({opacity:0.70},100);}
	});
	
	//animate category title opacity on hover
	$('.movie,.category-title').on("mouseenter", function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.15},400);});
	$('.movie,.category-title').on("mouseleave", function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.7},400);});
	
	//animate info reveals on cover hover
	$('.movie').on("mouseenter", function(event) {
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
	}).bind("mouseleave",function() {
		if ($(this).children('.trailer').length == 0) {	//if a trailer isn't playing here
			if ($(this).hasClass('accordian-left')) {	//if accordian has opened to the left
				$(this).removeClass('accordian-left');
				$(this).find('.poster').animate({left:"0px"},200);
				$(this).parent().addClass('closing').stop(true).animate({left:"+=400px"},200,function(){$(this).removeClass('closing');});
			}
			else {	//if it has opened to the right
				$(this).removeClass('accordian-right');
			}
			$(this).stop(true).animate({width:posterWidth},200,function(){
				$(this).find('.info').animate({left:posterWidth},0);
			});
		}
		else {	//if a trailer is playing, que it
			animQue=$(this);
		}
	});
	
	//embed trailer on link click
	$('.trailer-link').on("click",function(event) {
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
	$('.move-left').bind("mouseenter", function(dat) {
		$(this).parent().find('.movie-wrapper').attr('id','scrolling');
		this.iid = setInterval(function() {
			if ($(this).parent().find('.movie:eq('+posterNum+')')) {}
			moveIt(0);
		}, 20);
	}).bind("mouseleave", function() {
		this.iid && clearInterval(this.iid);
		$('#scrolling').attr('id','');
	});
	
	$('.move-right').bind("mouseenter", function() {
		if ($(this).parent().find('.move-left').hasClass('hidden')) {$(this).parent().find('.move-left').removeClass('hidden');}	//make left arrow useable after moving right
		$(this).parent().find('.movie-wrapper').attr('id','scrolling');
		this.iid = setInterval(function(dat) {
			moveIt(1);
		}, 25);
	}).bind("mouseleave", function() {
		this.iid && clearInterval(this.iid);
		$('#scrolling').attr('id','');
	});
	
	function moveIt(dir) {
		if (dir==1) {$('#scrolling').animate({left:"-=10"},10);}
		else if (dir==0) {$('#scrolling').animate({left:"+=10"},10);}
	};
});