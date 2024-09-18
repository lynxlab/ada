/**
 *
 *
 * @package   comunica
 * @author    Vito Modena <vito@lynxlab.com>
 * @copyright Copyright (c) 2009-2011, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version   0.1
 */

/*
 * id of the div containing the addressees list
 */
var ADDRESSEES_SELECT = 'js_destinatari_sel';
/*
 * id of the divs containing the users list based on user type
 */
var SWITCHERS_SELECT     = 'js_switcher_sel';
var PRACTITIONERS_SELECT = 'js_practitioner_sel';
var USERS_SELECT         = 'js_user_sel';
/*
 * id of the buttons used to switch between the users list
 */
var SWITCHERS_BUTTON     = 'js_switcher_bt';
var PRACTITIONERS_BUTTON = 'js_practitioner_bt';
var USERS_BUTTON         = 'js_user_bt';
/*
 * used to handle switching between lists
 */
var SELECTS = new Array(SWITCHERS_SELECT, PRACTITIONERS_SELECT, USERS_SELECT);
var BUTTONS = new Array(SWITCHERS_BUTTON, PRACTITIONERS_BUTTON, USERS_BUTTON);
/*
 * class name used to hide from CSS all the selects
 */
var CSS_HIDE_CLASSNAME = 'hidden_element';

/**
 * Adds a new addressee to the addresses' list.
 *
 * @param  select
 * @return void
 */
function add_addressee(select) {

  if (typeof select === 'string' && !select.startsWith('#')) {
		select = $j(`#${select}`);
	} else {
    select = $j(select);
  }

  /*
   * Addressee's username
   */
  var addressee = select.val(); // .replace(/[.@]/g ,'-');

  if(addressee == null) {
      return;
  }
  /**
   * Read the selected element HTML before deselectiong
   */
  var optionsList = select[0].getElementsByTagName('option');
  var options = Array.from(optionsList);
  var checkBoxHTML = options[select[0].selectedIndex].innerHTML;
  var divID = addressee.replace(/[.@]/g ,'-');
  /*
   * Deselect the selected element
   */
  select[0].selectedIndex = -1;
  /*
   * If the user has already added an addressee, do not
   * add him/her again.
   */
  if($j(`#${divID}`).length) {
    return;
  }
  /*
   * Add a div with id equal to the addressee username containing a checkbox
   * and the username
   */
  var div = document.createElement('div');
  div.id = divID;

  var checkbox = document.createElement('input');
  Object.assign(checkbox, {
    'name':'destinatari[]',
    'type':'checkbox',
    'value':addressee,
    'checked':'true',
    'onclick': () => remove_addressee(div.id)
  });

  div.append(checkbox);
  div.append(checkBoxHTML);

  $j(`#${ADDRESSEES_SELECT}`).append(div);
}

/**
 * Removes an addressee from the addressees' list.
 *
 * @param  addressee
 * @return void
 */
function remove_addressee(addressee) {
  $j(`#${addressee}`).remove();
}

/**
 * Handles switching between the various selects
 * of the address book.
 *
 * @param  string control
 * @return void
 */
function showMeHideOthers(control) {
  var index = SELECTS.indexOf(control);

  // prototype style
  // var to_hide = SELECTS.without(control);
	var to_hide = SELECTS.filter(el => control.split(',').indexOf(el.toString()) == -1 );

  var i = 0;
  var max = to_hide.length;
  var element;


  for (i = 0; i < max; i++) {
    element = to_hide[i];
    if($j(`#${element}`).length) {
      $j(`#${element}`).hide();
    }
  }

  for(i = 0; i < BUTTONS.length; i++) {
    element = BUTTONS[i];
    if(i == index && $j(`#${element}`).length) {
      $j(`#${element}`).addClass('selected');
    }
    else if($j(`#${element}`).length && $j(`#${element}`).hasClass('selected')) {
      $j(`#${element}`).removeClass('selected');
    }
  }

  if($j(`#${control}`).hasClass(CSS_HIDE_CLASSNAME)) {
    $j(`#${control}`).removeClass(CSS_HIDE_CLASSNAME);
  }

  if(!$j(`#${control}`).is(':visible')) {
    $j(`#${control}`).show();
  }
}

function load_addressbook() {
  var i = 0;
  var max = SELECTS.length;
  var select;

  for (i = 0; i < max; i++) {
    select = SELECTS[i];
    button = BUTTONS[i];
    if($j(`#${select}`).length) {
      if($j(`#${select}`).hasClass(CSS_HIDE_CLASSNAME)) {
        $j(`#${select}`).removeClass(CSS_HIDE_CLASSNAME);
      }
      $j(`#${select}`).show();
      $j(`#${button}`).addClass('selected');
      break;
    }
  }
}