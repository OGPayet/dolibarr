# Module Propalautosend

Effectue les relances des propositions commerciales.

## Config

  - Montant minimal pour les relances
  - Associer le PDF aux relances
  - Sujet mail
  - Texte de relance pour le tiers de la proposition si aucun "Contact client suivi proposition"
  - Texte de relance pour le "Contact client suivi proposition"
  - Définir automatiquement la date de relance à la date du jour + x jours si non renseigné (sur validate de la proposition) (vide ou 0 pour désactiver)

## Trigger validation propale

Lors de la validation d'une propale, si l'extrafield `date_relance` est vide alors sa valeur est calculée suivant le nombre de jour défini dans la config.

## Tâche CRON

Un script est à lancer en tâche CRON pour effectuer les relances.

 Le script recherche les propales avec une date de relance du jour et un total HT supérieur à celui défini dans la config.
 Pour chaque propale un mail est envoyé aux contacts de la propale (code `CUSTOMER`) ou à l'adresse email du tiers s'il n'y a pas de contact.