/*
 * basic.js
 *
 *
 */
function newWindow(nomefile, x, y) {
    openNewWindow(nomefile, x, y, 'Immagine', false, false);
}

function openMessenger(nomefile, x, y) {
    openNewWindow(nomefile, x, y, 'Messaggeria', true, true);
}

function openNewWindow(nomefile, x, y, title, resizable, forceFocus) {

    prop = ('width=' + x + ',height=' + y + ', toolbar=no, location=no, status=no, menubar=no, scrollbars=yes, resizable=' + ((resizable) ? 'yes' : 'no'));
    win2 = window.open(nomefile, title, prop);
    if (forceFocus) win2.focus();
}

function window_scroll(howmuch) {
    window.scroll(0, howmuch);
}

function parentLoc(nomefile) {
    win1 = window.opener;
    win1.location = nomefile;
}

function confirmCriticalOperationBeforeRedirect(message, critical_operation_url) {
    if (confirm(message)) {
        window.location = critical_operation_url;
    }
}


function closeMeAndReloadParent() {
    window.close();
    window.opener.location.reload();
}

function closeMe() {
    window.close();
}

function reloadParent() {
    window.opener.location.reload();
}

function close_page(message) {
    alert(message);
    self.close();
}

function initDateField() {
    if ($j("#birthdate").length > 0)
        $j("#birthdate").mask("99/99/9999");
    if ($j("#data_pubblicazione").length > 0)
        $j("#data_pubblicazione").mask("99/99/9999");
}

function validateContent(elements, regexps, formName) {
    var error_found = false;
    elements.forEach((element, i) => {
        var label = 'l_' + element;
        var regexp = regexps[i];
        var value = null;
        var id = null;
        const elSelector = `[id="${element}"]`;
        const lblSelector = `[id="${label}"]`;
        if ($j(elSelector).length && $j(elSelector).val) {
            if ($j(elSelector).is(':radio') || $j(elSelector).is(':checkbox')) {
                value = $j(`#${element}:checked`).val() || '';
            } else if ($j(elSelector).is('select')) {
                value = $j(`${elSelector}>option:selected`).val() || '';
            } else {
                value = $j(elSelector).val();
            }
            id = $j(elSelector).attr('id');
        }

        if (value != null && typeof value == 'string') {
            if (!value.match(regexp)) {
                if ($j(lblSelector).length) {
                    $j(lblSelector).addClass('error');
                }
                error_found = true;
            }
            else {

                if ($j(lblSelector).length) {
                    $j(lblSelector).removeClass('error');
                }
                /**
                 * giorgio, if element it's a date field it may validate the regexp,
                 * but could be an invalid date. Must check it.
                 * NOTE: assumption is made that a date field
                 * contains 'date' (NOT case sensitive) in its id.
                 */

                if (id.match(/date/i)) {
                    ok = null;
                    dateArray = value.split("/");
                    d = new Date(dateArray[2], dateArray[1] - 1, dateArray[0]);
                    now = new Date();
                    if (parseInt(dateArray[2]) < 1900) ok = false;
                    else if (id.match(/birthdate/i) && (d.getTime() > now.getTime())) ok = false;
                    else if (d.getFullYear() == dateArray[2] && d.getMonth() + 1 == dateArray[1] && d.getDate() == dateArray[0])
                        ok = true;
                    else ok = false;

                    if (!ok) {
                        if ($j(lblSelector).length) {
                            $j(lblSelector).addClass('error');
                        }
                        error_found = true;
                    }
                }
            }
        }
    });

    if (error_found) {
        if ($j(`#error_form_${formName}`)) {
            $j(`#error_form_${formName}`).addClass('show_error');
            $j(`#error_form_${formName}`).removeClass('hide_error');
        }
    }
    else {
        if ($j(`#error_form_${formName}`)) {
            $j(`#error_form_${formName}`).addClass('hide_error');
            $j(`#error_form_${formName}`).removeClass('show_error');
        }
    }

    return !error_found;
}

/**
 * @author giorgio 08/mag/2015
 *
 * cookie-policy banner management, in plain javascript
 */
function checkCookie() {

    elem = document.getElementById("cookies");

    if (elem != null) {
        if (readCookie("ada_comply_cookie") == null) {
            document.getElementById("cookies").style.display = 'block';
            document.getElementById("cookie-accept").onclick = function (e) {
                days = 365; //number of days to keep the cookie
                myDate = new Date();
                myDate.setTime(myDate.getTime() + (days * 24 * 60 * 60 * 1000));
                document.cookie = "ada_comply_cookie = comply_yes; expires = " + myDate.toGMTString() + "; samesite=lax; path=/"; //creates the cookie: name|value|expiry|path
                if (elem != null) elem.parentNode.removeChild(elem);
            }
        }
        else elem.parentNode.removeChild(elem);
    }
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

/**
 * file download: setting document.location.href and using a
 * beforeDownlad and afterDownload callback functions
 *
 * @param options object with properties: url, beforeDownload, afterDownload
 */
function doDownload(options) {
    if ('undefined' != typeof options.url) {
        var beforeRetval = null;
        if ('function' == typeof options.beforeDownload) beforeRetval = options.beforeDownload();
        if (beforeRetval == false) return;

        // send a token to the server, and check at time interval that
        // we have it back in a cookie, telling that file download has started
        var token = new Date().getTime();
        var fileDownloadCheckAttempts = 1800; // try for max 30 minutes (60sec*30)
        var fileDownloadCookie = 'fileDownloadToken';

        var form = $j('<form></form>').attr('action', options.url).attr('method', 'post');
        form.append($j("<input></input>").attr('type', 'hidden').attr('name', 'c').attr('value', fileDownloadCookie));
        form.append($j("<input></input>").attr('type', 'hidden').attr('name', 't').attr('value', token));

        var fileDownloadCheckTimer = window.setInterval(function () {
            fileDownloadCheckAttempts--;
            // NOTE: readCookie is in js/include/basic.js file
            var cookieValue = readCookie('fileDownloadToken');
            if (cookieValue == token || fileDownloadCheckAttempts <= 0) {
                window.clearInterval(fileDownloadCheckTimer);
                // removes the cookie by setting its expire time to yesterday
                myDate = new Date();
                myDate.setTime(myDate.getTime() + (-1 * 24 * 60 * 60 * 1000));
                document.cookie = fileDownloadCookie + " = ; samesite=lax; expires = " + myDate.toGMTString();
                if ('function' == typeof options.afterDownload) options.afterDownload(fileDownloadCheckAttempts <= 0);
            }
        }, 1000); // check cookie arrival every 1 second

        // append options.data hidden fields to the form
        Object.keys(options.data).forEach(function (key) {
            var value = options.data[key];
            if (value instanceof Array) {
                var index = 0;
                value.forEach(function (v) {
                    if (typeof v === 'object') {
                        var tmp = queryStringToObj($j.param(v));
                        Object.keys(tmp).forEach(function (tmpkey) {
                            var useKey = tmpkey.replace(']', '');
                            useKey = useKey.replace('[', '][');
                            if (typeof tmp[tmpkey] !== 'object') {
                                form.append($j("<input></input>").attr('type', 'hidden').attr('name', key + '[' + (index) + ']' + '[' + useKey + ']').attr('value', tmp[tmpkey]).attr('class', 'array-object'));
                            }
                        });
                    } else {
                        form.append($j("<input></input>").attr('type', 'hidden').attr('name', key + '[' + (index) + ']').attr('value', v).attr('class', 'array-scalar'));
                    }
                    index++;
                });
            } else if (typeof value === 'object') {
                Object.keys(value).forEach(function (subkey) {
                    form.append($j("<input></input>").attr('type', 'hidden').attr('name', key + '[' + subkey + ']').attr('value', value[subkey]).attr('class', 'object'));
                });
            } else {
                form.append($j("<input></input>").attr('type', 'hidden').attr('name', key).attr('value', value).attr('class', 'scalar'));
            }
        });

        //send request and remove form
        form.appendTo('body').submit().remove();
    }
}

function doPing() {

    const getQueryVariable = function (variable) {
        var query = window.location.search.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (decodeURIComponent(pair[0]) == variable) {
                return decodeURIComponent(pair[1]);
            }
        }
        return null;
    }

    // 1. Create a new XMLHttpRequest object
    const xhr = new XMLHttpRequest();
    // 2. Configure it: GET-request
    xhr.open('GET', '/comunica/ping.php');
    // 3. This will be called after the response is received
    xhr.onload = function () {
        if (xhr.status == 401) { // analyze HTTP status of the response
            console.log('Session expired, redirecting...');
            var r = getQueryVariable('r');
            if (r === null) r = document.location.href;
            document.location.href = '/?expired=1' + '&r=' + encodeURIComponent(r);
        }
    };
    xhr.onerror = function () {
        console.error('ping request failed!');
    };
    // 4. Send the request over the network
    xhr.send();
}

/**
 * shows and after 2 seconds removes the div to give feedback to the user about
 * the status of the executed operation (if it's been saved, delete or who knows what..)
 *
 * @param  title title to be displayed
 * @param  message message to the user
 * @param  isOK true to show a success feedback, defaults to false
 * @param  duration how many milliseconds to div must be visible, defaults to 2000
 * @return jQuery promise
 */
function showHideDiv(title, message, isOK, duration) {
    if ('undefined' == typeof isOK) isOK = false;
    if ('undefined' == typeof duration) duration = 2000;
    var errorClass = (!isOK) ? ' error' : 'success';
    var hasIcon = false, contentIcon = '', contentTitle = '', contentMessage = '';
    if ('undefined' != typeof title && title.length > 0) {
        title = $j("<div>" + title + "</div>");
        hasIcon = $j('i', title).length > 0;
        if (hasIcon) {
            contentIcon = "<i class='" + $j('i', title).attr('class') + "'></i>";
            $j('i', title).remove();
        }
        contentTitle = title.text();
    }
    if ('undefined' != typeof message && message.length > 0) {
        contentMessage = message;
    }

    var content = "<div id='ADAJAX' class='ui " +
        (hasIcon ? 'icon ' : '') + errorClass + " compact floating message' style='transition:none;'>";
    content += contentIcon + "<div class='content'><div class='header'>";
    content += contentTitle + "</div>";
    content += "<p>" + contentMessage + "</p></div></div>";

    var theDiv = $j(content);
    theDiv.hide().appendTo('body')
        .css("position", "fixed")
        .css("z-index", 9000)
        .css("top", ($j(window).height() / 2) - (theDiv.outerHeight() / 2))
        .css("left", ($j(window).width() / 2) - (theDiv.outerWidth() / 2))
        .fadeIn(500).delay(duration);
    var thePromise = theDiv.fadeOut(250);
    $j.when(thePromise).done(function () { theDiv.remove(); });
    return thePromise;
}

// Pass in the objects to merge as arguments.
// For a deep extend, set the first argument to `true`.
const extend = function () {

    // Variables
    var extended = {};
    var deep = false;
    var i = 0;
    var length = arguments.length;

    // Check if a deep merge
    if (Object.prototype.toString.call(arguments[0]) === '[object Boolean]') {
        deep = arguments[0];
        i++;
    }

    // Merge the object into the extended object
    const merge = function (obj) {
        for (var prop in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, prop)) {
                // If deep merge and property is an object, merge properties
                if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
                    extended[prop] = extend(true, extended[prop], obj[prop]);
                } else {
                    extended[prop] = obj[prop];
                }
            }
        }
    };

    // Loop through each object and conduct a merge
    for (; i < length; i++) {
        var obj = arguments[i];
        merge(obj);
    }

    return extended;
};

if (window.attachEvent) { window.attachEvent('onload', checkCookie); }
else if (window.addEventListener) { window.addEventListener('load', checkCookie, false); }
else { document.addEventListener('load', checkCookie, false); }
