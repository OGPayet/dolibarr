MailChimp connector
=========

This module will allow to send mail and mass mailling througt MailChimp

Licence
-------
GPLv3 any later version.

See COPYING file in the root of this module for more information.

INSTALL
-------

Check in english :
http://wiki.dolibarr.org/index.php/FAQ_ModuleCustomDirectory

A lire  en français :
http://wiki.dolibarr.org/index.php/FAQ_Repertoire_Custom_Module_Externe

- From your browser, log in as administrator dolibarr
  and left click on the "configuration" menu.
  Then click on the submenu "module".
  On the screen that appears and you whouls be able to see the new module (check all tabs, ban be in other than first one)
  The status menu should then proceed to "Enable" menu "MailChimp" should appear at the top of the application.
- Check the security right (Users->permitions) to be sure that right are correctly set for user or users group

MANUAL FR
-------
1 - Sur une nouvelle campagne dolibarr dans le corps du message vous copier coller la partie "champs de remplacement" sans faire de texte ou de mise en forme
2 - vous cibler vos destinataire avec les selecteur dolibarr ou avec un module tel "selecteur de destinataire avancer", puis dans l'onglet mailchimp vous "synchroniser" ces destinataires dolibarr avec une list/segment mailchimp (vérifier dans mailchimp, normalement vos list/segment se sont mis a jour a partir des informations de dolibarr)
3- Remplissez les champ obligatoire dans l'onglet mailchimp.
4 - Une fois la campagne dolibarr Valider, dans l'onglet mailchimp, vous avez le bouton Crée la campagne mailchimp
5- Allez dans mailchimp sur la campagne crée a partir de dolibarr et faite votre design a partir des templates ( ou comme vous le voulez)
6 - Retourner dans dolibarr sur votre campagne et dans l'onglet mailchimp cliquer sur "envoyer la campagne"
7 - Une fois la campagne envoyer sur dolibarr sur votre campagne et dans l'onglet mailchimp cliquer sur le boutton "mettre a jour les status". Ceci va récupérer la template que vous avez crée sur mailchimp dans la campagne dolibarr, ainsi que les status des destinataire.


Other Licences
--------------
Uses Michel Fortin's PHP Markdown Licensed under BSD to display this README.
MailChimp API Licensed under MIT licence to connect Dolibarr to MailChimp
