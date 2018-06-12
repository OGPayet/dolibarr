-- --------------------------------------------------------

--
-- Structure de la table `llx_projet_taskdet_equipement`
--
-- Contient le lien entre les equipements et la fabrication projet

CREATE TABLE IF NOT EXISTS `llx_projet_taskdet_equipement` (
  `fk_equipement` int(11) NOT NULL DEFAULT '0',
  `fk_projet_taskdet` 	int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `uk_factory_equipement` (`fk_equipement`,`fk_projet_taskdet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;
