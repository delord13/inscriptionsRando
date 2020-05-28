<?php
// mettreAJourInscriptions.php
/*
Les inscriptions en attente sont mises à jour automatiquement au moment ou une place devient disponible sauf dans un cas : un adhérent sanctionné dont l'inscription est en attente ne se verra pas attribué de place si l'évènement se produit plus de 2 jours avant la sortie.
La mise à jour des inscriptions en attente se fait automatiquement à chaque connexion à l'application mais on ne peut pas être sûr qu'une connexion aura lieu dans les 2 jours qui précèdent la sortie.
Pour palier cette difficulté, on peut mettre en place une des 2 solutions suivantes :
1. Si l'on accès aux tâches planifiées du serveur, on peut définir une définir une tâche programmée tous les jours à 1 heure du matin  qui consistera exécuter le script "mettreAJourInscriptions.php".
(paramètres du crontab : 00 01 * * *)
Cette solution est idéale mais peu d'hébergements mutualisés offre l'accès aux tâches planifiées du serveur.
2. Pour pallier cette difficulté, vous pouvez faire en sorte que la mise à jour soit lancée chaque fois que vous ouvrez votre propre navigateur internet : il suffit de configurer la page d'accueil de votre navigateur avec l'adresse suivante : <adresse de l'application>mettreAJourInscriptions.php?accueil=<adresse de la page d'accueil que vous souhaitez voir à l'écran exemple> ; par exemple : 
https://istresrando.fr/inscriptionsRando/mettreAJourInscriptions.php?accueil=https://google.fr
Quand vous ouvrez votre navigateur, la mise à jour des inscriptions sera lancée (sans que rien ne s'affiche) puis la page d'accueil que vous souhaitez réellement (ici https://google.fr) s'ouvrira dans votre navigateur.
*/

	include('init.inc.php');
	mettreAJourInscriptions();

	if (isset($_GET['accueil'])) {
		header("Location: {$_GET['accueil']}");
		exit;
	}
?>
