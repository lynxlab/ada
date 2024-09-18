
function followForkedPath (options, button) {

    if (button.hasClass('disabled')) return;

    if (options.baseUrl && options.fromId && options.toId) {
        if (options.baseUrl.indexOf(HTTP_ROOT_DIR)===0) {
            var showHidePromise = null;
            $j.ajax({
                type: "POST",
                url: options.baseUrl + '/ajax/followForkedPath.php',
                data: options,
                beforeSend: function() {
                    // disable all buttons in the container
                    button.parents('.forkedpaths.buttons').find('.ui.button').addClass('disabled');
                }
            })
            .done(function(result) {
                if ('status' in result && result.status == 'OK') {
                    // set to a rejected promise, so buttons won't be enabled
                    showHidePromise = $j.Deferred().reject().promise();
                    window.document.location.href = window.document.location.href.replace(/id_node=[0-9]+\_[0-9]+/g,"id_node="+result.redirectTo);
                }
            })
            .fail(function(result) {
                var title = 'Error ' + result.status;
                var message = result.statusText;
                if ('responseJSON' in result && 'status' in result.responseJSON && result.responseJSON.status == 'ERROR') {
                    title = result.responseJSON.title;
                    message = result.responseJSON.message;
                }
                showHidePromise = showHideDiv(title, message, result.responseJSON.status != 'ERROR');
            })
            .always(function(){
                $j.when(showHidePromise).done(function(){
                    // enable all buttons in the container
                    button.parents('.forkedpaths.buttons').find('.ui.button').removeClass('disabled');
                });
            });
        } else {
            console.error('invalid base url');
        }
    } else {
        console.error('invalid or incomplete options ', options);
    }
}
