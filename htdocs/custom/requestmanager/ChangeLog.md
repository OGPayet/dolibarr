# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [4.0.24] - 09-03-2019
- Allègement des vérification sur les modifications des données d'en-tête
- Allègement des blocages informatiques sur le choix des statut possible, rendant possible le croisement de statut de demande de type différent
- Ajout d'une option permettant de retirer le blocage de la demande parente en cas de création d'une ou plusieur demande fille. Ce paramétrage sur chaque statut permet de définir un point d'entrée et un point de sortie différent au niveau de la demande mère entre la création et la clôture de la demande fille.
- Correction de l'enregistrement de la date de la derniere fois ou l'utilisateur a regarder ses demandes suivis
- Correction de la modification des assignés si l'on en selectionne aucuns
- Correction d'affichage des boutons des statuts précedants et suivants

## [4.0.22] - 31-01-2019
- Ajout de la planification des demandes
- Peut changer le texte des 'Utilisateur(s) en charge pour les demandes de type "xxx"'  en définisant la traduction sur la balise "RequestManagerPlanningUserGroupsInChargeLabel_(Code du type de demande)"

## [4.0.21] - 29-01-2019
- Correction "error 500" sur l'écran de création rapide des demandes si type de demande non renseigné
- Correction lors de l'enregistrement du motif de résolution de la demande
- Correction de l'enregistrement du message d'une demande lorsqu'il y avais du code HTML dedans
- Correction de la prise en compte des minute des dates d'opération et d'échéance lors de sa modification sur la fiche de la demande
- Correction de l'affichage des demandes encours liés au tiers choisis sur la page de création rapide
- Correction lors de la suppression d'une demande comportant des demandes filles
- Correction de la copie des lignes d'une demande mère lors de la création d'une demande fille
- Correction des liens de création d'une commandes, devis, ... pour le passage du bénéficiaire
- Ajout de la selection automatique de la source lors de la selection d'un événement non traité sur la page de création rapide des demandes
- Rajout des info-bulle de "retard" (date d'échéance) sur la liste des demande et la fiche d'une demande

## [4.0.20] - 16-01-2019
- Correction du changement des dates operation et echeance lors d'une creation ou modification d'un evenement (seulement ceux qui ne sont pas automatiques)

## [4.0.19] - 31-12-2018
- Ajout de l'observateur

## [4.0.18] - 19-12-2018
- Correction lors de la creation d'une demande lors d'un forcage du donneur d'ordre en dehors de la plage horaire.
- Correction affichage de l'URL dans les courriels avec la variable de substitution "\_\_REQUEST_URL__".
- Ajout sur le dictionnaire des statuts des options suivantes :
  - Pour fermer tous les événements lié à la demande au passage à ce statut.
  - Pour que, lors de la création ou modification d'un événement lié à une demande et l'événements a la date de début la plus lointaine, la date d'opération soit égale à la date de début de l'événement et la date d'échéance (optionnel) soit recalculer ou soit égale à la date de fin de l'événement

## [4.0.17] - 04-12-2018
- Filtre pat tags/catégories sur la liste.
- Ajout d'un onglet sur le tiers affichant la liste de ses demandes (origine, donneur d'ordre, bénéficiaire).
- Ajout du bouton "creer demande" sur la fiche tiers "client" et "fournisseur".
- Ajout du motif de résolution.
- Ajout de nouveaux bouton "créer" sur la demande.
- Le module 'Company Relationships' est maintenant optionnel.
- Correction calcule de la date d'échéance au passage d'un statut selon les paramètres renseignés pour celui-ci.
- Correctionde l'affichage des tags de confidentialités externes sur la liste des événements de la demande.

## [4.0.16] - 27-11-2018
- Correction de l'ajout d'un message comportant une URL (Erreur message vide).
- Idem pour la création d'une demande avec la description.
- Cacher l'attribut complémentaire IPBX.
- Corrections textes.
- Correction sur le filtre des types d'événements sur la liste des événements d'une demande.
- Correction des informations sur l'evenement de changement de statut
- Ajout des boutons de modification et suppression des evenements sur la liste des evenements d'une demande.
- Correction de l'ajout de message (+ ajout des fichiers joints) par l'API
- Correction mineurs.

## [4.0.15] - 12-11-2018
- Correction API Call.
- Correction prise en compte des options de notification des messages.
- Ne notifie pas aussi l'utilisateur connecté sur une demande ou il est assigné.

## [4.0.14] - 09-11-2018
- Ajout d'un droit sur les statuts (utilisateur et groupe autorisés).
- Ajout d'un trigger pour passer à ce statut.
- Ajout de la permettre de n'avoir qu'un seul type de demande fille sans création automatique.
- Ajout d'une option de passage au statut suivant automatiquement.

## [4.0.14] - 08-11-2018
- Ajout du mode liste (chronologique) sur une demande et de la compatibilité avec le module EventConfidentiality pour les mode de vue chronologique 
- Ajout du retour sur la demande après la création d'un evenement depuis celle-ci
- Ajout du sujet dans le formulaire du message
- Ajout du sujet dans les modele de messages
- Ajout des variable de substitution : sujet du message et url de la demande
- Ajout de l'activation/désactivation d'element du formulaire en fonction du choix du type de message
- Correction affichage du filtre sur les statuts dans les listes de demandes
- Correction du filtrage des assignés dans la liste de suivie des demandes
- Correction du nombre de demandes affecter à soi ou ses groupe sur le lien rapide du suivie des demandes
- Correction API (CallBegin, et CallEnd)
- Correction lors de la creation d'un evenement.
- Correction lors de la suppression d'une demande.

## [4.0.13] - 05-11-2018
- Ajout des champs de filtres et corrections sur la page de suivie des demandes.
- Ajout du filtre sur les statuts dans la liste des demandes et du suivie des demandes.
- Troncage des champs affichés dans les listes et affichage complet au survole.
- Correction rappatriment des objects liés lors de la création d'une demande depuis une autre fiche (propale, ...).
- Un statut peut désormet se choisir lui-même dans le paramétrage.
- Corrections diverses.

## [4.0.12] - 31-10-2018
- Modification de l'API pour les appels IPBX.
- Ajout du statut des cantrats sur la page de creation des demandes.
- Corrections diverses.

## [4.0.11] - 30-10-2018
- Ajout de l'affiche des messages d'une demande sous forme chronologique.
- Corrections diverses.

## [4.0.10] - 29-10-2018
- Ajout support module Event Confidentiality.
- Corrections diverses.

## [4.0.9] - 25-10-2018
- Corrections diverses.

## [4.0.8] - 22-10-2018
- Ajout de la gestion des plages horaires.

## [4.0.7] - 19-10-2018
- Ajout notification de creation d'une demande.
- Corrections et refonte des appels IPBX sur l'API.
- Corrections diverses.

## [4.0.6] - 17-10-2018
- Ajout d'un chromomètre qui début dès l'affichage de la page de création d'une demande.
- Corrections diverses.

## [4.0.5] - 16-10-2018
- Ajout de la gestion des signatures pour l'envoi des notifications.

## [4.0.4] - 16-10-2018
- Corrections et améliorations diverses.

## [4.0.0] - 16-07-2018
- Version initial.

[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/compare/v4.0.20...HEAD
[4.0.20]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.20
[4.0.18]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.18
[4.0.15]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.15
[4.0.14]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.14
[4.0.13]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.13
[4.0.12]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.12
[4.0.11]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.11
[4.0.10]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.10
[4.0.9]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.9
[4.0.8]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.8
[4.0.7]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.7
[4.0.6]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.6
[4.0.5]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.5
[4.0.4]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.4
[4.0.0]: http://git.open-dsi.fr/dolibarr-extension/requestmanager/commits/v4.0.0
