<?php

/** 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 *
 * 		@param	array		$substitutionarray	Array with substitution key=>val
 * 		@param	Translate	$langs			Output langs
 * 		@param	Object		$object			Object to use to get values
 * 		@return	void					The entry parameter $substitutionarray is modified
 */
function synergiestechcontrat_completesubstitutionarray(&$substitutionarray, $langs, &$object, $parameters)
{
    global $conf, $db;

// won't be used ...
//    $substitutionarray['OUVRAGE_TYPE']                     = $conf->global->OUVRAGE_TYPE;
//    $substitutionarray['OUVRAGE_HIDE_PRODUCT_DETAIL']      = $conf->global->OUVRAGE_HIDE_PRODUCT_DETAIL;
//    $substitutionarray['OUVRAGE_HIDE_PRODUCT_DESCRIPTION'] = $conf->global->OUVRAGE_HIDE_PRODUCT_DESCRIPTION;
//    $substitutionarray['OUVRAGE_HIDE_MONTANT']             = $conf->global->OUVRAGE_HIDE_MONTANT;

}
