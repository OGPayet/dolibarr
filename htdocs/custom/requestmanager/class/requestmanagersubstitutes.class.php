<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 */

/**
 *	\file       requestmanager/core/class/requestmanagersubstitutes.class.php
 *  \ingroup    requestmanager
 *	\brief      File of class to manage substitution in message and notification
 */

/**
 *	Class to manage substitution in message and notification
 *
 */
class RequestManagerSubstitutes
{
    /**
     *  Cache of names of user
     * @var string[]
     */
    private static $users_cache = array();
    /**
     *  Cache of names of user group
     * @var string[]
     */
    private static $usergroups_cache = array();
    /**
     *  Cache of names of contact
     * @var string[]
     */
    private static $contact_cache = array();

    /**
	 *  Set substitutes array from RequestManager object
	 *
     * @param   DoliDB              $db                 Database handler
     * @param	RequestManager	    $requestmanager		RequestManager object
     * @param   int                 $alsofornotify      Return also key used for notufy
     * @param   int                 $addgeneral         Return also general key
     * @return	array                                   Array of substitution values for message.
	 */
    static function setSubstitutesFromRequest($db, &$requestmanager, $alsofornotify=0, $addgeneral=1)
    {
        // Create dynamic tags for __EXTRAFIELD_FIELD__
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($db);
        $extralabels = $extrafields->fetch_name_optionals_label($requestmanager->table_element, true);
        $requestmanager->fetch_optionals($requestmanager->id, $extralabels);

        return self::getAvailableSubstitutesKeyFromRequest($db, $alsofornotify, 0, $requestmanager, $addgeneral);
    }

    /**
     *  Set substitutes array from RequestManagerMessage object
	 *
     * @param   DoliDB                      $db                         Database handler
     * @param	RequestManagerMessage	    $requestmanagermessage		RequestManagerMessage object
     * @return	array                                                   Array of substitution values for message.
	 */
    static function setSubstitutesFromRequestMessage($db, &$requestmanagermessage)
    {
        // Create dynamic tags for __EXTRAFIELD_FIELD__
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $message_extrafields = new ExtraFields($db);
        $message_extralabels = $message_extrafields->fetch_name_optionals_label($requestmanagermessage->table_element, true);
        $requestmanagermessage->fetch_optionals($requestmanagermessage->id, $message_extralabels);

        return self::getAvailableSubstitutesKeyFromRequestMessage($db, 0, $requestmanagermessage);
    }

    /**
     *  Get list of substitutes keys available for RequestManager object.
	 *  This include the complete_substitutions_array and the getCommonSubstitutionArray().
	 *
     * @param   DoliDB              $db                 Database handler
     * @param   int                 $alsofornotify      Return also key used for notufy
     * @param   int                 $keyonly            Return only key, dont load infos
     * @param	RequestManager	    $requestmanager	    RequestManager object
     * @param   int                 $addgeneral         Return also general key
	 * @return	array                                   Array of substitution values for RequestManager object.
	 */
	static function getAvailableSubstitutesKeyFromRequest($db, $alsofornotify=0, $keyonly=1, &$requestmanager=null, $addgeneral=1)
    {
        global $langs;

        if (!isset($requestmanager)) {
            dol_include_once('/requestmanager/class/requestmanager.class.php');
            $requestmanager = new RequestManager($db);
        }

        if ($keyonly) {
            $vars = array(
                // Request
                '__REQUEST_ID__'                        => $langs->trans('RequestManagerRequestId'),
                '__REQUEST_REF__'                       => $langs->trans('RequestManagerRequestReference'),
                '__REQUEST_REF_EXT__'                   => $langs->trans('RequestManagerRequestReferenceExternal'),
                '__REQUEST_PARENT_ID__'                 => $langs->trans('RequestManagerRequestParentId'),
                '__REQUEST_PARENT_REF__'                => $langs->trans('RequestManagerRequestParentReference'),
                '__REQUEST_PARENT_REF_EXT__'            => $langs->trans('RequestManagerRequestParentReferenceExternal'),
                '__REQUEST_TYPE_ID__'                   => $langs->trans('RequestManagerRequestTypeId'),
                '__REQUEST_TYPE_CODE__'                 => $langs->trans('RequestManagerRequestTypeCode'),
                '__REQUEST_TYPE_LABEL__'                => $langs->trans('RequestManagerRequestTypeLabel'),
                '__REQUEST_CATEGORY_ID__'               => $langs->trans('RequestManagerRequestCategoryId'),
                '__REQUEST_CATEGORY_CODE__'             => $langs->trans('RequestManagerRequestCategoryCode'),
                '__REQUEST_CATEGORY_LABEL__'            => $langs->trans('RequestManagerRequestCategoryLabel'),
                '__REQUEST_TITLE__'                     => $langs->trans('RequestManagerRequestTitle'),
                '__REQUEST_ORIGIN_COMPANY_ID__'         => $langs->trans('RequestManagerRequestOriginCompanyId'),
                '__REQUEST_ORIGIN_COMPANY_NAME__'       => $langs->trans('RequestManagerRequestOriginCompanyName'),
                '__REQUEST_PRINCIPAL_COMPANY_ID__'      => $langs->trans('RequestManagerRequestPrincipalCompanyId'),
                '__REQUEST_PRINCIPAL_COMPANY_NAME__'    => $langs->trans('RequestManagerRequestPrincipalCompanyName'),
                '__REQUEST_BENEFACTOR_COMPANY_ID__'     => $langs->trans('RequestManagerRequestBenefactorCompanyId'),
                '__REQUEST_BENEFACTOR_COMPANY_NAME__'   => $langs->trans('RequestManagerRequestBenefactorCompanyName'),
                '__REQUEST_SOURCE_ID__'                 => $langs->trans('RequestManagerRequestSourceId'),
                '__REQUEST_SOURCE_CODE__'               => $langs->trans('RequestManagerRequestSourceCode'),
                '__REQUEST_SOURCE_LABEL__'              => $langs->trans('RequestManagerRequestSourceLabel'),
                '__REQUEST_URGENCY_ID__'                => $langs->trans('RequestManagerRequestUrgencyId'),
                '__REQUEST_URGENCY_CODE__'              => $langs->trans('RequestManagerRequestUrgencyCode'),
                '__REQUEST_URGENCY_LABEL__'             => $langs->trans('RequestManagerRequestUrgencyLabel'),
                '__REQUEST_IMPACT_ID__'                 => $langs->trans('RequestManagerRequestImpactId'),
                '__REQUEST_IMPACT_CODE__'               => $langs->trans('RequestManagerRequestImpactCode'),
                '__REQUEST_IMPACT_LABEL__'              => $langs->trans('RequestManagerRequestImpactLabel'),
                '__REQUEST_PRIORITY_ID__'               => $langs->trans('RequestManagerRequestPriorityId'),
                '__REQUEST_PRIORITY_CODE__'             => $langs->trans('RequestManagerRequestPriorityCode'),
                '__REQUEST_PRIORITY_LABEL__'            => $langs->trans('RequestManagerRequestPriorityLabel'),
                '__REQUEST_DURATION__'                  => $langs->trans('RequestManagerRequestDuration'),
                '__REQUEST_DATE_OPERATION__'            => $langs->trans('RequestManagerRequestDateOperation'),
                '__REQUEST_DATE_DEADLINE__'             => $langs->trans('RequestManagerRequestDateDeadline'),
                '__REQUEST_STATUS_NUM__'                => $langs->trans('RequestManagerRequestStatusNum'),
                '__REQUEST_STATUS_LABEL__'              => $langs->trans('RequestManagerRequestStatusLabel'),
                '__REQUEST_STATUS_TYPE_NUM__'           => $langs->trans('RequestManagerRequestStatusTypeNum'),
                '__REQUEST_STATUS_TYPE_LABEL__'         => $langs->trans('RequestManagerRequestStatusTypeLabel'),
                '__REQUEST_REASON_RESOLUTION_CODE__'    => $langs->trans('RequestManagerRequestReasonResolutionCode'),
                '__REQUEST_REASON_RESOLUTION_LABEL__'   => $langs->trans('RequestManagerRequestReasonResolutionLabel'),
                '__REQUEST_REASON_RESOLUTION_DETAILS__' => $langs->trans('RequestManagerRequestReasonResolutionDetails'),
                '__REQUEST_TAGS__'                      => $langs->trans('RequestManagerRequestTags'),
                '__REQUEST_DESCRIPTION__'               => $langs->trans('RequestManagerRequestDescription'),
                '__REQUEST_NOTIFY_ASSIGNED_BY_EMAIL__'  => $langs->trans('RequestManagerRequestNotifyAssigned'),
                '__REQUEST_ASSIGNED_USER_IDS__'         => $langs->trans('RequestManagerRequestAssignedUserIds'),
                '__REQUEST_ASSIGNED_USER_NAMES__'       => $langs->trans('RequestManagerRequestAssignedUserNames'),
                '__REQUEST_ASSIGNED_USERGROUP_IDS__'    => $langs->trans('RequestManagerRequestAssignedUserGroupIds'),
                '__REQUEST_ASSIGNED_USERGROUP_NAMES__'  => $langs->trans('RequestManagerRequestAssignedUserGroupNames'),
                '__REQUEST_NOTIFY_REQUESTER_BY_EMAIL__' => $langs->trans('RequestManagerRequestNotifyRequester'),
                '__REQUEST_REQUESTER_IDS__'             => $langs->trans('RequestManagerRequestRequesterIds'),
                '__REQUEST_REQUESTER_NAMES__'           => $langs->trans('RequestManagerRequestRequesterNames'),
                '__REQUEST_NOTIFY_WATCHERS_BY_EMAIL__'  => $langs->trans('RequestManagerRequestNotifyWatchers'),
                '__REQUEST_WATCHER_IDS__'               => $langs->trans('RequestManagerRequestWatcherIds'),
                '__REQUEST_WATCHER_NAMES__'             => $langs->trans('RequestManagerRequestWatcherNames'),
                '__REQUEST_DATE_CREATED__'              => $langs->trans('RequestManagerRequestDateCreated'),
                '__REQUEST_DATE_MODIFIED__'             => $langs->trans('RequestManagerRequestDateModified'),
                '__REQUEST_DATE_RESOLVED__'             => $langs->trans('RequestManagerRequestDateResolved'),
                '__REQUEST_DATE_CLOSED__'               => $langs->trans('RequestManagerRequestDateClosed'),
                '__REQUEST_USER_CREATED_ID__'           => $langs->trans('RequestManagerRequestUserCreatedId'),
                '__REQUEST_USER_CREATED_NAME__'         => $langs->trans('RequestManagerRequestUserCreatedName'),
                '__REQUEST_USER_MODIFIED_ID__'          => $langs->trans('RequestManagerRequestUserModifiedId'),
                '__REQUEST_USER_MODIFIED_NAME__'        => $langs->trans('RequestManagerRequestUserModifiedName'),
                '__REQUEST_USER_RESOLVED_ID__'          => $langs->trans('RequestManagerRequestUserResolvedId'),
                '__REQUEST_USER_RESOLVED_NAME__'        => $langs->trans('RequestManagerRequestUserResolvedName'),
                '__REQUEST_USER_CLOSED_ID__'            => $langs->trans('RequestManagerRequestUserClosedId'),
                '__REQUEST_USER_CLOSED_NAME__'          => $langs->trans('RequestManagerRequestUserClosedName'),
                '__REQUEST_URL__'                       => $langs->trans('RequestManagerRequestURL'),
            );

            // Create dynamic tags for __EXTRAFIELD_FIELD__
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($db);
            $extralabels = $extrafields->fetch_name_optionals_label($requestmanager->table_element, true);
            foreach ($extrafields->attribute_label as $key => $val) {
                $vars['__REQUEST_EXTRA_' . strtoupper($key) . '__'] = $val;
            }

            if ($alsofornotify) {
                $vars = array_merge($vars, array(
                    '__REQUEST_OLD_STATUS_NUM__'                    => $langs->trans('RequestManagerRequestOldStatusNum'),
                    '__REQUEST_OLD_STATUS_LABEL__'                  => $langs->trans('RequestManagerRequestOldStatusLabel'),
                    '__REQUEST_OLD_STATUS_TYPE_NUM__'               => $langs->trans('RequestManagerRequestOldStatusTypeNum'),
                    '__REQUEST_OLD_STATUS_TYPE_LABEL__'             => $langs->trans('RequestManagerRequestOldStatusTypeLabel'),
                    '__REQUEST_ASSIGNED_USERS_ID_ADDED__'           => $langs->trans('RequestManagerRequestAssignedUserIdsAdded'),
                    '__REQUEST_ASSIGNED_USERS_NAME_ADDED__'         => $langs->trans('RequestManagerRequestAssignedUserNamesAdded'),
                    '__REQUEST_ASSIGNED_USERS_ID_DELETED__'         => $langs->trans('RequestManagerRequestAssignedUserIdsDeleted'),
                    '__REQUEST_ASSIGNED_USERS_NAME_DELETED__'       => $langs->trans('RequestManagerRequestAssignedUserNamesDeleted'),
                    '__REQUEST_ASSIGNED_USERGROUPS_ID_ADDED__'      => $langs->trans('RequestManagerRequestAssignedUserGroupIdsAdded'),
                    '__REQUEST_ASSIGNED_USERGROUPS_NAME_ADDED__'    => $langs->trans('RequestManagerRequestAssignedUserGroupNamesAdded'),
                    '__REQUEST_ASSIGNED_USERGROUPS_ID_DELETED__'    => $langs->trans('RequestManagerRequestAssignedUserGroupIdsDeleted'),
                    '__REQUEST_ASSIGNED_USERGROUPS_NAME_DELETED__'  => $langs->trans('RequestManagerRequestAssignedUserGroupNamesDeleted'),
                ));
            }
        } else {
            dol_include_once('/requestmanager/lib/requestmanager.lib.php');
            $requestmanager->fetch_parent();
            $requestmanager->fetch_thirdparty_origin();
            $requestmanager->fetch_thirdparty();
            $requestmanager->fetch_thirdparty_benefactor();
            $requestmanager->fetch_assigned();
            $requestmanager->fetch_requesters();
            $requestmanager->fetch_watchers();

            $assigned_user_names = array();
            $requestmanager->assigned_user_ids = is_array($requestmanager->assigned_user_ids) ? $requestmanager->assigned_user_ids : array();
            foreach ($requestmanager->assigned_user_ids as $user_id) { $assigned_user_names[] = self::_getUserName($user_id); }
            $assigned_usergroup_names = array();
            $requestmanager->assigned_usergroup_ids = is_array($requestmanager->assigned_usergroup_ids) ? $requestmanager->assigned_usergroup_ids : array();
            foreach ($requestmanager->assigned_usergroup_ids as $usergroup_id) { $assigned_usergroup_names[] = self::_getUserGroupName($usergroup_id); }
            $requester_names = array();
            $requestmanager->requester_ids = is_array($requestmanager->requester_ids) ? $requestmanager->requester_ids : array();
            foreach ($requestmanager->requester_ids as $contact_id) { $requester_names[] = self::_getContactName($contact_id); }
            $watcher_names = array();
            $requestmanager->watcher_ids = is_array($requestmanager->watcher_ids) ? $requestmanager->watcher_ids : array();
            foreach ($requestmanager->watcher_ids as $contact_id) { $watcher_names[] = self::_getContactName($contact_id); }

            $url = dol_buildpath('/requestmanager/card.php', 2) . '?id='.$requestmanager->id;

            $vars = array(
                // Request
                '__REQUEST_ID__'                        => $requestmanager->id,
                '__REQUEST_REF__'                       => $requestmanager->ref,
                '__REQUEST_REF_EXT__'                   => $requestmanager->ref_ext,
                '__REQUEST_PARENT_ID__'                 => $requestmanager->fk_parent > 0 ? $requestmanager->fk_parent : '',
                '__REQUEST_PARENT_REF__'                => $requestmanager->fk_parent > 0 ? $requestmanager->parent->ref : '',
                '__REQUEST_PARENT_REF_EXT__'            => $requestmanager->fk_parent > 0 ? $requestmanager->parent->ref_ext : '',
                '__REQUEST_TYPE_ID__'                   => $requestmanager->fk_type,
                '__REQUEST_TYPE_CODE__'                 => $requestmanager->getLibType(1),
                '__REQUEST_TYPE_LABEL__'                => $requestmanager->getLibType(0),
                '__REQUEST_CATEGORY_ID__'               => $requestmanager->fk_category,
                '__REQUEST_CATEGORY_CODE__'             => $requestmanager->getLibCategory(1),
                '__REQUEST_CATEGORY_LABEL__'            => $requestmanager->getLibCategory(0),
                '__REQUEST_TITLE__'                     => $requestmanager->label,
                '__REQUEST_ORIGIN_COMPANY_ID__'         => $requestmanager->socid_origin > 0 ? $requestmanager->socid_origin : '',
                '__REQUEST_ORIGIN_COMPANY_NAME__'       => $requestmanager->socid_origin > 0 ? $requestmanager->thirdparty_origin->getFullName($langs) : '',
                '__REQUEST_PRINCIPAL_COMPANY_ID__'      => $requestmanager->socid > 0 ? $requestmanager->socid : '',
                '__REQUEST_PRINCIPAL_COMPANY_NAME__'    => $requestmanager->socid > 0 ? $requestmanager->thirdparty->getFullName($langs) : '',
                '__REQUEST_BENEFACTOR_COMPANY_ID__'     => $requestmanager->socid_benefactor > 0 ? $requestmanager->socid_benefactor : '',
                '__REQUEST_BENEFACTOR_COMPANY_NAME__'   => $requestmanager->socid_benefactor > 0 ? $requestmanager->thirdparty_benefactor->getFullName($langs) : '',
                '__REQUEST_SOURCE_ID__'                 => $requestmanager->fk_source,
                '__REQUEST_SOURCE_CODE__'               => $requestmanager->getLibSource(1),
                '__REQUEST_SOURCE_LABEL__'              => $requestmanager->getLibSource(0),
                '__REQUEST_URGENCY_ID__'                => $requestmanager->fk_urgency,
                '__REQUEST_URGENCY_CODE__'              => $requestmanager->getLibUrgency(1),
                '__REQUEST_URGENCY_LABEL__'             => $requestmanager->getLibUrgency(0),
                '__REQUEST_IMPACT_ID__'                 => $requestmanager->fk_impact,
                '__REQUEST_IMPACT_CODE__'               => $requestmanager->getLibImpact(1),
                '__REQUEST_IMPACT_LABEL__'              => $requestmanager->getLibImpact(0),
                '__REQUEST_PRIORITY_ID__'               => $requestmanager->fk_priority,
                '__REQUEST_PRIORITY_CODE__'             => $requestmanager->getLibPriority(1),
                '__REQUEST_PRIORITY_LABEL__'            => $requestmanager->getLibPriority(0),
                '__REQUEST_DURATION__'                  => $requestmanager->duration > 0 ? requestmanager_print_duration($requestmanager->duration) : '',
                '__REQUEST_DATE_OPERATION__'            => $requestmanager->date_operation > 0 ? dol_print_date($requestmanager->date_operation, 'dayhour') : '',
                '__REQUEST_DATE_DEADLINE__'             => $requestmanager->date_deadline > 0 ? dol_print_date($requestmanager->date_deadline, 'dayhour') : '',
                '__REQUEST_STATUS_NUM__'                => $requestmanager->statut > 0 ? $requestmanager->statut : $requestmanager->oldcopy->statut,
                '__REQUEST_STATUS_LABEL__'              => $requestmanager->statut > 0 ? $requestmanager->getLibStatut(0) : $requestmanager->oldcopy->getLibStatut(0),
                '__REQUEST_STATUS_TYPE_NUM__'           => $requestmanager->statut_type > 0 ? $requestmanager->statut_type : $requestmanager->oldcopy->statut_type,
                '__REQUEST_STATUS_TYPE_LABEL__'         => $requestmanager->statut > 0 ? $requestmanager->getLibStatut(12) : $requestmanager->oldcopy->getLibStatut(12),
                '__REQUEST_REASON_RESOLUTION_CODE__'    => $requestmanager->getLibReasonResolution(1),
                '__REQUEST_REASON_RESOLUTION_LABEL__'   => $requestmanager->getLibReasonResolution(0),
                '__REQUEST_REASON_RESOLUTION_DETAILS__' => $requestmanager->reason_resolution_details,
                '__REQUEST_TAGS__'                      => $langs->trans('RequestManagerRequestTags'),
                '__REQUEST_DESCRIPTION__'               => $requestmanager->description,
                '__REQUEST_NOTIFY_ASSIGNED_BY_EMAIL__'  => yn($requestmanager->notify_assigned_by_email),
                '__REQUEST_ASSIGNED_USER_IDS__'         => implode(', ', $requestmanager->assigned_user_ids),
                '__REQUEST_ASSIGNED_USER_NAMES__'       => implode(', ', $assigned_user_names),
                '__REQUEST_ASSIGNED_USERGROUP_IDS__'    => implode(', ', $requestmanager->assigned_usergroup_ids),
                '__REQUEST_ASSIGNED_USERGROUP_NAMES__'  => implode(', ', $assigned_usergroup_names),
                '__REQUEST_NOTIFY_REQUESTER_BY_EMAIL__' => yn($requestmanager->notify_requester_by_email),
                '__REQUEST_REQUESTER_IDS__'             => implode(', ', $requestmanager->requester_ids),
                '__REQUEST_REQUESTER_NAMES__'           => implode(', ', $requester_names),
                '__REQUEST_NOTIFY_WATCHERS_BY_EMAIL__'  => yn($requestmanager->notify_watcher_by_email),
                '__REQUEST_WATCHER_IDS__'               => implode(', ', $requestmanager->watcher_ids),
                '__REQUEST_WATCHER_NAMES__'             => implode(', ', $watcher_names),
                '__REQUEST_DATE_CREATED__'              => $requestmanager->date_creation > 0 ? dol_print_date($requestmanager->date_creation, 'dayhour') : '',
                '__REQUEST_DATE_MODIFIED__'             => $requestmanager->date_modification > 0 ? dol_print_date($requestmanager->date_modification, 'dayhour') : '',
                '__REQUEST_DATE_RESOLVED__'             => $requestmanager->date_resolved > 0 ? dol_print_date($requestmanager->date_resolved, 'dayhour') : '',
                '__REQUEST_DATE_CLOSED__'               => $requestmanager->date_cloture > 0 ? dol_print_date($requestmanager->date_cloture, 'dayhour') : '',
                '__REQUEST_USER_CREATED_ID__'           => $requestmanager->user_creation_id > 0 ? $requestmanager->user_creation_id : '',
                '__REQUEST_USER_CREATED_NAME__'         => $requestmanager->user_creation_id > 0 ? self::_getUserName($requestmanager->user_creation_id) : '',
                '__REQUEST_USER_MODIFIED_ID__'          => $requestmanager->user_modification_id > 0 ? $requestmanager->user_modification_id : '',
                '__REQUEST_USER_MODIFIED_NAME__'        => $requestmanager->user_modification_id > 0 ? self::_getUserName($requestmanager->user_modification_id) : '',
                '__REQUEST_USER_RESOLVED_ID__'          => $requestmanager->user_resolved_id > 0 ? $requestmanager->user_resolved_id : '',
                '__REQUEST_USER_RESOLVED_NAME__'        => $requestmanager->user_resolved_id > 0 ? self::_getUserName($requestmanager->user_resolved_id) : '',
                '__REQUEST_USER_CLOSED_ID__'            => $requestmanager->user_cloture_id > 0 ? $requestmanager->user_cloture_id : '',
                '__REQUEST_USER_CLOSED_NAME__'          => $requestmanager->user_cloture_id > 0 ? self::_getUserName($requestmanager->user_cloture_id) : '',
                '__REQUEST_URL__'                       => '<a href="'.$url.'">'.$url.'</a>',
            );

            // Create dynamic tags for __EXTRAFIELD_FIELD__
            $requestmanager->fetch_optionals();
            if (is_array($requestmanager->array_options)) {
                foreach ($requestmanager->array_options as $key => $val) {
                    $keyshort = preg_replace('/^options_/', '', $key);
                    $vars['__REQUEST_EXTRA_' . strtoupper($keyshort) . '__'] = $val;
                }
            }

            if ($alsofornotify) {
                $assigned_user_added_names = array();
                $requestmanager->assigned_user_added_ids = is_array($requestmanager->assigned_user_added_ids) ? $requestmanager->assigned_user_added_ids : array();
                foreach ($requestmanager->assigned_user_added_ids as $user_id) { $assigned_user_added_names[] = self::_getUserName($user_id); }
                $assigned_user_deleted_names = array();
                $requestmanager->assigned_user_deleted_ids = is_array($requestmanager->assigned_user_deleted_ids) ? $requestmanager->assigned_user_deleted_ids : array();
                foreach ($requestmanager->assigned_user_deleted_ids as $user_id) { $assigned_user_deleted_names[] = self::_getUserName($user_id); }
                $assigned_usergroup_added_names = array();
                $requestmanager->assigned_usergroup_added_ids = is_array($requestmanager->assigned_usergroup_added_ids) ? $requestmanager->assigned_usergroup_added_ids : array();
                foreach ($requestmanager->assigned_usergroup_added_ids as $usergroup_id) { $assigned_usergroup_added_names[] = self::_getUserGroupName($usergroup_id); }
                $assigned_usergroup_deleted_names = array();
                $requestmanager->assigned_usergroup_deleted_ids = is_array($requestmanager->assigned_usergroup_deleted_ids) ? $requestmanager->assigned_usergroup_deleted_ids : array();
                foreach ($requestmanager->assigned_usergroup_deleted_ids as $usergroup_id) { $assigned_usergroup_deleted_names[] = self::_getUserGroupName($usergroup_id); }

                $vars = array_merge($vars, array(
                    '__REQUEST_OLD_STATUS_NUM__'                    => $requestmanager->statut > 0 ? $requestmanager->oldcopy->statut : '',
                    '__REQUEST_OLD_STATUS_LABEL__'                  => $requestmanager->statut > 0 ? $requestmanager->oldcopy->getLibStatut(0) : '',
                    '__REQUEST_OLD_STATUS_TYPE_NUM__'               => $requestmanager->statut_type > 0 ? $requestmanager->oldcopy->statut_type : '',
                    '__REQUEST_OLD_STATUS_TYPE_LABEL__'             => $requestmanager->new_statut > 0 ? $requestmanager->oldcopy->getLibStatut(12) : '',
                    '__REQUEST_ASSIGNED_USERS_ID_ADDED__'           => implode(', ', $requestmanager->assigned_user_added_ids),
                    '__REQUEST_ASSIGNED_USERS_NAME_ADDED__'         => implode(', ', $assigned_user_added_names),
                    '__REQUEST_ASSIGNED_USERS_ID_DELETED__'         => implode(', ', $requestmanager->assigned_user_deleted_ids),
                    '__REQUEST_ASSIGNED_USERS_NAME_DELETED__'       => implode(', ', $assigned_user_deleted_names),
                    '__REQUEST_ASSIGNED_USERGROUPS_ID_ADDED__'      => implode(', ', $requestmanager->assigned_usergroup_added_ids),
                    '__REQUEST_ASSIGNED_USERGROUPS_NAME_ADDED__'    => implode(', ', $assigned_usergroup_added_names),
                    '__REQUEST_ASSIGNED_USERGROUPS_ID_DELETED__'    => implode(', ', $requestmanager->assigned_usergroup_deleted_ids),
                    '__REQUEST_ASSIGNED_USERGROUPS_NAME_DELETED__'  => implode(', ', $assigned_usergroup_deleted_names),
                ));
            }
        }

        if ($addgeneral) {
            $tmparray = getCommonSubstitutionArray($langs, $keyonly, array('objectamount'));
            $vars = array_merge($vars, $tmparray);
        }
        self::_complete_substitutions_array($vars, $langs, $requestmanager, array('alsofornotify' => $alsofornotify, 'keyonly' => $keyonly), 'requestmanager_completesubstitutionarray');

        return $vars;
    }
    /**
     *  Get list of substitutes keys available for RequestManagerMessage object.
	 *  This include the complete_substitutions_array and the getCommonSubstitutionArray().
	 *
     * @param   DoliDB                      $db                         Database handler
     * @param   int                         $keyonly                    Return only key, dont load infos
     * @param	RequestManagerMessage	    $requestmanagermessage		RequestManagerMessage object
	 * @return	array                                                   Array of substitutes values for RequestManagerMessage object.
	 */
	static function getAvailableSubstitutesKeyFromRequestMessage($db, $keyonly=1, &$requestmanagermessage=null)
    {
        global $langs;

        if (!isset($requestmanagermessage)) {
            dol_include_once('/requestmanager/class/requestmanagermessage.class.php');
            $requestmanagermessage = new RequestManagerMessage($db);
        }

        if ($keyonly) {
            $vars = array(
                // Request message
                '__REQUEST_MESSAGE_ID__'                            => $langs->trans('RequestManagerRequestMessageId'),
                '__REQUEST_MESSAGE_TYPE_NUM__'                      => $langs->trans('RequestManagerRequestMessageTypeNum'),
                '__REQUEST_MESSAGE_TYPE_LABEL__'                    => $langs->trans('RequestManagerRequestMessageTypeLabel'),
                '__REQUEST_MESSAGE_NOTIFY_ASSIGNED__'               => $langs->trans('RequestManagerRequestMessageNotifyAssigned'),
                '__REQUEST_MESSAGE_NOTIFY_REQUESTER__'              => $langs->trans('RequestManagerRequestMessageNotifyRequester'),
                '__REQUEST_MESSAGE_NOTIFY_WATCHERS__'               => $langs->trans('RequestManagerRequestMessageNotifyWatchers'),
                '__REQUEST_MESSAGE_ATTACHED_FILES__'                => $langs->trans('RequestManagerRequestMessageAttachedFiles'),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_IDS__'            => $langs->trans('RequestManagerRequestMessageKnowledgeBaseIds'),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_CODES__'          => $langs->trans('RequestManagerRequestMessageKnowledgeBaseCodes'),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_TITLES__'         => $langs->trans('RequestManagerRequestMessageKnowledgeBaseTitles'),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_CODE_TITLES__'    => $langs->trans('RequestManagerRequestMessageKnowledgeBaseCodeTitles'),
                '__REQUEST_MESSAGE_SUBJECT__'                       => $langs->trans('RequestManagerRequestMessageSubject'),
                '__REQUEST_MESSAGE_CONTENT__'                       => $langs->trans('RequestManagerRequestMessageContent'),
            );

            // Create dynamic tags for __EXTRAFIELD_FIELD__
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($db);
            $extralabels = $extrafields->fetch_name_optionals_label($requestmanagermessage->table_element, true);
            foreach ($extrafields->attribute_label as $key => $val) {
                $vars['__REQUEST_MESSAGE_EXTRA_' . strtoupper($key) . '__'] = $val;
            }
        } elseif (is_object($requestmanagermessage)) {
            $requestmanagermessage->fetch_message_type();
            $requestmanagermessage->fetch_knowledge_base(1);

            $knowledge_base_codes = array();
            $knowledge_base_titles = array();
            $knowledge_base_code_titles = array();
            $requestmanagermessage->knowledge_base_list = is_array($requestmanagermessage->knowledge_base_list) ? $requestmanagermessage->knowledge_base_list : array();
            foreach ($requestmanagermessage->knowledge_base_list as $knowledge_base) {
                $knowledge_base_codes[] = $knowledge_base->fields['code'];
                $knowledge_base_titles[] = $knowledge_base->fields['title'];
                $knowledge_base_code_titles[] = $knowledge_base->fields['code'] . ' - ' . $knowledge_base->fields['title'];
            }

            $vars = array(
                // Request message
                '__REQUEST_MESSAGE_ID__'                            => $requestmanagermessage->id,
                '__REQUEST_MESSAGE_TYPE_NUM__'                      => $requestmanagermessage->message_type,
                '__REQUEST_MESSAGE_TYPE_LABEL__'                    => $requestmanagermessage->getMessageType(),
                '__REQUEST_MESSAGE_NOTIFY_ASSIGNED__'               => yn($requestmanagermessage->notify_assigned),
                '__REQUEST_MESSAGE_NOTIFY_REQUESTER__'              => yn($requestmanagermessage->notify_requesters),
                '__REQUEST_MESSAGE_NOTIFY_WATCHERS__'               => yn($requestmanagermessage->notify_watchers),
                '__REQUEST_MESSAGE_ATTACHED_FILES__'                => !isset($requestmanagermessage->attached_files['names']) ? implode(', ', $requestmanagermessage->attached_files['names']) : '',
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_IDS__'            => implode(', ', $requestmanagermessage->knowledge_base_ids),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_CODES__'          => implode(', ', $knowledge_base_codes),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_TITLES__'         => implode(', ', $knowledge_base_titles),
                '__REQUEST_MESSAGE_KNOWLEDGE_BASE_CODE_TITLES__'    => implode(', ', $knowledge_base_code_titles),
                '__REQUEST_MESSAGE_SUBJECT__'                       => $requestmanagermessage->label,
                '__REQUEST_MESSAGE_CONTENT__'                       => $requestmanagermessage->note,
            );

            // Create dynamic tags for __EXTRAFIELD_FIELD__
            $requestmanagermessage->fetch_optionals();
            if (is_array($requestmanagermessage->array_options)) {
                foreach ($requestmanagermessage->array_options as $key => $val) {
                    $keyshort = preg_replace('/^options_/', '', $key);
                    $vars['__REQUEST_MESSAGE_EXTRA_' . strtoupper($keyshort) . '__'] = $val;
                }
            }
        }

        self::_complete_substitutions_array($vars, $langs, $requestmanagermessage, array('keyonly' => $keyonly), 'requestmanager_completesubstitutionarray');

        return $vars;
    }

    /**
     *  Complete the $substitutionarray with more entries.
     *  Can also add substitution keys coming from external module that had set the "substitutions=1" into module_part array. In this case, method completesubstitutionarray provided by module is called.
     *
     * @param   array		$substitutionarray		Array substitution old value => new value value
     * @param   Translate	$outputlangs            Output language
     * @param   Object		$object                 Source object
     * @param   mixed		$parameters       		Add more parameters (useful to pass product lines)
     * @param   string      $callfunc               What is the name of the custom function that will be called? (default: completesubstitutionarray)
     * @return	void
     */
    private static function _complete_substitutions_array(&$substitutionarray, $outputlangs, $object=null, $parameters=null, $callfunc="completesubstitutionarray")
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        // Check if there is external substitution to do, requested by plugins
        $dirsubstitutions = array_merge(array(), (array)$conf->modules_parts['substitutions']);

        foreach ($dirsubstitutions as $reldir) {
            $dir = dol_buildpath($reldir, 0);

            // Check if directory exists
            if (!dol_is_dir($dir)) continue;

            $substitfiles = dol_dir_list($dir, 'files', 0, 'functions_');
            foreach ($substitfiles as $substitfile) {
                if (preg_match('/functions_(.*)\.lib\.php/i', $substitfile['name'], $reg)) {
                    $module = $reg[1];

                    dol_syslog("Library functions_" . $substitfile['name'] . " found into " . $dir);
                    // Include the user's functions file
                    require_once $dir . $substitfile['name'];
                    // Call the user's function, and only if it is defined
                    $function_name = $module . "_" . $callfunc;
                    if (function_exists($function_name)) $function_name($substitutionarray, $outputlangs, $object, $parameters);
                }
            }
        }
    }

    /**
     *  Get user name
     *
     * @param   int     $user_id    User ID
     * @return  string              User name
     */
    private static function _getUserName($user_id) {
        global $db, $langs;

        if (!isset(self::$users_cache[$user_id])) {
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
            $user = new User($db);
            $user->fetch($user_id);
            self::$users_cache[$user_id] = $user->getFullName($langs);
        }

        return self::$users_cache[$user_id];
    }

    /**
     *  Get user group name
     *
     * @param   int     $usergroup_id       User group ID
     * @return  string                      User group name
     */
    private static function _getUserGroupName($usergroup_id) {
        global $db;

        if (!isset(self::$usergroups_cache[$usergroup_id])) {
            require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
            $usergroup = new UserGroup($db);
            $usergroup->fetch($usergroup_id);
            self::$usergroups_cache[$usergroup_id] = $usergroup->name;
        }

        return self::$usergroups_cache[$usergroup_id];
    }

    /**
     *  Get contact name
     *
     * @param   int     $contact_id     Contact ID
     * @return  string                  Contact name
     */
    private static function _getContactName($contact_id) {
        global $db, $langs;

        if (!isset(self::$contact_cache[$contact_id])) {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            $contact = new Contact($db);
            $contact->fetch($contact_id);
            self::$contact_cache[$contact_id] = $contact->getFullName($langs);
        }

        return self::$contact_cache[$contact_id];
    }
}
