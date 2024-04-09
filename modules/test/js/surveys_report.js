load_js([
    'js/commons.js',
]);

function initDoc(hasSurveys) {
    if (hasSurveys) {
        $j('#buttonsBar')
            .on('click', '#pdfexportBtn', function () {
                window.print();
            })
            .on('click', '#csvexportBtn', function () {
                window.location.href = window.location.href.replace(/#.*$/g, '') + '&output=csv'
            })
            .css('display', 'inline-block');
    }
}
