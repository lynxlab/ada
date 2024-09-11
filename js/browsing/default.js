load_js([
	`${HTTP_ROOT_DIR}/js/include/basic.js`,
	`${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

function toggleVisibilityByDiv(className, mode)
{
	$j('ul.'+className).each( function(i, e) {
		e = $j(e);
		if (e.length > 0) {
			toggleVisibilityByClassName(className, e.attr('id'), mode);
		}
	});
}

function toggleVisibilityByClassName(className, idName, mode)
{
	if (['show', 'hide', 'toggle'].indexOf(mode) == -1) {
		mode = 'toggle';
	}

	const children = $j('ul#'+idName+'.'+className);
	if (children.length > 0) {
		children.each((i, el) => $j(el).trigger(mode));
	}

	/*
	 * Get span element identifier for span element with title=container_div+item_class:
	 * since there is only one (if it exists) span element with this class name, it is safe
	 * to get its id in this way.
	 */

	const span_element_id = $j('span#s'+idName+'.'+className).first();

	if (span_element_id.length > 0)
	{
		if (mode == 'show' || (mode == 'toggle' && span_element_id.hasClass('hideNodeChildren')))
		{
			span_element_id.html('-');
			span_element_id.removeClass('hideNodeChildren');
			span_element_id.addClass('viewNodeChildren');
		}
		else if (mode == 'hide' || (mode == 'toggle' && span_element_id.hasClass('viewNodeChildren')))
		{
			span_element_id.html('+');
			span_element_id.removeClass('viewNodeChildren');
			span_element_id.addClass('hideNodeChildren');
		}
	}
}

function printit()
{
  if (typeof window.print == 'function') {
    window.print();
  }
}

function openInRightPanel(httpFilePath, fileExtension) {

    var rightPanel = '#rightpanel';
    if ($j(rightPanel).hasClass('sottomenu_off')){
    	$j(rightPanel).removeClass('sottomenu_off');
    	$j(rightPanel).hide();
    }

    if ($j(rightPanel).is(':visible')) {
    	$j(rightPanel).hide();
    } else {
    	$j('#flvplayer').html('');
        $j(rightPanel + ' .loader-wrapper .loader').toggleClass('active').show();
        $j(rightPanel).show();
    	$j.ajax({
    		type	:	'GET',
    		url		:	'ajax/videoplayer_panel_code.php',
    		data	:	{ media_url: httpFilePath, width: 500, height: 370, isAjax: true },
    		dataType:	'html'
    	})
    	.done(function (htmlcode){
    		if (htmlcode && htmlcode.length>0) {
    			$j('#flvplayer').html(htmlcode);
    		}
    	})
    	.always(function() { $j(rightPanel + ' .loader-wrapper .loader').toggleClass('active').hide(); }) ;
    }
}