<?php
// listeInscrits.php
/*
	($_GET['idSeance]);

*/

	session_start();
// contrôle d'accès
	if (!isset($_SESSION['statut'])) { header('Location: index.php'); exit();}


	include('init.inc.php');

	$titrePage = "Liste des inscrits";
	$titrePageCourt = $titrePage;
	
	$idSeance = intval ($_GET['idSeance']);
/*
	// paragraphe RIB
	$pRib = "";
	$pMessageRib = "";
	
	// paragraphes Piece
	$pPieces = "";
	$pMessagePieces = "";

	$maintenantTimeStamp = time();
	$maintenant= strftime('%d/%m/%Y %H:%M',$maintenantTimeStamp);
*/
	$maintenantTimeStamp = time();
	$maintenant= strftime('%d/%m/%Y à %Hh%M',$maintenantTimeStamp);

	// recherche de la séance
	$sql = "SELECT * FROM {$GLOBALS['prefixe']}seance WHERE idSeance= $idSeance";
	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$seance = mysqli_fetch_assoc ($res);
	
	// noms et prénoms mobiles des animateurs
	$sql1 = "SELECT * FROM {$GLOBALS['prefixe']}seanceAnimateur, {$GLOBALS['prefixe']}adherent WHERE seanceId= {$seance['idSeance']} AND animateurLicence=licenceAdherent ";
//die($sql1);
	$res1 = mysqli_query ($GLOBALS['lkId'], $sql1);
	$seance['animateurs'] = "";
	$premier = TRUE;
	while ($unAnim = mysqli_fetch_assoc ($res1)) {
		if (!$premier) $seance['animateurs'] .= "";
		$seance['animateurs'] .= $unAnim['prenomAdherent']." ".$unAnim['nomAdherent']." ".formaterMobile($unAnim['mobileAdherent']);
		$premier = FALSE;
	}

	
	// séance supprimée
	$seanceSupprimee = $seance['supprimeeSeance']=='O';
	
	// recherche des inscrits non annulés place attribuée
	$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription,  {$GLOBALS['prefixe']}adherent WHERE seanceId=$idSeance AND licenceAdherent=adherentLicence   AND dateHeureAnnulationInscription IS NULL  AND (attenteInscription IS NULL OR attenteInscription<0) ORDER BY nomAdherent, prenomAdherent";

	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$inscrit = array();
	while ($unInscrit = mysqli_fetch_assoc ($res)) {
		$inscrit[] = $unInscrit;
	}
	
	// recherche des inscrits non annulés en liste d'attente
	$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}adherent WHERE seanceId=$idSeance AND licenceAdherent=adherentLicence   AND dateHeureAnnulationInscription IS NULL AND attenteInscription >0  ORDER BY attenteInscription";

	$res = mysqli_query ($GLOBALS['lkId'], $sql);
	$enAttente = array();
	while ($unEnAttente= mysqli_fetch_assoc ($res)) {
		$enAttente[] = $unEnAttente;
	}
	$dateSeance = jourDateFr($seance['dateSeance']);
	$GLOBALS['titrePage'] = <<<EOT
	
				<p style="font-size: medium; margin: 10px; font-weight: bold;">
					Liste des inscrits
						à la sortie "{$seance['nomSeance']}" du $dateSeance à {$seance['heureRDVSeance']} {$seance['lieuRDVSeance']} 
				</p>
				<p style="font-size: medium; margin: 10px; font-weight: bold;">
					animation : {$seance['animateurs']}
					<br><span style="font-size: medium; font-size: small; font-weight: normal;">liste établie le : $maintenant</span>
				</p>
EOT;

	$GLOBALS['titrePageCourt'] = "Liste des inscrits";
	
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
			<?php
				if ($seanceSupprimee) echo"<p style='color: red;'>Attention sortie supprimée ! Les inscrits ont été informés par courriel.</p>";
			?>
				<br>
				<p style="text-align: center;">
				<input type="button" id="boutonImprimer" onclick="javascript:window.print();" value="Imprimer la page" />&nbsp; &nbsp; &nbsp; 
				<input type="button" id="boutonFermer" onclick="javascript:window.close();" value="Fermer cet onglet" />
				</p>
				<br>
			
				<h3> Inscrits·e·s</h3>
				<table border=1 style="width: 100%; border-collapse: collapse;">
					<tbody>
						<tr>
							<th>
								n°
							</th>
							<th>
								nom
							</th>
							<th>
								prénom
							</th>
							<th>
								mobileAdherent
							</th>
							<th>
								signature début
							</th>
							<th>
								signature fin
							</th>
							<th style="text-align: center;">
								absence
							</th>
						</tr>

	<?php
		$i=0;
		foreach($inscrit AS $unInscrit) {
			$i++;
		
	?>
						<tr>
							<td style="text-align: center;">
								<?php echo($i);?>
							</td>
							<td>
								<?php 
									echo($unInscrit['nomAdherent']); 
									if ($unInscrit['licenceAdherent'][0]=='9') echo(" (à l'essai)");
								?>
							</td>
							<td>
								<?php echo($unInscrit['prenomAdherent']);?>
							</td>
							<td>
								<?php echo formaterMobile($unInscrit['mobileAdherent']);?>
							</td>
							<td style="text-align: center;">
								&nbsp;
							</td>
							<td style="text-align: center;">
								&nbsp;
							</td>
							<td style="text-align: center;">
								<?php if(!is_null($unInscrit['absenceInscription'])) echo("absent(e)"); ?>
							</td>
						</tr>

	<?php
		}
	?>
					</tbody>
				</table>
				
				<h3> Liste d'attente</h3>
				<table border=1 style="width: 100%; font-style: italic; border-collapse: collapse;">
					<tbody>
						<tr>
							<th>
								n°
							</th>
							<th>
								nom
							</th>
							<th>
								prénom
							</th>
							<th>
								mobileAdherent
							</th>
							<th style="text-align: center;">
								rang en liste d'attente
							</th>
						</tr>

	<?php
		$i=0;
		foreach($enAttente AS $unInscrit) {
			$i++;
		
	?>
						<tr>
							<td style="text-align: center;">
								<?php echo $i;?>
							</td>
							<td>
								<?php echo $unInscrit['nomAdherent'];?>
							</td>
							<td>
								<?php echo $unInscrit['prenomAdherent'];?>
							</td>
							<td>
								<?php echo formaterMobile($unInscrit['mobileAdherent']);?>
							</td>
							<td style="text-align: center;">
								<?php if(!is_null($unInscrit['attenteInscription'])) echo $unInscrit['attenteInscription']; ?>
							</td>
						</tr>

	<?php
		}
	?>

					</tbody>
				</table>
			</div>
		</body>
	</html>
	
	
