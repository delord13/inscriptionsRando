<?php
// donneesPerso.php
/*

*/
	session_start();
	
// contrôle d'accès
	if (!isset($_SESSION['statut'])) { header('Location: index.php'); exit();}
	if (!isset($_SESSION['licencePerso'])) die("Accès interdit");
	

	$licencePerso = $_SESSION['licencePerso'];
//	unset($_SESSION['licencePerso']);

	include('init.inc.php');
	
	// chargement des données personnelles adherent
	$sql = "SELECT licenceAdherent, statut, actif, nomAdherent, prenomAdherent, courrielAdherent, mobileAdherent, dateLimiteAttenteAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$licencePerso'";
//die($sql);
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$adherent = mysqli_fetch_assoc ($res);

	
	// chargement des données personnelles inscription
	$sql = "SELECT idInscription, seanceId, adherentLicence, dateHeureInscription, dateHeureAttributionPlace, attributionTardiveInscription, attenteInscription, dateHeureAnnulationInscription, annulationTardiveInscription, absenceExcuseeInscription, absenceInscription, dateLimiteAttenteInscription, idSeance, nomSeance, niveauSeance, remarqueSeance, dateSeance, heureRDVSeance, lieuRDVSeance, maxSeance, supprimeeSeance FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}seance WHERE idSeance=seanceId AND adherentLicence='$licencePerso'";
//die($sql);
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	while ($uneInscription = mysqli_fetch_assoc ($res)) {
		$inscription[] = $uneInscription;
	}

	$titrePage = "Données personnelles";
	$titrePageCourt = $titrePage;
	
	$maintenantTimeStamp = time();
	$maintenant= strftime('%d/%m/%Y à %Hh%M',$maintenantTimeStamp);

	$GLOBALS['titrePage'] = <<<EOT
	
				<p style="font-size: medium; margin: 10px; font-weight: bold;">
					Données personnelles enregistrées de {$adherent['prenomAdherent']} {$adherent['nomAdherent']} à la date du $maintenant 
				</p>
EOT;

	$GLOBALS['titrePageCourt'] = "Données personnelles";
	
	// statut à afficher
	$tabStatut[1] = "adhérent";
	$tabStatut[2] = "animateur";
	$tabStatut[3] = "administrateur";
	$tabStatut[5] = "administrateur et animateur";
	$tabStatut[10] = "super-administrateur";
	$tabStatut[12] = "super-administrateur et animateur";
	
	// noms des colonnes (comment ou field) adherent
	$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}adherent";
	$res = mysqli_query($GLOBALS['lkId'],$sql);
	while ($uneLigne=mysqli_fetch_assoc ($res)) {
		if ($uneLigne['Comment']!="") $colonneAdherent[$uneLigne['Field']] = $uneLigne['Comment'];
		else 	$colonneAdherent[$uneLigne['Field']] = $uneLigne['Field'];
	}

	// noms des colonnes (comment ou field) seance
	$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}seance";
	$res = mysqli_query($GLOBALS['lkId'],$sql);
	while ($uneLigne=mysqli_fetch_assoc ($res)) {
		if ($uneLigne['Comment']!="") $colonneSeance[$uneLigne['Field']] = $uneLigne['Comment'];
		else 	$colonneSeance[$uneLigne['Field']] = $uneLigne['Field'];
	}

	// noms des colonnes (comment ou field) inscription
	$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}inscription";
	$res = mysqli_query($GLOBALS['lkId'],$sql);
	while ($uneLigne=mysqli_fetch_assoc ($res)) {
		if ($uneLigne['Comment']!="") $colonneInscription[$uneLigne['Field']] = $uneLigne['Comment'];
		else 	$colonneInscription[$uneLigne['Field']] = $uneLigne['Field'];
	}

	
?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>
		
	<body style="width: 800px; margin: auto;">
<?php 
		include("divEnTete.inc.php");
?>
		<div style="width: 100%; margin: auto;">
			
			<div id="divNonImprimable">
				<br>
				<p style="text-align: center;">
				<input type="button" id="boutonImprimer" onclick="javascript:window.print();" value="Imprimer la page" />&nbsp; &nbsp; &nbsp; 
				<input type="button" id="boutonFermer" onclick="javascript:window.close();" value="Fermer cet onglet" />
				</p>
				<br>
			</div>
		
			<hr style="width: 100%">
			<h2> Compte </h2>
			<table style="width: 100%; margin: auto;">
				<tbody>
					<tr>
						<td class="tdGauche">
							n° de licence : 
						</td>
						<td class="tdDroite">
							<?php echo($_SESSION['idActeur']); ?>
						</td>
					</tr>

					<tr>
						<td class="tdGauche">
							adhérent·e actif·ive :
						</td>
						<td class="tdDroite">
							<?php if ($adherent['actif']==0) echo 'non'; else echo 'oui';?>
						</td>
					</tr>

<?php
if (!is_null($adherent['dateLimiteAttenteAdherent'])) {
?>
					<tr>
						<td class="tdGauche">
							en liste d'attente jusqu'au :
						</td>
						<td class="tdDroite">
							<?php echo(nationaliserDate($adherent['dateLimiteAttenteAdherent'])); ?>
						</td>
					</tr>
<?php
}
?>
					<tr>
						<td class="tdGauche">
							statut : 
						</td>
						<td class="tdDroite">
							<?php echo $tabStatut[$adherent['statut']] ;?>
						</td>
					</tr>
					<tr>
						<td class="tdGauche">
							nom : 
						</td>
						<td class="tdDroite">
							<?php echo($adherent['nomAdherent']);?>
						</td>
					</tr>
					<tr>
						<td class="tdGauche">
							prénom : 
						</td>
						<td class="tdDroite">
							<?php echo($adherent['prenomAdherent']);?>
						</td>
					</tr>
					<tr>
						<td class="tdGauche">
							adresse de courriel : 
						</td>
						<td class="tdDroite">
							<?php echo($adherent['courrielAdherent']);?>
						</td>
					</tr>
					<tr>
						<td class="tdGauche">
							numéro de mobile : 
						</td>
						<td class="tdDroite">
							<?php echo($adherent['mobileAdherent']);?>
						</td>
					</tr>

					</tbody>
				</table>
				
			<hr style="width: 100%">
			<h2> Inscriptions aux sorties </h2>
			<table style="width: 100%">
				<tbody>
<?php 
if (isset($inscription)) foreach ($inscription AS $i => $uneInscription) {
// idInscription, seanceId, adherentLicence, dateHeureInscription, dateHeureAttributionPlace, attributionTardiveInscription, attenteInscription, dateHeureAnnulationInscription, annulationTardiveInscription, absenceExcuseeInscription, absenceInscription, dateLimiteAttenteInscription
// idSeance, nomSeance, niveauSeance, remarqueSeance, dateSeance, heureRDVSeance, lieuRDVSeance, maxSeance, supprimeeSeance

//var_dump($uneInscription);die;	
?>
					<tr>
						<td>
<?php 
	echo $uneInscription['nomSeance'].' du ';
	echo nationaliserDate($uneInscription['dateSeance']).' ; inscription enregistrée le ';
	
	echo nationaliserDateHeure($uneInscription['dateHeureInscription']);
	
	if(!is_null($uneInscription['dateHeureAttributionPlace'])) echo '&nbsp;; place attribuée le '.nationaliserDateHeure($uneInscription['dateHeureAttributionPlace']);
	
	if ($uneInscription['attributionTardiveInscription']=='oui') echo "' '(attribution tardive)";
		
	if ($uneInscription['attenteInscription']>0) echo "&nbsp;; rang en liste d'attente&nbsp: ".$uneInscription['attenteInscription'];
	
	if ($uneInscription['dateHeureAnnulationInscription']>0) echo "&nbsp;; inscription annulée le&nbsp;: ".nationaliserDateHeure($uneInscription['dateHeureAnnulationInscription']);
	
	if ($uneInscription['annulationTardiveInscription']=='oui') echo "&nbsp;; (annulation tardive)";
	
	
	if ($uneInscription['absenceInscription']=='oui') echo "&nbsp;; absence à la sortie";
	if ($uneInscription['absenceExcuseeInscription']=='oui') echo " (excusée)";
	
	if (!is_null($uneInscription['dateLimiteAttenteInscription'])) echo "&nbsp;; sanction pour absence jusqu'au ".nationaliserDate($uneInscription['dateLimiteAttenteInscription']);
	
?>
						</td>
					</tr>
<?php 
}
else echo "Aucune inscription";
?>
				</tbody>
			</table>
			<hr style="width: 100%">
			
<?php 
//die("statut : ".$adherent['statut']);
	// si statut d'animateur : lister les sorties animées par l'adhérent
	if ($adherent['statut']==2 OR $adherent['statut']==5 OR $adherent['statut']==12) {
		// chargement des sorties animées
		$sql = "SELECT nomSeance, dateSeance, lieuRDVSeance FROM {$GLOBALS['prefixe']}seanceAnimateur, {$GLOBALS['prefixe']}seance WHERE seanceId=idSeance AND animateurLicence='{$adherent['licenceAdherent']}' ORDER BY nomSeance";
//die($sql);
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($unesortie=mysqli_fetch_assoc ($res)) {
			$sortie[] = $unesortie; 
		}
		
?>
			<h2> Animation des sorties </h2>
			<table style="width: 100%">
				<tbody>
<?php 
		foreach($sortie AS $uneSortie) {
			$date = nationaliserDate($uneSortie['dateSeance']);
			$tr = <<<EOT
					<tr>
						<td>
							{$uneSortie['nomSeance']} $date à {$uneSortie['lieuRDVSeance']}
						</td>
					</tr>
EOT;
			echo $tr
?>
<?php 
		}
?>
				</tbody>
			</table>
			<hr style="width: 100%">
<?php 
	}
?>
			
		</div>
	</body>
</html>
	
	
