-- ===================================================================
-- Copyright (C) 2014-2016 Charlie Benke <charlie@patas-monkey.com>
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

create table llx_facture_fourn_ventil
(
 	rowid				integer AUTO_INCREMENT PRIMARY KEY,
 	entity				integer DEFAULT 1 NOT NULL,	-- multi company id
	fk_facture_fourn	integer DEFAULT 0,			-- facture fournisseur de départ
	fk_socid_fourn		integer,					-- tiers fournisseur de départ
	fk_facture_link		integer,					-- facture client ou fournisseur ou factory ventilé
	fk_socid_link		integer,					-- tiers client ou fournisseur ventilé
	fk_facture_typelink	integer,					-- 0 = facture fournisseur, 1 = facture client, 2 = factory
	tms					timestamp,
	datev				datetime,					-- date de ventilation
	qty					real,						-- Quantity (exemple 2)
	subprice			double(24,8),				-- P.U. HT (exemple 100)
	total_ht			double(24,8),				-- Total HT de la ligne
	total_ttc			double(24,8),				-- Total TTC de la ligne
	total_tva			double(24,8),				-- Total TVA de la ligne
	tva_tx				double(6,3),				-- Taux tva produit/service (exemple 19.6)
	label				varchar(255) DEFAULT NULL
)ENGINE=innodb;
