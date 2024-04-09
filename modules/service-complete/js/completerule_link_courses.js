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

function initDoc() {
    initButtons();
    initDataTables();
    initToolTips();

    $j('#linkCourses').on('submit', function (event) {
        // do standard submit if we don't want ajax call
        // else proceed with ajax
        if (!isAjax) {
            return true;
        }
        else {
            event.preventDefault();
            var postData = $j(this).serialize();
            postData += '&requestType=ajax';

            $j.ajax({
                type: 'POST',
                url: HTTP_ROOT_DIR + '/modules/service-complete/completerule_link_courses.php',
                data: postData,
                dataType: 'json',
            })
                .done(function (JSONObj) {
                    if (JSONObj) {
                        showHideDiv(JSONObj.title, JSONObj.msg, JSONObj.OK);
                    }
                });
            return false;
        }
    }
    );
}

function initButtons() {
    /**
     * submit button
     */
    $j('#submitButton').button({
        icons: {
            primary: 'ui-icon-disk'
        }
    });
}

function initDataTables() {
    datatable = $j('#linkedRulesTable').dataTable({
        "bFilter": true,
        "bInfo": false,
        "bSort": true,
        "bAutoWidth": true,
        "bPaginate": false,
        'aoColumns': [
            // first empty column generated by ADA HTML engine, let's hide it
            {
                "bSearchable": false,
                "bVisible": false
            },
            null,
            { "bSearchable": false, "bSortable": false, "sWidth": "25%" }
        ],
        "oLanguage": {
            "sUrl": HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
        }
    }).show();
}