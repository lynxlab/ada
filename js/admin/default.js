// vito, 28 nov 2008
load_js([
	`${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
	`${HTTP_ROOT_DIR}/js/include/basic.js`,
]);

// vito, 21 luglio 2008
function toggleVisibilityByClassName(container_div, item_class)
{
	$j(`#${container_div}`).find(`[id=${container_div}${item_class}]`).toggle();
	/*
	 * Get span element identifier for span element with title=container_div+item_class:
	 * since there is only one (if it exists) span element with this class name, it is safe
	 * to get its id in this way.
	 */
	const span_element_id = $j(`#${container_div}`).find(`[title=${container_div}${item_class}]`).first();

	if (span_element_id.length > 0) {
		if (span_element_id.hasClass('hideNodeChildren')) {
			span_element_id.html('-');
			span_element_id.removeClass('hideNodeChildren');
			span_element_id.toggleClass('viewNodeChildren');
		}
		else if (span_element_id.hasClass('viewNodeChildren')) {
			span_element_id.html('+');
			span_element_id.removeClass('viewNodeChildren');
			span_element_id.toggleClass('hideNodeChildren');
		}
	}

}

function printit() {
	if (typeof window.print == 'function') {
		window.print();
	}
}
