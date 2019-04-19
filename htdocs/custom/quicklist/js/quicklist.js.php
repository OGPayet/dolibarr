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

$default_text = json_encode(' (' . $langs->trans('QuickListDefault') .')');

$search_plus_img_title = dol_escape_htmltag($langs->trans("QuickListHelp"));
$search_plus_img_src = img_picto('', 'search_plus.png@quicklist', '', false, 1);

$button_delete_filter_text = dol_escape_htmltag($langs->trans('RemoveFilter'));
$button_add_filter_text = dol_escape_htmltag($langs->trans('QuickListAddFilter'));

$search_placeholder = dol_escape_htmltag($langs->trans('QuickListSearch'));

$scope_private_title = dol_escape_htmltag($langs->trans('QuickListScopePrivate'));
$scope_usergroup_title = dol_escape_htmltag($langs->trans('QuickListScopeUserGroup'));
$scope_public_title = dol_escape_htmltag($langs->trans('QuickListScopePublic'));

$edit_img = img_picto($langs->trans('QuickListEditFilter'), 'edit.png', ' id="quicklist_editfilter"');
$delete_img = img_picto($langs->trans('QuickListDeleteFilter'), 'delete.png', ' id="quicklist_deletefilter"');
$delete_img_src = img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1);

$filters_label = dol_escape_htmltag($langs->trans('QuickListFilters'));

$module_owntheme_activated = !empty($conf->owntheme->enabled) ? 1 : 0;

?>

function quicklist_replace_button_removefilter(base_url, filters) {
  var button_search = $('td.liste_titre [name="button_removefilter"]');

  if (button_search.length > 0) {
    var td = button_search.parent();
    var base_url_params = base_url+(base_url.indexOf('?')==-1?'?':'&');

    button_search.remove();
    var menu =
      '<div class="quicklist-dropdown">' +
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

    if (<?php echo $module_owntheme_activated ?>) {
      var owntheme_div = $('div.icon-plus-filter-cancel.search_icons_container');
      $('div.quicklist-dropdown').detach().appendTo(owntheme_div.parent());
      owntheme_div.remove();
      $('input.quicklist-dropbtn').removeClass('liste_titre').addClass('ql_owntheme_fix');
    }

    var quicklistElements = $('#quicklistElements');

    // Add private filter
    if (filters.private.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_private_title ?></span>');
      quicklistAddFilterItem(quicklistElements, filters.private);
    }

    // Add usergroup filter
    if (filters.usergroup.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_usergroup_title ?></span>');
      quicklistAddFilterItem(quicklistElements, filters.usergroup);
    }

    // Add public filter
    if (filters.public.length) {
      quicklistElements.append('<span class="item category"><?php echo $scope_public_title ?></span>');
      quicklistAddFilterItem(quicklistElements, filters.public);
    }

    $('input[name="button_quicklist"]').click(function (event) {
      $('#quicklistDropdown').toggleClass('show');

      event.stopPropagation();
      return false;
    });

    // For v9-
    $('img#quicklist_editfilter').click(function (event) {
      quicklistClickFilterButton($(this));
      event.stopPropagation();
      return false;
    });
    $('img#quicklist_deletefilter').click(function (event) {
      quicklistClickFilterButton($(this));
      event.stopPropagation();
      return false;
    });

    // For v9+
    $('span#quicklist_editfilter').click(function (event) {
      quicklistClickFilterButton($(this));
      event.stopPropagation();
      return false;
    });
    $('span#quicklist_deletefilter').click(function (event) {
      quicklistClickFilterButton($(this));
      event.stopPropagation();
      return false;
    });

    $(window).click(function() {
      $('#quicklistDropdown').removeClass('show');
    });

    $('#quicklistDropdown').click(function(event){
        event.stopPropagation();
    });
  }
}

function quicklist_show_filters_list_button(filters) {
  var button_search = $('td.liste_titre [name="button_search"]');

  if (button_search.length > 0 && (filters.private.length > 0 || filters.usergroup.length > 0 || filters.public.length > 0)) {
    var table = button_search.closest('form').find('table:first');
    table.after('<div class="quicklist-filter-list centpercent"><?php echo $filters_label ?> :</div>');
    var quicklistFiltersList = $('div.quicklist-filter-list');

    // Remove filter button
    quicklistFiltersList.append('<input type="image" class="button" name="button_removefilter" src="<?php echo $delete_img_src ?>" title="<?php echo $button_delete_filter_text ?>" value="<?php echo $button_delete_filter_text ?>">');

    // Add private filter
    if (filters.private.length) {
      quicklistAddFilterButton(quicklistFiltersList, filters.private);
    }

    // Add usergroup filter
    if (filters.usergroup.length) {
      quicklistAddFilterButton(quicklistFiltersList, filters.usergroup);
    }

    // Add public filter
    if (filters.public.length) {
      quicklistAddFilterButton(quicklistFiltersList, filters.public);
    }

    // Add new filter button
    quicklistFiltersList.append('<input type="submit" class="button" name="button_quicklist_addfilter" title="<?php echo $button_add_filter_text ?>" value="+">');
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

function quicklistAddFilterItem(quicklistElements, filterList) {
  $.map(filterList, function (filter) {
    quicklistElements.append(
      '<a id="' + filter.id + '" class="item filter" href="' + filter.url + (filter.hash_tag ? filter.hash_tag : '') + '">' +
      filter.name + (filter.default ? <?php echo $default_text ?> : '') + (filter.author ? '<span class="right"><?php echo $edit_img ?><?php echo $delete_img ?></span>' : '') +
      '</a>'
    );
  });
}

function quicklistClickFilterButton(_this) {
  var form = _this.closest('form');
  var actionType = _this.attr('id');
  var filterID = _this.closest('a').attr('id');

  var action = form.find('input[name="action"]');
  if (action.length) {
    action.val(actionType);
  } else {
    form.append('<input type="hidden" name="action" value="'+actionType+'">');
  }

  form.append('<input type="hidden" name="filter_id" value="'+filterID+'">');

  form.submit();
}

function quicklistAddFilterButton(quicklistFiltersList, filterList) {
  $.map(filterList, function (filter) {
    quicklistFiltersList.append('<a id="' + filter.id + '" class="button" href="' + filter.url + (filter.hash_tag ? filter.hash_tag : '') + '">' + filter.name + '</a>');
  });
}
