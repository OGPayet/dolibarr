<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */

dol_include_once('/cron/class/cronjob.class.php');


class CronInstall
	extends Cronjob {


	public function install($user, $cronjobs = array()){


		foreach($cronjobs as $job){

			$this->initAsSpecimen();

			$this->datec=dol_now(); ;
			$this->label=(isset($job['label'])? $job['label'] : '' );
			$this->jobtype=(isset($job['jobtype'])? $job['jobtype'] : '' );
			$this->command=(isset($job['command'])? $job['command'] : '' );
			$this->classesname=(isset($job['classesname'])? $job['classesname'] : '' );
			$this->objectname=(isset($job['objectname'])? $job['objectname'] : '' );
			$this->methodename=(isset($job['methodename'])? $job['methodename'] : '' );
			$this->params=(isset($job['params'])? $job['params'] : '' );
			$this->md5params=(isset($job['params'])? md5($job['params']) : '' );
			$this->module_name=(isset($job['module_name'])? $job['module_name'] : '' );
			$this->priority=(isset($job['priority'])? $job['priority'] : 0 );
			$this->dateend='';
			$this->datestart=dol_mktime(date('H'), date('i'), 0, date('m'), date('d'),date('Y'));
			$this->datelastresult='';
			$this->lastoutput='';
			$this->lastresult='';
			$this->unitfrequency=(isset($job['unitfrequency'])? $job['unitfrequency'] : 60 );
			$this->frequency=$this->unitfrequency * (isset($job['frequency'])? $job['frequency'] : 1);
			$this->status=1;
			$this->fk_user_author=$user->id;
			$this->fk_user_mod='';
			$this->note=(isset($job['note'])? $job['note'] : '' );
			$this->nbrun='';
			$this->libname = '';


			$this->create($user) ;

		}
	}

	public function remove($user, $cronjobs = array()){

		$error = 0;

		foreach($cronjobs as $job){
			//		{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cronjob";
		$sql.= " WHERE 1 ";


		if(isset($job['jobtype']) )
					$sql.= " AND jobtype='".$job['jobtype']."'";

				if(isset($job['command']) )
					$sql.= " AND command='".$job['command']."'";

				if(isset($job['classesname']) )
					$sql.= " AND classesname='".$job['classesname']."'";

				if(isset($job['objectname']) )
					$sql.= " AND objectname='".$job['objectname']."'";

				if(isset($job['methodename']) )
					$sql.= " AND methodename='".$job['methodename']."'";

				if(isset($job['module_name']) )
					$sql.= " AND module_name='".$job['module_name']."'";
				else
					return -1;




				$resql = $this->db->query($sql);
				if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }


		}

		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}

	}



}


?>