/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version		   0.1
 */

load_js([
    `${HTTP_ROOT_DIR}/js/include/basic.js`,
    `${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

/**
 * change the following to false if you want standard submit
 * instead of ajax
 */
var isAjax = true;

function initToolTips() {
    // inizializzo i tooltip sul title di ogni elemento!
    $j('.tooltip').tooltip(
        {
            show: {
                effect: "slideDown",
                delay: 300,
                duration: 100
            },
            hide: {
                effect: "slideUp",
                delay: 100,
                duration: 100
            },
            position: {
                my: "center bottom-5",
                at: "center top"
            }
        });
}
