load_js(`${HTTP_ROOT_DIR}/js/include/dropzone/dropzonei18n.php`);

const prepareDropzoneElement = (el) => {
    // const parentForm = el.closest('form');
    // parentForm.classList.add('dropzone');

    const parentLI = el.closest('li');
    // parentLI.classList.add('dropzone');
    const elName = el.name ?? el.id;

    // id for the dropzone div
    const dzName = `${elName}DZ`;
    // make a div to attach the dropzone to
    const dzDIV = document.createElement('div');
    dzDIV.classList.add('dropzone');
    dzDIV.id = dzName;

    // move element and its label in the dropzone fallback
    const dzFallback = document.createDocumentFragment();

    const fallbackDIV = document.createElement('div');
    fallbackDIV.classList.add('fallback');
    fallbackDIV.style.display = 'none';

    // get element label, to move it in the fallback
    const elLabel = [...parentLI.children].filter((c) => {
        return ('label' === c.tagName.toLowerCase() && (elName === c.getAttribute('for') ?? ''));
    }).pop();

    const dzMsg = document.createElement('span');
    dzMsg.classList.add('dz-message');
    dzMsg.innerHTML = elLabel.textContent;
    dzDIV.appendChild(dzMsg);

    parentLI.prepend(dzDIV);

    // actually place the fallback div
    fallbackDIV.appendChild(el);
    if ('undefined' !== typeof elLabel) {
        fallbackDIV.appendChild(elLabel);
    }
    dzFallback.appendChild(fallbackDIV);
    parentLI.prepend(dzFallback);

    return dzName;
};

const makeDropzoneError = (fileName, message, style) =>
    `<div class='ui small red message' style='${style}'>
    <i class='close icon' onclick="this.closest('.message').remove();"></i>
    <strong>${fileName}</strong>:${message}
    </div>`;
