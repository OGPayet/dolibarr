/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */
/**
 * Author:  Oscss Shop <support@oscss-shop.fr>
 * Created: 10 mai 2018
 */

ALTER TABLE `llx_contrat_extrafields`
ADD    `formule` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `preventivequotas` int(10) NOT NULL,
ADD    `correctivequota` int(10) NOT NULL,
ADD    `distantoptimisationquota` int(10) NOT NULL,
ADD    `localoptimisationquota` int(10) NOT NULL,
ADD    `formationquota` int(10) NOT NULL,
ADD    `initialvalue` double NOT NULL,
ADD    `initalequipmentvalue` double(24,8) DEFAULT NULL,
ADD    `equipmentvalue` double DEFAULT NULL,
ADD    `signaturedate` date NOT NULL,
ADD    `startdate` date NOT NULL,
ADD    `duration` int(10) NOT NULL,
ADD    `tacitagreement` int(1) NOT NULL DEFAULT '1',
ADD    `terminateddelay` int(10) NOT NULL,
ADD    `invoicetype` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `invoicingregime` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `invoicedates` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `reindexmethod` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `revalorisationactivationdate` date DEFAULT NULL,
ADD    `revalorisationperiod` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `newindicemonth` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `oldindicemonth` text COLLATE utf8_unicode_ci NOT NULL,
ADD    `fixedamount` double(24,8) DEFAULT '0.00000000',
ADD    `prohibitdecrease` int(1) NOT NULL,
ADD    `renewalcredit` int(10) NOT NULL;


INSERT INTO `llx_extrafields` ( `name`, `entity`, `elementtype`, `tms`, `label`, `type`, `size`, `fieldcomputed`, `fielddefault`, `fieldunique`, `fieldrequired`, `perms`, `pos`, `alwayseditable`, `param`, `list`, `langs`, `ishidden`) VALUES
( 'generalites', 1, 'contrat', '2018-05-02 12:27:27', 'Generalités', 'separate', '', NULL, NULL, 0, 0, NULL, 0, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'formule', 1, 'contrat', '2018-05-02 12:27:37', 'Formule', 'select', '', NULL, NULL, 0, 1, NULL, 1, 1, 'a:1:{s:7:"options";a:6:{i:1;s:9:"Contrat B";i:2;s:11:"Essentielle";i:3;s:8:"Optimale";i:4;s:8:"Initiale";i:5;s:7:"Confort";i:6;s:9:"Intégral";}}', 0, NULL, 0),
( 'preventivequotas', 1, 'contrat', '2018-05-02 12:32:06', 'Nombre d''interventions Préventive', 'int', '10', NULL, NULL, 0, 1, NULL, 1000, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'revalorisation_reindexation', 1, 'contrat', '2018-05-02 12:35:00', 'Revalorisation et réindexation', 'separate', '', NULL, NULL, 0, 0, NULL, 100, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'correctivequota', 1, 'contrat', '2018-05-02 13:48:42', 'Nombre d''interventions Corrective', 'int', '10', NULL, NULL, 0, 1, NULL, 1000, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'formationquota', 1, 'contrat', '2018-05-02 13:49:03', 'Nombre de Formations', 'int', '10', NULL, NULL, 0, 1, NULL, 1000, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'localoptimisationquota', 1, 'contrat', '2018-05-02 13:49:39', 'Nombre d''optimisations sur site', 'int', '10', NULL, NULL, 0, 1, NULL, 1000, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'distantoptimisationquota', 1, 'contrat', '2018-05-02 13:50:01', 'Nombre d''optimisations distante', 'int', '10', NULL, NULL, 0, 1, NULL, 1000, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'initialvalue', 1, 'contrat', '2018-05-09 06:58:31', 'Montant du contrat', 'double', '24,8', NULL, NULL, 0, 1, NULL, 2, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'initalequipmentvalue', 1, 'contrat', '2018-05-09 06:59:29', 'Valeur d’installation à la signature', 'double', '24,8', NULL, NULL, 0, 0, NULL, 3, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'equipmentvalue', 1, 'contrat', '2018-05-09 07:00:19', 'Valeur actuelle de l’installation', 'double', '24,8', NULL, NULL, 0, 0, NULL, 4, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'signaturedate', 1, 'contrat', '2018-05-09 07:00:50', 'Date de signature', 'date', '', NULL, NULL, 0, 1, NULL, 5, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'startdate', 1, 'contrat', '2018-05-09 07:01:19', 'Date d''effet', 'date', '', NULL, NULL, 0, 1, NULL, 6, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'tacitagreement', 1, 'contrat', '2018-05-09 07:02:19', 'Reconduction tacite', 'boolean', '', NULL, NULL, 0, 1, NULL, 8, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'duration', 1, 'contrat', '2018-05-09 07:02:26', 'Durée contractuelle en nombre de mois', 'int', '10', NULL, NULL, 0, 1, NULL, 7, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'terminateddelay', 1, 'contrat', '2018-05-09 07:03:29', 'Délais de résiliation en nombre de mois', 'int', '10', NULL, NULL, 0, 1, NULL, 9, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'facturation', 1, 'contrat', '2018-05-09 07:03:59', 'Facturation', 'separate', '', NULL, NULL, 0, 0, NULL, 50, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'invoicetype', 1, 'contrat', '2018-05-09 07:04:45', 'Facturation échue ou à échoir', 'select', '', NULL, NULL, 0, 1, NULL, 51, 1, 'a:1:{s:7:"options";a:2:{i:1;s:9:"A échoir";i:2;s:5:"échu";}}', 0, NULL, 0),
( 'invoicingregime', 1, 'contrat', '2018-05-09 07:06:38', 'Type de facturation', 'select', '', NULL, NULL, 0, 1, NULL, 52, 1, 'a:1:{s:7:"options";a:3:{i:1;s:19:"Facturation directe";i:2;s:34:"Facturation groupée - subrogation";i:3;s:19:"Apporteur d''affaire";}}', 0, NULL, 0),
( 'invoicedates', 1, 'contrat', '2018-05-09 07:07:37', 'Périodicité de la facturation', 'select', '', NULL, NULL, 0, 1, NULL, 53, 1, 'a:1:{s:7:"options";a:4:{i:1;s:7:"Mensuel";i:2;s:11:"Trimestriel";i:3;s:10:"Semestriel";i:4;s:6:"Annuel";}}', 0, NULL, 0),
( 'revalorisationactivationdate', 1, 'contrat', '2018-05-09 07:09:05', 'Activer la possibilité de revaloriser à partir du :', 'date', '', NULL, NULL, 0, 0, NULL, 102, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'reindexmethod', 1, 'contrat', '2018-05-09 07:09:13', 'Indice de revalorisation', 'select', '', NULL, NULL, 0, 1, NULL, 101, 1, 'a:1:{s:7:"options";a:3:{i:1;s:21:"Aucune Revalorisation";i:2;s:6:"Syntec";i:3;s:5:"Insee";}}', 0, NULL, 0),
( 'revalorisationperiod', 1, 'contrat', '2018-05-09 07:10:16', 'Date de revalorisation', 'select', '', NULL, NULL, 0, 1, NULL, 103, 1, 'a:1:{s:7:"options";a:5:{i:1;s:19:"A date anniversaire";i:2;s:11:"1er Janvier";i:3;s:9:"1er Avril";i:4;s:11:"1er Juillet";i:5;s:11:"1er Octobre";}}', 0, NULL, 0),
( 'newindicemonth', 1, 'contrat', '2018-05-09 07:11:53', 'Revalorisation l’aide de l’indice de', 'select', '', NULL, NULL, 0, 1, NULL, 104, 1, 'a:1:{s:7:"options";a:12:{i:1;s:7:"Janvier";i:2;s:8:"Février";i:3;s:4:"Mars";i:4;s:5:"Avril";i:5;s:3:"Mai";i:6;s:4:"Juin";i:7;s:7:"Juillet";i:8;s:4:"Aout";i:9;s:9:"Septembre";i:10;s:7:"Octobre";i:11;s:8:"Novembre";i:12;s:9:"Décembre";}}', 0, NULL, 0),
( 'oldindicemonth', 1, 'contrat', '2018-05-09 07:12:32', '1er indice à utiliser (ou dernier indice utilisé dans un calcul)', 'sellist', '', NULL, NULL, 0, 1, NULL, 105, 1, 'a:1:{s:7:"options";a:1:{s:56:"view_c_indice:label:rowid:options_reindexmethod|filter:1";N;}}', 0, NULL, 0),
( 'fixedamount', 1, 'contrat', '2018-05-09 07:13:18', 'Montant annuel fixe non revalorisable', 'double', '24,8', NULL, NULL, 0, 0, NULL, 106, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'prohibitdecrease', 1, 'contrat', '2018-05-09 07:14:01', 'Déflation non autorisée', 'boolean', '', NULL, NULL, 0, 1, NULL, 107, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'particulartietechnique', 1, 'contrat', '2018-05-09 07:14:25', 'Particularités technique', 'separate', '', NULL, NULL, 0, 0, NULL, 998, 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0),
( 'renewalcredit', 1, 'contrat', '2018-05-09 07:14:59', 'Durée entre 2 remises à zéro des crédits dépensé', 'int', '10', NULL, NULL, 0, 1, NULL, 999, 1, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 0, NULL, 0);


-- DISABLE CRON JOB

UPDATE `llx_cronjob` SET `status` = '0' WHERE `llx_cronjob`.`label` = 'RecurringInvoices';