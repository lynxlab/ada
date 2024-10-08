/**
 * @package 	studentsgroups module
 * @author		giorgio <g.consorti@lynxlab.com>
 * @copyright	Copyright (c) 2022, Lynx s.r.l.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version		0.1
 */

function initDoc() {
    let selectcount = 0;
    const debugForm = false;
    const formID = 'cloneinstance';
    const footerID = 'selectableFooter';
    const selectID = 'selectedCourses';
    const selectionTXT = {
        '0': $j('#noitemselectedTPL').text(),
        '1': $j('#oneitemselectedTPL').text(),
        'more': $j('#moreitemselectedTPL').text(),
    };
    const submitBtnID = `submit_${formID}`;

    const downloadCSV = (csv, filename) => {
        var csvFile;
        var downloadLink;

        //define the file type to text/csv
        csvFile = new Blob([csv], {type: 'text/csv'});
        downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";

        document.body.appendChild(downloadLink);
        downloadLink.click();
    }

    const updateSelectableFooter = () => {
        if (selectcount == 0) {
            $j(`#${footerID}`).html(selectionTXT['0']);
            $j(`#${submitBtnID}`).addClass('disabled').prop('disabled', true);
        } else if (selectcount == 1) {
            $j(`#${footerID}`).html(selectionTXT['1']);
            $j(`#${submitBtnID}`).removeClass('disabled').prop('disabled', false);
        } else {
            $j(`#${footerID}`).html(selectionTXT['more'].replace(/%d/, selectcount));
            $j(`#${submitBtnID}`).removeClass('disabled').prop('disabled', false);
        }
    }

    const updateCloneRecap = (recapData) => {
        if (debugForm) {
            console.log('doing updateCloneRecap with data ', recapData);
        }
        $j('#recapContainer .list').html('');
        htmlArr = [];
        csv = [];
        recapData.forEach((el) => {
            const courseName = $j(`#${selectID} option[value="${el.clonedInCourse}"]`).text();
            const text = $j('#recapRowTPL').html()
                .replace(":courseName", courseName)
                .replace(":courseId", el.clonedInCourse)
                .replace(":clonedId", el.clonedInstanceId);
            htmlArr.push(`<li>${text}</li>`);
            csv.push(`${courseName},${el.clonedInCourse},${el.clonedInstanceId}`);
        });
        $j('#recapContainer .list').html(htmlArr.join("\n"));
        if (csv.length == 0) {
            $j('#recapContainer #recapDownload').hide();
        }
        $j('#contentcontent .fform.form.ui').slideUp(function () {
            $j('#recapContainer').slideDown();
        });

        $j('#recapContainer #recapDownload').click(() => {
            if (csv.length > 0) {
                const now = new Date();
                const offsetMs = now.getTimezoneOffset() * 60 * 1000;
                const dateLocal = new Date(now.getTime() - offsetMs);
                const datestr = dateLocal.toISOString().slice(0, 19);
                const filename = `${$j('#recapCSVheaderTPL').data('filename')}-${datestr}.csv`;
                csv.unshift($j('#recapCSVheaderTPL').text().trim());
                downloadCSV(csv.join("\n"), filename);
            }
        });
    }

    $j(`#${submitBtnID}`)
        .attr('value', $j('#submitbuttonTPL').text())
        .attr('type', 'button').addClass('green')
        .click((event) => {
            const formData = $j(`#${formID}`).serialize();
            const url = 'ajax/cloneinstance.php';
            $j.ajax({
                method: 'POST',
                url: url,
                data: `${formData}&debugForm=${debugForm ? 1 : 0}`,
                beforeSend: () => {
                    $j(`#${selectID}`).attr('disabled', 'disabled');
                    $j(`#${selectID}`).selectMultiple('refresh');
                    $j(`#${submitBtnID}`).addClass('disabled').prop('disabled', true);
                },
            })
            .done(function (response) {
                if (debugForm) console.log('done callback got ', response);
                showHidePromise = showHideDiv(response.title, response.message, true);
            })
            .fail(function (response) {
                if (debugForm) console.log('fail callback ', response);
                if ('responseJSON' in response) {

                    if (debugForm) {
                        console.groupCollapsed(url + ' fail');
                        if ('errorMessage' in response.responseJSON) {
                            console.error('message: %s', response.responseJSON.errorMessage);
                        }
                        if ('errorTrace' in response.responseJSON) {
                            console.error('stack trace %s', response.responseJSON.errorTrace);
                        }
                        console.groupEnd();
                    }

                    showHidePromise = showHideDiv(response.responseJSON.title, response.responseJSON.message, false);

                } else {
                    var errorText = response.statusText;
                    if ('responseText' in response && response.responseText.length > 0) errorText += '<br/>' + response.responseText;
                    showHidePromise = showHideDiv('Error ' + response.status, errorText, false);
                }
            })
            .always(function (response) {
                if (debugForm) console.log('always callback', response);
                $j.when(showHidePromise).then(function () {
                    $j(`#${selectID}`).removeAttr('disabled');
                    $j(`#${selectID}`).selectMultiple('refresh');
                    $j(`#${selectID}`).selectMultiple('deselect_all');
                    if ('status' in response && 'cloneRecap' in response && response.status == 'OK') {
                        updateCloneRecap(response.cloneRecap);
                    }
                });
            });
        });

    $j(`#${selectID}`).selectMultiple({
        selectableHeader: $j('#selectableHeaderTPL').html(),
        selectableFooter: $j('#selectableFooterTPL').html(),
        cssClass: selectID,
        afterInit: function (ms) {
            $j('#selectableHeaderTPL, #selectableFooterTPL, #noitemselectedTPL, #oneitemselectedTPL, #moreitemselectedTPL').remove();
            updateSelectableFooter();
            // init search input
            // following code from: https://krazedkrish.com/select-multiple/
            var that = this,
                $selectableSearch = $j(`#ms-${selectID} input[type="text"]`).first(),
                selectableSearchString = `#ms-${selectID} .ms-elem-selectable`;
            that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
            .on('keydown', function (e) {
                if (e.which === 40 || e.which === 13) {
                    that.$selectableUl.focus();
                    return false;
                }
            });
            // select all button
            $j(`#ms-${selectID}`).on('click', '#selectAllBtn', (event) => {
                // first deselct all...
                $j(`#${selectID}`).selectMultiple('deselect_all');
                // ... then select all visible options
                const vislist = $j(`#ms-${selectID} .ms-elem-selectable`).find(':visible');
                // get all visibile elements text
                const vistxt = vislist.map((i, el) => $j(el).text()).toArray();
                // get all visibile elements val
                const optlist = [], visval = [];
                $j(`#${selectID} option`).each((i,el) => {
                    optlist[$j(el).text().trim()] = $j(el).val();
                });
                vistxt.forEach((el) => {
                    if ('undefined' !== typeof optlist[el]) {
                        visval.push(optlist[el]);
                    } else {
                        if (debugForm) {
                            console.log("optlist[el] is undefined");
                            console.log(optlist, el);
                        }
                    }
                });

                if (visval.length > 0) {
                    $j(`#${selectID}`).selectMultiple('select', visval);
                }
                if (debugForm) {
                    console.log('selecting courses id: ', visval);
                }
            });
            // deselect all button
            $j(`#ms-${selectID}`).on('click', '#deselectAllBtn', (event) => {
                $j(`#${selectID}`).selectMultiple('deselect_all');
            });
        },
        afterSelect: function () {
            this.qs1.cache();
            selectcount = $j(`#${selectID}`).find(':selected').length;
            updateSelectableFooter();
        },
        afterDeselect: function () {
            this.qs1.cache();
            selectcount = $j(`#${selectID}`).find(':selected').length;
            updateSelectableFooter();
        }
    });
}
