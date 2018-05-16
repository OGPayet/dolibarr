<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */

global $langs, $conf, $html, $user, $result, $currentmod,  $master, $subarray;



if( /*substr(*/$conf->global->{$master->cstname}/*,2)*/ == $master->api_key || empty($conf->global->{$master->cstname})) :
	print '<br>';
	print '<form method="post" action="">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="setvalue">';
	print '<input type="hidden" name="registrationnamekey" value="' .$master->cstname . '" />' ;

	clearstatcache();

	print '<table class="noborder" width="100%">';


	print '<tr class="liste_titre">';
	print '<td width="20%">' . $langs->trans("Name") . '</td>';
	print '<td width="20%">' . $langs->trans("Reglage") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print "</tr>\n";




	$var = !$var;
	print '<tr ' . $bc[$var] . '>';
	print '<td valign="top">' . $langs->trans("RegistrationUrlDoli") . '</td>';
	print '<td>' .
					'<input type="text" name="registrationurldoli" value="' . (($conf->global->REGISTRATIONURLDOLI) ? $conf->global->REGISTRATIONURLDOLI : $_SERVER['SERVER_NAME']) . '" />' .
					'</td>';
	print '<td>' . $langs->trans("RegistrationUrlDoliDetail") . '</td>';
	print '</tr>';

	$var = !$var;
	print '<tr ' . $bc[$var] . '>';
	print '<td valign="top">' . $langs->trans("RegistrationIpDoli") . '</td>';
	print '<td>' .
					'<input type="text" name="registrationipdoli" value="' . (($conf->global->REGISTRATIONIPDOLI) ? $conf->global->REGISTRATIONIPDOLI : $_SERVER['SERVER_ADDR']) . '" />' .
					'</td>';
	print '<td>' . $langs->trans("KpiLocLngDetail") . '</td>';
	print '</tr>';

	$var = !$var;
	print '<tr ' . $bc[$var] . '>';
	print '<td valign="top">' . $langs->trans("RegistrationCompanyDoli") . '</td>';
	print '<td>' .
					'<input type="text" name="registrationcompanydoli" value="' . (($conf->global->REGISTRATIONCOMPANYDOLI) ? $conf->global->REGISTRATIONCOMPANYDOLI : $conf->global->MAIN_INFO_SOCIETE_NOM) . '" />' .
					'</td>';
	print '<td>' . $langs->trans("RegistrationCompanyDoliDetail") . '</td>';
	print '</tr>';

	$var = !$var;
	print '<tr ' . $bc[$var] . '>';
	print '<td valign="top">' . $langs->trans("RegistrationEmailDolistore") . '</td>';
	print '<td>' .
					'<input type="text" name="registrationemaildoli" value="' . (($conf->global->REGISTRATIONEMAILDOLISTORE) ? $conf->global->REGISTRATIONEMAILDOLISTORE : $user->email) . '" />' .
					'</td>';
	print '<td>' . $langs->trans("RegistrationEmailDolistoreDetail") . '</td>';
	print '</tr>';



	?>




	<tr>
		<td colspan="2" align="right">
			<input type="submit" class="button" value="<?=  $langs->trans("Modify") ?>">
		</td>
	</tr>

	</table>
	</form>

 <?php else: ?>

	<h2><?php echo $langs->trans("RegistrationMasterKey"); ?></h2>


	<form method="post" action="">
	<input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>">
	<input type="hidden" name="action" value="check">

	<table class="noborder" width="100%">


		<tr>
			<td><?php echo $langs->trans("RegistrationApiKey") ?></td>
			<td><?php echo substr($conf->global->{$master->cstname},2) ?></td>
		</tr>

		<tr>
			<td></td>
			<td><input type="submit" class="button" value="<?= $langs->trans("Check") ?>"></td>
		</tr>

	</table>
	</form>


	<h2><?php echo $langs->trans("RegistrationModActivated"); ?></h2>

	<table class="noborder" width="100%">
		<caption><?php echo $langs->trans("RegistrationListActivateModule"); ?></caption>

		<tr>
			<th><?php echo $langs->trans("RegistrationModName") ?></th>
			<th><?php echo $langs->trans("RegistrationModDateRegistered") ?></th>
		</tr>

		<?php foreach((array)@$subarray as $line): ?>
		<tr>
			<td><?php echo $line['module'] ?></td>
			<td><?php echo $line['created_time']; ?></td>
		</tr>
		<?php endforeach; ?>

	</table>

	<?php if(subRegistration::$submodisprev): ?>

	<h2><?php echo $langs->trans("RegistrationthisModActivated"); ?></h2>

	<form method="post" action="">
	<input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>">
	<input type="hidden" name="action" value="addmod">

	<table class="noborder" width="100%">



		<tr>
			<td><?php echo $langs->trans("RegistrationApiNewMod") ?></td>
			<td><input type="text" name="newmod" value="<?php echo $currentmod ?>"></td>
		</tr>

		<tr>
			<td></td>
			<td><input type="submit" class="button" value="<?= $langs->trans("AddMod") ?>"></td>
		</tr>

	</table>


	</form>
	<?php endif; ?>

<?php endif; ?>
