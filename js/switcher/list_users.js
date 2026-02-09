/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var oTable = null;

function initDoc(userType = null, serverSide = false){
    createDataTable(userType, serverSide);
    initToolTips();
}

function toggleDetails(user_id,imgObj) {

//    }
//    $j('.imgDetls').on('click', function () {
    var promise = $j.Deferred();
    var nTr = $j(imgObj).parents('tr')[0];
    if ( oTable.row(nTr).child.isShown() )
    {
        /* This row is already open - close it */
        imgObj.src = HTTP_ROOT_DIR+"/layout/"+ADA_TEMPLATE_FAMILY+"/img/details_open.png";
        oTable.row(nTr).child.hide();
        promise.resolve({error:false, data: {}, action: 'close', row: nTr});
    }
    else
    {
        /* Open this row */
        imgObj.src = HTTP_ROOT_DIR+"/js/include/jquery/ui/images/ui-anim_basic_16x16.gif";
        var imageReference=imgObj;
        $j.when(fnFormatDetails(user_id))
        .done   (function( JSONObj )
       {
            oTable.row(nTr).child(JSONObj.html, 'details').show();
            if(JSONObj.status==='OK'){
                $j('.User_table').not('.dataTable').dataTable({
	                'aoColumnDefs': JSONObj.columnDefs,
	                "oLanguage":
	                {
	                      "sUrl": HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
	            	}
                });
                promise.resolve({error:false, data: JSONObj, action: 'open', row: nTr});
            }
       })
       .fail   (function() {
            console.log("ajax call has failed");
            promise.resolve({error:true, data: JSONObj, action: 'open', row: nTr});
	} )
        .always(function (){
            imageReference.src = HTTP_ROOT_DIR+"/layout/"+ADA_TEMPLATE_FAMILY+"/img/details_close.png";
        });

    }
    return promise.promise();
}


function createDataTable(userType = null, serverSide = false) {

    dtOptions = {
        searching: true,
        info: true,
        ordering: true,
        autoWidth: true,
        processing: serverSide,
        stateSave: true,
        stateSaveCallback: (settings, data)  => {
            localStorage.setItem(
                `DataTables_${settings.sInstance}.${userType ?? ''}`,
                JSON.stringify(data)
            );
        },
        stateLoadCallback: (settings) => {
            return JSON.parse(localStorage.getItem(`DataTables_${settings.sInstance}.${userType ?? ''}`));
        },
        order: [[ 1, "asc" ]],
        columnDefs: [
            { targets: [0, -2], searchable: false, orderable: false, },
            { targets: [0], className: 'expandCol', },
            { targets: [-1], className: 'confirmCol', },
            { targets: [-2], className: 'actionCol', },
        ],
        language: {
            url: HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
        },
        serverSide: serverSide,
    };

    if (dtOptions.serverSide) {
        dtOptions.ajax = {
            url: `${HTTP_ROOT_DIR}/switcher/ajax/listUsers.php`,
            type: 'POST',
            data: {
                list: userType,
            },
        };
    }

    oTable = $j(`#table_users`).DataTable(dtOptions);
}

  function fnFormatDetails ( idUser )
{
    return $j.ajax({
       type	: 'GET',
       url	: HTTP_ROOT_DIR+ '/switcher/ajax/get_userDetails.php',
       data	: {'id_user': idUser},
       dataType :'json'
       });

}



function  initToolTips()
 {
   $j('.tooltip').tooltip({

        show :     {
                effect : "slideDown",
                delay : 300,
                duration : 100
        },
        hide : {
                effect : "slideUp",
                delay : 100,
                duration : 100
        },
        position : {
                my : "center bottom-5",
                at : "center top"
        }


    });
}

function goToSubscription(path)
{
	$j.when(
	    $j('.table_result').effect('drop', function() {
	        $j('#course_instance_Table').effect('slide');
	    })
	).done(
		function() {
			self.document.location.href = path+'.php'+location.search;
		}
	);
}