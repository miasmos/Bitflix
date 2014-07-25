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
	var animQue=0;

	$(window).resize(function() {
		posterNum=$(window).width()/$('.poster').width();	//get number of poster that fit across the screen
	});
	
	setInterval(function() {
		if ($(animQue).children('.trailer').length == 0) {$(animQue).stop(true).animate({width:"154"},200); $(animQue).find('.poster img').stop(true).animate({opacity:0.75},100); animQue=0;}	//reset the movie that just had a trailer
		
		//check for row wrap
		$('.movie-wrapper').each(function(index) {
			if ($(this).children().length >= posterNum) {
				if ($(this).is(':animated')) {
					lastPos = $(this).find('.movie:nth-child('+Math.round(posterNum+1)+')').position();
					firstPos = $(this).find('.movie:nth-child(1)').position();
					
					console.log('firstPos:'+firstPos.left+',lastPos:'+lastPos.left);
					if (lastPos.left-$('.poster').width() < ($('.poster').width()*posterNum)) {
						$(this).find('.movie:nth-last-child(1)').prependTo(this);
						//$(this).find('.movie:nth-last-child(1)').remove();
						//$(this).animate({left:'+=154'},0);
					}
					if (firstPos.left > 0) {
						$(this).find('.movie:nth-child(1)').appendTo(this);
						//$(this).find('.movie:nth-child(1)').remove();
						//$(this).animate({left:'-=154'},0);
						
					}
				}
			}
		});
	},100);
	
	//adjust opacity of category titles,poster images on hover
	$('.movie,.category-title').on("mouseenter", function(event) {
		$(this).closest('.category-title').stop(true).fadeOut(400);
		$(this).find('.poster img').stop(true).animate({opacity:1},100);
	});
	$('.movie,.category-title').on("mouseleave", function(event) {
		$(this).closest('.category-title').stop(true).fadeTo(400,0.85);
		if ($(this).children('.trailer').length == 0) {$(this).find('.poster img').stop(true).animate({opacity:0.75},100);}
	});
	
	//animate category title opacity on hover
	$('.movie,.category-title').on("mouseenter", function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.15},400);});
	$('.movie,.category-title').on("mouseleave", function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.6},400);});
	
	//animate info reveals on cover hover
	$('.movie').on("mouseenter", function(event) {
		var offset=$(this).offset();
		if (parseFloat(offset.left + $(this).width() + 390) > $(window).width()) { //info will clip outside of window boundaries, open left
			$(this).find('.info').animate({left:"0px"},0);
			$(this).stop(true).animate({width:"554px"},200);
			$(this).find('.poster').animate({left:"400px"},200);
			$(this).addClass('accordian-left');
			if ($(this).prev().width() == 154) {$(this).parent().stop(true).animate({left:"-=400px"},200);}
			else {$(this).parent().stop(true);}
		}
		else {
			if ($(this).parent().hasClass('closing')) {$(this).parent().stop(true); $(this).parent().removeClass('closing');}
			$(this).addClass('accordian-right');
			$(this).stop(true).animate({width:"554px"}, 200);
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
			$(this).stop(true).animate({width:"154"},200,function(){
				$(this).find('.info').animate({left:'156px'},0);
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
			$('.trailer').not('.hidden').closest('.movie').stop(true).animate({width:"154"},200,function(){
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