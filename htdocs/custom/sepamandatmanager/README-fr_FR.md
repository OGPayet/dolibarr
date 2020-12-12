# SEPAMANDATMANAGER FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

Ce module permet de gérer de manière efficace la vie de plusieurs mandats SEPA sur un tiers et leur cadre d'utilisation

<!--
![Screenshot sepamandatmanager](img/screenshot_sepamandatmanager.png?raw=true "SepaMandatManager"){imgmd}
-->

## Traductions

Les traductions peuvent être modifiées manuellement en editant les fichiers dans le dossier langs, ou via un paramétrage dans Configuration/Traduction.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/digitalsignaturemanager```

```sh
cd ....../custom
git clone git@github.com:gitlogin/digitalsignaturemanager.git digitalsignaturemanager
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Code source

Code sous license GPL v3 ou toutes versions plus récente de cette dernière. Consultez le fichier COPYING pour plus d'information.

### Documentation

Tout le texte et les Lisez-moi sont sous license GFDL.
