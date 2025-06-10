const mainIndexMove = async (element, nodeId, direction, what) => {
    const debug = true;
    if (!element.classList.contains('disabled')) {
        if (debug) {
            console.log('disabling buttons');
        }
        // add disabled class to all siblings of element
        Array.from(element.parentElement.children).forEach(sibling => {
            Array.from(
                document.getElementsByClassName(sibling.classList)
            ).forEach(
                el => { el.classList.add('disabled'); }
            );
        });
        // send an async request and reload page when done
        if (debug) {
            console.log(`moving ${nodeId} ${what} ${direction}...`);
        }
        try {
            const response = await fetch(`${HTTP_ROOT_DIR}/services/ajax/mainIndexMove.php`, {
                method: 'POST',
                body: new URLSearchParams({
                    // your expected POST request payload goes here
                    nodeId: nodeId ?? '',
                    direction: direction ?? '',
                    what: what ?? '',
                })
            });
            const jsonData = await response.json();
            if (debug) {
                console.log('response status', response.status);
            }
            if (response.status == 200) {
                if (debug) {
                    console.log(jsonData);
                }
            } else {

            }
        } catch (error) {
            if (debug) {
                console.log(error);
            }
        } finally {
            document.location.reload();
        }
    }
};
