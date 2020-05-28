<?php
// index.php


	session_start();

	include('init.inc.php');
	mettreAJourInscriptions();
	
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
				header("Location: gestionInscriptions.php");
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
		$sql = "SELECT nomAdherent, prenomAdherent, statut, courrielAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$idActeur'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$acteur = mysqli_fetch_assoc ($res);
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
				header('Location: gestionInscriptions.php');
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
		
	/*	
		
	// retourne un tableau :
	// 'pwOK' T F
	// 'adhérent'	T F
	// 'animateur' T F
	// 'référent' T F
	// 'nbStatuts' 0 à 3
		$tabStatut['pwOK'] = FALSE;
		$tabStatut['adhérent'] = FALSE;
		$tabStatut['animateur'] = FALSE;
		$tabStatut['référent'] = FALSE;
		$tabStatut['nbStatuts'] = 0;
	//if (isset($_SESSION['connexion']['admin'])) {echo($pw." ; ".$idActeur); die();}
		// admin
		if($idActeur=="admin") {
			$_SESSION['administrateur'] = TRUE;
			$_SESSION['pw'] = $pw;
			$tabStatut['pwOK'] = TRUE;
			$_SESSION['idClub'] = "04680";
			$tabStatut['animateur'] = TRUE;
			$tabStatut['référent'] = TRUE;
			$tabStatut['adhérent'] = TRUE;
			$tabStatut['nbStatuts'] = 3;
			$_SESSION['idActeur'] = "0651411V";
	//die("#389");
			return $tabStatut;
			// c'est terminé pour admin !
		}

		// mot de passe
		$sql = "SELECT idClub FROM club WHERE motDePasseClub = '".$pw."'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$ligne = mysqli_fetch_assoc ($res);
		if (!($ligne)) {
			$_SESSION['pw'] = $pw;
			$tabStatut['pwOK'] = FALSE;
			return $tabStatut; // = pw pas reconnu => c'est terminé !
		}
		else {
			$_SESSION['idClub'] = $ligne['idClub'];
			$tabStatut['pwOK'] = TRUE; // pw reconnu
		}

		// adhérent 
		$sql = "SELECT * FROM adherent, club WHERE licenceadherent='$idActeur' AND clubId= idClub AND motDePasseClub='$pw'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$ligne = mysqli_fetch_assoc ($res);
		if ($ligne) {
			$tabStatut['adhérent'] = TRUE;
			$tabStatut['nbStatuts']++;
		}	
		// animateur
		$sql = "SELECT * FROM animateur, club WHERE licenceAnimateur='$idActeur' AND clubId= idClub AND motDePasseClub='$pw'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$ligne = mysqli_fetch_assoc ($res);
		if ($ligne) { 
			$tabStatut['animateur'] = TRUE;
			$tabStatut['nbStatuts']++;
		}	
		// référent
		$sql = "SELECT * FROM referent, club WHERE licencereferent='$idActeur' AND clubId= idClub AND motDePasseClub='$pw'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysql_error ());
		$ligne = mysqli_fetch_assoc ($res);
		if ($ligne) {
			$tabStatut['référent'] = TRUE;
			$tabStatut['nbStatuts']++;
		}
		return $tabStatut;
	*/
	} // fin function verifierActeur

	function editerAdherent($mode) { // insert ou update
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
									<input required="required" type="text"  name="courrielAdherent"   id="courrielAdherent"  value="<?php echo($courriel);?>" style="font-size:small;  width:150px;">
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
									puis cliquez sur : 
								</td>
								<td class="tdDroite">
									<input value="Envoyer" name="Envoyer" type="submit" style="font-size:medium; font-weight:bold; width:150px;" >
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
				header('Location: gestionInscriptions.php');
				exit();
			}
			else {
				header('Location: inscriptions.php');
				exit();
			}
	} // fin enregistrerAdherent()
?>

