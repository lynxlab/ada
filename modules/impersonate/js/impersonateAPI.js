/**
 * @package     impersonate module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

impDebug = false;

function newLinkedUser(domObj) {
    ajaxDoLinkedUser(domObj, 'new');
}

function deleteLinkedUser(domObj) {
    ajaxDoLinkedUser(domObj, 'delete');
}

function ajaxDoLinkedUser(domObj, action) {
    const baseUrl = domObj.data('baseUrl');
    const linkedType = domObj.data('linkedType');
    const sourceId = domObj.data('sourceId') || 0;
    const title = '';
    var msg = $j('#unknownErrorMSG').html();
    var isOK = false;
    var callback = function() {};

    if (action == 'new' || action == 'delete') {
        return $j.ajax({
            type: 'POST',
            url: `${baseUrl}/ajax/${action}LinkedUser.php`,
            data: { linkedType: linkedType, sourceId: sourceId },
            dataType: 'json',
        })
        .done(function (resp) {
            if ('status' in resp) {
                isOK = resp.status == 'OK';
            }
            if ('msg' in resp) {
                msg = resp.msg;
            }
            if ('reload' in resp && resp.reload == true) {
                callback = function() {
                    self.document.location.reload();
                }
            }
        })
        .fail(function (resp) {
            msg = 'Server Error';
        })
        .always(function (resp) {
            $j.when(showHideDiv('', msg, isOK)).then(callback);

        });
    }
}
