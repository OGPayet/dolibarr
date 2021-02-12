<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 ATM Consulting <support@atm-consulting.fr>
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
 */

/**
 * \file    class/actions_fastupload.class.php
 * \ingroup fastupload
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsFastUpload
 */
class ActionsFastUpload
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formattachOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs;

		$langs->load('fastupload@fastupload');
		
		if ((float) DOL_VERSION < 6.0)
		{
			$this->resprints = '<link rel="stylesheet" href="'.dol_buildpath('/fastupload/css/font-awesome.min.css', 1).'">';
		}
		
		$this->resprints .= '
			
			<script type="text/javascript">
				
				$(document).ready( function() {
					var fu_action = $("#formuserfile").attr("action")
						,fu_method = $("#formuserfile").attr("method")
						,fu_paramName = $("#formuserfile input[type=file]").attr("name");
					
					
					var dropzone_submit = $("#formuserfile input[type=submit]").parent().clone();
					$(dropzone_submit).find("input[type=file]").remove();
					dropzone_submit = $(dropzone_submit).html();
					
					var dropzone_savingdocmask = "";
					if ($("#formuserfile input[name=savingdocmask]").length > 0)
					{
						dropzone_savingdocmask = $("#formuserfile input[name=savingdocmask]").parent().clone();
						dropzone_savingdocmask = $("<div class=\'dropzone_savingdocmask\'>"+dropzone_savingdocmask.html()+"</div>");
						
					}
					
					var dropzone_div = $("<div class=\"dropzone center dz-clickable\"></div>");
					dropzone_div.append($("<i class=\"upload-icon ace-icon fa fa-cloud-upload blue fa-3x\"></i><br>"));
					dropzone_div.append($("<span class=\"bigger-150 grey\">'.(addslashes($langs->transnoentities('FastUpload_DefaultMessage'))).'</span>"));
					dropzone_div.append($("<div id=\"dropzone-previews-box\" class=\"dz dropzone-previews dz-max-files-reached\"></div>"));
					
					var dropzone_form = $("<form id=\'dropzone_form\' action=\'"+fu_action+"\' method=\'"+fu_method+"\' enctype=\'multipart/form-data\'></form>");
					dropzone_form.append(dropzone_div);
					dropzone_form.append("<br /><div '.(!empty($conf->global->FASTUPLOAD_ENABLE_AUTOUPLOAD) ? 'style=\'display:none;\'' : '').'>"+dropzone_submit+"</div>");
					if (dropzone_savingdocmask) dropzone_form.append(dropzone_savingdocmask);
					

					$("#formuserfile").hide();
					$("#formuserfile").after(dropzone_form);
					
					fu_paramName = fu_paramName.replace("[", "");
					fu_paramName = fu_paramName.replace("]", "");
					
					enableDropzone($(dropzone_form), fu_paramName);
					
				});
			</script>
		';
		
		return 0;
	}
}