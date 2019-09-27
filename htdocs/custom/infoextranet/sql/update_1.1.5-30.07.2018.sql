-- Copyright (C) 2018 SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.

-- Update element of COMPUTING contact
UPDATE llx_c_type_contact SET element = 'contrat' WHERE llx_c_type_contact.rowid = 448020;

-- Drop unused table
DROP TABLE llx_infoextranet_myobject;
DROP TABLE llx_infoextranet_myobject_extrafields;

-- Delete old extrafields
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42P_dest_backup_in';
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42P_dir_backup_in';
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42P_nb_backup';
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42M_maintenance';
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42R_contract_site';
DELETE FROM llx_extrafields WHERE llx_extrafields.name = 'c42R_vpn_ssl';
