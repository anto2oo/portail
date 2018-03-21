<?php

/*
 * Liste des scopes TODO en fonction des routes
 *   - Définition des scopes:
 *     + u :     user_credential => nécessite que l'application soit connecté à un utilisateur
 *     + c :     client_credential => nécessite que l'application est les droits d'application indépendante d'un utilisateur
 *
 *   - Définition du verbe:
 *     + get :   récupération des informations en lecture seule
 *     + set :   posibilité d'écrire et modifier les données
 *     + create:   créer une donnée associée
 *     + edit:    modifier une donnée
 *     + remove:  supprimer une donnée
 *     + manage:  gestion de la ressource entière
 */
return [
  // Droits relatifs aux utilisateurs
  'c-get-users' => "Récupérer la liste des utilisateurs",
  'c-set-users' => "Gérer la création et la modification d'utilisateurs",
  'c-manage-users' => "Gestion des utilisateurs",
  'u-get-user' => "Récupérer les informations de l'utilisateur",
  'u-set-user' => "Modifier les information de l'utilisateur",
  'u-manage-user' => "Gérer les information de l'utilisateur",
  'u-get-user-assos-currently-done' => "Récupérer les associations actuellement faites par l'utilisateur",
  'u-get-user-assos-currently-followed' => "Récupérer les associations actuellement suivies par l'utilisateur",
  'u-get-user-assos-currently' => "Récupérer les associations actuellement suivies et faites par l'utilisateur",
  'u-get-user-assos-done' => "Récupérer les associations faites par l'utilisateur",
  'u-get-user-assos-followed' => "Récupérer les associations suivies par l'utilisateur",
  'u-get-user-assos' => "Récupérer les associations suivies et faites par l'utilisateur",
  'u-get-user-assos-members' => "Récupérer les membres des associations faites par l'utilisateur",
  'u-set-user-assos' => "Gérer la création et la modification des associations suivies et faites par l'utilisateur",

  // Droits relatifs aux assos
  'c-get-assos' => "Récupérer la liste des associations",
  'c-set-assos' => "Créer et modifier des associations",
  'c-manage-assos' => "Gestion des associations",
  'c-get-assos-currently-members-done' => "Récupérer les actuels membres des associations",
  'c-get-assos-currently-members-followed' => "Récupérer les actuels suiveurs des associations",
  'c-get-assos-currently-members' => "Récupérer les actuels membres et suiveurs des associations",
  'c-get-assos-members-done' => "Récupérer les membres des associations",
  'c-get-assos-members-followed' => "Récupérer les suiveurs des associations",
  'c-get-assos-members' => "Récupérer les membres et suiveurs des associations",

  // Surement d'autres à ajouter pour chaque section haha
]; 
