<?php
/**
 * Created by PhpStorm.
 * User: adam
 * Date: 24/08/2018
 * Time: 11:22
 */

$permtoclone = $user->rights->infoextranet->clone;

// Clone record from mass action (massaction = 'clone' for direct clone, action/confirm='clone'/'yes' with a confirmation step before)
if (! $error && ($massaction == 'clone' || ($action == 'clone' && $confirm == 'yes')) && $permtoclone)
{
    $db->begin();

    $objecttmp=new $objectclass($db);
    $nbok = 0;
    foreach($toselect as $toselectid)
    {
        $result=$objecttmp->fetch($toselectid);
        if ($result > 0)
        {
            if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->cloneUser($user, $objecttmp->id);
            else $result = $objecttmp->cloneUser($user, $objecttmp->id);

            if ($result <= 0)
            {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            }
            else $nbok++;
        }
        else
        {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }

    if (! $error)
    {
        if ($nbok > 1) setEventMessages($langs->trans("RecordsClone", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("RecordClone", $nbok), null, 'mesgs');
        $db->commit();
    }
    else
    {
        $db->rollback();
    }
    //var_dump($listofobjectthirdparties);exit;
}
