/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function initDoc()
{

}
function initDataTable()
{
  $j.ajax({
        type	: 'POST',
        url	: HTTP_ROOT_DIR+ '/switcher/ajax/get_Translation.php',
        data	: $j('form[name="translatorForm"]').serialize(),
        dataType:'json',
        async	: false
        })
       .done   (function( JSONObj )
       {
           if(JSONObj.status=="OK")
           {
                var content=JSONObj.html;
                $j('.translationResults').html(content);
                createDataTable();
            }
            else
            {
                var content=JSONObj.html;

                $j('.translationResults').html(content);
                $j('#table_result').dataTable({
                    "paging":   false,
                    "ordering": false,
                    "info":     false,
                    "searching":false
                });
            }
            $j('.translationData').effect('drop', function() {
                    $j('.translationResults').effect('slide');
            });
            $j('#torna').toggle();
            $j('#home').css('display','none');
            $j('#question_mark').css('display','none');
      })
      .fail   (function() {
            console.log("ajax call has failed");
       } )
   return false;
}

var checkClick=false;
var SelectRow;
var oTable;
function createDataTable()
{
 $j(document).ready(function() {
   oTable= $j('#table_result').dataTable( {
	   "aaSorting": [[ 1, "asc" ]],
        "aoColumnDefs": [
            {
               "aTargets": [ 0 ],
               "sClass": "icon_Column",
            },
            {
               "aTargets": [ 1 ],
               "sClass": "text_Column",
            },
            {
               "aTargets": [ 2 ],
               "sClass": "action_Column",
            },
            {
               "aTargets": [ 3 ],
               "sClass": "details",
               "bVisible":false
            },
            {
               "aTargets": [ 4 ],
               "sClass": "details",
               "bVisible":false
            },
            {
               "aTargets": [ 5 ],
               "sClass": "details",
               "bVisible":false
            }


        ],
        "oLanguage":
            {
                "sUrl": HTTP_ROOT_DIR + "/js/include/jquery/dataTables/dataTablesLang.php"
            }


    } );

    $j('#table_result tbody  td img').on('click', function () {
        var nTr = $j(this).parents('tr')[0];
        if ( oTable.fnIsOpen(nTr) )
        {
            /* This row is already open - close it */
            this.src = HTTP_ROOT_DIR+"/layout/"+ADA_TEMPLATE_FAMILY+"/img/details_open.png";
            oTable.fnClose( nTr );
        }
        else
        {
            /* Open this row */
            this.src = HTTP_ROOT_DIR+"/layout/"+ADA_TEMPLATE_FAMILY+"/img/details_close.png";
            oTable.fnOpen( nTr, fnFormatDetails(nTr), 'details' );
        }
   });

  function fnFormatDetails ( nTr )
{
    var aData = oTable.fnGetData( nTr );
    var sOut = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">';
    sOut += '<tr><td>'+aData[3]+'</td></tr>';
    sOut += '</table>';
    return sOut;
}
 initButton();
 initToolTips();

function initButton()
{
   /*
    * actions button
    */

    var button=$j('.buttonTranslate').button({
            icons : {
                    primary : 'ui-icon-pencil'
            },
            text : false
    });

    button.click(function ()
    {
       SelectRow = $j(this).parents('tr')[0];
       var aData = oTable.fnGetData( SelectRow );
       if(!checkClick)
       {
            $j('.translationResults').animate({'marginLeft':'1%'});
            $j('.translationResults').animate({'width':'40%'},"slow");
            $j('.EditTranslation').effect('slide');
            $j('.EditTranslation').animate({'marginRight':'30%'},"slow");
            $j('#TranslationTextArea').val(aData[3]);
            $j('form[name="EditranslatorForm"]').append('<input type="hidden" id="id_record" name="id_record" value="'+aData[5]+'" />');
            $j('form[name="EditranslatorForm"]').append('<input type="hidden" id="cod_lang" name="cod_lang" value="'+aData[4]+'"/>');
            checkClick=true;
       }
       else
       {
            $j('#TranslationTextArea').val(aData[3]);
            $j('input[type="hidden"][id="id_record"]').remove();
            $j('input[type="hidden"][id="cod_lang"]').remove();
            $j('form[name="EditranslatorForm"]').append('<input type="hidden" id="id_record" name="id_record" value="'+aData[5]+'" />');
            $j('form[name="EditranslatorForm"]').append('<input type="hidden" id="cod_lang" name="cod_lang" value="'+aData[4]+'"/>');
       }


    });
 }
 function  initToolTips()
 {
    $j('.tooltip').tooltip({

        show : {
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

 });
}
function saveTranslation()
{
    $j.ajax({
       type	: 'POST',
       url	: HTTP_ROOT_DIR+ '/switcher/ajax/save_Translation.php',
       data	: $j('form[name="EditranslatorForm"]').serialize(),
       dataType :'json',
       async	: false
       })
       .done   (function( JSONObj )
       {
            if(JSONObj.status=='OK')
            {
                showHideDiv(JSONObj.title,JSONObj.msg,JSONObj.status=='OK');
                $j('input[type="hidden"][id="id_record"]').remove();
                $j('input[type="hidden"][id="cod_lang"]').remove();
                var message=JSONObj.text;
                var substr=message.substring(0,30);
                if(message.length>30)
                {
                    substr=substr+'...';
                }
                /*update datatable*/
                oTable.fnUpdate(substr,oTable.fnGetPosition(SelectRow),1,false);
                oTable.fnUpdate(JSONObj.text,oTable.fnGetPosition(SelectRow),3,false);
                oTable.fnStandingRedraw();
                $j('#TranslationTextArea').val('');
             }
             else
             {
                showHideDiv(JSONObj.title,JSONObj.msg,JSONObj.status=='OK');
             }
       })
       .fail   (function() {
		console.log("ajax call has failed");
	} )
      return false;
}
