load_js([
    `${HTTP_ROOT_DIR}/js/include/basic.js`,
    `${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

function zeroFill(number, width) {
    width -= number.toString().length;
    if (width > 0) {
        return new Array(width + (/\./.test(number) ? 2 : 1)).join('0') + number;
    }
    return number + ""; // always return a string
}
