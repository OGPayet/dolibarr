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
-- ===========================================================================

ALTER TABLE llx_event_agenda DROP COLUMN externe;
ALTER TABLE llx_event_agenda CHANGE COLUMN fk_object fk_actioncomm integer NOT NULL;
ALTER TABLE llx_event_agenda CHANGE COLUMN fk_dict_tag_confid fk_c_eventconfidentiality_tag integer NOT NULL;
ALTER TABLE llx_event_agenda CHANGE COLUMN level_confid mode integer(1);
ALTER TABLE llx_event_agenda RENAME llx_eventconfidentiality_mode;

DELETE llx_eventconfidentiality_mode FROM llx_eventconfidentiality_mode LEFT JOIN llx_actioncomm ON llx_actioncomm.id = llx_eventconfidentiality_mode.fk_actioncomm WHERE llx_actioncomm.id IS NULL;

ALTER TABLE llx_eventconfidentiality_mode ADD UNIQUE INDEX uk_eventconfidentiality_mode (fk_actioncomm, fk_c_eventconfidentiality_tag);

ALTER TABLE llx_eventconfidentiality_mode ADD INDEX idx_ecm_fk_actioncomm (fk_actioncomm);
ALTER TABLE llx_eventconfidentiality_mode ADD INDEX idx_ecm_fk_c_eventconfidentiality_tag (fk_c_eventconfidentiality_tag);

ALTER TABLE llx_eventconfidentiality_mode ADD CONSTRAINT fk_ecm_fk_actioncomm   FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id);
ALTER TABLE llx_eventconfidentiality_mode ADD CONSTRAINT fk_ecm_fk_cec_tag      FOREIGN KEY (fk_c_eventconfidentiality_tag) REFERENCES llx_c_eventconfidentiality_tag (rowid);
