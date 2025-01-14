load_js(`${HTTP_ROOT_DIR}/external/fckeditor/fckeditor.js`,).then(() => {
    function loadFCKeditor(textarea_name, toolbar) {
        console.log('loadFCKeditor', textarea_name, toolbar);
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

    var max_width = parseInt($j('div.fform.form').css('width'));
    $j('select.form').css('max-width', max_width + 'px');
    loadFCKeditor('consegna');
    setTimeout(function () {
        if (window.isCloze ?? false) {
            loadFCKeditor('testo', 'Cloze');
        }
        else {
            loadFCKeditor('testo');
        }
    }, 500);
});
