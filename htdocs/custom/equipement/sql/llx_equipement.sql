-- ===================================================================
-- Copyright (C) 2012-2014 Charles-Fr Benke <charles.fr@benke.fr>
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

create table llx_equipement
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_product		integer DEFAULT 0,			-- produit auquel est rattache l'équipement
  fk_product_batch	integer DEFAULT 0,			-- numéro de batch/lot du produit auquel est rattache l'équipement
  fk_soc_fourn		integer DEFAULT 0,			-- fournisseur de l'équipement
  fk_soc_client		integer DEFAULT 0,			-- client propriétaire de l'équipement
  ref				varchar(60) NOT NULL,		-- numéro de série interne de l'équipement ou du lot (si lot = numversion + compteur si découpé)
  numimmocompta		text,						-- numéro d'immobilisation comptable
  numversion		varchar(30) NULL,			-- version du produit du fournisseur (si gestion par lot)
  fk_commande_fourn	integer DEFAULT 0,			-- clé de la facture d'achat au fournisseur
  fk_facture_fourn	integer DEFAULT 0,			-- clé de la facture d'achat au fournisseur
  fk_facture		integer DEFAULT 0,			-- clé de la facture de vente au client
  fk_entrepot		integer DEFAULT 0,			-- lieu de stockage de l'équipement
  quantity			integer DEFAULT 1 NOT NULL,	-- NEW : quantité unitaire de l'équipement (par défaut à 1, > 0 si lot)
  price				double(24,8),				-- NEW : prix de vente HT de l'équipement
  pmp				double(24,8),				-- NEW : prix d'achat HT de l'équipement
  unitweight		double(24,8),				-- NEW : poid de l'équipement
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  tms				timestamp,
  datec				datetime,					-- date de création de l'équipement
  datev				datetime,					-- date de passage à l'état "validé" de l'équipement
  dateo				datetime,					-- date de début de vie de l'équipement
  datee				datetime,					-- date de fin de vie de l'équipement
  dated				datetime NULL DEFAULT  NULL,	-- date de dluo de l'équipement (si activé)
  fk_user_author	integer NULL DEFAULT  NULL,					-- createur de la fiche
  fk_user_valid		integer NULL DEFAULT  NULL,					-- valideur de la fiche
  fk_user_modif		integer NULL DEFAULT  NULL,					-- modifieur de la fiche
  fk_statut			smallint  DEFAULT 0,		-- statut de l'équipement
  fk_etatequipement integer,					-- état de l'équipement
  localisation		text,						-- localisation de l'équipement chez le client
  description		text,
  note_private		text,
  note_public		text,
  model_pdf			varchar(255),
  import_key 		VARCHAR( 14 ) NULL DEFAULT NULL,
  extraparams		varchar(255)				-- for stock other parameters with json format
)ENGINE=innodb;
