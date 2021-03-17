<?php
/* Copyright (C) 2018 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */
/*
if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
*/

/**
 * \file    contacttracking/js/contacttracking.js.php
 * \ingroup contacttracking
 * \brief   JavaScript file for module contacttracking.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}

if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $conf;

// Define js type
header('Content-Type: application/javascript');
?>

/* Ouvrage Script for Dolibarr Version lower than 7 */

$(document).on("select2-selecting", '#productid, #serviceid', function (e) {

    var prodid = e.choice.id;
    var prodtext = e.choice.text;

    addProductOuvrage(prodid, prodtext);
    setTimeout(function () {
        $('#productid, #serviceid').val(0).trigger('change');
    }, 20);

});
$(document).on("autocompleteselect", '#search_productid, #search_serviceid', function (event, ui) {
    var prodid = ui.item.id;
    var prodtext = ui.item.label;

    addProductOuvrage(prodid, prodtext);

    setTimeout(function () {
        $('#search_productid, #search_serviceid').val('').trigger('change');
    }, 20);
});

function addProductOuvrage(prodid, prodtext) {
    // Check si le produit est déjà dans l'ouvrage
    if ($("#sortable input[type=hidden][name='products[]'][value=" + prodid + "]").length == 0) {
        var newelt = $('<li class="ui-state-default ui-sortable-handle" style="cursor:grab;"></li>');
        $(newelt).append('<span class="icon-drag-drop"></span>');
        $(newelt).append('<input type="hidden" name="products[]" value="' + prodid + '" />');
        $(newelt).append(prodtext);
        $(newelt).append('<input type="number" step="<?php print $conf->global->OUVRAGE_QUANTITY_STEP ?>" min="0" name="quantity[]" value="1.000" length="4" style="width:5vw;"/>');
        $(newelt).append('<a href="#" class="delete">X</a>');
        $("#sortable").append($(newelt));
    }
}


$(document).on("click", '#sortable a.delete', function (e) {
    $(this).parent('li').remove();
});

$(function () {
    $("#sortable").sortable();
});