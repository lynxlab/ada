/**
 * @package 	badges module
 * @author		giorgio <g.consorti@lynxlab.com>
 * @copyright	Copyright (c) 2019, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version		0.1
 */

load_js([
    `${HTTP_ROOT_DIR}/js/include/basic.js`,
    `${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

/**
 * inits jquery tooltips
 */
function initToolTips() {
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

/**
 * inits jquery buttons
 */
function initButtons() {
    /**
     * new button
     */
    $j('.newButton').button({
        icons: {
            primary: 'ui-icon-document'
        }
    });

    /**
     * actions button
     */

    $j('.editButton').button({
        icons: {
            primary: 'ui-icon-pencil'
        },
        text: false
    });

    $j('.deleteButton').button({
        icons: {
            primary: 'ui-icon-trash'
        },
        text: false
    });
}
