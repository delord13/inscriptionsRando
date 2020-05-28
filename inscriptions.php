<?php
// inscriptions.php
	
	session_start();

// contrôle d'accès
	if (!isset($_SESSION['statut'])) { header('Location: index.php'); exit();}


	include('init.inc.php');
	
ini_set('display_errors', true);	
error_reporting (E_ALL);

	$titrePage = "Sorties du club";
	$titrePageCourt = $titrePage;
	
	// $_SESSION['idClub']
	// $_SESSION['statut'] 
	// $_SESSION['idActeur']
	// $_SESSION['message']


	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "inscrire" :
				$reponse = enregistrerInscription();
				afficherInscription($reponse);
				break;
			case "annuler" :
				$reponse = annulerInscription();
				afficherInscription($reponse);
				break;
			case "modifier" : // NON il faut POST vers index
				header('Location: editerAdherent.php');
				exit();
				break;
			case "s_inscrire" : 
				afficherInscription("");
				break;
			case "quitter" : 
				header("Location: index.php");
				break;
		}
	}
	else {
		if(isset($_SESSION['message'])) afficherInscription($_SESSION['message']);
		else afficherInscription("");
	}	
	
	function enregistrerInscription() {
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance=".$_POST['idSeance'];
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$uneSeance = mysqli_fetch_assoc ($res);
		
		$tropTot = FALSE;
		$tropTard = FALSE;
		$semaine1 = FALSE;
		$semaine2 = FALSE;
		
		$dejaInscrit = FALSE;
		$placeAttribuee = FALSE;
		$inscriptionAnnulee = FALSE;
		$inscriptionAnnuleeTardivement = FALSE;
		$annulationPossible = FALSE;
		$supprimee = FALSE;
		$numAttente = 0;
		
//		$placesDispoClub = 0;
		$placesDispoTotal = 0;
		$seanceSupprimee = $uneSeance['supprimeeSeance']=='O';
		
		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
		$dateHeureAujourdhui = date('Y-m-d H:i:s', $aujourdhuiTS);
		
		$dateHeureSeance = $uneSeance['dateSeance'];
		$dateHeureSeanceTS = strtotime($dateHeureSeance);
		$dateHeureVeilleSeanceTS = $dateHeureSeanceTS-(3600*24*$GLOBALS['fermetureNJoursAvant']);
		$dateVeilleSeance = date('Y-m-d', $dateHeureVeilleSeanceTS);
		
		$dateVeilleSeanceTS = strtotime($dateVeilleSeance);
		$dateVeilleSeance18hTS = $dateVeilleSeanceTS+(3600*$GLOBALS['heureFermeture']);  
		$dateVeilleSeance18h = date('Y-m-d H:i:s', $dateVeilleSeance18hTS);
		
		$dateAvantVeilleSeanceTS = $dateVeilleSeanceTS-(3600*24);
		$dateAvantVeilleSeance = date('Y-m-d', $dateAvantVeilleSeanceTS);
		
		$dateSeance = date('Y-m-d', $dateHeureSeanceTS);
		$dateSeanceTS = strtotime($dateSeance);
		
/*
		$dateSeanceMoins7TS = $dateSeanceTS-(3600*24*7);
		$dateSeanceMoins7 = date('Y-m-d', $dateSeanceMoins7TS);
*/		
		$dateSeanceMoins14TS = $dateSeanceTS-(3600*24*$GLOBALS['ouvertureNJoursAvant']);
		$dateSeanceMoins14 = date('Y-m-d', $dateSeanceMoins14TS);
/*		
		$semaine1 = ($dateAujourdhui>=$dateSeanceMoins14)&&($dateAujourdhui<$dateSeanceMoins7);
		$semaine2 = ($dateAujourdhui>=$dateSeanceMoins7)&&($dateHeureAujourdhui<=$dateVeilleSeance18h);
*/		
		$tropTot = $dateAujourdhui<$dateSeanceMoins14;
		$tropTard = $dateHeureAujourdhui>$dateVeilleSeance18h;
		
		
		// hors limite
		if ($tropTot) return("Les inscriptions à cette séance ne sont pas encore ouvertes.");
		if ($tropTard) return("Les inscriptions à cette séance sont fermées.");
		
		//adhérent : état inscription
		$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=".$uneSeance['idSeance']." AND adherentLicence='". $_SESSION['idActeur']."'";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$inscription = mysqli_fetch_assoc($res1);
		if ($inscription==TRUE) {	// inscrit
			$dejaInscrit = TRUE;
			if (!is_null($inscription['dateHeureAnnulationInscription']) ) { // inscription annulée
				$inscriptionAnnulee = TRUE;
			}
			else { // = pas annulé
				if (is_null($inscription['attenteInscription'] || $inscription['attenteInscription']==-1) ) {
					$placeAttribuee = TRUE;
					return("Une place vous a déjà été attribuée.");
				}
				else { // en attente
					$numAttente = $inscription['attenteInscription'];
					return("Vous êtes déjà en liste d'attente en position n°$numAttente");
				}
				// annnulation possible 
				$annulationPossible = TRUE;
			}
		} // fin existe inscription
		/* pas de else déja initialisé :
		$dejaInscrit = FALSE;
		$placeAttribuee = FALSE;
		$inscriptionAnnulee = FALSE;
		$inscriptionAnnuleeTardivement = FALSE;
		$annulationPossible = FALSE;
		$supprimee = FALSE;
		$numAttente = 0;
		*/
		
		
		// encore puni ?
		$sql = "SELECT dateLimiteAttenteAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='{$_SESSION['idActeur']}'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$encorePuni = $ligne['dateLimiteAttenteAdherent']>$dateAujourdhui;
		// calcul de la date d'examen de la demande du puini
		if ($encorePuni) {
			if ($dateAvantVeilleSeance<$ligne['dateLimiteAttenteAdherent']) $dateExamen = $dateAvantVeilleSeance;
			else $dateExamen = $dateAvantVeilleSeance;
			// francisation de la date
			$dateExamen = jourDateFr($dateExamen);
			$datePuniOK = $dateAujourdhui>=$dateExamen;
		}
		$datePuniOK = NULL;
		

		// avant d'attribuer une place ou un numéro de liste d'attente
		// on pose un verrou en écritrue sur la table inscription
		$sql = "LOCK TABLES {$GLOBALS['prefixe']}inscription WRITE";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);

		// déjà annulée => on commence par supprimer l'inscription
		if ($inscriptionAnnulee) {
			$sql = "DELETE FROM {$GLOBALS['prefixe']}inscription WHERE seanceId='{$_POST['idSeance']}' AND adherentLicence='{$_SESSION['idActeur']}'" ;
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
		}
		
		
		// placesDispoTotal
		$sql1 = "SELECT COUNT(idInscription) AS nbInscrits  FROM {$GLOBALS['prefixe']}inscription WHERE seanceId= ".$uneSeance['idSeance'] ." AND (attenteInscription IS NULL  OR attenteInscription=-1) AND dateHeureAnnulationInscription IS NULL";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$inscription = mysqli_fetch_assoc($res1);
		$placesDispoTotal = $uneSeance['maxSeance']-$inscription['nbInscrits'];
		
		// nouveau rang attente éventuel
		$sql = "SELECT MAX(attenteInscription) AS maxRang FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=".$_POST['idSeance'];
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$nouveauRang = $ligne['maxRang']+1;
		
		// encore puni OU semaine1 ET pas dispo club OU semaine2 ET pas dispo => en liste d'attente
		if (($encorePuni && !$datePuniOK) || ($placesDispoTotal<1)) { // inscription en liste d'attente'
			$sql = "INSERT INTO {$GLOBALS['prefixe']}inscription (idInscription, seanceId, adherentLicence, dateHeureInscription, dateHeureAttributionPlace , attenteInscription) VALUES (NULL, '".$_POST['idSeance']."', '".$_SESSION['idActeur']."',  CURRENT_TIMESTAMP, NULL, ".$nouveauRang.")";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			// on libère les verrous
			$sql = "UNLOCK TABLES";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			if (!$encorePuni) return("Vous êtes sur liste d'attente avec le rang $nouveauRang. Vous recevrez un courriel si une place se libère.");
			else return("Votre demande est enregistrée mais elle ne sera examinée que le $dateExamen.");
		}
		// sinon attribution place
		else { // inscription place attribuée
			$sql = "INSERT INTO {$GLOBALS['prefixe']}inscription (idInscription, seanceId, adherentLicence,  dateHeureInscription, dateHeureAttributionPlace, attenteInscription) VALUES (NULL, '".$_POST['idSeance']."', '".$_SESSION['idActeur']."',  CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL)";
//die($sql);
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			// on libère les verrous
			$sql = "UNLOCK TABLES";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			return("Une place vous a été attribuée.");
		}
		
	} // fin enregistrerInscription() 

	function annulerInscription() {
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance=".$_POST['idSeance'];
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$uneSeance = mysqli_fetch_assoc ($res);
		
		$tropTot = FALSE;
		$tropTard = FALSE;
		$semaine1 = FALSE;
		$semaine2 = FALSE;
		
		$dejaInscrit = FALSE;
		$placeAttribuee = FALSE;
		$inscriptionAnnulee = FALSE;
		$inscriptionAnnuleeTardivement = FALSE;
		$annulationPossible = FALSE;
		$supprimee = FALSE;
		$numAttente = 0;
		
		$placesDispoClub = 0;
		$placesDispoTotal = 0;
		$seanceSupprimee = $uneSeance['supprimeeSeance']=='O';
		
		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
		$dateHeureAujourdhui = date('Y-m-d H:i:s', $aujourdhuiTS);
		
		$dateHeureSeance = $uneSeance['dateSeance'];
		$dateHeureSeanceTS = strtotime($dateHeureSeance);
		$dateHeureVeilleSeanceTS = $dateHeureSeanceTS-(3600*24);
		$dateVeilleSeance = date('Y-m-d', $dateHeureVeilleSeanceTS);
		
		$dateVeilleSeanceTS = strtotime($dateVeilleSeance);
		$dateVeilleSeance18hTS = $dateVeilleSeanceTS+(3600*18);
		$dateVeilleSeance18h = date('Y-m-d H:i:s', $dateVeilleSeance18hTS);
		
		$dateAvantVeilleSeanceTS = $dateVeilleSeanceTS-(3600*24);
		$dateAvantVeilleSeance = date('Y-m-d', $dateAvantVeilleSeanceTS);
		
		$dateSeance = date('Y-m-d', $dateHeureSeanceTS);
		$dateSeanceTS = strtotime($dateSeance);

		$dateSeanceMoins7TS = $dateSeanceTS-(3600*24*7);
		$dateSeanceMoins7 = date('Y-m-d', $dateSeanceMoins7TS);
		
		$dateSeanceMoins14TS = $dateSeanceTS-(3600*24*14);
		$dateSeanceMoins14 = date('Y-m-d', $dateSeanceMoins14TS);
		
		$semaine1 = ($dateAujourdhui>=$dateSeanceMoins14)&&($dateAujourdhui<$dateSeanceMoins7);
		$semaine2 = ($dateAujourdhui>=$dateSeanceMoins7)&&($dateHeureAujourdhui<=$dateVeilleSeance18h);
		
		$tropTot = $dateAujourdhui<$dateSeanceMoins14;
		$tropTard = $dateHeureAujourdhui>$dateVeilleSeance18h;
		
		//adhérent : état inscription
		$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=".$uneSeance['idSeance']." AND adherentLicence='". $_SESSION['idActeur']."'";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$inscription = mysqli_fetch_assoc($res1);
		if ($inscription==TRUE) {	// inscrit
			$dejaInscrit = TRUE;
			if (!is_null($inscription['dateHeureAnnulationInscription']) ) { // inscription annulée
				$inscriptionAnnulee = TRUE;
				return("Vous avez déjà annulé cette inscription.");
			}
			else { // = pas annulé
				if (is_null($inscription['attenteInscription'])) {
					$placeAttribuee = TRUE;
					
				}
				else { // en attente
					$numAttente = $inscription['attenteInscription'];
				}
				// attribution tardive
				$attributionTardive = !is_null($inscription['attributionTardiveInscription']);
				// annnulation possible 
				$annulationPossible = TRUE;
			}
		} // fin existe inscription
		else return("Il n'y a pas d'inscription à annuler.");
		
		// hors limite
		if ($tropTard) return("Il est trop tard pour annuler votre inscription. Si vous ne pouvez pas venir à la séance, contactez l'animateur pour justifier d'un cas de force majeure.");
		
		// annulation de l'inscription
		if ($numAttente>0) { //  en attente : 
			// annuler
			$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = NULL, dateHeureAnnulationInscription = CURRENT_TIMESTAMP WHERE seanceId='{$_POST['idSeance']}' AND adherentLicence='{$_SESSION['idActeur']}'" ;
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			
			//remonter d'un cran tous les en attente
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId='".$_POST['idSeance']."' AND attenteInscription >0 ORDER BY attenteInscription ASC";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error($GLOBALS['lkId']));
			$inscription = array();
			while ($ligne = mysqli_fetch_assoc ($res)) {
				$inscription[] = $ligne;
			};
			foreach($inscription AS $positionInscription =>$uneInscription) {
				$idInscription = $uneInscription['idInscription'];
				$rangAttente = $positionInscription +1;
				$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = $rangAttente WHERE idInscription = $idInscription";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error($GLOBALS['lkId']));
			}
			mettreAJourInscriptions();
			return("Votre inscription a été annulée.");
		} // fin  en attente
		else { // pas en attente
			// pas annulation tardive ou attribution tardive
			$annulationTardive = $dateAujourdhui>$dateAvantVeilleSeance; // 
			if (!$annulationTardive||$attributionTardive) { // pas annulation tardive ou attribution tardive
			$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = NULL, dateHeureAnnulationInscription = CURRENT_TIMESTAMP WHERE seanceId='{$_POST['idSeance']}' AND adherentLicence='{$_SESSION['idActeur']}'" ;

			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			mettreAJourInscriptions();
			return("Votre inscription a été annulée.");
			}
			else { // annulation tardive
			$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = NULL, dateHeureAnnulationInscription = CURRENT_TIMESTAMP,  annulationTardiveInscription = 'oui' WHERE seanceId='{$_POST['idSeance']}' AND adherentLicence='{$_SESSION['idActeur']}'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			mettreAJourInscriptions();
			return("Votre inscription a été annulée tardivement. Pour éviter d'être placé systématiquement en liste d'attente pendant un mois, contactez l'animateur pour justifier d'un cas de force majeure.");
			} 
			
		} // fin pas en attente
		
	} // fin enregistrerInscription() 

	function afficherInscription($reponse) {
		
		// on peut s'inscrire jusqu'à $GLOBALS['fermetureNJourAvant'] à $GLOBALS['HeureFermeture']
		// on affiche les séances dont la date est comprise inférieure ou égale à aujourd'hui plus $GLOBALS['ouvertureNJourAvant']
		// les mots "veille" est "18" heures sont conservées dans les noms de variable mais les valeurs sont calculées en fonction des paramètres

		$_SESSION['message'] ="";
		// détermination de l'intervalle de temps
		// time() =>GMT pb été/hiver ; date() convertit en heure locale

		// on affiche toutes les séances non supprimées
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE supprimeeSeance='N'   ORDER BY dateSeance  DESC";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$seance = array();
		while ($ligne = mysqli_fetch_assoc ($res)) {
			// noms et prénoms des animateurs
			$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}seanceAnimateur, {$GLOBALS['prefixe']}adherent WHERE seanceId= {$ligne['idSeance']} AND animateurLicence=licenceAdherent ";
			$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
			$ligne['animateurs'] = "";
			$premier = TRUE;
			while ($unAnim = mysqli_fetch_assoc ($res1)) {
				if (!$premier) $ligne['animateurs'] .= "<br>";
				$ligne['animateurs'] .= $unAnim['prenomAdherent']." ".$unAnim['nomAdherent']." : ".$unAnim['mobileAdherent'];
				$premier = FALSE;
			}
			$seance[] = $ligne;
		};
		// recherche nom prénom adhérent
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='{$_SESSION['idActeur']}'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$prenom = $ligne['prenomAdherent'];
		$nom = $ligne['nomAdherent'];
		$dateLimiteAttenteAdherent = $ligne['dateLimiteAttenteAdherent'];


	

?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formInscription" id="formInscription" action="inscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="">
		<input type="hidden" name="idSeance" id="idSeance" value="">


		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "{$GLOBALS['titrePage']} <br>pour $prenom $nom";
		include("divEnTete.inc.php");

		// affichage réponse
		if ($reponse != "") {
?>
							<p style="padding:5px; text-align: center; color: red;">
								<?php echo($reponse);?>
							</p>
<?php
		}
		// affichage punition
		if (!is_null($dateLimiteAttenteAdherent)) {
?>
							<p style="padding:5px; text-align: center; color: red;">
								Jusqu'au <?php echo(jourDateFr($dateLimiteAttenteAdherent));?>, vos demandes ne seront prises en compte que <?php echo $GLOBALS['fermetureNJoursAvant']+1 ?> jours avant la fermeture des inscriptions. 
							</p>
	
<?php
		}
?>
		</div>

		<div id="content">

			<table style="width:100%;">
				<tbody>
					<tr>
						<th>
							date
						</th>
						<th>
							intitulé
						</th>
						<th>
							niveau
						</th>
						<th>
							lieu RDV
						</th>
						<th>
							heure RDV
						</th>
						<th>
							animateur·trice(s)
						</th>
						<th>
							remarques
						</th>
						<th>
							places disponibles
						</th>
						<th>
							inscription
						</th>
						<th style="width: 180px;">
							actions
						</th>
					</tr>
<?php

	foreach ($seance AS $uneSeance) {

		$tropTot = FALSE;
		$tropTard = FALSE;
		$semaine1 = FALSE;
		$semaine2 = FALSE;
		
		$dejaInscrit = FALSE;
		$placeAttribuee = FALSE;
		$inscriptionAnnulee = FALSE;
		$inscriptionAnnuleeTardivement = FALSE;
		$annulationPossible = FALSE;
		$supprimee = FALSE;
		$numAttente = 0;
		
//		$placesDispoClub = 0;
		$placesDispoTotal = 0;
		$seanceSupprimee = $uneSeance['supprimeeSeance']=='O';
		
		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
		$dateHeureAujourdhui = date('Y-m-d H:i:s', $aujourdhuiTS);
		
		$dateHeureSeance = $uneSeance['dateSeance'];
		$dateHeureSeanceTS = strtotime($dateHeureSeance);
		$dateHeureVeilleSeanceTS = $dateHeureSeanceTS-(3600*24*$GLOBALS['fermetureNJoursAvant']);
		$dateVeilleSeance = date('Y-m-d', $dateHeureVeilleSeanceTS);
		
		$dateVeilleSeanceTS = strtotime($dateVeilleSeance);
		$dateVeilleSeance18hTS = $dateVeilleSeanceTS+(3600*$GLOBALS['heureFermeture']);  
		$dateVeilleSeance18h = date('Y-m-d H:i:s', $dateVeilleSeance18hTS);
		
		$dateAvantVeilleSeanceTS = $dateVeilleSeanceTS-(3600*24);
		$dateAvantVeilleSeance = date('Y-m-d', $dateAvantVeilleSeanceTS);
		
		$dateSeance = date('Y-m-d', $dateHeureSeanceTS);
		$dateSeanceTS = strtotime($dateSeance);
		
/*
		$dateSeanceMoins7TS = $dateSeanceTS-(3600*24*7);
		$dateSeanceMoins7 = date('Y-m-d', $dateSeanceMoins7TS);
*/		
		$dateSeanceMoins14TS = $dateSeanceTS-(3600*24*$GLOBALS['ouvertureNJoursAvant']);
		$dateSeanceMoins14 = date('Y-m-d', $dateSeanceMoins14TS);
/*		
		$semaine1 = ($dateAujourdhui>=$dateSeanceMoins14)&&($dateAujourdhui<$dateSeanceMoins7);
		$semaine2 = ($dateAujourdhui>=$dateSeanceMoins7)&&($dateHeureAujourdhui<=$dateVeilleSeance18h);
*/		
		$tropTot = $dateAujourdhui<$dateSeanceMoins14;
		$tropTard = $dateHeureAujourdhui>$dateVeilleSeance18h;
		
		
		// en fonction des inscriptions
		
		
		
		// trop tard pour annuler ?
		
		//adhérent : état inscription
		$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=".$uneSeance['idSeance']." AND adherentLicence='". $_SESSION['idActeur']."'";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$inscription = mysqli_fetch_assoc($res1);
		if ($inscription==TRUE) {	// inscrit
			$dejaInscrit = TRUE;
			if (!is_null($inscription['dateHeureAnnulationInscription']) ) { // inscription annulée
				$inscriptionAnnulee = TRUE;
			}
			else { // = pas annulé
				if (is_null($inscription['attenteInscription'] || $inscription['attenteInscription']==-1) ) {
					$placeAttribuee = TRUE;
				}
				else { // en attente
//					$numAttente = $inscription['attenteInscription'];
					$numAttente = rangAttente($_SESSION['idActeur'], $uneSeance['idSeance']);
				}
				// annnulation possible 
				$annulationPossible = TRUE;
			}
		} // fin existe inscription
		/* pas de else déja initialisé :
		$dejaInscrit = FALSE;
		$placeAttribuee = FALSE;
		$inscriptionAnnulee = FALSE;
		$inscriptionAnnuleeTardivement = FALSE;
		$annulationPossible = FALSE;
		$supprimee = FALSE;
		$numAttente = 0;
		*/
		
		
		// placesDispoTotal
		$sql1 = "SELECT COUNT(idInscription) AS nbInscrits  FROM {$GLOBALS['prefixe']}inscription WHERE seanceId= ".$uneSeance['idSeance'] ." AND (attenteInscription IS NULL OR attenteInscription=-1) AND dateHeureAnnulationInscription IS NULL";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$inscription = mysqli_fetch_assoc($res1);
		$placesDispoTotal = $uneSeance['maxSeance']-$inscription['nbInscrits'];
		
		// demandeEnSuspens
		// dateLimiteAttenteAdherent IS NULL OR dateLimiteAttenteAdherent<'$dateAujourdhui' OR '$dateAvantVeilleSeance'<'$dateAujourdhui'
		if (!is_null($dateLimiteAttenteAdherent)) {
			$demandeEnSuspens = $dateAvantVeilleSeance>$dateAujourdhui;
		}
		else $demandeEnSuspens = FALSE;
		
?>
					<tr>
<!-- dateSeance -->					
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo(jourDateFr($uneSeance['dateSeance'])); ?>
							</td>
<!-- nomSeance -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo $uneSeance['nomSeance']; ?>
							</td>

<!-- niveauSeance -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo $uneSeance['niveauSeance']; ?>
							</td>

<!-- lieuRDVSeance -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo $uneSeance['lieuRDVSeance']; ?>
							</td>

<!-- heureRDVSeance -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo nationaliserHeure($uneSeance['heureRDVSeance']); ?>
							</td>

<!-- animateurs -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo $uneSeance['animateurs']; ?>
							</td>

<!-- remarqueSeance -->
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php echo $uneSeance['remarqueSeance']; ?>
							</td>
							
						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); text-align: center;">
						<?php 
							 }
							 else {
						?>
							<td style="text-align: center;">
						<?php
							 }
						?>
							<?php
								if ($seanceSupprimee) {
									echo('-');
								}
								else {
									if (!$tropTard) echo($placesDispoTotal);
									else {
										echo('-');
									}
								}
							?>
						</td>

						<?php
							 if ($tropTard || $tropTot || $seanceSupprimee) {
						?>
							<td style= "font-style: italic; color: rgb(64,128,128); ">
						<?php 
							 }
							 else {
						?>
							<td>
						<?php
							 }
						?>
							<?php
								if ($seanceSupprimee) {
									echo("séance supprimée");
								}
								else {
									if ($dejaInscrit) {
										if($demandeEnSuspens) echo("demande en suspens");
										else {
											if ($numAttente>0)  {
												if ($numAttente==1) echo("1<sup>er·ère</sup> sur liste d'attente"); 
												else echo("$numAttente<sup>e</sup> sur liste d'attente"); 
											}
											else {
												if (!$inscriptionAnnulee) echo("place attribuée");
												else echo("inscription annulée");
											}
										}
									} 
									else echo("");
								}
							?>
						</td>
						
						
						<td  style="text-align: center;" <?php if ($tropTard || $tropTot || $seanceSupprimee) echo('"style = font-style: italic; color: rgb(64,128,128); ") '); ?>>
<?php
	if (!$seanceSupprimee && !$tropTot && !$tropTard) {
		// si déjà inscrit et inscription pas annulée et annulation possible
		if ($dejaInscrit && !$inscriptionAnnulee && $annulationPossible) { 
			// bouton annulation
?>
							<button class = "boutonFiche" type=button title="Annuler l'inscription"
								onClick="document.getElementById('newAction').value='annuler'; 
								document.getElementById('idSeance').value='<?php echo($uneSeance['idSeance'])?>';
								document.getElementById('formInscription').submit();"
								> 
								Annuler
							</button>
<?php 
		}
		// ni déjà inscrit ou inscription annulée
		else {
			// bouton inscription
?>
							<button class = "boutonFiche" type=button
								onClick="document.getElementById('newAction').value='inscrire'; 
								document.getElementById('idSeance').value='<?php echo($uneSeance['idSeance'])?>';
								document.getElementById('formInscription').submit();"
								> 
								S'inscrire
							</button>
<?php 
		}
	} // fin séance non supprimée ni trop tôt ni trop tard
	// dans tous les cas, si lister autorisé
	if ($_SESSION['statut']>1 || $GLOBALS['adherentListe']) {
		// bouton lister
?>
							<button class = "boutonFiche" type="button" title="lister les inscrits"
								onclick="window.open('listeInscrits.php?idSeance=<?php echo($uneSeance['idSeance'])?>');"
								>
								<img alt="lister" src="images/fiche.png">
							</button>
<?php 
	}
?>
						</td>

					</tr>
<?php
	} // fin pour chaque séance
?>
				</tbody>
			</table>
			</form>

		</div>
			
		<div id="bas">
			<hr>
				<p style="text-align: justify; margin-left: 10px; margin-right: 10px;">
<?php 
	if ($GLOBALS['ouvertureNJoursAvant']>0) {
?>
				Les inscriptions sont ouvertes <?php echo $GLOBALS['ouvertureNJoursAvant']; ?> jours avant la sortie. 
				
				Les inscriptions sont fermées la veille de la sortie à <?php echo $GLOBALS['heureFermeture']; ?> heures&nbsp;; si aucune place n'est disponible, vous serez placé en liste d'attente et si une place peut vous être attribuée suite à des annulations, vous en serez informé·e par courriel.
				<br>
				
<?php 
	}
?>
				Vous pouvez annuler une inscription jusqu'à la veille de la séance à 18 heures.
<?php 
	if ($GLOBALS['dureePunition']>0) {
?>
				Si vous annulez pendant les 2 jours qui précèdent la séance ou si vous n'avez pas pu annuler, vous devez contacter l'animateur par téléphone pour justifier votre absence par un cas de force majeure afin d'éviter que  vos nouvelles demandes ne soient mises en attente jusqu'à l'avant-veille des sorties pendant une durée de <?php echo $GLOBALS['dureePunition'] ?> jours.
				
<?php
	}
?>
				</p>
			<table style="width: 100%;">
				<tbody>
						<tr>
							<td colspan=3>
								<p style="font-size: x-small; font-style: italic;">
									Les données personnelles que vous fournissez ne servent qu'à l'organisation de activités du club. Ces données ne sont pas communiquées à l'extérieur du club. Vous disposez d'un droit d’opposition, d’accès et de rectification concernant ces données personnelles que vous pouvez exercer en cliquant sur : <img alt="message" src="images/courriel0.png" style="position: relative; top: 4px;" onclick="window.open('droitsDoneesPersonnelles.php');"></button>

								</p>
							</td>
						</tr>
					<tr>
						<td class="tdTitre" style="text-align: center; width: 33%;">
<?php 
	if ($_SESSION['statut']>1) {
?>
								Gestion des sorties : 
								<button type=button title="Gestion des sorties" onClick="var formulaire=document.getElementById('formInscription') ; formulaire.action='index.php'; formulaire.newAction.value='gestion'; formulaire.submit();">
								<img alt="modifier" src="images/gerer16.png">
								</button>
<?php 
	}
?>

						</td>
						<td class="tdTitre" style="text-align: center; width: 33%;">
								Modifier votre compte : 
								<button type=button title="Modifier nom, prénom, adresse de courriel, numéro de mobile" onClick="var formulaire=document.getElementById('formInscription') ; formulaire.action='index.php'; formulaire.newAction.value='modifierAdherent'; formulaire.submit();">
								<img alt="modifier" src="images/edit-tool.png">
								</button>

						</td>
						<td class="tdTitre" style="text-align: center;">
								Quitter : 
								<button type=button title="Se déconnecter" onClick="document.getElementById('newAction').value='quitter'; document.getElementById('formInscription').submit();">
								<img alt="quitter" src="images/sortir.png">
								</button>
						</td>
					</tr>
				</tbody>
			</table>
			<hr>
		</div>
		</form>
	</body>
</html>

<?php

// document.getElementById('formInscription').action='index.php';
	} // fin afficherInscription


?>