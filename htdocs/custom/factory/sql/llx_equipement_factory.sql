-- --------------------------------------------------------

--
-- Structure de la table `llx_equipement_factory`
--
-- Contient le lien entre les equipements et factory

-- composant réservé 0 si fabriqué (permanent), 1 si composant (temporaire)
CREATE TABLE IF NOT EXISTS `llx_equipement_factory` (
  `fk_equipement` int(11) NOT NULL DEFAULT '0',
  `fk_factory` 	int(11) NOT NULL DEFAULT '0',
  `children` 	int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `uk_factory_equipement` (`fk_equipement`,`fk_factory`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;