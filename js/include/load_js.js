const load_js = function (data, callback) {
    const debug = false;
    if (typeof data === 'string') {
        data = [data];
    }
    // Check to see if the counter has been initialized
    if (typeof load_js.counter == 'undefined') {
        // It has not... perform the initialization
        load_js.counter = 0;
    }
    if (typeof load_js.loaded == 'undefined') {
        load_js.loaded = [];
    }

    const head = document.getElementsByTagName("head")[0];
    const index = Array.from(head.childNodes).findIndex(el =>
        el.nodeType != Node.TEXT_NODE && el.nodeName.toLowerCase() == 'script' && el.src.toLowerCase().includes('load_js')
    );

    const promises = data.map(scriptSrc =>
        new Promise((resolve, reject) => {
            var script = null;
            if (load_js.loaded.indexOf(scriptSrc) == -1) {
                load_js.loaded.push(scriptSrc);
                script = document.createElement("script");
                script.type = "text/javascript";
                script.src = scriptSrc;
                script.setAttribute('data-append-offset', ++load_js.counter);
                script.onerror = () => {
                    if (debug) {
                        console.log(`rejecting ${scriptSrc}`);
                    }
                    reject(script);
                };

                if (index == -1) {
                    head.appendChild(script);
                } else {
                    head.insertBefore(script, head.childNodes[index + load_js.counter]);
                }

                if (script.onreadystatechange) {
                    script.onreadystatechange = function () {
                        if (script.readyState == "complete" || script.readyState == "loaded") {
                            script.onreadystatechange = false;
                            if (debug) {
                                console.log(`resolving ${script.src}`);
                            }
                            resolve(script);
                        }
                    }
                } else {
                    script.onload = function () {
                        if (debug) {
                            console.log(`resolving ${script.src}`);
                        }
                        resolve(script);
                    }
                }
            } else {
                if (debug) {
                    console.log(`${scriptSrc} already loaded, skypping`);
                }
            }
        })
    );

    return Promise.allSettled(promises).then((values) => {
        if (callback != undefined) {
            if (debug) {
                console.log(`doing the callback`);
            }
            callback(values);
        }
        return values;
    });
};

/**
 * Run a callback function whenever the document.readyState is
 * 'interactive' or 'complete'.
 *
 * see https://javascript.info/onload-ondomcontentloaded
 *
 * @param {*} fn
 */
const onDOMLoaded = (fn) => {
    var once = false;
    document.addEventListener('readystatechange', () => {
        if (document.readyState == 'loading') {
            // still loading, wait for the event
            document.addEventListener('DOMContentLoaded', fn);
        } else if (!once) {
            // DOM is ready!
            once = true;
            fn();
        }
    });
};
