<?php
// initialiser.php
/*
 
*/
	session_start();
	
	$GLOBALS['logoClub'] = "logo-association-affilliee-FFR.png";
	
	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "initialiserBD" :
				initialiserBD();
				$_SESSION['scriptOrigine'] = "initaliser.php";
				parametrer();
				break;
		}
	}
	else saisirBD();
		
	function saisirBD() {
	// saisir info bd et préfixe des tables
		$GLOBALS['titrePage'] = "Saisie des informations sur la base de données";
		$GLOBALS['titrePageCourt'] = $GLOBALS['titrePage'];
		include("headHTML.inc.php");
?>

	<body onLoad="redim();">
	<form method="POST" name="formAdherent" id="formBD" action="initialiser.php" >
		<input type="hidden" name="newAction" id="newAction" value="initialiserBD">

		<div id="haut" >
<?php
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
								nom d'hôte du serveur MySql : 
							</td>
							<td class="tdDroite">
								
								<input required="required" type="text"  name="host"   id="host"  value="" style="font-size:small;  width:150px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								nom de la base de données : 
							</td>
							<td class="tdDroite">
								<input required="required" type="base"  name="base"   id="nomAdherent"  value="" style="font-size:small;  width:150px;">
							</td>
						</tr>
						<tr>
							<td class="tdGauche">
								utilisateur ayant les droits sur la base : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="user"   id="user"  value="" style="font-size:small;  width:150px;">
							</td>
						</tr>
						
						
						<tr>
							<td class="tdGauche">
								mot de passe de cet utilisateur : 
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="passwd"   id="passwd"  value="" style="font-size:small;  width:150px;">
							</td>
						</tr>
						
						
						
						<tr>
							<td class="tdGauche">
								préfixe à ajouter aux noms de table de l'application : <br>
								(préfixe destiné à éviter les conflits avec d'autres tables si la base de données est partagée avec d'autres application)
							</td>
							<td class="tdDroite">
								<input required="required" type="text"  name="prefixe"   id="prefixe"  value="ins_" style="font-size:small;  width:150px;">
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
	} // fin function saisirBD
	
	function initialiserBD() {
	// créer les tables en utilisant le préfixe
		$sql = <<<EOT

DROP TABLE IF EXISTS `{$_POST['prefixe']}adherent`;

CREATE TABLE `{$_POST['prefixe']}adherent` (
  `licenceAdherent` varchar(20) NOT NULL COMMENT 'n° de licence',
  `statut` int(11) NOT NULL DEFAULT '1' COMMENT 'statut',
  `actif` int(11) NOT NULL DEFAULT '1',
  `nomAdherent` varchar(100) NOT NULL COMMENT 'nom',
  `prenomAdherent` varchar(100) NOT NULL COMMENT 'prénom',
  `courrielAdherent` varchar(100) NOT NULL COMMENT 'courriel',
  `mobileAdherent` varchar(20) NOT NULL COMMENT 'mobile',
  `dateLimiteAttenteAdherent` date DEFAULT NULL COMMENT 'date limite liste d''attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$_POST['prefixe']}inscription`;

CREATE TABLE `{$_POST['prefixe']}inscription` (
  `idInscription` int(11) NOT NULL COMMENT 'n° inscription',
  `seanceId` int(11) NOT NULL COMMENT 'n° sortie inscription',
  `adherentLicence` varchar(100) NOT NULL COMMENT 'n° licence inscription',
  `dateHeureInscription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'date heure inscription',
  `dateHeureAttributionPlace` datetime DEFAULT NULL,
  `attributionTardiveInscription` varchar(3) DEFAULT NULL,
  `attenteInscription` int(11) DEFAULT NULL COMMENT 'rang liste d''attente inscription',
  `dateHeureAnnulationInscription` datetime DEFAULT NULL COMMENT 'date heure annulation inscription',
  `annulationTardiveInscription` varchar(3) DEFAULT NULL,
  `absenceExcuseeInscription` int(11) DEFAULT NULL COMMENT 'absence excusée',
  `absenceInscription` int(11) DEFAULT NULL COMMENT 'absent sortie inscription',
  `dateLimiteAttenteInscription` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$_POST['prefixe']}seance`;

CREATE TABLE `{$_POST['prefixe']}seance` (
  `idSeance` int(11) NOT NULL COMMENT 'n° sortie',
  `nomSeance` varchar(255) NOT NULL COMMENT 'intitulé',
  `niveauSeance` varchar(255) NOT NULL COMMENT 'niveau',
  `remarqueSeance` varchar(255) NOT NULL COMMENT 'remarques',
  `dateSeance` date NOT NULL COMMENT 'date',
  `heureRDVSeance` time NOT NULL COMMENT 'heure RDV',
  `lieuRDVSeance` varchar(255) NOT NULL COMMENT 'lieu RDV',
  `maxSeance` int(11) NOT NULL DEFAULT '20' COMMENT 'max participants sortie',
  `supprimeeSeance` varchar(1) NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `{$_POST['prefixe']}seanceAnimateur`;

CREATE TABLE `{$_POST['prefixe']}seanceAnimateur` (
  `idSeanceAnimateur` int(11) NOT NULL,
  `seanceId` int(11) NOT NULL COMMENT 'n° sortie',
  `animateurLicence` varchar(20) NOT NULL COMMENT 'n° licence animateur sortie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `{$_POST['prefixe']}adherent`
  ADD PRIMARY KEY (`licenceAdherent`);

ALTER TABLE `{$_POST['prefixe']}inscription`
  ADD PRIMARY KEY (`idInscription`);

ALTER TABLE `{$_POST['prefixe']}seance`
  ADD PRIMARY KEY (`idSeance`);

ALTER TABLE `{$_POST['prefixe']}seanceAnimateur`
  ADD PRIMARY KEY (`idSeanceAnimateur`),
  ADD KEY `idSeance` (`seanceId`) USING BTREE,
  ADD KEY `animateurLicence` (`animateurLicence`);

ALTER TABLE `{$_POST['prefixe']}inscription`
  MODIFY `idInscription` int(11) NOT NULL AUTO_INCREMENT COMMENT 'n° inscription';

ALTER TABLE `{$_POST['prefixe']}seance`
  MODIFY `idSeance` int(11) NOT NULL AUTO_INCREMENT COMMENT 'n° sortie';

ALTER TABLE `{$_POST['prefixe']}seanceAnimateur`
  MODIFY `idSeanceAnimateur` int(11) NOT NULL AUTO_INCREMENT;
	
EOT;
		if (! $lkId=mysqli_connect($_POST['host'], $_POST['user'], $_POST['passwd'])) {
		echo "Impossible d'établir la connexion à ",$_POST['host'],"<br>";
		die;
		}
		if (!$res=mysqli_select_db($lkId,$_POST['base'])) {
				echo "Impossible d'ouvrir la base {$_POST['base']}<br>";
				die;
		}
		$res = mysqli_multi_query ($lkId, $sql);
//die($sql);
		if (!$res) die("Impossible d'initialiser les tables dans {$_POST['base']}");
//else echo  "C'est fait ! ".$res;
//die();
	
	
	// passage en session pour parametrer.php
	$_SESSION['host'] = $_POST['host'];
	$_SESSION['base'] = $_POST['base'];
	$_SESSION['user'] = $_POST['user'];
	$_SESSION['passwd'] = $_POST['passwd'];
	$_SESSION['prefixe'] = $_POST['prefixe'];
		
	} // fin function initialiserBD
	
	function parametrer() {
	// session start : session   statut=10 bd : host base user passwd prefixe 
	$_SESSION['statut'] = 10;
	
	// header location parametrer.php
		header("Location: parametrer.php");
		exit;
	} // fin function parametrer
?>