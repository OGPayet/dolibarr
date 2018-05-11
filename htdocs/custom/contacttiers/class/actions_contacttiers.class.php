<?php
	class ActionsContacttiers {

		function __construct($db) {
			global $langs;

			$this->db = $db;
		}

		function addMoreActionsButtons($parameters=false, &$object, &$action='') {
			global $conf,$user,$langs,$mysoc,$soc,$societe;

			if (is_array($parameters) && ! empty($parameters)) {
				foreach($parameters as $key=>$value) {
					$$key=$value;
				}
			}

			if(empty($soc->id)) {
				$socid = $societe->id;
			} else {
				$socid = $soc->id;
			}

			$element = $object->element;
			if ($user->rights->societe->contact->creer)
            {
				if (file_exists(DOL_DOCUMENT_ROOT."/contacttiers/card.php")) {
					$file=DOL_URL_ROOT."/contacttiers/card.php";
				} else {
					$file=DOL_URL_ROOT."/custom/contacttiers/card.php";
				}
                print '<a class="butAction" href="'.$file.'?id='.$object->id.'&action=create">Créer un tiers</a>';
            }
			return 0;
		}
	}
?>