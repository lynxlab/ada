load_js(`${HTTP_ROOT_DIR}/external/fckeditor/fckeditor.js`);
//document.write("<script type='text/javascript' src='../js/include/fckeditor_integration.js'></script>");

function includeFCKeditor(textarea_name) {
   var oFCKeditor = new FCKeditor( textarea_name );
   oFCKeditor.BasePath = '../external/fckeditor/';
   oFCKeditor.Width = '100%';
   oFCKeditor.Height = '350';
   oFCKeditor.ToolbarSet = 'Default';
   oFCKeditor.ReplaceTextarea();
  }


/**
 * function createEditor
 *
 * creates and returns an instance of FCKeditor.
 * .@param FCKeditorID - the id of the textarea to be replaced by FCKeditor
 * .@return oFCKeditor - FCKeditor instance
 */
function createEditor(FCKeditorID, Plain_textID) {

	$j(`#${FCKeditorID}`).val(ADAToFCKeditor($j(`#${Plain_textID}`).val()));

	var oFCKeditor = new FCKeditor(FCKeditorID);
        oFCKeditor.BasePath = '../external/fckeditor/';
        oFCKeditor.Width = '100%';
	oFCKeditor.Height = '350';
	oFCKeditor.ToolbarSet = 'Basic';

	oFCKeditor.ReplaceTextarea();

	return oFCKeditor;
}
