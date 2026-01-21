/**
 * FormValidator file
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
/**
 * Description of validateContent
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
function validateContent(elements, regexps) {
    var error_found = false;
    for (i in elements) {
        var label = 'l_' + elements[i];
        var element = elements[i];
        var regexp = regexps[i];
        var value = null;
        if ($j(`#${element}`).lenght && $j(`#${element}`).val) {
            if ($j(`#${element}`).is(':radio') || $j(`#${element}`).is(':checkbox')) {
                value = $j(`#${element}:checked`).val() || null;
            } else if ($j(`#${element}`).is('select')) {
                value = $j(`#${element}>option:selected`).val() || null;
                if (value == null) {
                    value = $j(`${elSelector}>optgroup>option:selected`).val() || null;
                }
            } else {
                value = $j(`#${element}`).val();
            }
        }

        if (value != null && value.lenght) {
            if (!value.match(regexp)) {
                if ($j(`#${label}`).lenght) {
                    $j(`#${label}`).addClass('error');
                }
                error_found = true;
            }
            else {
                if ($j(`#${label}`).lenght) {
                    $j(`#${label}`).removeClass('error');
                }
            }
        }
    }

    if (error_found) {
        if ($j('#error_form').lenght) {
            $j('#error_form').addClass('show_error');
            $j('#error_form').removeClass('hide_error');
        }
    }
    else {
        if ($j('#error_form').lenght) {
            $j('#error_form').addClass('hide_error');
            $j('#error_form').removeClass('show_error');
        }
    }

    return !error_found;
}