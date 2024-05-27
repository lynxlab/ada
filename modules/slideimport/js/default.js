/**
 * SLIDEIMPORT MODULE.
 *
 * @package        slideimport module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2016, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           slideimport
 * @version		   0.1
 */

load_js([
    '../../js/include/basic.js',
    '../../js/include/menu_functions.js',
    'js/modules_define.js.php',
]);

/**
 * shows and after 500ms removes the div to give feedback to the user about
 * the status of the executed operation (if it's been saved, delete or who knows what..)
 *
 * @param  title title to be displayed
 * @param  message message to the user
 * @return jQuery promise
 */
function showHideDiv(title, message, isOK, duration) {
    if ('undefined' == typeof isOK) isOK = false;
    if ('undefined' == typeof duration) duration = 2000;
    var errorClass = (!isOK) ? ' error' : '';
    var content = "<div id='ADAJAX' class='saveResults popup" + errorClass + "'>";
    if ('undefined' != typeof title && title.length > 0) content += "<p class='title'>" + title + "</p>";
    if ('undefined' != typeof message && message.length > 0) content += "<p class='message'>" + message + "</p>";
    content += "</div>";
    var theDiv = $j(content);
    theDiv.css("position", "fixed");
    theDiv.css("z-index", 9000);
    theDiv.css("width", "350px");
    theDiv.css("top", ($j(window).height() / 2) - (theDiv.outerHeight() / 2));
    theDiv.css("left", ($j(window).width() / 2) - (theDiv.outerWidth() / 2));
    theDiv.hide().appendTo('body').fadeIn(500).delay(duration);
    var thePromise = theDiv.fadeOut(500);
    $j.when(thePromise).done(function () { theDiv.remove(); });
    return thePromise;
}