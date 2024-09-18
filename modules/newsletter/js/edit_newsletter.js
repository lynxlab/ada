load_js(`${HTTP_ROOT_DIR}/external/fckeditor/fckeditor.js`);

/**
 * change the following to false if you want standard submit
 * instead of ajax
 */
var isAjax = true;

function loadFCKeditor(textarea_name, toolbar) {
    if ($j('#' + textarea_name).length == 1) {
        toolbar = (typeof toolbar === 'undefined') ? 'Test' : toolbar;

        var oFCKeditor = new FCKeditor(textarea_name);
        oFCKeditor.BasePath = '../../external/fckeditor/';
        oFCKeditor.Width = '100%';
        oFCKeditor.Height = '300';
        oFCKeditor.ToolbarSet = toolbar;
        oFCKeditor.ReplaceTextarea();
    }
}

function initDoc() {
    if ($j('div.fform.form').length > 0) {
        $j('div.fform.form').css('width', '100%');
        $j('#date').mask("99/99/9999");
        loadFCKeditor('htmltext');
        $j('form[name=editnewsletter] :submit').button();
        /**
         * generate plain text button
         */
        $j('#generatePlainText').button({
            icons: {
                primary: 'ui-icon-refresh'
            }
        });
    } else {
        /**
         * save results ok button
         */
        $j('#newsletterSaveResultsbutton').button();
    }

    $j('form[name=editnewsletter]').on('submit', function (event) {
        // do standard submit if we don't want ajax call
        // else proceed with ajax
        if (!isAjax) {
            return true;
        }
        else {
            event.preventDefault();

            // must load correct fck content before serializing
            $j('#htmltext').val(FCKeditorAPI.GetInstance('htmltext').GetData());

            var postData = $j(this).serialize();
            postData += '&requestType=ajax';

            $j.ajax({
                type: 'POST',
                url: HTTP_ROOT_DIR + '/modules/newsletter/edit_newsletter.php',
                data: postData,
                dataType: 'html'
            })
                .done(function (html) {
                    $j('div.fform.form').css('display', 'none');

                    $j('#moduleContent').html(html).hide();
                    $j('#newsletterSaveResultsbutton').button();
                    $j('#moduleContent').effect('slide');
                });

            return false;
        }
    });
}

function toPlainText(htmlText) {
    $j('#l_htmltext').effect('transfer', { to: '#l_plaintext', className: 'ui-effects-transfer' }, 500, function () {
        $j('#plaintext').val(html_to_text(htmlText));
    });


}
