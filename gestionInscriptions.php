<?php
// gestionInscriptions.php

	session_start();
//var_dump($_SESSION); die();
// $_SESSION['idActeur']
// $_SESSION['statut']
// $_SESSION['idClub']
// $_SESSION['message']
// contrôle d'accès
	if (!isset($_SESSION['statut'])) { 
		header('Location: index.php'); exit();
	} 
	if ($_SESSION['statut']<2) { 
		header('Location: index.php'); exit();
	} 

	include('init.inc.php');

	switch ($_SESSION['statut']) {
		case 2 :
			$GLOBALS['statut'] = "animateur";
			break;
		case 3 :
			$GLOBALS['statut'] = "administrateur";
			break;
		case 5 :
			$GLOBALS['statut'] = "administrateur et animateur";
			break;
		case 10 :
			$GLOBALS['statut'] = "super-administateur";
			break;
		case 12 :
			$GLOBALS['statut'] = "super-administateur et animateur";
			break;
	}
	
	
	$titrePage = "Gestion des sorties et des adhérents";
	$titrePageCourt = $titrePage;

	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "supprimer" :
				$message = supprimerSeance();
				afficherSeances($message);
				break;
			case "absences" :
				$titrePage = "Gestion des absences";
				$titrePageCourt = $titrePage;
				$idSeance = $_POST['idSeance'];
				gererAbsences($idSeance,"");
				break;
			case "enregistrerAbsences" :
				$idSeance = $_POST['idSeance'];
				$message = enregistrerAbsences();
				gererAbsences($idSeance,$message);
//				afficherSeances($message);
				break;
			case "ajouterSeance" :
				$titrePage = "Ajouter une séance";
				$titrePageCourt = $titrePage;
				$message = editerSeance('insert');
				break;
			case "modifierSeance" :
				$message = editerSeance('update');
				break;
			case "enregistrerSeance" :
				$message = enregistrerSeance();
				afficherSeances($message);
				break;
			case "composerCourriel" : // INUTILE
				$titrePage = "Courriel aux adhérents";
				$titrePageCourt = $titrePage;
				composerCourrielParResponsable("");
				break;
			case "envoyerCourriel" : // INUTILE
				$message = envoyerCourrielParResponsable($_POST['de'],$_POST['sujet'],$_POST['message']);
				afficherSeances($message);
				break;
			case "gererAdherents" :
				afficherAdherents("");
				break;
			case "ajouterAdherent" :
				afficherEditerAdherent('');
//				afficherAdherents($reponse);
				break;
			case "enregistrerEditerAdherent" :
				$reponse = enregistrerEditerAdherent($_POST['modeEdition']);
				afficherAdherents($reponse);
				break;
			case "modifierAdherent" :
				afficherEditerAdherent($_POST['select']);
				break;
			case "supprimerAdherent" :
				$reponse = supprimerAdherent($_POST['select']);
				afficherAdherents($reponse);
				break;
			case "statistiques" :
				telechargerStatistiques();
				break;
			case "paramétrer" :
				$_SESSION['scriptOrigine'] = "gestionInscriptions.php";
				header("Location: parametrer.php");
				exit;
			case "retourner" :
				afficherSeances("");
				break;
			case "quitter" : 
				$keySession = array("utilisateur","admin","comptable","administrateur","animateur","adherent","cookieAdmin","nom","prenom");
				foreach ($_SESSION AS $key => $value) {
					if (!in_array($key,$keySession)) unset($_SESSION[$key]);
				}
				header("Location: index.php");
				exit;
				break;

		}
	}
	else {
		if (isset($_SESSION['message'])) $message = $_SESSION['message'];
		else $message = "";
		afficherSeances($message);
	}
/*
SELECT * FROM inscription, adherent, club, seance, animateur WHERE adherentLicence=licenceAdherent AND seanceId=idSeance AND licenceAnimateur=animateurLicence AND adherent.clubId=idClub ORDER BY dateSeance
*/	

	function editerSeance($mode) { // insert ou update
		$maintenant = time();
		$dateMaintenantDB = strftime('%Y-%m-%d',$maintenant);
		$dateMaintenant = strftime('%d/%m/%Y',$maintenant);

		// si mode update charger la séance et ses animateurs
		if ($mode=='update') {
			// séance
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance={$_POST['idSeance']} ";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			$seance = mysqli_fetch_assoc ($res);
			// heure dans les secondes
			$seance['heureRDVSeance'] = substr($seance['heureRDVSeance'],0,5);
			// ses animateurs
			$sql = "SELECT licenceAdherent FROM {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}seanceAnimateur WHERE licenceAdherent=animateurLicence AND seanceId={$_POST['idSeance']} ";
//die($sql);
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			while( $unAnimateur = mysqli_fetch_assoc ($res)) {
				$seance['animateurLicence'][] = $unAnimateur['licenceAdherent'];
			}
//var_dump($seance); die;
			
		} // fin charger séance et ses animateurs
		else { 
			$seance['idSeance'] = '';
			$seance['nomSeance'] = '';
			$seance['niveauSeance'] = '';
			$seance['remarqueSeance'] = '';
			$seance['dateSeance'] = $dateMaintenantDB;
			$seance['heureRDVSeance'] = '08:00';
			$seance['lieuRDVSeance'] = '';
			$seance['maxSeance'] = 9;
			$seance['supprimeeSeance'] = 'N';
		}
		
//		$message = '';
		// recherche des animateurs du club
		// noms et prénoms des animateurs 
		$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE statut>=2";
		$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
		$ligne['animateurs'] = "";
		$ligne['animateurLicence'] = array();
		$premier = TRUE;
		$animateur[0]['nom'] = '';
		$animateur[0]['prenom'] = '';
		$animateur[0]['licence'] = FALSE;
		$i = 1;
		while ($unAnim = mysqli_fetch_assoc ($res1)) {
			$animateur[$i]['nom'] = $unAnim['nomAdherent'];
			$animateur[$i]['prenom'] = $unAnim['prenomAdherent'];
			$animateur[$i]['licence'] = $unAnim['licenceAdherent'];
			$i++;
		}
		
		// select animateur à utiliser $nombreMaxAnimateurs fois
		$selectAnimateur = '';
		for ($i=0;$i<$GLOBALS['nombreMaxAnimateurs'];$i++) {
			$selectAnimateur .= <<<EOT
					<select name="licenceAnimateur[$i]">
EOT;
			// pour chaque animateur du club
			foreach ($animateur AS $n => $unAnimateur) {
				$selectAnimateur .= <<<EOT
					<option value='{$unAnimateur['licence']}'
EOT;
				if ($mode=='update') {
					if ($i==0) $selectAnimateur .= '  required="required" ';
					if (isset($animateur[$i]['licence'])) {
						if (isset($seance['animateurLicence'][$i])) 
							if ($unAnimateur['licence']==$seance['animateurLicence'][$i]) {
								$selectAnimateur .= '  selected="selected" ';
						}
					}
				}
				$selectAnimateur .= <<<EOT
					>{$unAnimateur['prenom']} {$unAnimateur['nom']}
					</option>
EOT;
			}
			$selectAnimateur .= <<<EOT
				</select>
EOT;
		} // pour chaque animateur possible
		
		$selectSupprimee = <<<EOT
			<select name="supprimeeSeance">
				<option value='N' 
EOT;
		if($seance['supprimeeSeance']=='N') $selectSupprimee .= ' selected '; 
		$selectSupprimee .= <<<EOT
					> non </option>
				<option value='O' 
EOT;
		if($seance['supprimeeSeance']=='O') $selectSupprimee .= ' selected ';
		$selectSupprimee .= <<<EOT
				> oui </option>
			</select>
EOT;


/*		
		if ($_SESSION['statut']=="animateur") {
			$sql = "SELECT * FROM animateur WHERE licenceAnimateur='{$_SESSION['idActeur']}'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			$animateur = mysqli_fetch_assoc ($res);
			$licenceAnimateur = $animateur['licenceAnimateur'];
			$nomAnimateur = $animateur['nomAnimateur'];
			$prenomAnimateur = $animateur['prenomAnimateur'];
		}
		else {
			$licenceAnimateur = "";
			$nomAnimateur = "";
			$prenomAnimateur = "";
		}
*/

?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formGestion" id="formGestion" action="gestionInscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="enregistrerSeance">


		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "{$GLOBALS['titrePage']}";
		include("divEnTete.inc.php");
?>
			<p style="margin-left: 450px;">
			
			</p>
		</div>
		
		<div id="content">
			<hr>
			<table>
				<tbody>
<!-- idSeance -->
					<tr style="font-style: italic;">
						<td class="tdGauche">
							identifiant :
						</td>
						<td class="tdDroite">
								<input type="text" name="idSeance" value="<?php echo $seance['idSeance']; ?>" readonly style="text-align: center; width: 50px;"> <?php if ($mode=='update') echo 'non modifiable'; else echo 'sera calculé automatiquement' ?>
						</td>
					</tr>
					
<!-- dateSeance -->
					<tr>
						<td class="tdGauche">
							date de la sortie :
						</td>
						<td class="tdDroite">
								<input type="date" name="dateSeance" required value="<?php echo $seance['dateSeance']; ?>" >
						</td>
					</tr>
					
<!-- nomSeance -->
					<tr>
						<td class="tdGauche">
							intitulé :
						</td>
						<td class="tdDroite">
								<input type="text" name="nomSeance" required value="<?php echo $seance['nomSeance']; ?>" style="width: 98%;">
						</td>
					</tr>
					
<!-- animateurs -->
					<tr>
						<td class="tdGauche">
							animateurs :
						</td>
						<td class="tdDroite">
							<?php echo $selectAnimateur;  ?>
						</td>
					</tr>
					
<!-- niveauSeance -->
					<tr>
						<td class="tdGauche">
							niveau :
						</td>
						<td class="tdDroite">
								<input type="text" name="niveauSeance" required value="<?php echo $seance['niveauSeance']; ?>" >
						</td>
					</tr>
					
					
<!-- maxSeance -->
					<tr>
						<td class="tdGauche">
							nombre maximum de participants (hors animateur) :
						</td>
						<td class="tdDroite">
								<input type="text" name="maxSeance" required value="<?php echo $seance['maxSeance']; ?>"  style="width: 20px">
						</td>
					</tr>
					
<!-- heureRDVSeance -->
					<tr>
						<td class="tdGauche">
							heure du rendez-vous :
						</td>
						<td class="tdDroite">
								<input type="time" name="heureRDVSeance" required value="<?php echo $seance['heureRDVSeance']; ?>" >
						</td>
					</tr>
					
					
<!-- lieuRDVSeance -->
					<tr>
						<td class="tdGauche">
							lieu du rendez-vous :
						</td>
						<td class="tdDroite">
								<input type="text" name="lieuRDVSeance" required value="<?php echo $seance['lieuRDVSeance']; ?>"  style="width: 98%;">
						</td>
					</tr>
					
					
<!-- remarqueSeance -->
					<tr>
						<td class="tdGauche">
							remarque :
						</td>
						<td class="tdDroite">
								<input type="text" name="remarqueSeance" required value="<?php echo $seance['remarqueSeance']; ?>" style="width: 98%;" >
						</td>
					</tr>
					
<!-- supprimeeSeance -->
					<tr>
						<td class="tdGauche">
							sortie annulée :
						</td>
						<td class="tdDroite">
								<?php echo $selectSupprimee; ?>
						</td>
					</tr>
					
				</tbody>
			</table>
			</form>
			<hr>
		</div>
			
		<div id="bas">
			<hr>
			<table>
				<tbody>
					<tr>
						<td class="tdTitre" style="text-align: center; width: 50%;">
								Enregistrer la sortie : 
								<button type="button" title="Enregistrer la sortie" onClick="document.getElementById('formGestion').submit();">
									<img alt="enregistrer" src="images/enregistrer.png">
								</button>
						</td>
						<td class="tdTitre" style="text-align: center;">
								Quitter sans enregistrer : 
								<button type="button" title="Quitter sans enregistrer" onClick="document.getElementById('newAction').value='retourner'; document.getElementById('formGestion').submit();">
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
//		return $message;
	} // fin function editerSeance

	function afficherAdherents($message) {
		
		// les intitulés des statuts
		$initituleStatut[1] = "adhérent";
		$initituleStatut[2] = "animateur";
		$initituleStatut[3] = "administrateur";
		$initituleStatut[5] = "administrateur et animateur";
		$initituleStatut[10] = "super-administrateur";
		$initituleStatut[12] = "super-administrateur et animateur";

		// les adhérents du club
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent  ORDER BY nomAdherent, prenomAdherent ";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$nbAdherents = 0;
		while ($unAdherent = mysqli_fetch_assoc($res)) {
			$adherent[$nbAdherents] = $unAdherent;
			// statut à afficher = intitulé
			$adherent[$nbAdherents]['statut'] = $initituleStatut[$unAdherent['statut']];
			// statut pour test
			$adherent[$nbAdherents]['numStatut'] = $unAdherent['statut'];

			$nbAdherents++;
		}
		
		// les colonnes à afficher
		$colonne[0]['titre'] = "n° de licence";
		$colonne[0]['nom'] = "licenceAdherent";
		$colonne[1]['titre'] = "NOM";
		$colonne[1]['nom'] = "nomAdherent";
		$colonne[2]['titre'] = "Prénom";
		$colonne[2]['nom'] = "prenomAdherent";
		$colonne[3]['titre'] = "statut";
		$colonne[3]['nom'] = "statut";
		$colonne[4]['titre'] = "adresse de courriel";
		$colonne[4]['nom'] = "courrielAdherent";
		$colonne[5]['titre'] = "n° mobile";
		$colonne[5]['nom'] = "mobileAdherent";
		
		// les intitulés des statuts
		$initituleStatut[1] = "adhérent";
		$initituleStatut[2] = "animateur";
		$initituleStatut[3] = "administrateur";
		$initituleStatut[5] = "administrateur et animateur";
		$initituleStatut[10] = "super-administrateur";
		$initituleStatut[12] = "super-administrateur et animateur";

?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formGestion" id="formGestion" action="gestionInscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="">
		<input type="hidden" name="idSeance" id="idSeance" value="">
		


		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "Gestion des $nbAdherents adhérent·e·s <br>pour {$_SESSION['prenomActeur']} {$_SESSION['nomActeur']} {$GLOBALS['statut']}";
		include("divEnTete.inc.php");

		if ($message != "") {
?>
							<p style="padding:5px; text-align: center; color: red;">
								<?php echo($message);?>
							</p>
<?php
		}
?>
			<hr>
		</div>
		
		<div id="content" style=" overflow:auto; "> <!-- position:absolute;   width:80%;-->
			<div id="contentContent" >
				
				<table class="hoverTable" border="0" width="100%" style="font-size:small;">
					<tbody>
						<tr class="trTitre" style="font-size:x-small; font-style:italic; text-align:center;">
							<td>&nbsp;</td>
<?php
	foreach ($colonne AS $uneColonne) {
?>
							<td><?php echo($uneColonne['titre']); ?></td>
<?php
	}
?>
						</tr>
<?php
		$tr = 0;
		foreach ($adherent AS $unAdherent) {
			$tr++;
?>
						<tr  style="font-size:x-small;" <?php if ($tr%2==0) echo(' class="trPair" '); else echo(' class="trImpair" ');?>>
							<td style="text-align: center;">
<?php 
 // un simple administrateur ne peut pas modifier un super-administrateur
			if ($unAdherent['numStatut']<10 || $_SESSION['statut']>=10) {
?>
								<input name="select" value="<?php echo($unAdherent['licenceAdherent']); ?>" form="formGestion" type="radio">
<?php 
		}
?>
							</td>
<?php
	foreach ($colonne AS $uneColonne) {
?>
							<td><?php echo($unAdherent[$uneColonne['nom']]); ?></td>
<?php
	}
?>
						</tr>
				
<?php
		} // fin foreach ($adherent
?>
					</tbody>
				</table>


			</div>  <!-- fin div contentContent -->
		</div> <!-- fin div content -->

		<div id="bas">
			<hr>
			<table>
				<tbody>
					<tr>
						<td class="tdTitre" style="text-align: center; width: 25%;">
							Modifier l'adhérent·e<br> 
							<button type="button" title="Modifier l'adhérent·e sélectionné·e") onClick="						if (CheckRadio('select')) {
										document.getElementById('newAction').value='modifierAdherent'; document.getElementById('formGestion').submit();
								}
								else alert('Veuillez sélectionner l\'adhérent·e à modifier');
							">
							<img alt="stat" src="images/editer.png">
							</button>
						</td>
						<td class="tdTitre" style="text-align: center; width: 25%;">
							Supprimer l'adhérent·e<br> 
							<button type="button" title="Supprimer l'adhérent·e sélectionné·e" onClick="
							if (CheckRadio('select')) {
								if (confirm('Êtes-vous sûr de vouloir supprimer l\'adhérent·e sélectionné·e ?'))
								{document.getElementById('newAction').value='supprimerAdherent'; document.getElementById('formGestion').submit();}
							}
							else alert('Veuillez sélectionner l\'adhérent·e à supprimer');
							">
							<img alt="stat" src="images/close.png">
							</button>
						</td>
						<td class="tdTitre" style="text-align: center; width: 25%;">
							Ajouter un·e adhérent·e<br> 
							<button type="button" title="Ajouter un·e adhérent·e" onClick="document.getElementById('newAction').value='ajouterAdherent'; document.getElementById('formGestion').submit();">
							<img alt="stat" src="images/add1.png">
							</button>
						</td>
						<td class="tdTitre" style="text-align: center; width: 25%;">
							Retour aux sorties<br>
							<button type="button" title="Retourner aux sorties" onClick="
								document.getElementById('newAction').value='retourner'; document.getElementById('formGestion').submit();">
							<img alt="retour" src="images/retour.png">
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
	}
	
	function afficherEditerAdherent($licence) {
		// si $licence=="" alors ajout
		// sinon modification
		if ($licence=="") $modeEdition = "ajout";
		else $modeEdition = "modification";
		// champ $modeEdition : 'ajout' ou 'modification'
		
		// si modification rechercher l'adhérent'
		if ($modeEdition=="modification") {
			$sql = "SELECT licenceAdherent, statut, nomAdherent, prenomAdherent, courrielAdherent, mobileAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$licence'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			$adherent = mysqli_fetch_assoc($res);
		}
		else { // ajout
			$adherent['licenceAdherent'] = "";
			$adherent['nomAdherent'] = "";
			$adherent['prenomAdherent'] = "";
			$adherent['statut'] = 1;
			$adherent['courrielAdherent'] = "";
			$adherent['mobileAdherent'] = "";
		}
		$tabStatut[1] = "adhérent";
		$tabStatut[2] = "animateur";
		$tabStatut[3] = "administrateur";
		$tabStatut[5] = "administrateur et animateur";
		$tabStatut[10] = "super-administrateur";
		$tabStatut[12] = "super-administrateur et animateur";
		
		// select statut
		$selectStatut = <<<EOT
						<select name="statut"  style="font-size:small;  width:300px;">
EOT;
		foreach ($tabStatut AS $i => $nomStatut) {
			// pour un simple admin les statuts super-admin ne sont pas proposés
			if ($i<10 || $_SESSION['statut']>=10) {
				$selectStatut .= <<<EOT
								<option value="$i"
EOT;
				if ($adherent['statut']==$i) $selectStatut .= " selected ";
				$selectStatut .= <<<EOT
								> $nomStatut
								</option>
EOT;

			}	
		}

		$selectStatut .= <<<EOT
						</select>
EOT;

// champ mode : 'ajout' ou 'modification'
?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formAdherent" id="formAdherent" action="gestionInscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="enregistrerEditerAdherent">
		<input type="hidden" name="modeEdition" id="modeEdition" value="<?php echo($modeEdition) ?>">
		<input type="hidden" name="ancienneLicenceAdherent" id="ancienneLicenceAdherent" value="<?php echo($licence); ?>">

		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "Edition de l'adhérent·e";
		include("divEnTete.inc.php");
?>
		</div>
		<div id="content">
			<br><br><br>
			<hr>
				<table>

						<tbody>
						<tr>
							<td class="tdGauche">
								n° de licence : 
							</td>
							<td class="tdDroite">
								
								<input required="required" type="text"  name="licenceAdherent"   id="licenceAdherent"  value="<?php echo($adherent['licenceAdherent']); ?>" style="font-size:small;  width:150px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								NOM : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="nomAdherent"   id="nomAdherent"  value="<?php echo($adherent['nomAdherent']);?>" style="font-size:small;  width:150px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								Prénom : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="prenomAdherent"   id="prenomAdherent"  value="<?php echo($adherent['prenomAdherent']);?>" style="font-size:small;  width:150px;">
							</td>
						</tr>
						
						
						<tr>
							<td class="tdGauche">
								statut : 
							</td>
							<td class="tdDroite">
								<?php echo $selectStatut; ?>
							</td>
						</tr>
						
						
						
						<tr>
							<td class="tdGauche">
								adresse de courriel : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="courrielAdherent"   id="courrielAdherent"  value="<?php echo($adherent['courrielAdherent']);?>" style="font-size:small;  width:150px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								numéro de mobile : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="mobileAdherent"   id="mobileAdherent"  value="<?php echo($adherent['mobileAdherent']);?>" style="font-size:small;  width:150px;">
							</td>
						</tr>

						<tr>
							<td  class="tdGauche">
								puis enregistrez : 
							</td>
							<td class="tdDroite">
								<input value="Enregistrer" name="Envoyer" type="submit" style="font-size:medium; font-weight:bold; width:150px;" >
							</td>
						</tr>

						<tr>
							<td  class="tdGauche">
								ou quittez sans enregistrer : 
							</td>
							<td class="tdDroite">
								<button type="button" title="Quitter" onClick="
								document.getElementById('newAction').value='gererAdherents'; document.getElementById('formAdherent').submit();">Quitter							</button>

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

	}
	
	function enregistrerEditerAdherent($modeEdition) {
		$_POST['nomAdherent'] = strtoupper($_POST['nomAdherent']);
		$_POST['prenomAdherent'] = ucwords(strtolower($_POST['prenomAdherent']));
		// $modeEdition = "ajout" ou "modification"
		if ($modeEdition=="ajout") { //INSERT
			// contôle de doublon pour le nouveau n° de licence 
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent = '".$_POST['licenceAdherent']."'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error ($GLOBALS['lkId']));
			if ($ligne = mysqli_fetch_assoc ($res)) {
				return "L'adhérent·e n'a pas été enregistré·e  car le numéro de licence proposé est déjà enregistré.";
			} 
			else {
			// insert
				$sql = "INSERT INTO {$GLOBALS['prefixe']}adherent (licenceAdherent, statut, nomAdherent, prenomAdherent, courrielAdherent, mobileAdherent, dateLimiteAttenteAdherent) VALUES ('".$_POST['licenceAdherent']."', ".$_POST['statut'].", '".$_POST['nomAdherent']."', '".$_POST['prenomAdherent']."', '".$_POST['courrielAdherent']."', '".$_POST['mobileAdherent']."', NULL)";
//die($sql);
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error ($GLOBALS['lkId']));
/*				
				// courriel à admin
				$destinataire = "mac.lc@free.fr";
				$de = "mac.lc@free.fr";
				$sujet = "nouvel adhérent";
				$codeHtml = "<html><p>Bonjour</p>";
				$codeHtml .= "<p>Voici les coordonnées du nouvel adhérent :</p>";
				$codeHtml .= "<p>".$_POST['licenceAdherent']."', '".$_POST['nomAdherent']."', '".$_POST['prenomAdherent']."', '".$_POST['courrielAdherent']."', '".$_POST['mobileAdherent']."'.</p>";
				$codeHtml .= "<p>Cordialement</p>";
				envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
*/				

			}

			return "L'adhérent·e a été ajouté·e";
		}
		else { // modification : UPDATE
			// ? adresse courriel modifiée ?
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent = '".$_POST['ancienneLicenceAdherent']."'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			$adherent = mysqli_fetch_assoc ($res);
			$ancienCourriel = $adherent['courrielAdherent'];
			$courrielModifie = $ancienCourriel!=$_POST['courrielAdherent'];

			// contôle de doublon pour le nouveau n° de licence 
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent = '".$_POST['licenceAdherent']."'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error ($GLOBALS['lkId']));
			if ($ligne = mysqli_fetch_assoc ($res) && $_POST['licenceAdherent']!=$_POST['ancienneLicenceAdherent']) {
				return "Le compte ne peut pas être mis à jour car le numéro de licence proposé est déjà enregistré.";
			} 
			else {
				// update
				$sql = "UPDATE {$GLOBALS['prefixe']}adherent SET statut={$_POST['statut']}, licenceAdherent =  '".$_POST['licenceAdherent']."', nomAdherent = '".$_POST['nomAdherent']."', prenomAdherent = '".$_POST['prenomAdherent']."', courrielAdherent = '".$_POST['courrielAdherent']."', mobileAdherent = '".$_POST['mobileAdherent']."' WHERE licenceAdherent = '".$_POST['ancienneLicenceAdherent']."'";

				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die (mysqli_error ($GLOBALS['lkId']));
/*				
				// info admin si courriel modifiée
				if ($courrielModifie) {
					$destinataire = "mac.lc@free.fr";
					$de = "mac.lc@free.fr";
					$sujet = "adresse courriel adhérent modifiée";
					$codeHtml = "<html><p>Bonjour</p>";
					$codeHtml .= "<p>Voici les coordonnées de l'adhérent qui a modifié son adresse de courriel (ancienne courriel : $ancienCourriel):</p>";
					$codeHtml .= "<p>".$_POST['licenceAdherent'].", ".$_POST['nomAdherent'].", ".$_POST['prenomAdherent'].", ".$_POST['courrielAdherent'].", ".$_POST['mobileAdherent'].".</p>";
					$codeHtml .= "<p>Cordialement</p>";
					envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
					
				}
*/
				return "L'adhérent·e a été modifié·e";
			}
		}
	}
	
	function supprimerAdherent($licence) {
		$sql = "DELETE FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$licence'";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		return "L'adhérent·e $licence a été supprimé·e";
	}
	
// fonction telecharger toutes les données
	function telechargerStatistiques() {

		// recherche nom prénom du gestionaire
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='{$_SESSION['idActeur']}' ";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$prenom = $ligne['prenomAdherent'];
		$nom = $ligne['nomAdherent'];

		$maintenantTimeStamp = time();
		$maintenant= strftime('%d/%m/%Y à %Hh%M',$maintenantTimeStamp);
		
		$csv = "Statistiques des inscriptions établies le ". $maintenant;
		$csv .= chr(10) ;
		$csv .= "pour $prenom $nom {$_SESSION['statut']} ";
		$csv .= chr(10) ;
		// les noms de champ : commentaire si non vide, sinon id
		$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}inscription";
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($ligne=mysqli_fetch_assoc ($res)) {
			if ($ligne['Comment']!="") $csv .=  '"' .str_replace('"','""', $ligne['Comment']).'";';
			else 	$csv .= '"'.$ligne['Field'].'";';
		}
		$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}adherent";
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($ligne=mysqli_fetch_assoc ($res)) {
			if ($ligne['Comment']!="") $csv .=  '"' .str_replace('"','""', $ligne['Comment']).'";';
			else 	$csv .= '"'.$ligne['Field'].'";';
		}
		$sql = "SHOW FULL COLUMNS FROM {$GLOBALS['prefixe']}seance";
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($ligne=mysqli_fetch_assoc ($res)) {
			if ($ligne['Comment']!="") $csv .=  '"' .str_replace('"','""', $ligne['Comment']).'";';
			else 	$csv .= '"'.$ligne['Field'].'";';
		}
/*
		$sql = "SHOW FULL COLUMNS FROM animateur";
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($ligne=mysqli_fetch_assoc ($res)) {
			if ($ligne['Comment']!="") $csv .=  '"' .str_replace('"','""', $ligne['Comment']).'";';
			else 	$csv .= '"'.$ligne['Field'].'";';
		}
*/
		$csv .= chr(10) ;
		// WHERE selon statut
		$where = "";

		// les lignes
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}adherent,  {$GLOBALS['prefixe']}seance WHERE adherentLicence=licenceAdherent AND seanceId=idSeance  ".$where." ORDER BY dateSeance, dateHeureInscription";
//die($sql);
		$res = mysqli_query($GLOBALS['lkId'],$sql);
		while ($ligne=mysqli_fetch_assoc ($res)) {
//var_dump($ligne);die;
			foreach ($ligne AS $valeur) {

				if ($valeur[0]>'0' && $valeur[0]<='9' ) $csv .= $valeur.";";
				else $csv .= '"' .str_replace('"','""',$valeur).'";';

//				$csv .= 'yeah'.';';
			}
			$csv .= chr(10) ;
		}

		$nomFichier = "Statistiques.csv";
		$contentType = "text/csv";
		$longueur = strlen($csv);
		header("Content-Type: text/csv; charset=UTF-8");
//		header("Content-Length: ".$longueur."\"");
		header("Content-Length: ".$longueur);
//		header('Content-Disposition: attachment; filename="'.$nomFichier."\"'");
		header('Content-Disposition: attachment; filename="'.$nomFichier.'"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		//	header('Cache-Control: private');
		//	header('Pragma:  no-cache');
		echo ($csv);
		die();	
	} // fin fonction telecharger

	function enregistrerSeance() {
//var_dump($_POST);die();		
		// insert ou update ??
		if ($_POST['idSeance']=='') $mode ='insert';
		else $mode = 'update';
		
		// echappement des POST
		foreach ($_POST AS $key => $value) {
			if ($key!='licenceAnimateur') $_POST[$key] = addslashes($value);
		}
		
		if ($mode=='insert') { // ajout nouvelle sortie et animateurs 
			// ajout de la nouvelle sortie
			$sql = "INSERT INTO {$GLOBALS['prefixe']}seance (idSeance, nomSeance, niveauSeance, remarqueSeance, dateSeance, heureRDVSeance, lieuRDVSeance, maxSeance, supprimeeSeance) VALUES (NULL, '{$_POST['nomSeance']}', '{$_POST['niveauSeance']}','{$_POST['remarqueSeance']}', '{$_POST['dateSeance']}', '{$_POST['heureRDVSeance']}', '{$_POST['lieuRDVSeance']}', {$_POST['maxSeance']}, '{$_POST['supprimeeSeance']}')";
			$res = mysqli_query ($GLOBALS['lkId'], $sql);
			$idSeance =  mysqli_insert_id($GLOBALS['lkId']);

			// ajout des animateurs
			foreach ($_POST[licenceAnimateur] AS $animateurLicence) {
				$sql = "INSERT INTO {$GLOBALS['prefixe']}seanceAnimateur (idSeanceAnimateur, seanceId, animateurLicence) VALUES (NULL, {$_POST['idSeance']}, '$animateurLicence')";
				$res = mysqli_query ($GLOBALS['lkId'], $sql);
			}
			
			$message = "La sortie '{$_POST['nomSeance']}' a été ajoutée.";
		}
		else { // mode "update" mise à jour de la sortie et des animateurs
			// ancienne sortie supprimée ?
			$sql = "SELECT supprimeeSeance FROM {$GLOBALS['prefixe']}seance WHERE idSeance={$_POST['idSeance']}";
			$res = mysqli_query($GLOBALS['lkId'],$sql);
			$oldSeance = mysqli_fetch_assoc ($res);
			$supprimeeOldSeance = $oldSeance['supprimeeSeance'];

			// mise à jour de la sortie 
			$sql = "UPDATE {$GLOBALS['prefixe']}seance SET  nomSeance='{$_POST['nomSeance']}', niveauSeance='{$_POST['niveauSeance']}', remarqueSeance='{$_POST['remarqueSeance']}', dateSeance='{$_POST['dateSeance']}', heureRDVSeance='{$_POST['heureRDVSeance']}', lieuRDVSeance='{$_POST['lieuRDVSeance']}', maxSeance={$_POST['maxSeance']}, supprimeeSeance='{$_POST['supprimeeSeance']}' WHERE  idSeance={$_POST['idSeance']}";
			$res = mysqli_query($GLOBALS['lkId'],$sql);
			
			// courriels si changement de statut annulée ou non
			$suppression = $supprimeeOldSeance=='N' && $_POST['supprimeeSeance']=='O';
			$retablissement = $supprimeeOldSeance=='O' && $_POST['supprimeeSeance']=='N';
			if ($suppression) { // informer annulation
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}inscription WHERE licenceAdherent = adherentLicence AND seanceId = '{$_POST['idSeance']}' AND dateHeureAnnulationInscription IS NULL";
				$res = mysqlmd_query ($GLOBALS['lkId'], $sql) or die (mysqlmd_error($GLOBALS['lkId']));
				while ($inscription = mysqlmd_fetch_assoc ($res)) {
					$destinataire = $inscription['courrielAdherent'];
					$de = $GLOBALS['courrielClub'];
					$sujet = $GLOBALS['nomClub']." : annulation de sortie";
					$prenom = $inscription['prenomAdherent'];
					$nom = $inscription['nomAdherent'];

					$codeHtml = "<html>";
					$codeHtml .= "<p>à $prenom $nom</p><p> </p>";
					$codeHtml .= "<p>Bonjour</p>";
					$codeHtml .= "<p>Nous sommes au regret de vous informer que la sortie '{$_POST['nomSeance']}' du ".jourDateFr($_POST['dateSeance'])." a été annulée. </p>";
					$codeHtml .= "<p>Cordialement</p>";
					$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
					$codeHtml .= "</html>";

					if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
				}
				
			}
			if ($retablissement) { // informer rétablissement
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}inscription WHERE licenceAdherent = adherentLicence AND seanceId = '{$_POST['idSeance']}' AND dateHeureAnnulationInscription IS NULL";
				$res = mysqlmd_query ($GLOBALS['lkId'], $sql) or die (mysqlmd_error($GLOBALS['lkId']));
				while ($inscription = mysqlmd_fetch_assoc ($res)) {
					$destinataire = $inscription['courrielAdherent'];
					$de = $GLOBALS['courrielClub'];
					$sujet = $GLOBALS['nomClub']." : rétablissement de sortie annulée";
					$prenom = $inscription['prenomAdherent'];
					$nom = $inscription['nomAdherent'];

					$codeHtml = "<html>";
					$codeHtml .= "<p>à $prenom $nom</p><p> </p>";
					$codeHtml .= "<p>Bonjour</p>";
					$codeHtml .= "<p>Nous sommes heureux de vous informer que la sortie '{$_POST['nomSeance']}' du ".jourDateFr($_POST['dateSeance'])." qui avait été annulée aura bien lieu. Si une place vous avez été attribuée, elle redevient effective ; si vous aviez été placé·e en liste d'attente, votre rang est de nouveau effectif.</p>";
					$codeHtml .= "<p>Cordialement</p>";
					$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
					$codeHtml .= "</html>";

					if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
				}
			}
			
			// suppression des seanceAnimateur
			$sql = "DELETE FROM {$GLOBALS['prefixe']}seanceAnimateur WHERE seanceId={$_POST['idSeance']}";
			$res = mysqli_query($GLOBALS['lkId'],$sql);
			
			// ajout des seanceAnimateur
			foreach ($_POST['licenceAnimateur'] AS $animateurLicence) {
				$sql = "INSERT INTO {$GLOBALS['prefixe']}seanceAnimateur(idSeanceAnimateur, seanceId, animateurLicence) VALUES (NULL, {$_POST['idSeance']}, '$animateurLicence')";
				$res = mysqli_query ($GLOBALS['lkId'], $sql);
			}
			
			$message = "La sortie '{$_POST['nomSeance']}' a été modifiée.";
		}

		return($message);	
	} // fin enregistrerSeance
	
	function enregistrerAbsences() {
//var_dump($_POST); die();
		// $_POST['idSeance']
		// $_POST['absenceLicenceAdherent']
		// $_POST['absenceExcuseeLicenceAdherent']
		// $_POST['annulationLicenceAdherent']
		// $_POST['annulationExcuseeLicenceAdherent']
		

		// calcul dateLimiteAttente éventuelle
		$aujourdhuiTS = time();
		$dateLimiteAttenteTS = $aujourdhuiTS+3600*24*30;
		$dateLimiteAttente = date('Y-m-d', $dateLimiteAttenteTS);
		
		// recherche séance
		$idSeance = $_POST['idSeance'];
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance=$idSeance";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$seance = mysqli_fetch_assoc ($res);

		// recherche de toutes les inscriptions
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=$idSeance";
//die($sql);
 		$res = mysqli_query ($GLOBALS['lkId'], $sql);

		// pour chaque inscription
		while ($inscription = mysqli_fetch_assoc ($res)) {
 			$idInscription = $inscription['idInscription'];
			$licenceAdherent = $inscription['adherentLicence'];
			// recherche du courriel de licenceAdherent absent
			$sql0 = "SELECT courrielAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$licenceAdherent'";
 			$res0 = mysqli_query ($GLOBALS['lkId'], $sql0);
			$adherent = mysqli_fetch_assoc ($res0);
			$destinataire = $adherent['courrielAdherent'];
/*			
if ($licenceAdherent=='0952931U') {
	echo($licenceAdherent.'<br>\n');
	var_dump($_POST['absenceLicenceAdherent']);
	die();
}
*/

/*
////////////////////////////////////////////////////////////////////////////////////////////
		// absence
			// si absence cochée
			if (isset($_POST['absenceLicenceAdherent']) && in_array($licenceAdherent,$_POST['absenceLicenceAdherent'])) {
				// si pas enregistrée
				if (is_null($inscription['absenceInscription'])) {
					// enregistrer l'absence
					$sql1 = "UPDATE inscription SET absenceInscription=1 WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
					$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
					// si excusée cochée
					if (isset($_POST['absenceExcuseeLicenceAdherent']) && in_array($licenceAdherent,$_POST['absenceExcuseeLicenceAdherent'])) {
						// enregistrer l'excuse
						$sql1 = "UPDATE inscription SET absenceExcuseeInscription=1 WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
						// dépunir
					}
					// sinon (excuse noncochée)
						// punir : un mois (de plus)
						// annuler l'excuse qui traîne !
				}
			}
			// sinon (absence  pas cochée)
				// si absence enregistrée
					// annuler l'absence (et l'excuse)
					// dépunir (un mois de moins)
				// sinon
					// annuler l'excuse à tout hasard
					
		// annulation tardive


///////////////////////////////////////////////////////////////////////////////////////////
*/

			// si absence excusée cochée
			if (isset($_POST['absenceExcuseeLicenceAdherent']) && in_array($licenceAdherent,$_POST['absenceExcuseeLicenceAdherent'])) {
 				// si absence excusée non enregistrée, l'enregistrer
				if (is_null($inscription['absenceExcuseeInscription'])) {
					$sql1 = "UPDATE {$GLOBALS['prefixe']}inscription SET absenceExcuseeInscription=1 WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
//die($sql1);
					$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
				}
			}
			// si absence cochée
			if (isset($_POST['absenceLicenceAdherent']) && in_array($licenceAdherent,$_POST['absenceLicenceAdherent'])) {
 				// si absence non enregistrée, l'enregistrer
				if (is_null($inscription['absenceInscription'])) {
					$sql1 = "UPDATE {$GLOBALS['prefixe']}inscription SET absenceInscription=1 WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
//die($sql1);
					$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
				}
				// si sanction enregistrée
				if (!is_null($inscription['dateLimiteAttenteInscription'])) {
 					// si excusée :
					if (isset($_POST['absenceExcuseeLicenceAdherent']) && in_array($licenceAdherent,$_POST['absenceExcuseeLicenceAdherent'])) {
						// lever sanction inscription dateLimiteAttenteInscription
						$sql2 = "UPDATE {$GLOBALS['prefixe']}inscription SET dateLimiteAttenteInscription= NULL WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// lever la sanction adhérent dateLimiteAttenteAdherent
						// rechercher max dateLimiteAttenteInscription
						$sql2 = "SELECT MAX(dateLimiteAttenteInscription) AS max FROM {$GLOBALS['prefixe']}inscription WHERE adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						$maxLimite = mysqli_fetch_assoc ($res2);
						if (is_null($maxLimite)) $dateLimiteAttenteAdherent = NULL;
						else $dateLimiteAttenteAdherent = $maxLimite['max'];
						// appliquer ce max à dateLimiteAttenteAdherent
						$sql2 = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent= $dateLimiteAttente WHERE licenceAdherent='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// informer adhérent levée 
						// avertir l'absent
						$de = $GLOBALS['courrielClub'];
						$sujet = $GLOBALS['nomClub']." : absence à une sortie";
						$codeHtml = "<html><p>Bonjour</p>";
						if (is_null($dateLimiteAttenteAdherent))
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que votre absence à la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance'])." a été excusée et donc que vos demandes d'inscription seront prises en compte immédiatement.</p>";
						else {
							$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttenteAdherent);
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que votre absence à la sortie '{$seance['nomSeance']}' du ".jourDateFr($eance['dateSeance'])." a été excusée ; vos demandes d'inscription ne seront prises en compte que la veille de la date limite d'inscription jusqu'au $dateLimiteAttenteAdherent.</p>";
						}
						$codeHtml .= "<p>Cordialement</p>";
						$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
						if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
					} // (sinon on la laisse enregistrée)
				} // fin si sanction enregistrée
				// sinon sanction pas enregistrée
				else {
					// si pas excusée :
					if (!isset($_POST['absenceExcuseeLicenceAdherent']) || !in_array($licenceAdherent,$_POST['absenceExcuseeLicenceAdherent'])) {
						// enregistrer dateLimiteAttenteInscription
						$sql2 = "UPDATE {$GLOBALS['prefixe']}inscription SET dateLimiteAttenteInscription='$dateLimiteAttente' WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// enregister dateLimiteAttenteAdherent
						$sql2 = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent='$dateLimiteAttente' WHERE licenceAdherent='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// informer adhérent sanction
						$de = $GLOBALS['courrielClub'];
						$sujet = $GLOBALS['nomClub']." : absence à une sortie";
						$codeHtml = "<html><p>Bonjour</p>";
						
						$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttente);
						$codeHtml .= "<p>Nous sommes au regret de vous informer qu'en raison de votre absence à la la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance']).", vos demandes d'inscription ne seront prises en compte que la veille de la date limite d'inscriptiion jusqu'au $dateLimiteAttenteAdherent.</p>";
						$codeHtml .= "<p>Cordialement</p>";
						$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
						if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
					} // fin si pas excusée
					// sinon (excusée) ne rien faire de plus
				}
			} // fin si absence cochée
			// sinon (absence pas cochée)
			else {
				// si absence enregistrée
				if (!is_null($inscription['absenceInscription'])) {
					// annuler absence et son éventuelle excuse
					$sql2 = "UPDATE {$GLOBALS['prefixe']}inscription SET absenceInscription= NULL, absenceExcuseeInscription=NULL WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
					$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
					// si sanction enregistrée
					if (!is_null($inscription['dateLimiteAttenteInscription'])) {
						// lever sanction inscription dateLimiteAttenteInscription
						$sql2 = "UPDATE {$GLOBALS['prefixe']}inscription SET dateLimiteAttenteInscription= NULL WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// lever la sanction adhérent dateLimiteAttenteAdherent
						// rechercher max dateLimiteAttenteInscription
						$sql2 = "SELECT MAX(dateLimiteAttenteInscription) AS max FROM {$GLOBALS['prefixe']}inscription WHERE adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						$maxLimite = mysqli_fetch_assoc ($res2);
						if (is_null($maxLimite)) $dateLimiteAttenteAdherent = NULL;
						else $dateLimiteAttenteAdherent = $maxLimite['max'];
						// appliquer ce max à dateLimiteAttenteAdherent
						$sql2 = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent= $dateLimiteAttente WHERE licenceAdherent='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// informer adhérent levée 
						// avertir l'absent
						$de = $GLOBALS['courrielClub'];
						$sujet = $GLOBALS['nomClub']." : absence à une sortie";
						$codeHtml = "<html><p>Bonjour</p>";
						if (is_null($dateLimiteAttenteAdherent))
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que votre absence à la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance'])." a été annulée et donc que vos demandes d'inscription seront prises en compte immédiatement'.</p>";
						else {
							$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttenteAdherent);
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que votre absence à la la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance'])." a été annulée ; vos demandes d'inscription ne seront prises en compte que la veille de la date limite d'inscription que jusqu'au $dateLimiteAttenteAdherent.</p>";
						}
						$codeHtml .= "<p>Cordialement</p>";
						$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
						if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);

					} // fin si sanction enregistrée
				} // fin si absence enregistrée
				
			} // fin si absence pas cochée
		} // fin pour chaque inscription	
		
		
		// $_POST['idSeance']
		// $_POST['absenceLicenceAdherent']
		// $_POST['absenceExcuseeLicenceAdherent']
		// $_POST['annulationLicenceAdherent']
		// $_POST['annulationExcuseeLicenceAdherent']
		
		
		// traitement des annulations tardives
		// si existe(nt) annulation(s) tardive(s)
		if (isset($_POST['annulationLicenceAdherent'])) {
			// pour chaque annulation
			foreach($_POST['annulationLicenceAdherent'] AS $annulation) {
				$licenceAdherent = $annulation;
				// recherche de l'inscription correspondante
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";

				$res = mysqli_query ($GLOBALS['lkId'], $sql);
				$inscription = mysqli_fetch_assoc ($res);
				// recherche du courriel de licenceAdherent absent
				$sql = "SELECT courrielAdherent FROM {$GLOBALS['prefixe']}adherent WHERE licenceAdherent='$licenceAdherent'";
				$res = mysqli_query ($GLOBALS['lkId'], $sql);
				$adherent = mysqli_fetch_assoc ($res);
				$destinataire = $adherent['courrielAdherent'];

				// si sanction enregistrée
				if (!is_null($inscription['dateLimiteAttenteInscription'])) {
					// si excusée
//					if (isset($_POST['annulationExcuseeLicenceAdherent'][$licenceAdherent])) {
					if (isset($_POST['annulationExcuseeLicenceAdherent']) && in_array($licenceAdherent,$_POST['annulationExcuseeLicenceAdherent'])) {
						// lever sanction inscription dateLimiteAttenteInscription
						$sql2 = "UPDATE {$GLOBALS['prefixe']}inscription SET dateLimiteAttenteInscription= NULL WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// lever la sanction adhérent dateLimiteAttenteAdherent
						// rechercher max dateLimiteAttenteInscription
						$sql2 = "SELECT MAX(dateLimiteAttenteInscription) AS max FROM {$GLOBALS['prefixe']}inscription WHERE adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						$maxLimite = mysqli_fetch_assoc ($res2);
						if (is_null($maxLimite)) $dateLimiteAttenteAdherent = NULL;
						else $dateLimiteAttenteAdherent = $maxLimite['max'];
						// appliquer ce max à dateLimiteAttenteAdherent
						$sql2 = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent= $dateLimiteAttente WHERE licenceAdherent='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// informer adhérent levée 
						// avertir l'absent
						$de = $GLOBALS['courrielClub'];
						$sujet = $GLOBALS['nomClub']." : annulation tardive d'une sortie";
						$codeHtml = "<html><p>Bonjour</p>";
						if (is_null($dateLimiteAttenteAdherent))
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que l'annulation tardive de votre inscription à la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance'])." a été excusée et donc que vos demandes d'inscription seront prises en compte immédiatement'.</p>";
						else {
							$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttenteAdherent);
							$codeHtml .= "<p>Nous avons le plaisir de vous informer que l'annulation tardive de votre inscription à la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance'])." a été excusée ; vos demandes d'inscription ne seront prises en compte que la veille de la date limite d'inscription que jusqu'au $dateLimiteAttenteAdherent.</p>";
						}
						$codeHtml .= "<p>Cordialement</p>";
						$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
						if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
					} // fin si excusée
					// sinon ne rien faire
				}
				// sinon (sanction non enregistrée)
				else {
					// si non excusée
//					if (!isset($_POST['annulationExcuseeLicenceAdherent'][$licenceAdherent])) {
					if (!isset($_POST['annulationExcuseeLicenceAdherent']) || !in_array($licenceAdherent,$_POST['annulationExcuseeLicenceAdherent'])) {
						// enregistrer dateLimiteAttenteInscription
						$sql2 = "UPDATE{$GLOBALS['prefixe']} inscription SET dateLimiteAttenteInscription='$dateLimiteAttente' WHERE seanceId=$idSeance AND adherentLicence='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// enregister dateLimiteAttenteAdherent
						$sql2 = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent='$dateLimiteAttente' WHERE licenceAdherent='$licenceAdherent'";
						$res2 = mysqli_query ($GLOBALS['lkId'], $sql2);
						// informer adhérent sanction
						$de = $GLOBALS['courrielClub'];
						$sujet = $GLOBALS['nomClub']." : absence à une sortie";
						$codeHtml = "<html><p>Bonjour</p>";
						
						$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttente);
						$codeHtml .= "<p>Nous sommes au regret de vous informer qu'en raison de l'annulation tardive de votre inscription à la sortie '{$seance['nomSeance']}' du ".jourDateFr($seance['dateSeance']).", vos demandes d'inscription ne seront prises en compte que la veille de la date limite d'inscription jusqu'au $dateLimiteAttenteAdherent.</p>";
						$codeHtml .= "<p>Cordialement</p>";
						$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
						if ($destinataire!='') envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
					} // fin si non excusée
					// sinon ne rien faire
				}
			} // fin pour chaque annulation
		} // fin si existe annulation tardive

/*	
		
	if (isset($_POST['absenceLicenceAdherent'])) {
			$nbAbsents = 0;
			foreach ($_POST['licenceAdherent'] AS $licenceAdherent ) {
				$nbAbsents++;
				// recherche de l'idInscription de l'absent'
				$sql = "SELECT * FROM inscription WHERE seanceId={$_POST['idSeance']} AND adherentLicence='$licenceAdherent'";
				$res = mysqli_query ($GLOBALS['lkId'], $sql);
				$inscription = mysqli_fetch_assoc ($res);
				$idInscription = $inscription['idInscription'];
				
				// si l'absence n'a pas déjà été enregistrée
				if (is_null($inscription['absenceInscription']) ) {
					// enregistrer l'absence
					$sql = "UPDATE inscription SET absenceInscription=1 WHERE idInscription=$idInscription";

					$res = mysqli_query ($GLOBALS['lkId'], $sql);
					// recherche du courriel de licenceAdherent absent
					$sql = "SELECT courrielAdherent FROM adherent WHERE licenceAdherent='$licenceAdherent'";
					$res = mysqli_query ($GLOBALS['lkId'], $sql);
					$adherent = mysqli_fetch_assoc ($res);
					$destinataire = $adherent['courrielAdherent'];
					// recherche séance
					$sql = "SELECT * FROM seance WHERE idSeance={$_POST['idSeance']}";
					$res = mysqli_query ($GLOBALS['lkId'], $sql);
					$seance = mysqli_fetch_assoc ($res);
					// avertir le puni
					$debutTS = strtotime($seance['dateSeance']);
					$dateLimiteAttenteAdherent = date("Y-m-d",$debutTS+(3600*24*30));
					$sql = "UPDATE adherent SET dateLimiteAttenteAdherent = '$dateLimiteAttenteAdherent' WHERE adherent.licenceAdherent = '$licenceAdherent'";
					$res = mysqli_query ($GLOBALS['lkId'], $sql);
					$dateLimiteAttenteAdherent = nationaliserDate($dateLimiteAttenteAdherent);
					$de = "mac.lc@free.fr";
					$sujet = "absence à une séance ";
					$codeHtml = "<html><p>Bonjour</p>";
					$codeHtml .= "<p>Nous sommes au regret de vous informer qu'en raison de votre absence à la séance de  du ".jourDateFr($seance['dateSeance']).", rendez-vous :  {$seance['lieuRDVSeance']} à ".nationaliserHeure($seance['heureRDVSeance']).", vous serez placé(e) en liste d'attente lors de vos prochaines inscriptions jusqu'au $dateLimiteAttenteAdherent.</p>";
					$codeHtml .= "<p>Cordialement</p>";
					$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
					envoyerCourriel($destinataire,$sujet,$codeHtml);
					enregistrerLog($_SESSION['idActeur'],$_SESSION['statut'],"information d'un absent $licenceAdherent");
				}
			}
			if ($nbAbsents==1) return("L'absent est enregistré.");
			else return("Les $nbAbsents absents sont enregistrés.");
			enregistrerLog($_SESSION['idActeur'],$_SESSION['statut'],"enregistrement absence inscription n°{$_POST['idSeance']} ");
		}
		else return("Aucune absence enregistrée");
*/	
//  die("fin function enregistrerAbsences");
	} // fin function enregistrerAbsences() 

	function gererAbsences($idSeance, $message) {
		
		// recherche de la séance
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance= $idSeance";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$seance = mysqli_fetch_assoc ($res);
		
		// recherche des inscrits non annulés avec place réservée
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}adherent WHERE seanceId=$idSeance AND licenceAdherent=adherentLicence   AND (attenteInscription IS NULL OR attenteInscription<0) AND dateHeureAnnulationInscription IS NULL ORDER BY nomAdherent, prenomAdherent";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$inscrit = array();
		while ($unInscrit = mysqli_fetch_assoc ($res)) {
			$inscrit[] = $unInscrit;
		}
		
		// recherche des inscrits annulés tardivement non attenteInscription=-1 (attribution tardive pour en attente)
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}adherent WHERE seanceId=$idSeance AND licenceAdherent=adherentLicence AND annulationTardiveInscription ='oui'   AND attenteInscription IS NULL  ORDER BY nomAdherent, prenomAdherent";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$tardif = array();
		while ($unTardif = mysqli_fetch_assoc ($res)) {
			$tardif[] = $unTardif;
		}
//var_dump($tardif); die();		
?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formGestion" id="formGestion" action="gestionInscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="">
		<input type="hidden" name="idSeance" id="idSeance" value="<?php echo($idSeance); ?>">


		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "{$GLOBALS['titrePage']} <br><span style='font-size: medium;'>Séance du ".jourDateFr($seance['dateSeance'])." à  ".nationaliserHeure($seance['heureRDVSeance'])." ".$seance['lieuRDVSeance']."</span>";
		include("divEnTete.inc.php");
		if ($message != "") {
?>
							<p style="padding:5px; text-align: center; color: red;">
								<?php echo($message);?>
							</p>
<?php
		}
?>

		</div>
		
		<div id="content">
			<hr>
			<p style="padding:5px; color: red; width: 1024px; margin: auto;">
				La sanction d'une absence non excusée ou d'une annulation tardive non excusée (dans les 2 jours qui précèdent la séance) consiste, pendant <?php echo $GLOBALS['dureePunition']; ?> jours, en la mise en attente de toute nouvelle demande jusqu'à l'avant-veille de la séance
			</p>
			<table>
				<tbody>
					<tr>
						<td class="tdTitre" colspan=6>
							Inscrits avec place attribuée
						</td>
					</tr>
					<tr>
						<td class="tdTitre">
							n°
						</td>
						<td class="tdTitre">
							nom
						</td>
						<td class="tdTitre">
							prénom
						</td>
						<td class="tdTitre" style="text-align: center;">
							absence
						</td>
						<td class="tdTitre" style="text-align: center;">
							excusée
						</td>
					</tr>
<?php

	$i=0;
	foreach($inscrit AS $unInscrit) {
		$i++;
?>
					<tr>
						<td>
							<?php echo($i);?>
						</td>
						<td>
							<?php echo($unInscrit['nomAdherent']); ?>
						</td>
						<td>
							<?php echo($unInscrit['prenomAdherent']); ?>
						</td>
						<td  style="text-align: center;">
							 <input type="checkbox" name="absenceLicenceAdherent[]" value="<?php echo($unInscrit['licenceAdherent']); ?>" <?php if($unInscrit['absenceInscription']==1) echo('checked = "checked"') ?>>
						</td>
						<td  style="text-align: center;">
							 <input type="checkbox" name="absenceExcuseeLicenceAdherent[]" value="<?php echo($unInscrit['licenceAdherent']); ?>" <?php if($unInscrit['absenceExcuseeInscription']==1 ) echo('checked = "checked"') ?>>
						</td>
					</tr>

<?php
	}
?>
				</tbody>
			</table>
			
			<table>
				<tbody>
					<tr>
						<td class="tdTitre" colspan=5>
							Annulations tardives
						</td>
					</tr>
					<tr>
						<td class="tdTitre">
							nom
						</td>
						<td class="tdTitre">
							prénom
						</td>
						<td class="tdTitre" style="text-align: center;">
							annulation tardive
						</td>
						<td class="tdTitre" style="text-align: center;">
							excusée
						</td>
					</tr>
<?php
	foreach ($tardif AS $unTardif) {
?>
					<tr>
						<td>
							<?php echo($unTardif['nomAdherent']); ?>
						</td>
						<td>
							<?php echo($unTardif['prenomAdherent']); ?>
						</td>
						<td  style="text-align: center;">
							 <input type="checkbox" name="annulationLicenceAdherent[]" checked="checked" onclick="return false" value="<?php echo($unTardif['licenceAdherent']); ?>">
						</td>
						<td  style="text-align: center;">
							 <input type="checkbox" name="annulationExcuseeLicenceAdherent[]" value="<?php echo($unTardif['licenceAdherent']); ?>" <?php if(is_null($unTardif['dateLimiteAttenteInscription'])) echo('checked = "checked"') ?>>
						</td>
					</tr>

<?php
	}
?>
				</tbody>
			</table>
			
			
			</form>
			
		</div>
			
		<div id="bas">
			<hr>
			<table>
				<tbody>
					<tr>
						<td class="tdTitre" style="text-align: center; width: 50%;">
								Enregistrer les absences : 
								<button type="button" title="Enregistrer les absences" onClick="document.getElementById('newAction').value='enregistrerAbsences'; document.getElementById('formGestion').submit();">
								<img alt="" src="images/enregistrer.png">
								</button>
						</td>
						<td class="tdTitre" style="text-align: center;">
								Quitter sans enregistrer : 
								<button type="button" title="Quitter sans enregistrer" onClick="document.getElementById('newAction').value='retourner'; document.getElementById('formGestion').action='gestionInscriptions.php'; document.getElementById('formGestion').submit();">
								<img alt="" src="images/sortir.png">
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
	} // fin gererAbsences()
	
	
	function afficherSeances($message) {
		$_SESSION['message'] ="";
/*		
		// détermination de l'intervalle de temps
		$apres18h = (time()%(3600*24)>3600*18);
		if ($apres18h) $dateDebut = date("Y-m-d",time()+(3600*24*1));
		else $dateDebut = date("Y-m-d",time()+(3600*24*2));
		$aujourdhuiTS = time();
		$dateFin = date('Y-m-d', ($aujourdhuiTS + (24 * 3600 * 14)));
*/
		// recherche des séances à afficher : toutes
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance ORDER BY dateSeance  DESC";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$seance = array();
		while ($ligne = mysqli_fetch_assoc ($res)) {
			// noms et prénoms des animateurs
			$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}seanceAnimateur, {$GLOBALS['prefixe']}adherent WHERE seanceId= {$ligne['idSeance']} AND animateurLicence=licenceAdherent ";
			$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
			$ligne['animateurs'] = "";
			$ligne['animateurLicence'] = array();
			$premier = TRUE;
			while ($unAnim = mysqli_fetch_assoc ($res1)) {
				if (!$premier) $ligne['animateurs'] .= "<br>";
				$ligne['animateurs'] .= $unAnim['prenomAdherent']." ".$unAnim['nomAdherent'];
				$ligne['animateurLicence'][] = $unAnim['licenceAdherent'];
				$premier = FALSE;
			}
			$seance[] = $ligne;
		};
		
		// nom et prénom de l'admin ou animateur
		$prenom = $_SESSION['prenomActeur'];
		$nom = $_SESSION['nomActeur'];

?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formGestion" id="formGestion" action="gestionInscriptions.php" >
		<input type="hidden" name="newAction" id="newAction" value="">
		<input type="hidden" name="idSeance" id="idSeance" value="">
		
		

		<div id="haut" >
<?php
		$GLOBALS['titrePage'] = "{$GLOBALS['titrePage']} <br>pour $prenom $nom {$GLOBALS['statut']}";
		include("divEnTete.inc.php");

		if ($message != "") {
?>
							<p style="padding:5px; text-align: center; color: red;">
								<?php echo($message);?>
							</p>
<?php
		}
?>
		</div>
		
		<div id="content">
			<hr>
			<table style="width:100%;">
				<tbody>
					<tr>
						<th>
							date
						</th>
						<th>
							heure
						</th>
						<th>
							lieu
						</th>
						<th>
							animation
						</th>
						<th>
							nb inscrits
						</th>
						<th>
							nb attente
						</th>
						<th>
							nb absents
						</th>
						<th>
							actions
						</th>
					</tr>
<?php
	foreach ($seance AS $uneSeance) {
		// séance supprimée ?
		$seanceSupprimee = $uneSeance['supprimeeSeance']=='O';
		// séance passée ?
		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
		$dateHeureAujourdhui = date('Y-m-d H:i:s', $aujourdhuiTS);
		$dateSeance = $uneSeance['dateSeance'];
		$dateHeureSeance = $uneSeance['dateSeance']." ".$uneSeance['heureRDVSeance'];
		$seancePassee = ($dateHeureSeance<=$dateHeureAujourdhui);
		// calcul inscrits non annulés
		$sql = "SELECT COUNT(idInscription) AS nbInscrits FROM {$GLOBALS['prefixe']}inscription WHERE seanceId={$uneSeance['idSeance']} AND (attenteInscription IS NULL OR attenteInscription=-1) AND dateHeureAnnulationInscription IS NULL ";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
//echo($sql."<br>\n");
		$ligne = mysqli_fetch_assoc ($res);
		$nbInscrits = $ligne['nbInscrits'];
		// calcul enAttente non annulés
		$sql = "SELECT COUNT(idInscription) AS nbEnAttente FROM {$GLOBALS['prefixe']}inscription WHERE seanceId={$uneSeance['idSeance']} AND attenteInscription >0 AND dateHeureAnnulationInscription IS NULL";
//echo($sql."<br>\n");
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$nbEnAttente = $ligne['nbEnAttente'];
		// calcul absents
		$sql = "SELECT COUNT(idInscription) AS nbAbsences FROM {$GLOBALS['prefixe']}inscription WHERE seanceId={$uneSeance['idSeance']} AND absenceInscription IS NOT NULL";
//echo($sql."<br>\n");
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$ligne = mysqli_fetch_assoc ($res);
		$nbAbsences = $ligne['nbAbsences'];
		// calcul de animateurSeance vrai si c'est une séance de l'animateur
		$animateurSeance = in_array($_SESSION['idActeur'],$uneSeance['animateurLicence']);
		//$uneSeance['animateurLicence']==$_SESSION['idActeur'];
		// calcul actions  pour référent lister inscrits
		// style pour séance supprimée
		if ($seanceSupprimee) echo('<tr  style= "font-style: italic; color: rgb(64,128,128); " >');
		else echo('<tr>');
?>
					
						<td>
							<?php echo(jourDateFr($uneSeance['dateSeance']));?>
						</td>
						<td>
							<?php echo(nationaliserHeure($uneSeance['heureRDVSeance']));?>
						</td>
						<td>
							<?php echo($uneSeance['lieuRDVSeance']);?>
						</td>
						<td>
							<?php echo($uneSeance['animateurs']);?>
						</td>
						<td style="text-align: center;">
							<?php echo($nbInscrits);?>
						</td>
						<td style="text-align: center;">
							<?php echo($nbEnAttente);?>
						</td>
						<td style="text-align: center;">
							<?php echo($nbAbsences);?>
						</td>
						<td style="text-align: center;">
<?php
		
	?>
								<button class = "boutonFiche" type="button" title="lister les inscrits"
									onclick="window.open('listeInscrits.php?idSeance=<?php echo($uneSeance['idSeance'])?>');"
									> 
									<img alt="lister" src="images/fiche.png">
								</button>
	<?php
			if ( !$seanceSupprimee && $seancePassee && ($animateurSeance || $_SESSION['statut']>2)) {
	?>
								<button class = "boutonFiche" type="button" title="gérer les absences"
									onClick="document.getElementById('newAction').value='absences'; 
									document.getElementById('idSeance').value='<?php echo($uneSeance['idSeance']);?>';
									document.getElementById('formGestion').submit();"
									> 
									<img alt="lister" src="images/absences.png">
								</button>
	<?php
			}
	?>

	<?php
			if ($_SESSION['statut']>2) {

	?>


								<button class = "boutonFiche" type="button" title="modifier la séance"
									onClick="document.getElementById('newAction').value='modifierSeance'; 
									document.getElementById('idSeance').value='<?php echo($uneSeance['idSeance'])?>';
									document.getElementById('formGestion').submit();"
									> 
									<img alt="modifier" src="images/editer.png">
								</button>
	<?php
			}
	?>
						</td>
					</tr>
	<?php
			}
	?>
				</tbody>
			</table>
			</form>
			<hr>
		</div>
			
		<div id="bas">
			<hr>
			<table style="width:100%;">
				<tbody>
					<tr>
<!-- s'inscrite à une sortie -->
						<td class="tdTitre" style="text-align: center; width: 16.6%;">
							S'inscrire à une sortie<br>
							<button type="button" title="S'inscrire à une sortie'" onClick="document.getElementById('formGestion').action='inscriptions.php' ; document.getElementById('newAction').value='s_inscrire'; document.getElementById('formGestion').submit();">
							<img alt="s'inscrire" src="images/s_inscrire16.png">
							</button>
						</td>

<!-- ajouter séance, statistiques, quitter -->
						<td class="tdTitre" style="text-align: center; width: 16.6%;">
<?php
	if ($_SESSION['statut']>2) {
?>
							Ajouter une sortie<br>
							<button type="button" title="Ajouter une sortie" onClick="document.getElementById('newAction').value='ajouterSeance'; document.getElementById('formGestion').submit();">
							<img alt="ajouter" src="images/add1.png">
							</button>
<?php 
	}
?>
						</td>
						
						<td class="tdTitre" style="text-align: center; width: 16.6%;">
<?php
	if ($_SESSION['statut']>2) {
?>
							Gérer les adhérent·e·s<br>
							<button class = "bouton" type="button" title="gérer les adhérent·e·s du club inscrits"
								onclick="document.getElementById('newAction').value='gererAdherents'; document.getElementById('formGestion').submit();"
								> 
								<img alt="adhérents" src="images/edit-tool.png">
							</button>
<?php 
	}
?>
						</td>

						<td class="tdTitre" style="text-align: center; width: 16.6%;">
<?php
	if ($_SESSION['statut']>2) {
?>
							Télécharger csv<br> 
							<button type="button" title="Télécharger toutes les inscriptions (fichier csv)" onClick="document.getElementById('newAction').value='statistiques'; document.getElementById('formGestion').submit();">
							<img alt="stat" src="images/download.png">
							</button>
<?php
	}
?>
						</td>
						
						<td class="tdTitre" style="text-align: center; width: 16.6%;">
<?php
	if ($_SESSION['statut']>5) {
?>
							Paramétrer<br> 
							<button type="button" title="Modifier le paramétrage de l'application" onClick="document.getElementById('newAction').value='paramétrer'; document.getElementById('formGestion').submit();">
							<img alt="stat" src="images/parametrer16.png">
							</button>
<?php
	}
?>
						</td>

						<td class="tdTitre" style="text-align: center; width: 16.6%;">
							Quitter<br>
							<button type="button" title="Se déconnecter" onClick="document.getElementById('newAction').value='quitter'; document.getElementById('formGestion').submit();">
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
	} // fin function afficherSeances
?>