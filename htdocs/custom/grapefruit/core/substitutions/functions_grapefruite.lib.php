<?php

/**
 * Function de substitution de clé custom
 * Actuellement disponible en standard @see tooltip dans la configuration du module facture:
 *     - __MYCOMPANY_NAME__
 *     - __MYCOMPANY_EMAIL__
 *     - __MYCOMPANY_PROFID1__
 *     - __MYCOMPANY_PROFID2__
 *     - __MYCOMPANY_PROFID3__
 *     - __MYCOMPANY_PROFID4__
 *     - __MYCOMPANY_PROFID5__
 *     - __MYCOMPANY_PROFID6__
 *     - __MYCOMPANY_CAPITAL__
 *     - __MYCOMPANY_COUNTRY_ID__
 *     - __TOTAL_TTC__
 *     - __TOTAL_HT__
 *     - __TOTAL_VAT__
 *     - __AMOUNT__
 *     - __AMOUNT_WO_TAX__
 *     - __AMOUNT_VAT__
 *     - __DAY__
 *     - __MONTH__
 *     - __YEAR__
 *     - __PREVIOUS_DAY__
 *     - __PREVIOUS_MONTH__
 *     - __PREVIOUS_YEAR__
 *     - __NEXT_DAY__
 *     - __NEXT_MONTH__
 *     - __NEXT_YEAR__
 *     - __USER_ID__
 *     - __USER_LOGIN__
 *     - __USER_LASTNAME__
 *     - __USER_FIRSTNAME__
 *     - __USER_FULLNAME__
 *     - __USER_SUPERVISOR_ID__
 *     - __FROM_NAME__
 *     - __FROM_EMAIL__
 *     - __EXTRA_mode_transport__
 *     - %EXTRA_mode_transport%
 *     - __EXTRA_reason__
 *     - %EXTRA_reason%
 *     - __EXTRA_grapefruitReminderBill__
 *     - %EXTRA_grapefruitReminderBill%
 *
 * @param array $substitutionarray
 * @param type $outputlangs
 * @param type $object
 * @param type $parameters
 */
function grapefruite_completesubstitutionarray(&$substitutionarray,$outputlangs,$object,$parameters)
{
	global $conf;


}