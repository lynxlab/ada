var fileSharingTable;

function initDoc()
{
	initDataTables();
	// initButtons();
}

function initDataTables() {
	fileSharingTable = $j('#file_sharing_table').dataTable( {
		'bLengthChange': false,
        'bFilter': true,
        'bInfo': false,
        'bSort': true,
        'bAutoWidth': true,
		'bDeferRender': true,
		'bPaginate': false,
		"order": [[ 2, 'desc' ], [ 0, 'asc' ]],
		"aoColumnDefs": [
			{ "aTargets": [2], "sType": "date-eu" },
			{ "aTargets": [-1], "sortable": false },
		],
        "oLanguage": {
            "sUrl": HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
         }
	});
	fileSharingTable.show();
}

function initButtons() {
	$j('.deleteButton').button({
		icons : {
			primary : 'ui-icon-trash'
		},
		text : false
	});
}

function deleteFile(confirmQuestion, fileName, rowID) {
	if (confirm (decodeURIComponent(confirmQuestion)))
	{
		$j.ajax({
			type	:	'POST',
			url		:	HTTP_ROOT_DIR+ '/browsing/ajax/delete_uploadedFile.php',
			data	:	{ fileName : decodeURIComponent(fileName) },
			dataType:	'json'
		})
		.done  (function (JSONObj) {
			if (JSONObj)
				{
					if (JSONObj.status=='OK')
					{
						$j('#'+rowID).fadeOut(600, function () {
							// delete the row using dataTables methods
							var pos = fileSharingTable.fnGetPosition(this);
							fileSharingTable.fnDeleteRow(pos);
							showHideDiv(JSONObj.title ,JSONObj.msg, JSONObj.status=='OK'); } );
					} else {
						showHideDiv(JSONObj.title ,JSONObj.msg, JSONObj.status=='OK');
					}
				}
		});
	}
}
