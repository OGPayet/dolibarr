-- ============================================================================
-- Copyright (C) 2020   Alexis LAURIER      <alexis@alexislaurier.fr>
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

ALTER TABLE llx_digitalsignaturemanager_digitalsignaturerequest ADD invitation_message TEXT;
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturerequest ADD is_staled_according_to_source_object Boolean;
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturerequest ADD last_update_from_provider date;
ALTER TABLE llx_digitalsignaturemanager_digitalsignaturedocument ADD fk_ecm_signed integer;
ALTER TABLE llx_societe_rib CHANGE `label` `label` VARCHAR(1000);
