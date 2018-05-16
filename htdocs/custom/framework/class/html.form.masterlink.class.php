<?php



class htmlformmasterlink {

		public $db;

		function __construct($db){
			$this->db = $db;
		}


	/**
     *  Return select list of incoterms
     *
     *  @param	string	$selected       		Id or Code of preselected incoterm
     *  @param	string	$location_incoterms     Value of input location
     *  @param	string	$page       			Defined the form action
     *  @param  string	$htmlname       		Name of html select object
     *  @param  string	$htmloption     		Options html on select object
     * 	@param	int		$forcecombo				Force to use standard combo box (no ajax use)
     *  @param	array	$events					Event options to run on change. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     *  @return string           				HTML string with select and input
     */
    function select_customlink($original, $selected='', $location_incoterms='', $page='', $htmlname='incoterm_id', $htmloption='', $forcecombo=1, $events=array())
    {
        global $conf,$langs;

        $langs->load("dict");

        $out='';
        $Array=array();

//         $sql = "SELECT rowid, p.original, p.custom, p.active";
//         $sql.= " FROM ".MAIN_DB_PREFIX."c_incoterms";
//         $sql.= " WHERE active > 0";
//         $sql.= " ORDER BY code ASC";


			$sql = "SELECT p.rowid, p.original, p.custom, p.active";
		$sql.= " FROM ".MAIN_DB_PREFIX."masterlink as p";
		$sql.= " WHERE 1 ";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND p.original = '".$original."' ";
// 		$sql.= " GROUP BY  p.original ";
		$sql.= " ORDER BY  p.active DESC";


        dol_syslog(get_class($this)."::select_incoterm", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
//         	if ($conf->use_javascript_ajax && ! $forcecombo)
// 			{
// 				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
// 				$out .= ajax_combobox($htmlname, $events);
// 			}

// 			if (!empty($page))
// 			{
// 				$out .= '<form method="post" action="'.$page.'">';
// 	            $out .= '<input type="hidden" name="action" value="set_incoterms">';
// 	            $out .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
// 			}

            $out.= '<select id="'.$htmlname.'" class="flat selectincoterm noenlargeonsmartphone" name="'.$htmlname.'" '.$htmloption.'>';
			$out.= '<option value="0">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $Array[$i]['rowid'] = $obj->rowid;
                    $Array[$i]['custom'] = $obj->custom;
                    $i++;
                }

                foreach ($Array as $row)
                {
                    if ($selected && ($selected == $row['rowid'] || $selected == $row['custom']))
                    {
                        $out.= '<option value="'.$row['rowid'].'" selected>';
                    }
                    else
					{
                        $out.= '<option value="'.$row['rowid'].'">';
                    }

                    if ($row['custom']) $out.= $row['custom'];

					$out.= '</option>';
                }
            }
            $out.= '</select>';

// 			$out .= '<input id="location_incoterms" name="location_incoterms" size="14" value="'.$location_incoterms.'">';

// 			if (!empty($page))
// 			{
// 	            $out .= '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></form>';
// 			}
        }
        else
		{
            dol_print_error($this->db);
        }

        return $out;
    }



}
