var datatable;

function initDoc() {
    initDataTables();
    initButtons();
    initToolTips();
}

function newNewsletter() {
    self.document.location.href = 'edit_newsletter.php';
}


function deleteNewsletter(jqueryObj, id_newsletter, message) {
    // the trick below should emulate php's urldecode behaviour
    if (confirm(decodeURIComponent((message + '').replace(/\+/g, '%20')))) {
        $j.ajax({
            type: 'POST',
            url: 'ajax/delete_newsletter.php',
            data: { id: id_newsletter },
            dataType: 'json'
        })
            .done(function (JSONObj) {
                if (JSONObj) {
                    if (JSONObj.status == 'OK') {
                        // deletes the corresponding row from the DOM with a fadeout effect
                        jqueryObj.parents("tr").fadeOut("slow", function () {
                            var pos = datatable.fnGetPosition(this);
                            datatable.fnDeleteRow(pos);
                        });
                    }
                }
            });
    }
}

function duplicateNewsletter(id_newsletter) {
    $j.ajax({
        type: 'POST',
        url: 'ajax/duplicate_newsletter.php',
        data: { id: id_newsletter },
        dataType: 'json'
    })
        .done(function (JSONObj) {
            var error = false;
            if (JSONObj) {
                if (JSONObj.status == 'OK') self.document.location.reload();
                else error = true;
            }
            else error = true;

            if (error) alert('Errore nella duplicazione della newsletter');
        })
        .fail(function () { alert('Errore nella duplicazione della newsletter'); });
}

/**
 * inits jquery buttons
 */
function initButtons() {
    /**
     * new button
     */
    $j('.newButton').button({
        icons: {
            primary: 'ui-icon-document'
        }
    });

    /**
     * actions button
     */

    $j('.editButton').button({
        icons: {
            primary: 'ui-icon-pencil'
        },
        text: false
    });

    $j('.sendButton').button({
        icons: {
            primary: 'ui-icon-arrowthickstop-1-e'
        },
        text: false
    });

    $j('.detailsButton').button({
        icons: {
            primary: 'ui-icon-info'
        },
        text: false
    });

    $j('.copyButton').button({
        icons: {
            primary: 'ui-icon-copy'
        },
        text: false
    });

    $j('.deleteButton').button({
        icons: {
            primary: 'ui-icon-trash'
        },
        text: false
    });
}

function initDataTables() {
    datatable = $j('#newsletterHistory').dataTable({
        "bFilter": true,
        "bInfo": false,
        "bSort": true,
        "bAutoWidth": true,
        "bPaginate": false,
        'aoColumns': [
            // first empty column generated by ADA HTML engine, let's hide it
            {
                "bSearchable": false,
                "bVisible": false
            },
            null,
            { "sType": "date-eu" },
            null,
            null,
            { "bSearchable": false, "bSortable": false, "sWidth": "15%" }
        ],
        "oLanguage": {
            "sUrl": HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
        }
    }).show();
}

function initToolTips() {
    // inizializzo i tooltip sul title di ogni elemento!
    $j('.tooltip').tooltip(
        {
            show: {
                effect: "slideDown",
                delay: 300,
                duration: 100
            },
            hide: {
                effect: "slideUp",
                delay: 100,
                duration: 100
            },
            position: {
                my: "center bottom-5",
                at: "center top"
            }
        });
}