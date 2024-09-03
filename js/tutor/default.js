load_js([
    `${HTTP_ROOT_DIR}/js/include/basic.js`,
    `${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

function Pager(page) {
}

Pager.prototype.showPage = function(page) {
    if($(page)) {
        var pagedElement = 'pe_' + page;

        $(page).siblings().each(this.hidePageElement);

        $(page).addClassName('selectedPage');
        $(pagedElement).show();
    }
}

Pager.prototype.hidePageElement = function(pageElement) {
   var pagedElement = 'pe_' + pageElement.identify();
    if($(pagedElement) && $(pagedElement).visible()) {
        $(pagedElement).hide();
        $(pageElement).removeClassName('selectedPage');
    }

}

var PAGER = new Pager();