<?php

/* 	Copyright (C) 2014      Maxime MANGIN         <maxime@tuxserv.fr>
 *		Function called to complete substitution array (before generating on ODT)
 *
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$langs			Output langs
 *		@param	Object		$object			Object to use to get values
 * 		@return	void					The entry parameter $substitutionarray is modified
 */

function contratabonnement_completesubstitutionarray(&$substitutionarray,$langs,$object) {

	global $conf,$db;
	$substitutionarray['contract_ref'] = $object->contract_ref;
	$substitutionarray['line_puht'] = $substitutionarray['line_qty'];
}

?>
