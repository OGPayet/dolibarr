<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/js/datepicker.js.php
 * \brief      File that include javascript functions for datepickers
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

// Define javascript type
header('Content-type: text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

$langs->load('main');
$langs->load('quicklist@quicklist');

$search_plus_img_title = dol_escape_htmltag($langs->trans("QuickListHelp"));
$search_plus_img_src = img_picto('', 'search_plus.png@quicklist', '', false, 1);

$button_delete_filter_text = dol_escape_htmltag($langs->trans('RemoveFilter'));
$button_add_filter_text = dol_escape_htmltag($langs->trans('QuickListAddFilter'));

$search_placeholder = dol_escape_htmltag($langs->trans('QuickListSearch'));

$scope_private_title = dol_escape_htmltag($langs->trans('QuickListScopePrivate'));
$scope_usergroup_title = dol_escape_htmltag($langs->trans('QuickListScopeUserGroup'));
$scope_public_title = dol_escape_htmltag($langs->trans('QuickListScopePublic'));

$edit_img = img_picto($langs->trans('QuickListEditFilter'), 'edit.png');
$delete_img = img_picto($langs->trans('QuickListDeleteFilter'), 'delete.png');

?>

function quicklist_replace_button_removefilter(base_url, filters) {
  var button_search = $('td.liste_titre [name="button_removefilter"]');

  if (button_search.length > 0) {
    var td = button_search.parent();
    var base_url_params = base_url+(base_url.indexOf('?')==-1?'?':'&');

    button_search.remove();
    var menu = '<div class="quicklist-dropdown">' +
      '<input type="image" class="liste_titre quicklist-dropbtn" name="button_quicklist" src="<?php echo $search_plus_img_src ?>" value="" title="<?php echo $search_plus_img_title ?>">' +
      '<div id="quicklistDropdown" class="quicklist-dropdown-content">' +
      '<input type="submit" class="quicklist-action" name="button_removefilter" value="<?php echo $button_delete_filter_text ?>">' +
      '<input type="submit" class="quicklist-action" name="button_quicklist_addfilter" value="<?php echo $button_add_filter_text ?>">' +
      '<input type="text" placeholder="<?php echo $search_placeholder ?>" id="quicklistInput" onkeyup="quicklistFilterFunction()">' +
      '<div id="quicklistElements">' +
      '</div>' +
      '</div>' +
      '</div>';
    td.append(menu);

    var quicklistElements = $('#quicklistElements');

    // Add private filter
    if (filters.private.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_private_title ?></span>');
      $.map(filters.private, function (filter) {
        quicklistElements.append('<a id="' + filter.id + '" class="item filter" href="' + filter.url + '">' + filter.name + '</a>');
        $('#quicklistElements .item.filter#' + filter.id).append('<span class="right'+(filter.author?'':' hide')+'">' +
          '<a class="quicklist-button" action="quicklist_editfilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $edit_img ?></a>' +
          '<a class="quicklist-button" action="quicklist_deletefilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $delete_img ?></a>' +
          '</span>');
      });
    }

    // Add usergroup filter
    if (filters.usergroup.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_usergroup_title ?></span>');
      $.map(filters.usergroup, function (filter) {
        quicklistElements.append('<a id="' + filter.id + '" class="item filter" href="' + filter.url + '">' + filter.name + '</a>');
        $('#quicklistElements .item.filter#' + filter.id).append('<span class="right'+(filter.author?'':' hide')+'">' +
          '<a class="quicklist-button" action="quicklist_editfilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $edit_img ?></a>' +
          '<a class="quicklist-button" action="quicklist_deletefilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $delete_img ?></a>' +
          '</span>');
      });
    }

    // Add public filter
    if (filters.public.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_public_title ?></span>');
      $.map(filters.public, function (filter) {
        quicklistElements.append('<a id="' + filter.id + '" class="item filter" href="' + filter.url + '">' + filter.name + '</a>');
        $('#quicklistElements .item.filter#' + filter.id).append('<span class="right'+(filter.author?'':' hide')+'">' +
          '<a class="quicklist-button" action="quicklist_editfilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $edit_img ?></a>' +
          '<a class="quicklist-button" action="quicklist_deletefilter" filterid="'+filter.id+'" href="javascript:;"><?php echo $delete_img ?></a>' +
          '</span>');
      });
    }

    $('input[name="button_quicklist"]').click(function (event) {
      $('#quicklistDropdown').toggleClass('show');

      event.stopPropagation();
      return false;
    });

    $('a.quicklist-button').click(function (event) {
      var _this = $(this);
      var form = _this.closest('form');
      var actionType = _this.attr('action');
      var filterID = _this.attr('filterid');

      var action = form.find('input[name="action"]');
      if (action.length) {
        action.val(actionType);
      } else {
        form.append('<input type="hidden" name="action" value="'+actionType+'">');
      }

      form.append('<input type="hidden" name="filter_id" value="'+filterID+'">');

      form.submit();
    });

    $(window).click(function() {
      $('#quicklistDropdown').removeClass('show');
    });

    $('#quicklistDropdown').click(function(event){
        event.stopPropagation();
    });
  }
}

function quicklistFilterFunction() {
  var filter = $('input#quicklistInput').val().toUpperCase();
  var items = $('div#quicklistElements a.item.filter');
  items.map(function () {
    var _this = $(this);
    if (_this.text().toUpperCase().indexOf(filter) > -1) {
      _this.show();
    } else {
      _this.hide();
    }
  });
}
