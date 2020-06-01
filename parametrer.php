<?php
// parametrer.php
// appelé à la fin d'initialiser.php
// et par gestion.php

	// session start
	session_start();

	$GLOBALS['logoClub'] = "logo-association-affilliee-FFR.png";

	// contrôle d'accès
	if (!isset($_SESSION['statut'])) die("Accès réservé au super-administrateur");
	else if ($_SESSION['statut']!=10) die("Accès réservé au super-administrateur");
	
	// si fichier initialiser.php : le détruire
//	if (file_exists('initialiser.php')) unlink('initialiser.php');
	
	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "enregistrer" :
				enregistrerParametre();
				break;
			case "terminer" :
				terminer();
				break;
		}
	}
	else {
		editerParametres();
	}
	
	function chargerParametres()	{
		// si param.inc.php existe le lire et l'inclure
		if (file_exists('param.inc.php')) include('param.inc.php');
		// sinon initialiser les variables paramètre
		else { //
			// mot de passe des adhérents
				$pwAdherent = "adherent";
				
			// mot de passe animateur (à communiquer aux animateurs)
				$pwAnimateur = "animateur";
				
			// mot de passe admin
				$pwAdministrateur = "admin";
				
			// mot de passe super-administrateur
				$pwSuperAdministrateur = "superadmin";

			// nom du club
				$nomClub = "Club";

			// logo du club
				$logoClub = "logo-association-affilliee-FFR.png"; // nom du fichier image logo du club ; le fichier doit être placé dans le répertoire images
				
			// adresse de courriel du club qui sera l'expéditeur des courriels envoyés par l'application (annulation ou rétablissement de sortie, attribution d'une place à un ashérent en attente)
				$courrielClub = "";
				
			// adresse courriel de l'administrateur qui recevra une copie de tous les courriels envoyés par l'application en copie cachée
				$courrielAdmin = "";
				
			// base de données MySql
				$host = $_SESSION['host']; // nom du serveur MySql
				$base = $_SESSION['base'];  // nom de la base mysql
				$user = $_SESSION['user'];  // nom de l'utilisateur mysql ayant les droits sur la base
				$passwd  = $_SESSION['passwd'];  // mot de passe de l'utilisateur de la base
				$prefixe = $_SESSION['prefixe']; // préfixe des noms de table de l'application : pour éviter d'écraser d'autres tables si la base de données contient des tables liées à d'autres application

			// inscriptions ouvertes n jours avant la date de la séance (0 pour ouverture immédiate)
				$ouvertureNJoursAvant = 15;

			// inscriptions fermées n jours avant la date de la séance (1 pour la veille, 2 pour l'avant-veille...')
				$fermetureNJoursAvant = 1;

				// heure de fermetrue des inscription le jour fixé (ex : 18 pour 18 heures)
				$heureFermeture = 18;
				
			// durée de la "punition" pour absence non excusée en nombre de jours (ex : 30 pour 30 jours)
				$dureePunition = 30; // 0 : le système de punition ne sera pas utilisé
				
			// nombre maximum d'animateurs déclarés pour une sortie
				$nombreMaxAnimateurs = 2;
				
			// droits des adhérents
				$adherentListe = 1; // droit de consulter et imprimer les listes des inscrits aux sorties 1: vrai ; 0 : FAUX
			
		}
		
	} // fin function chargerParametres
	
	function editerParametres() {
		// si param.inc.php existe le lire et l'inclure
		if (file_exists('param.inc.php')) {
			include('param.inc.php');
			// on récupère les paramètres MySQL pour les utiliser pour l'enregistrement des paramètres
			$_SESSION['host'] = $host; 
			$_SESSION['base'] = $base; 
			$_SESSION['user'] = $user; 
			$_SESSION['passwd'] = $passwd; 
			$_SESSION['prefixe'] = $prefixe;
		} 
		// sinon initialiser les variables paramètre
		else { //
			// mot de passe des adhérents
				$pwAdherent = "adherent";
				
			// mot de passe animateur (à communiquer aux animateurs)
				$pwAnimateur = "animateur";
				
			// mot de passe admin
				$pwAdministrateur = "admin";
				
			// mot de passe super-administrateur
				$pwSuperAdministrateur = "superadmin";

			// nom du club
				$nomClub = "Club";

			// logo du club
				$logoClub = "logo-association-affilliee-FFR.png"; // nom du fichier image logo du club ; le fichier doit être placé dans le répertoire images
				
			// adresse de courriel du club qui sera l'expéditeur des courriels envoyés par l'application (annulation ou rétablissement de sortie, attribution d'une place à un ashérent en attente)
				$courrielClub = "";
				
			// adresse courriel de l'administrateur qui recevra une copie de tous les courriels envoyés par l'application en copie cachée
				$courrielAdmin = "";
				
			// base de données MySql
				$host = $_SESSION['host']; // nom du serveur MySql
				$base = $_SESSION['base'];  // nom de la base mysql
				$user = $_SESSION['user'];  // nom de l'utilisateur mysql ayant les droits sur la base
				$passwd  = $_SESSION['passwd'];  // mot de passe de l'utilisateur de la base
				$prefixe = $_SESSION['prefixe']; // préfixe des noms de table de l'application : pour éviter d'écraser d'autres tables si la base de données contient des tables liées à d'autres application

			// inscriptions ouvertes n jours avant la date de la séance (0 pour ouverture immédiate)
				$ouvertureNJoursAvant = 15;

			// inscriptions fermées n jours avant la date de la séance (1 pour la veille, 2 pour l'avant-veille...')
				$fermetureNJoursAvant = 1;

				// heure de fermetrue des inscription le jour fixé (ex : 18 pour 18 heures)
				$heureFermeture = 18;
				
			// durée de la "sanction" pour absence non excusée en nombre de jours (ex : 30 pour 30 jours)
				$dureePunition = 30; // 0 : le système de sanction ne sera pas utilisé
				
			// nombre maximum d'animateurs déclarés pour une sortie
				$nombreMaxAnimateurs = 2;
				
			// droits des adhérents
				$adherentListe = 1; // droit de consulter et imprimer les listes des inscrits aux sorties 1: vrai ; 0 : FAUX
			
		} // fin initialiser les variables paramètre

		
	// afficher paramètres pour saisie !!! prefixe non modifiable !!!
		$GLOBALS['titrePage'] = "Saisie des paramètres de l'application";
		$GLOBALS['titrePageCourt'] = $GLOBALS['titrePage'];

		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formParam" id="formParam" action="parametrer.php" >
		<input type="hidden" name="newAction" id="newAction" value="enregistrer">

		<div id="haut" >
<?php
		include("divEnTete.inc.php");
		$html = <<<EOT

		</div>
		<div id="content">
			<hr>
					<table style="width: 100%;">
						<tbody  style="font-size:small;">
						<tr>
							<td class="tdGauche">
								mot de passe des adhérents : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="pwAdherent"   value="$pwAdherent" style="width:150px;">
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								mot de passe des animateurs : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="pwAnimateur"   value="$pwAnimateur" style="width:150px;">
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								mot de passe du ou des administrateur(s) : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="pwAdministrateur"   value="$pwAdministrateur" style="width:150px;">
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								mot de passe du super-administrateur : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="pwSuperAdministrateur"   value="$pwSuperAdministrateur" style="width:150px;">
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								nom du club : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="nomClub"   value="$nomClub" style="width:500px;">
							</td>
						</tr>


						<tr>
							<td class="tdGauche">
								logo du club : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="logoClub"   value="$logoClub" style="width:150px;"> nom du fichier image logo du club ; le fichier doit être placé dans le répertoire images
							</td>
						</tr>


						<tr>
							<td class="tdGauche">
								courriel du club : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="courrielClub"   value="$courrielClub" style="width:350px;"> adresse de courriel du club qui sera l'expéditeur des courriels envoyés par l'application (annulation ou rétablissement de sortie, attribution d'une place à un adhérent en attente)
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								courriel de l'administrateur : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="courrielAdmin"   value="$courrielAdmin" style="width:350px;"> adresse courriel de l'administrateur qui recevra une copie de tous les courriels envoyés par l'application en copie cachée
							</td>
						</tr>
						
						<tr>
							<td class="tdGauche">
								nombre de jours avant la sortie pour l'ouverture des inscriptions : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="ouvertureNJoursAvant"   value="$ouvertureNJoursAvant" style="width:50px;">  
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								nombre de jours avant la sortie pour la fermeture des inscriptions : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="fermetureNJoursAvant"   value="$fermetureNJoursAvant" style="width:50px;"> 1 pour la veille, 2 pour l'avant-veille... 
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								heure de fermeture des inscriptions le jour fixé : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="heureFermeture"   value="$heureFermeture" style="width:50px;"> ex : 18 pour 18 heures  
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								durée de la sanction pour absence non excusée en nombre de jours :<br>
								la sanction consiste à n'examiner la demande d'inscription que l'avant-veille de la sortie 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="dureePunition"   value="$dureePunition" style="width:50px;"> ex : 30 pour 30 jours ; 0 pour ne pas utiliser de système de sanction  
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								nombre maximum d'animateurs déclarés pour une sortie 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="nombreMaxAnimateurs"   value="$nombreMaxAnimateurs" style="width:50px;">
							</td>
						</tr>

						<tr>
							<td class="tdGauche">
								les adhérents peuvent accéder à la liste des participants aux sorties : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="adherentListe"   value="$adherentListe" style="width:50px;"> 1 pour oui; 0 pour non.  
							</td>
						</tr>
						
						</tbody>
					</table>
			<hr>
			</div>
			
		<div id="bas">
					<table style="width: 100%;">
						<tbody>
							<tr>
EOT;
		if ($_SESSION['scriptOrigine']=='gestion.php') { 
			$html .= <<<EOT
								<th style="width: 50%;">
									<button type="button" title="Quitter" style="font-weight: bold;  font-size: medium;" onClick="document.location.href='{$_SESSION['scriptOrigine']}';"> Quitter sans enregistrer </button>
								</th>
EOT;
		}
		$html .= <<<EOT
								<th>
									<button type="button" title="Enregistrer" style="font-weight: bold;  font-size: medium;" 
									onClick="document.getElementById('formParam').submit();"> Enregistrer </button>
								</th>
							</tr>
						</tbody>
					</table>
		</div>
		</form>
	</body>
</html>
EOT;
		echo $html;


		
	} // fin function editerParametres
	
	function enregistrerParametre() {
	// contenu du fichier
		$contenuFichier = <<<EOT
<?php
// param.inc.php
			// mot de passe des adhérents
				\$pwAdherent = "{$_POST['pwAdherent']}";
				
			// mot de passe animateur (à communiquer aux animateurs)
				\$pwAnimateur = "{$_POST['pwAnimateur']}";
				
			// mot de passe admin
				\$pwAdministrateur = "{$_POST['pwAdministrateur']}";
				
			// mot de passe super-administrateur
				\$pwSuperAdministrateur = "{$_POST['pwSuperAdministrateur']}";

			// nom du club
				\$nomClub = "{$_POST['nomClub']}";

			// logo du club
				\$logoClub = "{$_POST['logoClub']}"; // nom du fichier image logo du club ; le fichier doit être placé dans le répertoire images
				
			// adresse de courriel du club qui sera l'expéditeur des courriels envoyés par l'application (annulation ou rétablissement de sortie, attribution d'une place à un ashérent en attente)
				\$courrielClub = "{$_POST['courrielClub']}";
				
			// adresse courriel de l'administrateur qui recevra une copie de tous les courriels envoyés par l'application en copie cachée
				\$courrielAdmin = "{$_POST['courrielAdmin']}";
				
			// base de données MySql
				\$host = "{$_SESSION['host']}"; // nom du serveur MySql
				\$base = "{$_SESSION['base']}";  // nom de la base mysql
				\$user = "{$_SESSION['user']}";  // nom de l'utilisateur mysql ayant les droits sur la base
				\$passwd  = "{$_SESSION['passwd']}";  // mot de passe de l'utilisateur de la base
				\$prefixe = "{$_SESSION['prefixe']}"; // préfixe des noms de table de l'application : pour éviter d'écraser d'autres tables si la base de données contient des tables liées à d'autres application

			// inscriptions ouvertes n jours avant la date de la séance (0 pour ouverture immédiate)
				\$ouvertureNJoursAvant = {$_POST['ouvertureNJoursAvant']};

			// inscriptions fermées n jours avant la date de la séance (1 pour la veille, 2 pour l'avant-veille...')
				\$fermetureNJoursAvant = {$_POST['fermetureNJoursAvant']};

				// heure de fermetrue des inscription le jour fixé (ex : 18 pour 18 heures)
				\$heureFermeture = {$_POST['heureFermeture']};
				
			// durée de la "punition" pour absence non excusée en nombre de jours (ex : 30 pour 30 jours)
				\$dureePunition = {$_POST['dureePunition']}; // 0 : le système de punition ne sera pas utilisé
				
			// nombre maximum d'animateurs déclarés pour une sortie
				\$nombreMaxAnimateurs = {$_POST['nombreMaxAnimateurs']};
				
			// droits des adhérents
				\$adherentListe = {$_POST['adherentListe']}; // droit de consulter et imprimer les listes des inscrits aux sorties 1: vrai ; 0 : FAUX
?>
EOT;
//die($contenuFichier);

	// enregistrer les paramètres en (re) créant param.inc.php
		$fp = fopen("param.inc.php", "w");
//if(!$fp) die("pb fopen");
		$rep = fwrite($fp,$contenuFichier);
//if(!$rep) die("pb fwrite");
		fclose($fp);
//die("OK");

		$GLOBALS['titrePage'] = "Fin du paramétrage de l'application";
		$GLOBALS['titrePageCourt'] = $GLOBALS['titrePage'];

		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formParam" id="formParam" action="parametrer.php" >
		<input type="hidden" name="newAction" id="newAction" value="terminer">

		<div id="haut" >
<?php
		include("divEnTete.inc.php");
		$html = <<<EOT

		</div>
		<div id="content" style="font-size: medium;">
			<hr>
			<br><br><br><br>
			<p style="text-align: center;" style="vertical-align:middle; display: table-cell;">
				Les paramètres ont été enregistrés dans le fichier <b>param.inc.php</b>
			</p>
			<br>
EOT;
		if ($_SESSION['scriptOrigine']=="initialiser.php") {
			$html = <<<EOT
			<p style='text-align: center;'>Cliquez sur le bouton pour ouvrir l'application :</p>
			<br>
			<button type="button" title="Ouvrir" style="font-weight: bold;  font-size: medium;" 		onClick="document.getElementById('formParam').submit();"> Ouvrir l'application </button>
EOT;
		}
		else {
			$html .= <<<EOT
			<p style='text-align: center;'>Cliquez sur le bouton pour retourner à l'application :</p>
			<br>
			<p style='text-align: center;'>
				<button type="button" title="Retour" style="font-weight: bold;  font-size: medium;" onClick="document.getElementById('formParam').submit();"> Retourner au menu </button>
			</p>
			<br><br><br><br>
EOT;
		}
		$html .= <<<EOT
			<hr>
		</div>
			
		<div id="bas">
		</div>
		</form>
	</body>
</html>
EOT;
		echo $html;


	} // fin function enregistrerParametre()
	
	function terminer() {
	// redirection vers index si appelé par initialiser sinon vers gestionInscriptions
		if ($_SESSION['scriptOrigine']=="initaliser.php") {
			$scriptCible = "index.php";
			// détruire variables de session
			$_SESSION = array();
		}
		else $scriptCible = "gestion.php";
		header("Location: $scriptCible");
		exit;
	} // fin function terminer
	
?>