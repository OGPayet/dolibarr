-- ===================================================================
-- Copyright (C) 2012-2017 Charlene Benke <charlie@patas-monkey.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- ===================================================================

create table llx_equipementconsumption
(
	rowid					integer AUTO_INCREMENT PRIMARY KEY,
	fk_equipement			integer,				-- equipement associé à la consommation produit
	fk_product				integer NOT NULL,		-- produit consommé par l'équipement
	fk_equipementcons		integer,				-- numéro d'équipement du produit
	fk_entrepot				integer,				-- entrepot où a été sortie le produit consommé
	fk_equipementevt		integer,				-- evenement associé à la consommation produit
	fk_equipementconsevt	integer,				-- evenement associé à l'équipement consommé
	datecons				datetime,				-- date de la consommation
	description				text,					-- description de la ligne de consommation
	fk_user_author			integer,				-- createur de la consommation
	tms						timestamp,
	qty						double(24,8)	DEFAULT 1,	-- quantité de produit affecté, si supérieur à 1, pas possible de l'associé à un équipement
	price					double(24,8)	DEFAULT 0,	-- prix unitaire du produit consommé au moment de la consommation
	pmp						double(24,8)	DEFAULT 0,	-- pmp unitaire du produit consommé
	import_key 				VARCHAR( 14 )	NULL DEFAULT NULL
)ENGINE=innodb;
