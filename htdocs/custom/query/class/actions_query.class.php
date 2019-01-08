<?php
class ActionsQuery
{
	/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param      parameters  meta datas of the hook (context, etc...)
	 *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *  @param      action             current action (if set). Generally create or edit or null
	 *  @return       void
	 */

	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $user;

		if(($parameters['currentcontext'] == 'projectcard'
		|| $parameters['currentcontext'] == 'productcard'
		|| $parameters['currentcontext'] == 'thirdpartycard'
		|| $parameters['currentcontext'] == 'usercard')
		&& $action == '' && !empty($user->rights->query->dashboard->viewin)) {

			define('INC_FROM_DOLIBARR',true);
			dol_include_once('/query/config.php');
			dol_include_once('/query/class/dashboard.class.php');
			dol_include_once('/query/class/query.class.php');

			$PDOdb=new TPDOdb;
			$TDash = TQDashBoard::getDashboard($PDOdb, $parameters['currentcontext'],$user->id);

			foreach($TDash as $uid) {
				$url = dol_buildpath('/query/dashboard.php?action=run&allow_gen=1&uid='.$uid.'&table_element='.$object->table_element.'&fk_object='.$object->id,1);

				?>
				<tr>
					<td colspan="2">
						<iframe src="<?php echo $url ?>" width="100%" frameborder="0" onload="this.height = this.contentWindow.document.body.scrollHeight + 'px'"></iframe>
					</td>
				</tr>
				<?php

			}

		}
	}

	function addStatisticLine($parameters, &$object, &$action, $hookmanager)
	{
		global $langs,$db, $user, $conf,$query_just_after_login;

		if (in_array('index',explode(':',$parameters['context'])) && !empty($conf->global->QUERY_HOME_SELECTOR))
		{

			$sql="SELECT qd.uid as 'uid', qd.title ,qd.use_as_landing_page,qd.rowid
				FROM ".MAIN_DB_PREFIX."qdashboard qd
				WHERE uid<>'' ";

			if($user->admin) {
				null;
			}
			else {
				$sql.=" AND (qd.fk_user_author=".$user->id." OR  qd.fk_usergroup IN (SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_user=".$user->id." ) )";
			}

			$langs->load('query@query');

			$TDashboard=array();
			$res = $db->query($sql);
			if (!$res) {
				setEventMessages(array($db->lasterror),'','errors');
			} else {
				while($obj = $db->fetch_object($res)) {

					if($obj->use_as_landing_page == 1 && !empty($query_just_after_login)) {

						?>
						<script type="text/javascript">
						document.location.href="<?php echo dol_buildpath('/query/dashboard.php?action=run&id='.$obj->rowid,1) ?>";
						</script>
						<?php

					}

					$TDashboard[] = $obj;
				}
			}



			?>
			<script type="text/javascript">
				$(document).ready(function() {

					$select = $('<div class="box"><table class="noborder boxtable" width="100%" summary="Query"><tr class="liste_titre"><th class="liste_titre"><?php echo $langs->trans('QueryDashBoard'); ?> <select name="qdashboardList"><option value=""> </option><?php
						foreach($TDashboard as &$obj) {
							echo '<option value="'.$obj->uid.'">'.strtr($obj->title,array("'"=>"\'")).'</option>';
						}
					?></select></th></tr><tr><td class="impair"><div id="queryDashboardview"></div></td></tr></table></div>');

					$select.change(function() {

						var uid = $(this).find(":selected").val();
						$('#queryDashboardview').empty();
						console.log(uid);
						if(uid!='') {
							var url="<?php echo dol_buildpath('/query/dashboard.php',1) ?>?action=run&allow_gen=1&storechoice=1&fk_user=<?php echo $user->id ?>&uid="+uid;
							$('#queryDashboardview').html('<iframe src="'+url+'" width="100%" frameborder="0" onload="this.height = this.contentWindow.document.body.scrollHeight + \'px\'"></iframe>');
						}
					});

					<?php
					if(DOL_VERSION >= 6.0) {
						?> $('.fichehalfleft .box').first().after($select);<?php
					}
					elseif(DOL_VERSION>=4.0) {
						?> $('#left').before($select);<?php
					}
					else{
						?> $('table#otherboxes').before($select); <?php
					}
					?>


					<?php
					if(!empty($conf->global->{'QUERY_HOME_DEFAULT_DASHBOARD_USER_'.$user->id} )) {
						?>
						$select.val("<?php echo $conf->global->{'QUERY_HOME_DEFAULT_DASHBOARD_USER_'.$user->id}; ?>");
						$select.change();
						<?php
					}
					?>


				});

			</script>
			<?php
		}

		return 0;
	}

}
