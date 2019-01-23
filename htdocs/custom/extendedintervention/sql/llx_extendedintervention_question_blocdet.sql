-- ============================================================================
-- Copyright (C) 2018  Open-DSI    <support@open-dsi.fr>
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

create table llx_extendedintervention_question_blocdet
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  fk_question_bloc          integer NOT NULL,		            -- id of the question bloc

  fk_c_question             integer NOT NULL,		            -- id of the question line in intervention dictionary
  position_question         integer NOT NULL,		            -- position of the question
  code_question             varchar(16) NOT NULL,		        -- code of the question
  label_question            varchar(255) NOT NULL,		      -- label of the question
  extrafields_question      varchar(2000) NULL,		          -- extra fields of the question

  fk_c_answer               integer NULL,		            -- id of the answer line in intervention dictionary
  code_answer               varchar(16) NULL,		        -- code of the answer
  label_answer              varchar(255) NULL,		      -- label of the answer
  mandatory_answer          integer(1) NULL,		            -- justificatory mandatory of the answer
  text_answer               text NULL,		                  -- justificatory of the answer

  datec					            datetime NOT NULL,						  -- date creation
  tms					              timestamp,						          -- date creation/modification
  fk_user_author		        integer NOT NULL, 			        -- user who created
  fk_user_modif			        integer,						            -- user who modified
  import_key                varchar(14)      	              -- import key
)ENGINE=innodb;
