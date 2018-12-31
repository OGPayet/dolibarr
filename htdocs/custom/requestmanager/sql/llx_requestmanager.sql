-- ============================================================================
-- Copyright (C) 2018	 Open-DSI 	 <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
-- ============================================================================

create table llx_requestmanager
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,

  ref                       varchar(30) NOT NULL,		        -- ref of the request
  ref_ext                   varchar(30) NOT NULL,		        -- ref external of the request
  fk_soc_origin             integer NOT NULL,		            -- id of the thirdparty origin
  fk_soc                    integer NOT NULL,		            -- id of the thirdparty bill
  fk_soc_benefactor         integer NOT NULL,		            -- id of the thirdparty benefactor
  fk_soc_watcher            integer,		                    -- id of the thirdparty watcher

  label                     varchar(255) NOT NULL,		      -- label of the request
  description               text NOT NULL,		              -- description of the request

  fk_type                   integer NOT NULL,		            -- id of the request type
  fk_category               integer,		                    -- id of the request category
  fk_source                 integer,		                    -- id of the request source
  fk_urgency                integer,		                    -- id of the request urgency
  fk_impact                 integer,		                    -- id of the request impact
  fk_priority               integer,		                    -- id of the request priority
  notify_requester_by_email integer(1),                     -- Notify requester by email
  notify_watcher_by_email   integer(1),                     -- Notify watcher by email
  notify_assigned_by_email  integer(1),                     -- Notify assigned by email
  duration                  integer DEFAULT 0 NOT NULL,      -- duration of the request

  date_operation  	        datetime,						            -- date operation
  date_deadline			        datetime,						            -- date deadline
  date_resolved			        datetime,						            -- date resolved
  date_closed				        datetime,						            -- date closed
  fk_user_resolved	        integer, 						            -- user who resolved the request
  fk_user_closed		        integer, 						            -- user who created the request

  fk_status                 integer NOT NULL,	              -- id of the request status
  entity				            integer DEFAULT 1 NOT NULL,	    -- multi company id
  datec					            datetime NOT NULL,						  -- date creation
  tms					              timestamp,						          -- date creation/modification
  fk_user_author		        integer NOT NULL, 			        -- user who created the request
  fk_user_modif			        integer,						            -- user who modified the request

  total_ht				          double(24,8) DEFAULT 0,	    		-- montant total ht apres remise globale
  tva					              double(24,8) DEFAULT 0,		    	-- montant total tva apres remise globale
  localtax1				          double(24,8) DEFAULT 0,		    	-- amount total localtax1
  localtax2				          double(24,8) DEFAULT 0,		    	-- amount total localtax2
  total_ttc					        double(24,8) DEFAULT 0,			    -- montant total ttc apres remise globale
  fk_multicurrency			    integer,
  multicurrency_code			  varchar(255),
  multicurrency_tx			    double(24,8) DEFAULT 1,
  multicurrency_total_ht		double(24,8) DEFAULT 0,
  multicurrency_total_tva	  double(24,8) DEFAULT 0,
  multicurrency_total_ttc	  double(24,8) DEFAULT 0
)ENGINE=innodb;
