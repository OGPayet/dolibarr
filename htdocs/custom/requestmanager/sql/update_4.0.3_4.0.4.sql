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
--
-- ===========================================================================

ALTER TABLE llx_requestmanager_message DROP INDEX idx_requestmanager_message_fk_knowledge_base;
ALTER TABLE llx_requestmanager_message DROP COLUMN fk_knowledge_base;

ALTER TABLE llx_requestmanager_message ADD CONSTRAINT fk_requestmanager_m_fk_actioncomm FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);

ALTER TABLE llx_requestmanager_message ADD notify_assigned    integer(1) NULL; -- notify assigned
ALTER TABLE llx_requestmanager_message ADD notify_requesters  integer(1) NULL; -- notify requesters
ALTER TABLE llx_requestmanager_message ADD notify_watchers    integer(1) NULL; -- notify watchers
