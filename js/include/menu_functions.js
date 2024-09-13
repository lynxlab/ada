const DROPDOWN_MENU_OPEN_ANIMATION     = true;
const DROPDOWN_MENU_CLOSE_ANIMATION    = false;
const NAVIGATION_PANEL_OPEN_ANIMATION  = false;
const NAVIGATION_PANEL_CLOSE_ANIMATION = false;
const NAVIGATION_PANEL_IDENTIFIER      = 'menuright';
const NODE_TEXT_CONTAINER_IDENTIFIER   = 'content_view';
const MAIN_INDEX_CONTAINER_IDENTIFIER   = 'contentcontent';
const EFFECT_BLIND_DURATION_IN_SECONDS = 0.3;

/**
 * hides the sidebar (aka menuright) from an
 * href onclick event generated inside the sidebar
 */
const hideSideBarFromSideBar = function () {
	if (IE_version==false || IE_version>8) {
		$j('#menuright').sidebar('hide');
		$j('li.item.active').removeClass('active');
	} else {
		if ($j('#menuright').is(':visible')) $j('#menuright').hide();
	}

}

document.onreadystatechange = () => {
	// wait for jQuery to be loaded before doing stuff
	if (document.readyState === "complete") {
	initMenu();
	}
};

const initMenu = function() {
	/**
	 * display the unread messages badge if it's needed
	 */
	if($j('#unreadmsgbadge').length) {
		$j.ajax({
			type: 'GET',
			url: HTTP_ROOT_DIR + '/comunica/ajax/getUnreadMessagesCount.php',
			cache: false,
			dataType: 'json'
		})
		.done(function (JSONObj) {
			if (JSONObj) {
				const value = parseInt (JSONObj.value);
				if (!isNaN(value) && value>0) {
					$j('#unreadmsgbadge').html(value);
					$j('#msglabel').show();
				}
			}
		});
	}

	/**
	 * add trim method to String object
	 */

	if(typeof String.prototype.trim !== 'function') {
		  String.prototype.trim = function() {
		    return this.replace(/^\s+|\s+$/g, '');
		  }
		}
	/**
	 * sets the dropdown menu to appear on hover
	 * and the menuitem onclick handler for proper css class switching
	 *
	 * WARNING: I'm using $j inside a function called by prototype
	 * document observer. One day all shall be handled by jQuery...
	 * This is not going to harm anybody, but you've been warned
	 */

	/**
	 * If it's not internet explorer or is IE>8
	 * use semantic-ui components
	 */
	if (IE_version==false || IE_version>8) {
		/**
		 * Copy the .computer.menu HTML code, make the
		 * needed changes and use it as a .mobile.menu
		 */
		if ($j('.ui.mobile.ada.menu').length>0) {
			var menuHTML = $j('.ui.computer.ada.menu').clone();
			$j(menuHTML).find('ul.left.menu').toggleClass('left menu sm sm-ada');
			var rightMenu = $j(menuHTML).find('.right.menu');
			if (rightMenu.length >0) {
				rightMenu.remove();
				$j(menuHTML).find('ul').first().append(rightMenu.html());
			}

			$j(menuHTML).find('.ui.dropdown.item').toggleClass('item').toggleClass('fluid').
			wrap('<li class="item"><ul></ul></li>');
			// use the generated html as the mobile menu
			$j('.ui.mobile.ada.menu').html(menuHTML.html());
			// hook the menubutton onclick to mobile menu display
		    $j('.ui.mobile.ada.menubutton .ui.button').on('click', function() {
		    	$j('#mobilesidebar').sidebar('toggle');
		    });
		}


		// mobile dropdown on click
    	/**
    	 * @author giorgio 16/set/2014
    	 * commented line to have a non-js working
    	 * dropdown as a workaround to some bug causing
    	 * firefox crash on xp and vista.
    	 * Should you wish to revert to a js dropdown,
    	 * remove the simple class from menu_functions.inc.php
    	 * and uncomment the following line
    	 */
		// $j('.mobile.menu .dropdown').dropdown({ on: 'click' });

		// enable menu items (non dropdown) active class
		var menuItem = $j('.computer.ada > .menu > li.item').not('.closepanel');
		menuItem.on('click', function() {
		    if(!$j(this).hasClass('dropdown')) {
		          $j(this).toggleClass('active').closest('.ui.menu')
		          .find('.item').not($j(this)).removeClass('active');
		    }
		});

		// enable userpopup, if found
		if ($j('li.item.userpopup').length>0 && $j('#status_bar').length>0) {
			$j('#status_bar').hide();
			$j('li.item.userpopup').popup({
				variation: 'large',
			    position: 'bottom center',
			    html: $j('#status_bar').html(),
			    on: 'click',
			    onHide: function() {
		    		$j('.ada.menu .item.active').removeClass('active');
			    }
			  });
		}

		// enable com_tools popup
		if ($j('div#com_tools').length>0) {
			$j('.ui.menu .item','#com_tools').each (function() {
				var popupContent = $j(this).children('span').first();
				var totalRows = 0;
				if ($j(this).hasClass('whosonline')) {
					totalRows = popupContent.find("ul>li").length;
				} else if ($j(this).hasClass('messages') || $j(this).hasClass('appointments')){
					// maxium number of rows to display
					var maxRows = 5;
					var rows = popupContent.find("table>tbody>tr");
					totalRows = rows.length;
					while(totalRows >= maxRows) {
						$j(rows[totalRows--]).remove();
					}
				}
				// if the resulting popup have no rows, disable
				// the respective link/button in the com_tools bar
				if (totalRows>0) {
					$j(this).popup({
						position: 'top left',
						html: popupContent.html(),
						on: 'click',
						target: $j(this),
						offset: -50,
						inline: true,
						onShow: function() { $j(this).toggleClass('active'); },
						onHide: function() { $j(this).toggleClass('active'); }
					});
				} else {
					$j(this).addClass('disabled');
				}
			});
		}

	    // perform search on search icon click
    	if ($j('.search.link.icon').length>0) {
    		$j('.search.link.icon').on('click',function(){
    			var text = $j(this).siblings('input[type="text"]').val().trim();
    			if (text.length>0) {
    				document.location.href='search.php?s_UnicNode_text='+text+'&l_search=l_search&submit=cerca';
    			}
    		});
    	}

	    // init and set resize for mobile sidebar if needed
	    if ($j('#mobilesidebar').length>0) {
	        $j('#mobilesidebar').sidebar();
	        $j(window).resize(function() {
	        	var w = $j(window).width();
	        	if (w>768 && $j('#mobilesidebar').sidebar('is open')) {
	        		$j('#mobilesidebar').sidebar('toggle');
	        	}
	        });
	    }

	    $j('.ui.accordion').accordion();

	} else {
		/**
		 * it's internet explorer v.8 or less, use smartmenus
		 */
		$j('.ui.mobile.ada.menubutton ,#mobilesidebar').remove();

		$j('ul.left.menu, ul.right.menu').smartmenus({
			subMenusSubOffsetX: 1,
			subMenusSubOffsetY: -8,
			subIndicators: false
		});

		if ($j('#menuright').length>0) {
			$j('li.item.rightpaneltoggle').removeClass('disabled');
		}

		// disable click event on disabled items
		if ($j('.ui.ada.menu  ul.menu > li.item.disabled > a').length>0) {
			$j('.ui.ada.menu  ul.menu > li.item.disabled > a').prop('onclick',null).off('click');
		}

		// enable show/hide userpopup, if found
		if ($j('li.item.userpopup').length>0 && $j('#status_bar').length>0) {
			$j('#status_bar').hide();
			$j('li.item.userpopup').on('click',function() {
				if($j('#status_bar').is(':visible')) {
					$j('#status_bar').fadeOut();
				} else {
					$j('#status_bar').fadeIn();
				}
			});
		}
	}

    // if there's the searchbox, make it work
    if($j('.item.searchItem input[type="text"]').length>0) {
		// perform search on searchmenutext enter key press
		$j('.item.searchItem input[type="text"]').on('keyup', function(event){
			if(event.which == 13) {
				var text = $j(this).val().trim();
    			if (text.length>0) {
    				document.location.href='search.php?s_UnicNode_text='+text+'&l_search=l_search&submit=cerca';
    			}
			}
		});
    }

    // if help div element is empty remove it
    if ($j('#help').length>0 && $j('#help').html().trim().length<=0) {
    	$j('#help').remove();
    }

}

const navigationPanelToggle = function(options) {
	if ('undefined' === typeof options) options = {};
	options = $j.extend({ action: 'toggle', removeCookie: true }, options);
	if (IE_version==false || IE_version>8) {
		// right panel pushes content if window width > 1280
		var overlay = !$j('#menuright').sidebar('is open') && $j(window).width()<=1280;
		$j('#menuright').sidebar({
				overlay:overlay,
				onShow: function() {
					document.cookie = "closeRightPanel = ; expires = -1; samesite=lax; path=/";
				},
				onHide: function() {
					if (options.removeCookie) {
						days = 365; //number of days to keep the cookie
						myDate = new Date();
						myDate.setTime(myDate.getTime()+(days*24*60*60*1000));
						document.cookie = "closeRightPanel = 1; " +
						"expires = " + myDate.toGMTString() + "; " +
						"samesite=lax; path=/"; //creates the cookie: name|value|expiry
					}
				}
			})
			.sidebar(options.action);
	} else {
		$j('#menuright').toggle('fade');
		if (!index_loaded) showIndex();
	}
}

function showElement(element, direction) {

	if (typeof element === 'string' && !element.startsWith('#')) {
		element = `#${element}`;
	}
	$j(element).show('blind', { direction: direction }, EFFECT_BLIND_DURATION_IN_SECONDS);
}

function hideElement(element, direction) {
	if (typeof element === 'string' && !element.startsWith('#')) {
		element = `#${element}`;
	}
	$j(element).hide('blind', { direction: direction }, EFFECT_BLIND_DURATION_IN_SECONDS);
}

function navigationPanelHide(direction) {
	if(NAVIGATION_PANEL_CLOSE_ANIMATION) {
		hideElement(NAVIGATION_PANEL_IDENTIFIER, direction);
	}
	else {
		if($j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).length
		   && $j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).hasClass('content_small')){

			$j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).removeClass('content_small');
		}
                else {
                    if ($j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).length
                        && $j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).hasClass('content_small')){
                            $j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).removeClass('content_small');
                    }
                }
		$j(`#${NAVIGATION_PANEL_IDENTIFIER}`).hide();
	}
}

function navigationPanelShow(direction) {
	if(NAVIGATION_PANEL_OPEN_ANIMATION) {
		showElement(NAVIGATION_PANEL_IDENTIFIER, direction);
	}
	else {
		if($j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).length
		   && !$j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).hasClass('content_small')){

			$j(`#${NODE_TEXT_CONTAINER_IDENTIFIER}`).addClass('content_small');
		}
                else {
                    if($j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).length
                        && !$j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).hasClass('content_small')){
                            $j(`#${MAIN_INDEX_CONTAINER_IDENTIFIER}`).addClass('content_small');
                    }
                }
		$j(`#${NAVIGATION_PANEL_IDENTIFIER}`).show();
	}
}

var index_loaded=false;
function showIndex() {
    if(!index_loaded) {
	    $j.ajax({
		type	: 'GET',
		url     : HTTP_ROOT_DIR + '/browsing/ajax/index_menu.php',
        dataType:'html',
		async	: true,
        success: function(data) {
        		$j('#show_index').slideUp(function(){
        			$j('#show_index').html(data).slideDown();
        		});
            	index_loaded=true;
        	}
	    });
    }
}
