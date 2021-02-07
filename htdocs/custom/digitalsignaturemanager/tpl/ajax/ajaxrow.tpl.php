<?php
/* Copyright (C) 2010-2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 *
 * Javascript code to activate drag and drop on lines
 * You can use this if you want to be abale to drag and drop rows of a table.
 */

// Protection to avoid direct call of template
if (empty($elementType))
{
	print "Error, template page can't be called as URL or some parameter are missing";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE AJAXROW.TPL.PHP - Script to enable drag and drop on tables -->
<?php
$forceReloadPage=empty($conf->global->MAIN_FORCE_RELOAD_PAGE)?0:1;
?>
<script type="text/javascript">
$(document).ready(function(){
	$(".imgupforline").hide();
	$(".imgdownforline").hide();
    $(".lineupdown").removeAttr('href');
    $(".tdlineupdown").css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/grip.png'; ?>)');
    $(".tdlineupdown").css("background-repeat","no-repeat");
    $(".tdlineupdown").css("background-position","center center");

    console.log("Prepare tableDnd for #<?php echo $IdOfTableDisplayingRowToBeSorted; ?>");
    $("#<?php echo $IdOfTableDisplayingRowToBeSorted; ?>").tableDnD({
		onDrop: function(table, row) {
			var reloadpage = "<?php echo $forceReloadPage; ?>";
			console.log("tableDND onDrop");
			console.log($("#<?php echo $IdOfTableDisplayingRowToBeSorted; ?>").tableDnDSerialize());
			var rowOrder = cleanSerialize($("#<?php echo $IdOfTableDisplayingRowToBeSorted; ?>").tableDnDSerialize());
			var elementType = "<?php echo $elementType; ?>";
			$.post("<?php echo dol_buildpath('digitalsignaturemanager/ajax/rowOrder.php', 1) ?>",
					{
						rowOrder,
						elementType,
					},
					function() {
						console.log("tableDND end of ajax call");
						if (reloadpage == 1) {
							location.href = '<?php echo dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']); ?>';
						} else {
							$("#<?php echo $IdOfTableDisplayingRowToBeSorted; ?> .drag").each(
									function( intIndex ) {
										// $(this).removeClass("pair impair");
										//if (intIndex % 2 == 0) $(this).addClass('impair');
										//if (intIndex % 2 == 1) $(this).addClass('pair');
									});
						}
					});
		},
		onDragClass: "dragClass",
		dragHandle: "tdlineupdown"
	});
    $(".tdlineupdown").hover( function() { $(this).addClass('showDragHandle'); },
	function() { $(this).removeClass('showDragHandle'); }
    );
});
</script>
<!-- END PHP TEMPLATE AJAXROW.TPL.PHP -->
