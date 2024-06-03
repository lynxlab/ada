const load_js = function (data, callback) {
    if (typeof data === 'string') {
        data = [data];
    }
    const head = document.getElementsByTagName("head")[0];
    var script = null;
    data.forEach(function (scriptSrc) {
        script = document.createElement("script");
        script.type = "text/javascript";
        script.src = scriptSrc;
        head.appendChild(script);
    });
    if (script != null && callback != undefined) {
        if (script.onreadystatechange) {
            script.onreadystatechange = function () {
                if (script.readyState == "complete" || script.readyState == "loaded") {
                    script.onreadystatechange = false;
                    callback();
                }
            }
        } else {
            script.onload = function () {
                callback();
            }
        }
    }
};
