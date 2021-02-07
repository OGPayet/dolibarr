# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [7.0.19] - xx-12-2020
- Ajout d'un contrôle planifié de vérification des données en lien avec l'API Sirene
- Correction problème sur les établissements fermés

## [7.0.18] - 23-07-2020
- Ajout d'une option dans le panneau de configuration pour choisir le lien de vérification du numéro siret (introduit dans Sirene 7.0.15)

## [7.0.17] - 06-07-2020
- Correction problème calcul numéro TVA intracommunautaire dans de rare cas (FRXX < 10)

## [7.0.16] - 01-07-2020
- Remplissage automatique du code du département

## [7.0.15] - 29-06-2020
- Modification de l'url de verification du numéro de SIRET

## [7.0.14] - 22-06-2020
- Ne pas tenir compte du Code NAF pour les pays hors France

## [7.0.13] - 16-04-2020
- Modification affichage bloc recherche répertoire SIRENE lors de la création du tiers pour les petits écrans.
- Compatibilité v12

## [7.0.12] - 12-02-2020
- Clarifie la recherche du code naf dans le service Sirene.
- Ajout d'une option pour n'afficher que les tiers qui n'ont jamais fermés

## [7.0.11] - 13-01-2020
- Fix "include vendor/autoload.php" avec d'autres modules.

## [7.0.10] - 26-11-2019
- Correction récupération des identifiants des dictionnaires (compatibilité PHP v7.2)

## [7.0.9] - 06-11-2019
- Correction du non chargement de la fiche tiers lors de la présence d'un code NAF non présent dans le dictionnaire des codes NAF
- Correction de l'accès au dictionnaire des codes NAF

## [7.0.8] - 21-10-2019
- Ajout du calcul du numéro de TVA intracommunautaire (Basé sur le numéro Siren)

## [7.0.7] - 07-10-2019
- Correction du chemin d'un include pour une fonction nécessitant la librairie codenaf. (Compatibilité v10)

## [7.0.6] - 30-09-2019
- Correction de la pérénité des valeures du formulaire de création du tiers à l'affichage du choix du tiers retourné par Sirene.

## [7.0.5] - 05-09-2019
- Fusion avec le module Code Naf.
- Corrections mineurs.

## [7.0.4] - 02-09-2019
- Correction de la gestion des erreurs lors de la requete a Sirene.

## [7.0.3] - 01-07-2019
- Correction de l'ajout des paramètres du formulaires lors de l'affichage de la boite de confirmation(affichage des resultats)
- Simplification du message d'erreur (avec option pour afficher l'erreur complète)

## [7.0.2] - 27-06-2019
- N'écrase les paramètres déjà renseignés

## [7.0.1] - 26-06-2019
- Sélectionne automatiquement la première société active trouvée

## [7.0.0] - 12-06-2019
- Version initial.

[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/sirene/compare/v7.0.19...HEAD
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.13
[7.0.12]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.12
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.6
[7.0.5]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.5
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.0
