-- ============================================================================
-- Copyright (C) 2019	 Open-DSI 	 <support@open-dsi.fr>
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

create table llx_extendedintervention_survey_bloc
(
  rowid                       integer AUTO_INCREMENT PRIMARY KEY,

  fk_fichinter                integer NOT NULL,		            -- id of the intervention
  fk_equipment                integer NOT NULL,		            -- id of the equipment
  fk_product                  integer NULL,		                -- id of the product
  equipment_ref               varchar(255) NULL,		          -- ref of the equipment
  product_ref                 varchar(255) NULL,		          -- ref of the product
  product_label               varchar(255) NULL,		          -- label of the product

  entity				              integer DEFAULT 1 NOT NULL,	    -- multi company id
  datec					              datetime NOT NULL,						  -- date creation
  tms					                timestamp,						          -- date creation/modification
  fk_user_author		          integer NOT NULL, 			        -- user who created
  fk_user_modif			          integer,						            -- user who modified
  import_key                  varchar(14)      	              -- import key
)ENGINE=innodb;
