<?php
// index.php


	session_start();

	include('init.inc.php');

	mettreAJourInscriptions();
	
	// mise à jour de la structure des tables de la version 1 (commentaires manquants)
	$sql = "ALTER TABLE {$GLOBALS['prefixe']}inscription CHANGE dateHeureAttributionPlace dateHeureAttributionPlace DATETIME NULL DEFAULT NULL COMMENT 'date attribution de place', CHANGE attributionTardiveInscription attributionTardiveInscription VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'atribution tardive', CHANGE annulationTardiveInscription annulationTardiveInscription VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'annulation tardive', CHANGE dateLimiteAttenteInscription dateLimiteAttenteInscription DATE NULL DEFAULT NULL COMMENT 'date limite sanction pour absence'";
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$sql = "ALTER TABLE {$GLOBALS['prefixe']}adherent CHANGE actif actif INT(11) NOT NULL DEFAULT '1' COMMENT 'actif·ive', CHANGE dateLimiteAttenteAdherent dateLimiteAttenteAdherent DATE NULL DEFAULT NULL COMMENT 'date limite sanction pour absence'";
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$sql = "ALTER TABLE {$GLOBALS['prefixe']}seanceAnimateur CHANGE idSeanceAnimateur idSeanceAnimateur INT(11) NOT NULL AUTO_INCREMENT COMMENT 'id'";
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$sql = "ALTER TABLE {$GLOBALS['prefixe']}inscription CHANGE dateHeureAttributionPlace dateHeureAttributionPlace TIMESTAMP NULL DEFAULT NULL COMMENT 'date attribution de place', CHANGE dateHeureAnnulationInscription dateHeureAnnulationInscription TIMESTAMP NULL DEFAULT NULL COMMENT 'date heure annulation inscription';";
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	
	// gérer liste d'attente avec courriel(s) éventuel(s)
	
	$titrePage = $GLOBALS['nomClub'];
	$titrePageCourt = $titrePage;


/////////////////////////////////////////////////////////////////////
// MAIN
/////////////////////////////////////////////////////////////////////
{
	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "authentifierActeur":
				// mot de passe simplifié accepté
				if (!preg_match("/^[0,1,9]{1}[0-9]{6}[A-Z]{1}$|^admin$/",$_POST['idActeur'])) {
					identifier($_POST['idActeur']." n'est pas un numéro de licence valide.");
				}
				else {
					$_POST['idActeur'] = mb_strtoupper($_POST['idActeur']);
					$_SESSION['idActeur'] = $_POST['idActeur'];
					$_SESSION['pwActeur'] = $_POST['pwActeur'];
					verifierActeur($_SESSION['idActeur'],$_SESSION['pwActeur']);
				}
				break;
				
			case "modifierAdherent":
				editerAdherent('update');
				break;
				
			case "gestion":
				header("Location: gestion.php");
				exit;
				break;
				
			case "enregistrerAdherent" :
				enregistrerAdherent();
				// ?????
				break;
				
				
				
				
			case "quitter" : 
				header("Location: index.php");
				exit;
				break;
		}	// fin case	
	}
	else { // pas de POST : initial
		if (isset($_SESSION['statut'])) unset($_SESSION['statut']);
		identifier("");
	}
}
// fin MAIN
/////////////////////////////////////////////////////////////////////

	function identifier($reponse) {
		// POST : authentifierActeur idActeur pw
	?>
	<!DOCTYPE html>
	<html lang="fr-fr">

	<?php
			include("headHTML.inc.php");
	?>
		<body onLoad="var identifiant=document.getElementById('idActeur'); identifiant.focus(); identifiant.select(); redim();">

			<div id="haut">
<?php 
	include('divEnTete.inc.php');
?>
			</div>
			<div id="content">
				<br>
				<hr style="">
				<table style="width: 100%">
				<tbody>
				<form method="POST" action="index.php" id="formAuthentifier" >
					<input type="hidden" name="newAction" value="authentifierActeur">

						<tr>
							<td class="tdTitre" style="font-size:large; font-weight:bold; text-align: center; " colspan="2">
								Identification
							</td>
						</tr>
	<?php
	if ($reponse!="") {
	?>
						<tr>
							<td class="rouge" colspan="2">
								<?php echo($reponse);?>
							</td>
						</tr>
	<?php
	}
	?>
						<tr>
							<td class="tdGauche">
								identifiant (votre numéro de licence) : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="idActeur"   id="idActeur"  value="" style= "width: 100px;" placeholder="0123456A" pattern="^[0,1,9]{1}[0-9]{6}[A-Z]{1}$|^admin$"> <span style="font-size: x-small; font-style: italic;">7 chiffres puis 1 lettre majuscule</span>
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								mot de passe : 
							</td>
							<td class="tdDroite">
								<input required="required" type="password"  name="pwActeur"   id="pwActeur"  value="" style=" width: 100px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								puis cliquez sur : 
							</td>
							<td class="tdDroite">
								<input value="Envoyer" name="Envoyer" type="submit" style="font-size:medium; font-weight:bold; width: 150px;" >
							</td>
						</tr>

						</tbody>
						</form>
					</table>
					<br>
					
					<hr style="">

				</div>

				<div id="bas">
				</div>
			</form>
		</body>
	</html>

	<?php
	} // fin function identifier

	function verifierActeur($idActeur, $pw) {

		$_SESSION['idActeur'] = $idActeur;

		switch ($pw) {
			case $GLOBALS['pwSuperAdministrateur'] :
				$_SESSION['statut'] = 10;
				break;
			case $GLOBALS['pwAdministrateur'] :
				$_SESSION['statut'] = 3;
				break;
			case $GLOBALS['pwAnimateur'] :
				$_SESSION['statut'] = 2;
				break;
			case $GLOBALS['pwAdherent'] :
				$_SESSION['statut'] = 1;
				break;
			default :
				identifier("Le mot de passe n'a pas été reconnu.");
				break;
		}
		
		// rechercher idActeur
		$sql = "SELECT nomAdherent, prenomAdherent, statut, courrielAdherent, actif FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$idActeur'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$acteur = mysqli_fetch_assoc ($res);
		// refus des comptes inactifs
		if ($acteur['actif']=='non') die('Accès interdit');
		$_SESSION['nomActeur'] = $acteur['nomAdherent'];
		$_SESSION['prenomActeur'] = $acteur['prenomAdherent'];
		$_SESSION['courrielActeur'] = $acteur['courrielAdherent'];
		
		// si déjà inscrit : mettre à jour statut
		if ($acteur) {
			if (($acteur['statut']+$_SESSION['statut'])==5) $_SESSION['statut'] = 5; // administrateur et animateur
			if (($acteur['statut']+$_SESSION['statut'])==12) $_SESSION['statut'] = 12; // administrateur et animateur
			if ($acteur['statut']<$_SESSION['statut'])
			$sql = "UPDATE {$GLOBALS['prefixe']}adherent SET statut={$_SESSION['statut']} WHERE licenceAdherent='$idActeur'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
			
			if ($_SESSION['statut']>1) {
				header('Location: gestion.php');
				exit();
			}
			else {
				header('Location: inscriptions.php');
				exit();
			}
		}
		// sinon ajouter dans la table
		else {
			editerAdherent('insert');
		}
		
	} // fin function verifierActeur

	function editerAdherent($mode) { // insert ou update
	// appelée par inscription.php et gestion.php
		if ($mode=='update') {
			$sql = "SELECT licenceAdherent, statut, actif, nomAdherent, prenomAdherent, courrielAdherent, mobileAdherent, dateLimiteAttenteAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='{$_SESSION['idActeur']}' ";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
			$acteur = mysqli_fetch_assoc ($res);
			if ($acteur['dateLimiteAttenteAdherent']=='NULL') $dateLimiteAttenteAdherent = NULL;
			else $dateLimiteAttenteAdherent = $acteur['dateLimiteAttenteAdherent'];
			$nom = $acteur['nomAdherent'];
			$prenom = $acteur['prenomAdherent'];
			$courriel = $acteur['courrielAdherent'];
			$mobile = $acteur['mobileAdherent'];
			$statut = $acteur['statut'];
		}
		else {
			$dateLimiteAttenteAdherent = NULL;
			$nom = '';
			$prenom = '';
			$courriel = '';
			$mobile = '';
			$statut = $_SESSION['statut'];
		}
		// statut à afficher
		$tabStatut[1] = "adhérent";
		$tabStatut[2] = "animateur";
		$tabStatut[3] = "administrateur";
		$tabStatut[5] = "administrateur et animateur";
		$tabStatut[10] = "super-administrateur";
		$tabStatut[12] = "super-administrateur et animateur";
		
		if ($_SESSION['statut']>1) {
			$scriptRetour = 'inscriptions.php';
		}
		else {
			$scriptRetour = 'inscriptions.php';
		}
		
		// pour accès aux données personnelles
		$_SESSION['licencePerso'] = $_SESSION['idActeur'];


	?>
	<!DOCTYPE html>
	<html lang="fr-fr">
	<?php
			include("headHTML.inc.php");
	?>

		<body onLoad="redim();">
		<form method="POST" name="formAdherent" id="formAdherent" action="index.php" >
			<input type="hidden" name="newAction" id="newAction" value="enregistrerAdherent">
			<input type="hidden" name="mode" id="mode" value="<?php echo($mode) ?>">


			<div id="haut" >
	<?php
			include("divEnTete.inc.php");
			$GLOBALS['titrePage'] = "Adhérent";
	?>
			</div>
			<div id="content">
				<br><br><br>
				<hr>
					<table>
							<tbody>
							<tr>
								<td class="tdGauche">
									votre n° de licence : 
								</td>
								<td class="tdDroite">
									
									<input required="required" readonly = "readonly" type="text"  name="licenceAdherent"   id="licenceAdherent"  value="<?php echo($_SESSION['idActeur']); ?>" style="font-size:small;  width:150px;">
								</td>
							</tr>
	<?php
		if (!is_null($dateLimiteAttenteAdherent)) {
	?>
							<tr>
								<td class="tdGauche">
									en liste d'attente jusqu'au :
								</td>
								<td class="tdDroite">

									<?php echo(nationaliserDate($dateLimiteAttenteAdherent)); ?>
								</td>
							</tr>
	<?php
		}
	?>
							<tr>
								<td class="tdGauche">
									votre statut : 
								</td>
								<td class="tdDroite">
									<?php echo $tabStatut[$statut] ;?>
								</td>
							</tr>
							<tr>
								<td class="tdGauche">
									votre nom : 
								</td>
								<td class="tdDroite">
									<input required="required" type="text"  name="nomAdherent"   id="nomAdherent"  value="<?php echo($nom);?>" style="font-size:small;  width:150px;">
								</td>
							</tr>
							<tr>
								<td class="tdGauche">
									votre prénom : 
								</td>
								<td class="tdDroite">
									<input required="required" type="text"  name="prenomAdherent"   id="prenomAdherent"  value="<?php echo($prenom);?>" style="font-size:small;  width:150px;">
								</td>
							</tr>
							<tr>
								<td class="tdGauche">
									votre adresse de courriel : 
								</td>
								<td class="tdDroite">
									<input required="required" type="text"  name="courrielAdherent"   id="courrielAdherent"  value="<?php echo($courriel);?>" style="font-size:small;  width:250px;">
								</td>
							</tr>
							<tr>
								<td class="tdGauche">
									votre numéro de mobile : 
								</td>
								<td class="tdDroite">
									<input required="required" type="text"  name="mobileAdherent"   id="mobileAdherent"  value="<?php echo($mobile);?>" style="font-size:small;  width:150px;">
								</td>
							</tr>

							<tr>
								<td  class="tdGauche">
									enregistrer les modifications : 
								</td>
								<td class="tdDroite">
									<input value="Envoyer" name="Envoyer" type="submit" style="font-size:medium; font-weight:bold; width:250px;" >
								</td>
							</tr>

							<tr>
								<td  class="tdGauche">
									quitter sans enregistrer : 
								</td>
								<td class="tdDroite">
									<button type="button" style="width: 250px" onClick = "document.location.href='<?php echo $scriptRetour;?>';">Retour</button>
								</td>
							</tr>
							</tbody>
						</table>
				<hr>
					<table>
						<tbody>
							<tr>
								<td class="tdGauche">
									accéder aux données personnelles : 
								</td>
								<td class="tdDroite">
									<button type="button" style="font-weight: bold; width: 250px" onClick = "window.open('donneesPerso.php?licencePerso=<?php echo $_SESSION['idActeur'];?>','_blank');">Données personnelles</button>
								</td>
							</tr>
						</tbody>
					</table>

					</form>
				<hr>
				</div>
				
			<div id="bas">
			</div>
			</form>
		</body>
	</html>

	<?php
		
	} // fin function editerAdherent

	function enregistrerAdherent() {
		$_POST['nomAdherent'] = mb_strtoupper($_POST['nomAdherent']);

		// echappement des POST
		foreach ($_POST AS $key => $value) {
			$_POST[$key] = addslashes($value);
		}

		if ($_POST['mode']=='update') {
			$sql = "UPDATE {$GLOBALS['prefixe']}adherent SET statut={$_SESSION['statut']}, actif=1, nomAdherent='{$_POST['nomAdherent']}', prenomAdherent='{$_POST['prenomAdherent']}', courrielAdherent='{$_POST['courrielAdherent']}', mobileAdherent='{$_POST['mobileAdherent']}', dateLimiteAttenteAdherent=NULL WHERE licenceAdherent='{$_SESSION['idActeur']}'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error($GLOBALS['lkId']));
		}
		else { // mode insert
			$sql = "INSERT INTO {$GLOBALS['prefixe']}adherent(licenceAdherent, statut, actif, nomAdherent, prenomAdherent, courrielAdherent, mobileAdherent, dateLimiteAttenteAdherent) VALUES ('{$_SESSION['idActeur']}', {$_SESSION['statut']}, 1, '{$_POST['nomAdherent']}', '{$_POST['prenomAdherent']}', '{$_POST['courrielAdherent']}', '{$_POST['mobileAdherent']}', NULL)";

			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error($GLOBALS['lkId']));
		}
			if ($_SESSION['statut']>1) {
				header('Location: inscriptions.php');
				exit();
			}
			else {
				header('Location: inscriptions.php');
				exit();
			}
	} // fin enregistrerAdherent()
?>

