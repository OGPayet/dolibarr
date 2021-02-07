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
 * \file       htdocs/core/js/extendedemail.js.php
 * \brief      File that include javascript functions for Extended Email
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
$langs->load('mails');
$langs->load('extendedemail@extendedemail');

$sender_label = str_replace("'", "\\'", $langs->transnoentitiesnoconv('MailFrom'));
$placeholder_label = $langs->trans('ExtendedEmailPlaceholder');
$placeholder_label = $placeholder_label == 'ExtendedEmailPlaceholder'?'':str_replace('"', '\\"', $placeholder_label);
$add_label = str_replace('"', '\\"', $langs->trans('ExtendedEmailAdd'));
$invalid_email_label = str_replace('"', '\\"', $langs->trans('ExtendedEmailInvalidEmail'));
$no_email_label = str_replace('"', '\\"', $langs->trans('NoEMail'));
$no_email_label_noconv = str_replace('"', '\\"', $langs->transnoentitiesnoconv('NoEMail'));
$no_email_for_user_label = str_replace('"', '\\"', $langs->trans('ErrorNoMailDefinedForThisUser'));
$no_email_for_user_label_noconv = str_replace('"', '\\"', $langs->transnoentitiesnoconv('ErrorNoMailDefinedForThisUser'));
$remove_noconv = str_replace('"', '\\"', $langs->transnoentitiesnoconv('Remove'));

?>

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

var REGEX_EMAIL = '([a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@' +
  '(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)';

var extendedemail_regex = new RegExp('^([^<]*)(?:\<([^>]*)\>)?$', 'i');
var extendedemail_regex_email = new RegExp('^' + REGEX_EMAIL + '$', 'i');
var extendedemail_regex_name_email = new RegExp('^([^<]*)\<' + REGEX_EMAIL + '\>$', 'i');

function extendedemail_select_email(input_htmlname, select_htmlname, label, selected_users_email, users_email, hide_no_email, read_only, max_options) {
  var separator = ',';
  var input = $('input#' + input_htmlname);
  var select = $('select#' + select_htmlname);
  var selecttd = input.length > 0 ? input.parent() : (select.length > 0 ? select.parent() : select);

  if (selecttd.length > 0) {
    var selectlabeltd = selecttd.parent().find('td:first');
    selectlabeltd.empty();
    selectlabeltd.append(label);

    var values = [];
    //console.log('values for '+input_htmlname, users_email);
    var selectedvalues = [];
    if (input.length > 0) {
      input.val().split(separator).map(function (value) {
        var infos = extendedemail_get_email_infos(values.length + 1, value);
        if (infos && !(hide_no_email && infos.disabled)) {
          values.push(infos);
          if (infos.email && infos.email.length > 0)
            selectedvalues.push(infos.email);
        }
      });
    }
    if (select.length > 0) {
      select.find('option').map(function () {
        var option = $(this);
        var infos = extendedemail_get_email_infos(values.length + 1, option.text());
        if (infos && !(hide_no_email && infos.disabled)) {
          values.push(infos);
          if (option.is(':selected') && infos.email && infos.email.length > 0) selectedvalues.push(infos.email);
        }
      });
    }
    console.log('values', values);
    values.sort(function(a, b) {
      var aName = name in a ? a.name.toLowerCase() : '';
      var bName = name in b > 0 ? b.name.toLowerCase() : '';
      return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    });
    selectedvalues = $.merge(selectedvalues, selected_users_email);
    values = $.merge(values, users_email);
    //console.log('values', values);
    console.log('selectedvalues', selectedvalues);

    selecttd.empty();
    selecttd.append('<input type="hidden" id="' + input_htmlname + '" name="' + input_htmlname + '" value="">');
    selecttd.append('<select id="' + input_htmlname + 'select" class="contacts" placeholder="<?php echo $placeholder_label ?>"></select>');

    var select_input = $('select#' + input_htmlname + 'select');
    var _selectize = select_input.selectize({
      persist: false,
      maxItems: null,
      maxOptions: max_options,
      valueField: 'email',
      labelField: 'name',
      disabledField: 'disabled',
      searchField: ['name', 'email'],
      options: values,
      items: selectedvalues,
      delimiter: separator,
      plugins: {
        'remove_button': {
          'title': '<?php echo $remove_noconv ?>'
        }
      },
      render: {
        item: function (item, escape) {
          return '<div>' +
            (item.name ? '<span class="name">' + escape(item.name) + '</span>' : '') +
            (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') +
            '</div>';
        },
        option: function (item, escape) {
          var label = item.name || item.email;
          var caption = item.name ? (!$.isNumeric(item.email) ? item.email : "<?php echo $no_email_label_noconv ?>") : null;
          return '<div>' +
            '<span class="label">' + escape(label) + '</span>' +
            (caption ? '<span class="caption">' + escape(caption) + '</span>' : '') +
            '</div>';
        },
        option_create: function (data, escape) {
          return '<div class="create"><?php echo $add_label ?> <strong>' + escape(data.input) + '</strong>&hellip;</div>';
        }
      },
      createFilter: function (input) {
        var match;

        // email@address.com
        match = input.match(extendedemail_regex_email);
        if (match) return !this.options.hasOwnProperty(match[0]);

        // name <email@address.com>
        match = input.match(extendedemail_regex_name_email);
        if (match) return !this.options.hasOwnProperty(match[2]);

        return false;
      },
      create: function (input) {
        return extendedemail_get_email_infos(this.options.length + 1, input);
      },
      onInitialize: function () {
        if (read_only) {
          this.$control_input.attr('readonly', true);
          this.onKeyDown_original = this.onKeyDown;

          var KEY_BACKSPACE = 8;
         	var KEY_DELETE    = 46;
          this.onKeyDown = function (e) {
            switch (e.keyCode) {
              case KEY_BACKSPACE:
              case KEY_DELETE:
                e.keyCode = null;
                break;
            }
            this.onKeyDown_original(e);
          }
        }
      },
      onChange: function (value) {
        $('input#' + input_htmlname).val(value ? value.join(separator) : '');
      }
    });

    var sel_values = [];
    selectedvalues.map(function (value) {
      sel_values.push(value);
    });
    $('input#' + input_htmlname).val(sel_values ? sel_values.join(separator) : '');

    $('input#' + input_htmlname + 'select-selectized').keypress(function (event) {
      extendedemail_reject_enter_key(event);
    });
  }
}

function extendedemail_select_from(generic_email, selected_value, from_type, hide_no_email, read_only, max_options) {
  var input_fromname = $('input#fromname');
  var input_frommail = $('input#frommail');
  var select_fromtype = $('select#fromtype');
  var selecttd = select_fromtype.length > 0 ? select_fromtype.closest('td') : (input_fromname.length > 0 ? input_fromname.closest('td') : input_fromname);

  if (selecttd.length > 0) {
    var values = [];
    if (select_fromtype.length > 0) {
      selected_value = [];
      select_fromtype.find('option').map(function () {
        var option = $(this);
        var infos = extendedemail_get_email_infos(values.length + 1, option.text());
        if (infos && !(hide_no_email && infos.disabled)) {
          values.push(infos);
          if (option.val() == from_type) selected_value.push(infos.email);
        }
      });
    }
    values = $.merge(values, generic_email);
    values.sort(function (a, b) {
      var aName = a.name.toLowerCase();
      var bName = b.name.toLowerCase();
      return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    });

    selecttd.empty();
    if (input_fromname.length == 0) selecttd.append('<input type="hidden" id="fromname" name="fromname" value="">');
    if (input_frommail.length == 0) selecttd.append('<input type="hidden" id="frommail" name="frommail" value="">');
    selecttd.append('<select id="fromselect" class="contacts" placeholder="<?php echo $placeholder_label ?>"></select>');

    var fromselect = $('select#fromselect').selectize({
      persist: false,
      maxItems: 1,
      maxOptions: max_options,
      valueField: 'email',
      labelField: 'name',
      disabledField: 'disabled',
      searchField: ['name', 'email'],
      options: values,
      items: selected_value,
      render: {
        item: function (item, escape) {
          return '<div>' +
            (item.name ? '<span class="name">' + escape(item.name) + '</span>' : '') +
            (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') +
            '</div>';
        },
        option: function (item, escape) {
          var label = item.name || item.email;
          var caption = item.name ? (!$.isNumeric(item.email) ? item.email : "<?php echo $no_email_label_noconv ?>") : null;
          return '<div>' +
            '<span class="label">' + escape(label) + '</span>' +
            (caption ? '<span class="caption">' + escape(caption) + '</span>' : '') +
            '</div>';
        },
        option_create: function (data, escape) {
          return '<div class="create"><?php echo $add_label ?> <strong>' + escape(data.input) + '</strong>&hellip;</div>';
        }
      },
      createFilter: function (input) {
        var match;

        // email@address.com
        match = input.match(extendedemail_regex_email);
        if (match) return !this.options.hasOwnProperty(match[0]);

        // name <email@address.com>
        match = input.match(extendedemail_regex_name_email);
        if (match) return !this.options.hasOwnProperty(match[2]);

        return false;
      },
      create: function (input) {
        return extendedemail_get_email_infos(this.options.length + 1, input);
      },
      onInitialize: function () {
        if (read_only) {
          this.$control_input.attr('readonly', true);
          this.onKeyDown_original = this.onKeyDown;

          var KEY_BACKSPACE = 8;
          var KEY_DELETE = 46;
          this.onKeyDown = function (e) {
            switch (e.keyCode) {
              case KEY_BACKSPACE:
              case KEY_DELETE:
                e.keyCode = null;
                break;
            }
            this.onKeyDown_original(e);
          }
        }
      },
      onChange: function (value) {
        //console.log(value);
        input_fromname.val(this.options[value] ? this.options[value].name : '');
        input_frommail.val(value);
      }
    });

    var from_mail = selected_value[0];
    var from_name = fromselect["0"].selectize.options[from_mail].name;
    input_fromname.val(from_name ? from_name : '');
    input_frommail.val(from_mail);

    $('input#fromselect-selectized').keypress(function (event) {
      extendedemail_reject_enter_key(event);
    });
  }
}

function extendedemail_reject_enter_key(event) {
  // Compatibilité IE / Firefox
  if (!event && window.event) {
    event = window.event;
  }
  // IE
  if (event.keyCode == 13) {
    event.returnValue = false;
    event.cancelBubble = true;
  }
  // DOM
  if (event.which == 13) {
    event.preventDefault();
    event.stopPropagation();
  }
}

function extendedemail_get_email_infos(id, input) {
  input = input.trim().replace('&lt;', '<').replace('&gt;', '>');
  if (input.length > 0) {
    var match = input.match(extendedemail_regex);
    if (match) {
      // No email
      if ("<?php echo $no_email_label ?>" == match[2] || "<?php echo $no_email_label_noconv ?>" == match[2] ||
          "<?php echo $no_email_for_user_label ?>" == match[2] || "<?php echo $no_email_for_user_label_noconv ?>" == match[2] ||
          (match[2] && match[2].trim() == "")
      ) {
        return {
          email: id,
          name: $.trim(match[1]),
          disabled: true
        };
      }

      // Check email
      var email = $.trim(match[1]);
      if (match[2]) email = match[2];
      if (extendedemail_regex_email.test(email)) {
        if (match[2]) {
          return {
            email: match[2],
            name: $.trim(match[1])
          };
        } else {
          return { email: input };
        }
      }
    }
    alert('<?php echo $invalid_email_label ?> : '+input);
  }

  return false;
}

