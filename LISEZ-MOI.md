# Application "inscriptionsRando" #

L'application "inscriptionsRando" s'adresse aux clubs de rando qui veulent mettre en place un système d'inscriptions en ligne de leurs adhérents aux sorties du club dans la limite du nombre de places déterminé pour chaque sortie.

## Principales fonctionnalités ##
1. inscription en ligne pour les adhérents (possibilité d'annulation d'une inscription) : le maximum d'inscrits atteint, les inscriptions sont mises en attente avec gestion automatique de la file d'attente ; si une place est attribuée à un utilisateur en liste d'attente (suite à une annulation), il est automatiquement informé par courriel ; le paramétrage permet de fixer la date et l'heure limite d'inscription (par défaut : la veille à 18 heures) ;
2. consultation et impression de la liste des inscrits et des inscriptions en attente pour chaque sortie par l'animateur et les administrateurs ;
3. ajout de sorties avec possibilité d'annulation ; en cas d'annulation, les utilisateurs inscrits ou en attente sont automatiquement informés par courriel ;
4. enregistrement par l'animateur des absences et des éventuelles excuses ;
5. système optionnel de sanction des absents non excusés : les demandes d'inscription des adhérents sanctionnés sont mise en attente jusqu'à la veille de la fermeture des inscriptions ; ce système a pour but de décourager les adhérents qui s'inscrivent et qui ne viennent pas à la sortie et qui bloquent ainsi des places qui resterons inutilisées.

## 4 statuts d'utilisateur ##
1. adhérent
2. animateur
3. administrateur
4. super-administrateur (il est souhaitable qu'il n'y ait qu'un seul super-administrateur)

Chaque utilisateur s'identifie dans l'application en fournissant :
- un identifiant : son numéro de licence ;
- un mot de passe : le mot de passe adhérent ou le mot de passe animateur ou le mot de passe administrateur ou le mot de passe super-administrateur.

Lors de la première connexion, l'utilisateur est invité à indiquer son nom, son prénom, son adresse de courriel et son numéro de mobile. Il est alors automatiquement enregistré dans l'application.
Son statut est déterminé automatiquement en fonction du mot de passe utilisé.

Si l'utilisateur utilise un autre mot de passe lors d'une connexion ultérieure, le statut correspondant est automatiquement enregistré ainsi, par exemple, un utilisateur peut avoir à la fois le statut d'animateur et le satut d'administrateur.

## Droits des utilisateurs ##
1. adhérent :  s'inscrire et annuler une inscription antérieure ;
2. animateur : les droits de l'adhérent plus : consulter et imprimer la liste des inscrits à une des sorties ; enregistrer les absences et les éventuelles excuses ;
3. administrateur : les droits de l'animateur plus : ajouter, modifier, annuler une sortie ; modifier les attributs des adhérents inscrits dans l'application (sauf ceux du super-administrateur) ; ajouter et supprimer des adhérents ; télécharger la totalité des inscriptions sous forme d'un fichier au format csv que l'on peut ouvrir dans un tableur ;
4. super-administrateur : les droits de l'administrateur plus le droit de modifier les paramètres de l'application et d'attribuer (ou de retirer) le statut de super-administrateur.

## Modification des droits des utilisateurs ##
Le paramétrage de l'application permet de donner aux adhérent la possibilité de consulter et d'imprimer la liste des inscrits (cela a l'inconvenient de diffuser les adresses de courriels et les numéros de téléphone des adhérents à tous les adhérents mais cela peut faciliter l'organsiation du covoiturage)

## Paramétrage de l'application ##
Le paramétrage de l'application est réservé au super-administrateur.

Voici la liste des paramètres :

- mot de passe des adhérents (à communiquer aux adhérents)
- mot de passe des animateurs (à communiquer aux seuls animateurs)
- mot de passe du ou des administrateur(s) (à communiquer aux seuls administrateurs)
- mot de passe du super-administrateur (à conserver par le super-administrateur)
- nom du club
- logo du club : nom du fichier image logo du club ; le fichier doit être placé dans le répertoire images
- courriel du club : adresse de courriel du club qui sera l'expéditeur des courriels envoyés par l'application (annulation ou rétablissement de sortie, attribution d'une place à un adhérent en attente)
- courriel de l'administrateur : adresse courriel de l'administrateur qui recevra, en copie cachée, une copie de tous les courriels envoyés par l'application
- nombre de jours avant la sortie pour l'ouverture des inscriptions : (1 pour la veille, 2 pour l'avant-veille...')
- nombre de jours avant la sortie pour la fermeture des inscriptions :
- heure de fermeture des inscriptions le jour fixé : (ex : 18 pour 18 heures)
- durée de la sanction pour absence non excusée en nombre de jours : (ex : 30 pour 30 jours ; 0 pour ne pas utiliser de système de sanction) ; la sanction consiste à n'examiner la demande d'inscription que l'avant-veille de la sortie
- nombre maximum d'animateurs déclarés pour une sortie
- les adhérents peuvent accéder à la liste des participants aux sorties : 1 pour oui; 0 pour non

## Traitement automatique des inscriptions en attente et système de sanction ##
Les inscriptions en attente sont mises à jour automatiquement au moment ou une place devient disponible sauf dans un cas : un adhérent sanctionné dont l'inscription est en attente ne se verra pas attribué de place si l'évènement se produit plus de 2 jours avant la sortie.
La mise à jour des inscriptions en attente se fait automatiquement à chaque connexion à l'application mais on ne peut pas être sûr qu'une connexion aura lieu dans les 2 jours qui précèdent la sortie.
Pour palier cette difficulté, on peut mettre en place une des 2 solutions suivantes :
1. Si l'on accès aux tâches planifiées du serveur, on peut définir une définir une tâche programmée tous les jours à 1 heure du matin  qui consistera exécuter le script "mettreAJourInscriptions.php".
(paramètres du crontab : 00 01 * * *)
Cette solution est idéale mais peu d'hébergements mutualisés offre l'accès aux tâches planifiées du serveur.
2. Pour pallier cette difficulté, vous pouvez faire en sorte que la mise à jour soit lancée chaque fois que vous ouvrez votre propre navigateur internet : il suffit de configurer la page d'accueil de votre navigateur avec l'adresse suivante : <adresse de l'application>mettreAJourInscriptions.php?accueil=<adresse de la page d'accueil que vous souhaitez voir à l'écran exemple> ; par exemple : 
https://istresrando.fr/inscriptionsRando/mettreAJourInscriptions.php?accueil=https://google.fr
Quand vous ouvrez votre navigateur, la mise à jour des inscriptions sera lancée (sans que rien ne s'affiche) puis la page d'accueil que vous souhaitez réellement (ici https://google.fr) s'ouvrira dans votre navigateur.


## Installation de l'application ##
Pour pouvoir installer l'application, il faut disposer :
- un hébergement Web permettant d'utiliser le langage php (version 7.0 ou plus)
- au moins une base de données MySQL ou MariaDB (la base peut être partagée avec d'autres applications)
- un moyen de déposer des fichiers sur cet hébergement (accès FTP ou gestionnaire de fichier en ligne du compte d'hébergement)

La procédure d'installation est la suivante :
1. déposer dans un répertoire de l'hébergement les fichiers obtenus après avoir décompressé le fichier zip : inscriptionsRando.zip ;
2. recueillir les informations sur la base de données partagée ou créée pour l'application :
- nom d'hôte (serveur MySQL)
- nom de la base de données
- nom de l'utilisateur qui a les droits sur la base de données
- mot de passe de cet utilisateur
3. choisir un préfixe pour les noms de tables de l'application (indispensable en cas de base de données partagée afin d'éviter d'écraser d'autres tables appartenant à d'autres applications ;
4. lancer le script initialiser.php et fournir les informations demandées concernant la base de données ; à la fin de cette étape, les tables nécessaires à l'application sont créées dans la base de données ; attention ! les éventuelles tables portant le même nom sont écrasées et leur contenu perdu ; le script initialiser.php est automatiquement effacé pour éviter une réinitialisation accidentelle de l'application (il est souhaitable d'en conserver une copie pour pouvoir recommencer l'initialisation si nécessaire) ;
5. à la fin de l'initialisation, un formulaire est proposé automatiquement : il permet de paramétrer l'application ;
6. le paramétrage se termine avec la génération automatique du fichier param.inc.ph qui contient les paramètres définis ; on peut alors se connecter sur la page d'accueil de l'application et s'enregistrer comme super-administrteur.

## Licence ##
Copyright Michel Delord 26/05/2020 logiciel libre sous licence CeCILL compatible avec la licence GNU GPL ; la licence est consultable à l'adresse : 
https://cecill.info/licences/Licence_CeCILL_V2.1-fr.html 

En ce qui concerne la garantie, votre attention est attirée en particulier sur l'article 9.3 de la licence :
> Le Licencié reconnaît que le Logiciel est fourni "en l'état" par le Concédant sans autre garantie, expresse ou tacite, que celle prévue à l'article 9.2 et notamment sans aucune garantie sur sa valeur commerciale, son caractère sécurisé, innovant ou pertinent.
> En particulier, le Concédant ne garantit pas que le Logiciel est exempt d'erreur, qu'il fonctionnera sans interruption, qu'il sera compatible avec l'équipement du Licencié et sa  configuration logicielle ni qu'il remplira les besoins du Licencié.

## Téléchargement ##
L'application est disponible sur GitHub
1. allez à l'adresse : https://github.com/delord13/inscriptionsRando
2. cliquez sur le bouton vert : "Clone or download"
3. cliquez sur : Download ZIP

## Contact ##
Adresse de contact : inscriptions.rando@free.fr
à utiliser pour :
- me signaler un problème
- s'inscrire pour être informé des mises à jour de l'application
