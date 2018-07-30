/**
 * VIEW.JS
 *
 * @package		view
 * @author		giorgio <g.consorti@lynxlab.com>
 * @copyright	Copyright (c) 2013, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link		view
 * @version		0.1
 */

/**
 * Main view.php initializations, starts up tabbed content and nivo slider as
 * appropriate
 */
function initDoc() {
	// run script after document is ready
	$j(function() {
		// install flowplayer to an element with CSS class "ADAflowplayer"
		// generated by the media_viewing_classes if it's needed
		if ($j(".ADAflowplayer").length > 0)
			$j(".ADAflowplayer").flowplayer();

		// if there are tabs on the page, initialize'em
		if ($j("#tabs").length > 0) {
			$j("#tabs").tabs({
				collapsible : true,
				active : false
			});
			showTabs = true;
		}

		// if there's a nivo slider, start it
		if ($j("#slider").length > 0) {
			$j('#slider').nivoSlider({
				// effect: 'fade', // Specify sets like: 'fold,fade,sliceDown'
				// effect: 'fold', // Specify sets like: 'fold,fade,sliceDown'
				effect : 'slideInRight', // Specify sets like: 'fold,fade,sliceDown'
				slices : 15, // For slice animations
				boxCols : 8, // For box animations
				boxRows : 4, // For box animations
				animSpeed : 500, // Slide transition speed
				pauseTime : 3000, // How long each slide will show
				startSlide : 0, // Set starting Slide (0 index)
				directionNav : true, // Next & Prev navigation
				controlNav : true, // 1,2,3... navigation
				controlNavThumbs : true, // Use thumbnails for Control Nav
				pauseOnHover : true, // Stop animation while hovering
				manualAdvance : false, // Force manual transitions
				prevText : 'Prev', // Prev directionNav text
				nextText : 'Next', // Next directionNav text
				randomStart : false, // Start on a random slide
				beforeChange : function() {
				}, // Triggers before a slide transition
				afterChange : function() {
				}, // Triggers after a slide transition
				slideshowEnd : function() {
				}, // Triggers after all slides have been shown
				lastSlide : function() {
				}, // Triggers when last slide is shown
				afterLoad : function() {
				} // Triggers when slider has loaded
			}); // end nivoSlider init
		}

		if ($j('.ui.menu','.semantic.tabs').length>0) {
			$j('.ui.menu > a.item','.semantic.tabs').click( function(e) {
				e.preventDefault();
				$j(this).parents('.semantic.tabs').find('a.item').removeClass('active');
				$j(this).parents('.semantic.tabs').find('.ui.tab').removeClass('active');
				$j(this).addClass('active');
				$j($j(this).attr('href')).addClass('active');
			});
		}

		// add class to style keywords as labels
		$j('a','div.keywords.content').each(function() {
			// remove keywords equals to string 'null'
			if ($j(this).text().toLowerCase()=='null') $j(this).remove();
			$j(this).addClass('ui label');
		});

		// if no keywords, remove the divs
		if ($j('a','div.keywords.content').length<=0) $j('div.keywords').remove();

		// if accordion holding notes and keywords is empty, remove it
		if ($j('.ui.accordion','#content_view').last().children().length <= 0) {
			$j('.ui.accordion','#content_view').last().remove();
		}
		// show the accordion, if it's still there
		$j('.ui.accordion','#content_view').last().fadeIn();

		// close navigation right panel if its cookie is there
		var closeRightPanel = parseInt(readCookie("closeRightPanel"))==1;
		if ((!$j('#menuright').sidebar('is open') && !closeRightPanel) ||
			($j('#menuright').sidebar('is open') && closeRightPanel)) {
			$j('a[onclick*="navigationPanelToggle()"]').trigger('click');
			navigationPanelToggle();
		}

		var checkRepeater = [];
		for (i=0; i<window.frames.length; i++) {
			if ('Reveal' in window.frames[i].window) {
				setupRevealListeners(i, checkRepeater, function(i){
					// empty callback, argument i is the iframe index that has just ended
				});
			}
		}
	}); // end $j function
} // end initDoc

function setupRevealListeners(frameIdx, checkRepeater, endCallback) {
	var revealObj = window.frames[i].window.Reveal;
	revealObj.addEventListener('ready', function( event ) {
		// remove unwanted footer
		$j(window.frames[frameIdx].window.document).contents().find('.embed-footer').remove(); 
	});
	revealObj.addEventListener('slidechanged', function( event ) {
		// if in the last slide, check every second if the navigate-right button
		// is disabled. when it is, the slide has actually reached the end
		if (revealObj.isLastSlide()) {
			checkRepeater[frameIdx] = window.setInterval(function(){
				// check if next button is still there
				if ($j(window.frames[frameIdx].window.document).contents().find('button.navigate-right[disabled="disabled"]').length>0) {
					window.clearInterval(checkRepeater[frameIdx]);
					if ('function' === typeof endCallback) endCallback(frameIdx);
				}
			},1000);
		}
	});
}