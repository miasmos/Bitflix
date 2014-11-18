$(document).ready(function() {
	/*
	
	TO DO
	
	frontend

	major
	advanced search?
	help menus of some kind
	genre, year, actor menus
	program 'featured' row..  info revealed by default, wider, full art
	implement header, same size as featured row, explains what's going on etc.
	do something with empty space when a category can't fill the screen

	medium
	trailer full screen/volume?
	minify everything and worry about page size
	analytics?
	fix encoding issues
	cross-browser
	rate-limiting
	error status in bottom-right / loading indicator when fetching a search
	implement 'like' movie searches when clicking on a movie title
	implement year movie search when clicking on a movie year
	start removing categories that have been viewed after X number of categories have been revealed (prevent lag)

	minor
	fix trailer div freeze on video fail
	fix movie pane close on trailer ending even if still being hovered
	fix trailer being placed on wrong side in some cases
	fix overview pagination breaking in middle of words instead of on a space
	fix wierd row scrolling bugs
	fix row wrapping not triggering in some cases
	add hover delay for opening a movie pane?
	fix fetching of actors even if they have already been populated
	fix title opacity changing to 1 when trailer is playing and cursor is not in row
	
	backend
	force english results?
	finish search feature
	add more db query combinations
	implement randomization
	fix dailyUpdate and GetTorrentData scripts
	server redundancy
	set up script intervals
	move to linux virtual machine/remote server

	future
	implement user account system
	email notifications for movie additions
	RPC capability
	categories based on downloaded or liked movies
	taste preferences
	tv shows
	
	*/
	var posterNum=$(window).width()/$('.poster').width();
	var posterWidth=$('.poster').width();
	var animQue=0;
	var appendPosterNum=3;
	var titleAnim;

	//add tabulated movie descriptions for those that overflow
	$('.info-overview-tabs').tabs().each(function(){
		var newTop = $(this).height();
		$(this).find('.ui-tabs-nav').css('top',newTop);
	});

	//once page is done loading, show the content
	Pace.once('done',function() {
		$('#content').fadeIn();
	});

	$(window).resize(function() {
		posterNum=$(window).width()/$('.poster').width();	//get number of poster that fit across the screen
		posterWidth=$('.poster').width();
	});

	setInterval(function() {
		if ($(animQue).children('.trailer').length === 0) {$(animQue).stop(true).animate({width:posterWidth},200); $(animQue).find('.poster img').stop(true).animate({opacity:0.75},100); animQue=0;}	//reset the movie that just had a trailer
		
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
		if ($(this).children('.trailer').length === 0) {$(this).find('.poster img').stop(true).animate({opacity:0.70},100);}
	});
	
	//animate category title opacity on hover
	$('#content').on("mouseenter", '.movie,.category-title', function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.15},400);});
	$('#content').on("mouseleave", '.movie,.category-title', function(event) {$(this).parent().parent().find('.category-title').stop(true).animate({opacity:0.7},400);});
	
	//animate info reveals on cover hover
	$('#content').on("mouseenter", '.movie', function(event) {
		//get actors in movie
		if (!$(this).find('.info-actors').hasClass('f')) {
			$.ajax({
				type: "POST",
				context: this,
				url: "php/actors.php",
				data: { query: $(this).attr('id') },
				cache: false,
				success: function(ret) {
					if (ret != -1) {
						$(this).find('.info-actors').addClass('f');
						$(this).find('.info-rating').animate({bottom:'38px'},200,function(){
							$(this).closest('.info').find('.info-actors').append(ret).fadeIn();
						});
					}
				}
			});
		}

		var offset=$(this).offset();
		doTitleAnimation($(this));

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
		clearTitleAnimation($(this));
		if ($(this).children('.trailer').length === 0) {	//if a trailer isn't playing here
			if ($(this).hasClass('accordian-left')) {	//if accordian has opened to the left
				$(this).find('.poster').animate({left:"0px"},200);
				$(this).parent().addClass('closing').stop(true).animate({left:"+=400px"},200,function(){
					$(this).removeClass('closing');
				});
				
			}
			$(this).stop(true).animate({width:posterWidth},200,function(){
				$(this).find('.info').animate({left:posterWidth},0);
				$(this).find('.quality').animate({left:posterWidth+$('.info').width()+30},0);
				$(this).removeClass('accordian-left').removeClass('accordian-right');
			});
		}
		else {	//if a trailer is playing, que it
			animQue=$(this);
		}
	});

	//animate quality reveal on poster click
	$('#content').on('click', '.poster,.icon-down-bold', function(){
		var movie = $(this).closest('.movie');
		var targetLeft = $(movie).hasClass('accordian-right') ? posterWidth : 0;
		$(movie).find('.quality').animate({left:targetLeft},200);
	});

	//close quality on quality click
	$('#content').on("click",'.quality', function(){
		$(this).animate({left:posterWidth+$('.info').width()+30},200);
	});

	//close quality on quality mouseleave
	$('#content').on("mouseleave", '.quality', function() {
		$(this).animate({left:posterWidth+$('.info').width()+30},200);
	});

	//search for an actor on click
	$('#content').on("click",'.actor', function() {
		doSearch($(this).text());
	});
	
	//embed trailer on link click
	$('#content').on("click",'.icon-youtube-play',function(event) {
		event.preventDefault();
		if ($('.trailer').length === 0) {
			$(this).closest('.movie').append("<div class='trailer hidden'></div");
			$('.trailer').css('left', $(this).closest('.movie').hasClass('accordian-left') ? 0 : posterWidth);
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
			$('.trailer').not('.hidden').parent().find('.poster').stop(true).animate({left:0},200);
			$('.trailer').not('.hidden').closest('.movie').stop(true).animate({width:posterWidth},200,function(){
				$('.trailer').not('.hidden').remove();
				$('.trailer').css('left', $(this).closest('.movie').hasClass('accordian-left') ? 0 : posterWidth);
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
		else if (dir===0) {$('#scrolling').animate({left:"+=10"},10);}
	}


	//bind search calls
	$('#searchfield').keyup(function(e) {
		if (e.keyCode == 13) {
			doSearch();	
		}
	}).focus(function(){
		$('#searchfield').val('');
	});

	$('#search-icon').click(function(){
		if ($('#searchfield').val() === '') {
			$('#searchfield').focus();
		} else {
			doSearch();
		}
	});

	function doSearch(query) {
		query = typeof(query) !== 'undefined' ? query : $('#searchfield').val();
		temp = query.replace(/ /g,'-'); temp = temp.toLowerCase();

		if (query.length >= 2 && !$('.search-'+temp).length) {
			Pace.ignore(function() {
				$.ajax({
					type: "POST",
					url: "php/search.php",
					data: { query: query },
					cache: false,
					success: function(ret) {
						if (ret == -1) {
							$('#searchfield').val('');
							$('#searchfield,#search-icon').effect("highlight", {color:"#f291af"}, 500);
						} else {
							ret = ret.replace(/w154/g,basePosterURL+"w154");
							ret = ret.replace(/w185/g,basePosterURL+"w185");
							ret = ret.replace(/w300/g,basePosterURL+"w300");
							ret = ret.replace("class='category'","class='category' style='height:0px'");
							$('.category').each(function(index){
								if ($(this).offset().top > $(window).scrollTop()) {
									$(this).before(ret);

									//add tabulated movie descriptions for those that overflow
									$('.category').eq(index).find('.info-overview-tabs').tabs().each(function(){
										var newTop = $(this).height();
										$(this).find('.ui-tabs-nav').css('top',newTop);
									});

									$('.category').eq(index).addClass('search-'+temp).animate({height:$('.poster').height()},500);
									$('.category').eq(index).find('.move-left').addClass('.move-left-nudged');
									$('#searchfield').val('').blur();
									return false;
								}
							});
						}
					}
				});
			});
		} else {
			$('#searchfield').val('');
			$('#searchfield').effect("highlight", {color:"#f291af"}, 500);
		}
	}

	//do scrolling on long movie titles
	function doTitleAnimation(target) {
		clearInterval(titleAnim);
		titleAnim = setInterval(doAnimation,4000);
		doAnimation();

		function doAnimation() {
			var targetInner = $(target).find('.info-title-title>span');
			var targetOuter = $(target).find('.info-title-title');
			var innerWidth = $(targetInner).width();
			var outerWidth = $(targetOuter).width();

			if (innerWidth > outerWidth) {
				var scrollRight = outerWidth-innerWidth-5;
				var scrollLeft = 0;
				var direction = parseInt($(targetInner).css('left')) == scrollLeft ? scrollRight : scrollLeft;
				if ($(targetOuter).hasClass("read")) {
					$(targetInner).stop(true).delay(1000).animate({left:direction},3000);
				} else {
					$(targetInner).stop(true).delay(2000).animate({left:direction},2000);
				}
				$(targetOuter).addClass("read");
			} else {
				clearInterval(titleAnim);
				$(targetOuter).css('overflow','visible');
			}
		}
	}

	function clearTitleAnimation(target) {
		$(target).find('.info-title-title>span').animate({left:"0"},0).removeClass("read");
	}
});