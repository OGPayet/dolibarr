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
-- ===========================================================================

ALTER TABLE llx_requestmanager_message_knowledge_base ADD position integer NOT NULL DEFAULT 0; -- position

ALTER TABLE llx_requestmanager_message_knowledge_base DROP FOREIGN KEY fk_requestmanager_mkb_fk_actioncomm;
ALTER TABLE llx_requestmanager_message_knowledge_base DROP FOREIGN KEY fk_requestmanager_mkb_fk_knowledge_base;
ALTER TABLE llx_requestmanager_message_knowledge_base DROP INDEX uk_requestmanager_mkb;

ALTER TABLE llx_requestmanager_message_knowledge_base ADD UNIQUE INDEX uk_requestmanager_mkb (fk_actioncomm, position, fk_knowledge_base);
ALTER TABLE llx_requestmanager_message_knowledge_base ADD CONSTRAINT fk_requestmanager_mkb_fk_actioncomm      FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);
ALTER TABLE llx_requestmanager_message_knowledge_base ADD CONSTRAINT fk_requestmanager_mkb_fk_knowledge_base  FOREIGN KEY (fk_knowledge_base) REFERENCES llx_c_requestmanager_knowledge_base (rowid);
