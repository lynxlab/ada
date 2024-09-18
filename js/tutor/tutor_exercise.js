load_js([
    `${HTTP_ROOT_DIR}/js/include/basic.js`,
    `${HTTP_ROOT_DIR}/js/include/menu_functions.js`,
]);

/**
 * Used by the tutor/tutor_exercise.php module, updates the contents of the current
 * exercise accordingly with answers given by the selected student.
 *
 * The css class names used are defined in exercise_player.css.
 */
function updateExerciseWithStudentAnswer() {
    /*
     * The element containing a json string with the answer given by a student
     * to this exercise.
     *
     * It's defined in tutor_exercise.tpl as a div with style display:none.
     */
    const jsonContainer = $j('#jsonResponse');

    if(jsonContainer.length) {

        var jsonResponse = jsonContainer.html();
        var responseObject = JSON.parse(jsonResponse);
        var answersCount = responseObject.answers.length;
        for(var i = 0; i< answersCount; i++) {

            var element = $j(`#${responseObject.answers[i].id}`);
            var userAnswer = responseObject.answers[i].userAnswer;
            var correctAnswer = responseObject.answers[i].correctAnswer;
            var correctness = responseObject.answers[i].correctness;

            var elementAsString = element.toString();
            var ancestors = element.parents();
            var firstAncestor = element.parent();
            var cssClass = '';

            if (elementAsString == '[object HTMLInputElement]') {

                if(correctness) {
                    cssClass = 'correct-image';
                } else {
                    cssClass = 'wrong-image';
                }

                var inputType = element.attr('type');
                switch (inputType) {
                   case 'checkbox':
                       firstAncestor.addClass(cssClass);
                       if (userAnswer == 'true') {
                           element.attr('checked', true);
                       }
                       break;
                   case 'radio':
                       if (userAnswer == 'true') {
                           element.attr('checked', true);
                           firstAncestor.addClass(cssClass);
                       }
                       break;
                   case 'text':
                       element.addClass(cssClass);
                       element.attr('value', userAnswer);
                       break;
                }
                /*
                 * By default all the input elements are disabled, since these
                 * are needed only to show how the student answered to the
                 * questions.
                 */
                element.attr('disabled', true);

            } else if((elementAsString == '[object HTMLSpanElement]') || (elementAsString == '[object HTMLElement]')) {

                if(correctness) {
                    cssClass = 'correct';
                } else {
                    cssClass = 'wrong';
                }

                if (firstAncestor.toString() == '[object HTMLTableCellElement]') {
                    var tableCell = firstAncestor;
                    var tableRow = ancestors[1];

                    element.remove();

                    tableCell.append(userAnswer);
                    tableCell.addClass(cssClass);
                } else {
                    element.append(userAnswer);
                    element.addClass(cssClass);
                }
            }else if (elementAsString == '[object HTMLElement]') {

            }
        }

        updateExerciseWithCorrection(responseObject.answers);
    }
}

function dataTablesExec() {
//	$j('#container').css('width', '99%');
	var datatable = $j('#exercise_table').dataTable( {
//		'sScrollX': '100%',
                'aoColumns': [
                                null,
                                null,
                                { "sType": "date-euro" },
                                null,
                                null
                            ],
                'bLengthChange': false,
		//'bScrollCollapse': true,
//		'iDisplayLength': 50,
                "bFilter": false,
                "bInfo": false,
                "bSort": true,
                "bAutoWidth": true,
//		'bProcessing': true,
		'bDeferRender': true,
                "aaSorting": [[ 2, "desc" ],[ 3, "desc" ]],
                'bPaginate': false
//		'sPaginationType': 'full_numbers'
	}).show();
}

