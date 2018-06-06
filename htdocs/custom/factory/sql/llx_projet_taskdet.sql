-- ===================================================================
-- Copyright (C) 2014		Charles-Fr BENKE		<charles.fr@benke.fr>
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
-- ===================================================================

create table llx_projet_taskdet
(
	`rowid`			int(11) NOT NULL AUTO_INCREMENT,
	`fk_task`		int(11) NOT NULL DEFAULT '0',
	`fk_product` 	int(11) NOT NULL DEFAULT '0',
	`qty_planned`	double DEFAULT NULL,			-- quantité par produit prévu d'etre utilisé
	`qty_used` 		double DEFAULT NULL,			-- quantité par produit utilisé
	`qty_deleted` 	double DEFAULT NULL,			-- quantité par produit detruit
	tms				timestamp,
	`pmp`			double(24,8) DEFAULT 0,
	`price`			double(24,8) DEFAULT 0,
	`fk_statut`		int(11) NOT NULL DEFAULT '0', 	-- état de consommation du produit  (0 prévue, 1 commandé, 2 en cours d'utilisation, 3 terminé)
	`note_public`	text,
	PRIMARY KEY (`rowid`),
	UNIQUE KEY `uk_projet_taskdet` (`fk_task`, `fk_product`)
)ENGINE=innodb;

--
-- List of codes for special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
-- 3 : produit/service propose en option
--
