<?php
/**
 *  \file       /ouvrage/class/html.form_ouvrage.class.php
 *  \ingroup    ouvrage
 *  \brief      Class ouvrage HTML
 */

/**
 * Class to manage building of HTML components
 */
class FormOuvrage extends Form
{
	var $db;
	var $error;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		return 1;
	}

        function select_ouvrage($htmlname = 'ouvrageid')
        {
            global $langs,$conf;
            
            $out = '';
            
            include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
            $comboenhancement =ajax_combobox($htmlname, null, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
            $out.= $comboenhancement;
            
            $sql = "SELECT w.* FROM ".MAIN_DB_PREFIX."works as w";
            $sql.= " WHERE `entity` = " . $conf->entity;
            
            $resql=$this->db->query($sql);
            
            $out.='<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'" style="min-width:40%">';
            $out.='<option value="0"></option>';
            while ($i < $this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
        
                $out.='<option value="'.$obj->rowid.'">'.$obj->ref.' - '.$obj->label.'</option>';
                
                $i++;
            }
            
            $out .='</select>';
                
            return $out;
        }
}
