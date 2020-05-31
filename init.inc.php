<?php
// init.inc.php
/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
*/

	// contrôle de l'initialisation de l'application
	if (!file_exists('param.inc.php')) { 				
		header("Location: initialiser.php");
		exit;
	}

	include('param.inc.php');

/*
// affichage des erreurs
	if (PHP_VERSION_ID < 50400) error_reporting (E_ALL | E_STRICT);
	else error_reporting (E_ALL);
	ini_set('display_errors', true);
	ini_set('html_errors', false);
	ini_set('display_startup_errors',true);		  
	ini_set('log_errors', false);
	ini_set('error_prepend_string','<span style="color: red;">');
	ini_set('error_append_string','<br /></span>');
	ini_set('ignore_repeated_errors', true);
*/


// redirection si MISE <9
	if(preg_match('/(?i)msie [1-8]/',$_SERVER['HTTP_USER_AGENT']))// MSIE <9
	{
		header('Location: erreur.html');
	}
	
// connexion sql
	if (! $lkId=mysqli_connect($GLOBALS['host'], $GLOBALS['user'], $GLOBALS['passwd'])) {
			echo "Impossible d'établir la connexion à ",$GLOBALS['host'],"<br>";
			die;
	}


	/* Modification du jeu de résultats en utf8 */
	if (function_exists('mysqli_set_charset')) {
			mysqli_set_charset($lkId, 'utf8');
	} else {
			mysqli_query($lkId, 'SET NAMES utf8');
	}

	
	if (! $res=mysqli_select_db($lkId,$GLOBALS['base'])) {
			echo "Impossible d'ouvrir la base ",$GLOBALS['base'],"<br>";
			die;
	}


// pour éviter les injections SQL, on échappe les POST
	if (isset($_POST)) {
		foreach($_POST as $index => $unPost) {
				if (is_string($unPost)) {
					$_POST[$index] = mysqli_real_escape_string($GLOBALS['lkId'],$unPost);
				}
		}
	}

// date
// fonction internationaliserDate
	function internationaliserDate($laDate) {
		$tabDate = explode('/',$laDate);
		return ($tabDate[2]."-".$tabDate[1]."-".$tabDate[0]);
	}

// fonction nationaliserDate
	function nationaliserDate($laDate) {
		$tabDate = explode('-',$laDate);
		return ($tabDate[2]."/".$tabDate[1]."/".$tabDate[0]);
	}
	
// fonction nationaliserDateHeure
	function nationaliserDateHeure($laDateHeure) {
//		2020-05-28 11:49:11
		$tabDateHeure = explode(' ',$laDateHeure);
		$tabDate = explode('-',$tabDateHeure[0]);
		$tabHeure = explode(':',$tabDateHeure[1]);
		
		return ($tabDate[2]."/".$tabDate[1]."/".$tabDate[0].' '.$tabHeure[0].'&nbsp'.'h '.$tabHeure[1].'&nbsp'.'min '.$tabHeure[2].'&nbsp'.'s');
	}
	
	
// fonction jourDate
	function jourDateFr($laDate) {
		if ($_SERVER['SERVER_NAME']=="localhost") setlocale(LC_TIME, "fr_FR.utf8");
		else setlocale(LC_TIME, "fr_FR.utf8");
		$timeStamp = strtotime($laDate);
		$laDateFr = strftime("%A %e %B %G ",$timeStamp );
		return($laDateFr);
	}
	
// fonction internationaliserHeure
	function internationaliserHeure($lHeure) {
		$tabHeure = explode('h',$lHeure);
		return ($tabHeure[0].":".$tabHeure[1].":00");
	}
	
// fonction nationaliserHeure
	function nationaliserHeure($lHeure) {
		$tabHeure = explode(':',$lHeure);
		return ($tabHeure[0]."h".$tabHeure[1]);
	}

	
// fonction internationaliserDecimal
	function internationaliserDecimal($leDecimal) {
		$leDecimal = str_replace(" ","",$leDecimal);
		return(str_replace(",",".",$leDecimal));
	}
	
// fonction nationaliserDecimal
	function nationaliserDecimal($leDecimal) {
		if (!strpos($leDecimal,".")) $leDecimal .= ",00";
		else $leDecimal = str_replace(" ","",$leDecimal);
		return(str_replace(".",",",$leDecimal));
	}

// formater n° de mobile
	function formaterMobile($mobile) {
		$ftMobile = $mobile;
		if (strlen($mobile)==10) {
			$ftMobile = '';
			$i = 0;
			while ($i<9) {
				 $ftMobile .= substr($mobile, $i, 2).' ';
				 $i = $i+2;
			}
		}
		return $ftMobile;
	}
	
// MYSQL


	// conversion en majuscule de toutes les lettres y compris accentuées
	function fullUpper($string){
		return strtr(strtoupper($string), array(
      "à" => "À",
      "è" => "È",
      "ì" => "Ì",
      "ò" => "Ò",
      "ù" => "Ù",
          "á" => "Á",
      "é" => "É",
      "í" => "Í",
      "ó" => "Ó",
      "ú" => "Ú",
          "â" => "Â",
      "ê" => "Ê",
      "î" => "Î",
      "ô" => "Ô",
      "û" => "Û",
          "ä" => "Ä",
      "ë" => "Ë",
      "ï" => "Ï",
      "ö" => "Ö",
      "ü" => "Ü",
          "ç" => "Ç",
    ));
	} 
/*	
	// encodage et décodage utf8 seulement sur serveur free
	function utf8_encode_md($ch) {
		if ($_SERVER['SERVER_NAME']=="mac.lc.free.fr") return(utf8_encode($ch));
		else return($ch);
	}
	
	function utf8_decode_md($ch) {
		if ($_SERVER['SERVER_NAME']=="mac.lc.free.fr") return(utf8_decode($ch));
		else return($ch);
	}
*/
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// fonction mail
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function envoyerCourriel($de,$destinataire,$sujet,$codeHtml) {

		$codeHtml = wordwrap($codeHtml, 70, "\r\n");
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		// avec copie cachée à admin
		$headers .= "From: ".$de. '\r\n' .
		"Bcc: {$GLOBALS["courrielAdmin"]}" . '\r\n' .
		"Reply-To: " .$de. '\r\n' .
		"X-Mailer: PHP/" . phpversion();


		// En-têtes additionnels
	//     $headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
	//    $headers .= 'From: Anniversaire <anniversaire@example.com>' . "\r\n";
	//    $headers .= 'Cc: anniversaire_archive@example.com' . "\r\n";
	//     $headers .= 'Bcc: anniversaire_verif@example.com' . "\r\n";
		
		// en local ne pas envoyer le courriel mais afficher 
		if ($_SERVER['SERVER_NAME']=="localhost") {  
			echo("$destinataire, $sujet, $codeHtml, $headers");
		}
		else {
			if ($destinataire!='') $ok = mail($destinataire, $sujet, $codeHtml, $headers);
			else $ok = "Le courriel n'a pas été envoyé car il n'y avait pas d'adresse de destinataire.";
//			if (!$ok) die("Le courriel adressé à $destinataire n'a pas été accepté pour livraison.");
			return $ok;
		}

		
	} // fin envoyerCourriel
////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// fonctions pour mettre à jour les inscriptions
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function mettreAJourInscriptions() {

		// on pose un verrou en écritrue sur la table inscription
		$sql = "LOCK TABLES {$GLOBALS['prefixe']}inscription WRITE, {$GLOBALS['prefixe']}seance WRITE, {$GLOBALS['prefixe']}adherent WRITE";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);


		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
	/*
		$datePlus14 = date('Y-m-d', ($aujourdhuiTS + (24 * 3600 * 14)));
		$datePlus8 = date('Y-m-d', ($aujourdhuiTS + (24 * 3600 * 8)));
		$datePlus7 = date('Y-m-d', ($aujourdhuiTS + (24 * 3600 * 7)));
		$datePlus6 = date('Y-m-d', ($aujourdhuiTS + (24 * 3600 * 6)));

		$semaine1 = " BETWEEN '$datePlus8' AND '$datePlus14' "; // 1ere semaine => quotas
		$semaine2 = " BETWEEN '$dateAujourdhui'  AND '$datePlus7' "; // 2me semaine
	*/
		
		$aujourdhuiTS = time();
		$dateAujourdhui = date('Y-m-d', $aujourdhuiTS);
		$dateHeureAujourdhui = date('Y-m-d H:i:s', $aujourdhuiTS);
			
		// levée des punitions
		// pour chaque adhérent si dateLimiteAttenteAdherent<aujoud'hui 
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent WHERE dateLimiteAttenteAdherent<'$dateAujourdhui'";

		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		if ($unAdherent=mysqli_fetch_assoc ($res)) {
			$sql = "UPDATE {$GLOBALS['prefixe']}adherent SET dateLimiteAttenteAdherent=NULL WHERE licenceAdherent='".$unAdherent['licenceAdherent']."'";
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		}

	///////////////////////
	//
	///////////////////////

		// on peut s'inscrire jusqu'à $GLOBALS['fermetureNJourAvant'] à $GLOBALS['HeureFermeture']
		// on affiche les séances dont la date est comprise inférieure ou égale à aujourd'hui plus $GLOBALS['ouvertureNJourAvant']
		// les mots "veille" est "18" heures sont conservées dans les noms de variable mais les valeurs sont calculées en fonction des paramètres
		// l'avant-veille est la date à partir de laquelle une annulation peut entraîner une pénalité et la date à partir de laquelle une attribution est considérée comme tardive 

	//	Pour chaque séance
		$sql0 = "SELECT * FROM {$GLOBALS['prefixe']}seance ";
		$res0 = mysqli_query ($GLOBALS['lkId'], $sql0) or die ($sql0." : ".mysqli_error($GLOBALS['lkId']));
		while ($uneSeance = mysqli_fetch_assoc ($res0)) {
	//echo("<br>\nSéance : ".$uneSeance['idSeance']." en 2e semaine<br>\n");			
			$dateHeureSeance = $uneSeance['dateSeance'];
			$dateHeureSeanceTS = strtotime($dateHeureSeance);
			$dateHeureVeilleSeanceTS = $dateHeureSeanceTS-(3600*24*$GLOBALS['fermetureNJoursAvant']);
			$dateVeilleSeance = date('Y-m-d', $dateHeureVeilleSeanceTS);
			
			$dateVeilleSeanceTS = strtotime($dateVeilleSeance);
			$dateVeilleSeance18hTS = $dateVeilleSeanceTS+(3600*$GLOBALS['heureFermeture']);
			$dateVeilleSeance18h = date('Y-m-d H:i:s', $dateVeilleSeance18hTS);
			
			$dateAvantVeilleSeanceTS = $dateVeilleSeanceTS-(3600*24);
			$dateAvantVeilleSeance = date('Y-m-d', $dateAvantVeilleSeanceTS);


	//		$enAttente = enAttente($uneSeance['idSeance']);
			$nombrePlacesDisponibles = nombrePlacesDisponibles($uneSeance['idSeance']);
			$idPremiereInscriptionEnAttente = idPremiereInscriptionEnAttenteATraiter($uneSeance['idSeance'], 0, $dateAujourdhui, $dateAvantVeilleSeance);
	//if($uneSeance['idSeance']==122) die($idPremiereInscriptionEnAttente." dispo : ".$nombrePlacesDisponibles);
			$idDerniereInscriptionPlaceAttribuee = idDerniereInscriptionPlaceAttribueeATraiter($uneSeance['idSeance']);

	//   tant que place attribuée et nombre de places disponibles négatif
			while ($idDerniereInscriptionPlaceAttribuee && $nombrePlacesDisponibles<0) {

				// décaler +1 les rangs d'attente
				$sql = "SELECT idInscription, attenteInscription FROM {$GLOBALS['prefixe']}inscription WHERE seanceId='".$uneSeance['idSeance']."'  AND attenteInscription>0 ORDER BY attenteInscription ASC";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
				while ($ligne = mysqli_fetch_assoc ($res)) {
					$rang[$ligne['idInscription']] = $ligne['attenteInscription'];
				};

				foreach($rang AS $idInscription =>$unRang) {
					$nouveauRang = $rang+1;
					$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = $nouveauRang WHERE idInscription = $idInscription";
					$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
//echo("&nbsp; &nbsp; ".$idInscription." en attente remonté<br>\n");			

				}
				
				// annuler la dernière place attribuée
				$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription=1 WHERE idInscription=$idDerniereInscriptionPlaceAttribuee";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
				// informer le malheureux
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}inscription WHERE licenceAdherent = adherentLicence AND idinscription = '$idPremiereInscriptionEnAttente'";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
				$adherent = mysqli_fetch_assoc ($res);
				$destinataire = $adherent['courrielAdherent'];
				$prenom = $adherent['prenomAdherent'];
				$nom = $adherent['nomAdherent'];
				$de = $GLOBALS['courrielClub'];
				$sujet = $GLOBALS['nomClub']." : inscription aux sorties";
				$codeHtml = "<html>";
				$codeHtml .= "<p>à $prenom $nom</p><p> </p>";
				$codeHtml .= "<p>Bonjour</p>";
				$codeHtml .= "<p>La place qui vous avait été attribuée pour la sortie '{$uneSeance['nomSeance']}' du ".jourDateFr($uneSeance['dateSeance'])." n'est plus disponible à la suite de la réduction du nombre maximum de participants. Vous avez été placé en liste d'attente. Vous serez informé·e par courriel si une place venait à se libérer.</p>";
				$codeHtml .= "<p>Cordialement</p>";
				$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
				$codeHtml .= "</html>";
				envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
				

				// mise à jour :
				$nombrePlacesDisponibles = nombrePlacesDisponibles($uneSeance['idSeance']);
				$idDerniereInscriptionPlaceAttribuee = idDerniereInscriptionPlaceAttribueeATraiter($uneSeance['idSeance']);
				
			}

			
	//		tant que en attente et au moins une place disponible
			while ($idPremiereInscriptionEnAttente && $nombrePlacesDisponibles>0) {
	//			attribuer la place au premier en attente  si pas encore puni ou si dans les 2 jours qui précèdent la séance
				// si la date de la séance est dans plus de 2 jours on met attenteInscription à NUL
				if ($dateAujourdhui<$dateAvantVeilleSeance) $sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = NULL WHERE idInscription = $idPremiereInscriptionEnAttente";
				// sinon  on met attenteInscription NULL et attibutionTardiveInscription à oui pour signaler une attribution tardive
				else $sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = NULL, attributionTardiveInscription = 'oui' WHERE idInscription = $idPremiereInscriptionEnAttente";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
	//echo("&nbsp; &nbsp; en attente annulé<br>\n");			
	//			courriel
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}inscription WHERE licenceAdherent = adherentLicence AND idinscription = '$idPremiereInscriptionEnAttente'";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
				$adherent = mysqli_fetch_assoc ($res);
				$destinataire = $adherent['courrielAdherent'];
				$prenom = $adherent['prenomAdherent'];
				$nom = $adherent['nomAdherent'];
				$de = $GLOBALS['courrielClub'];
				$sujet = $GLOBALS['nomClub']." : inscription aux sorties";
				$codeHtml = "<html>";
				$codeHtml .= "<p>à $prenom $nom</p><p> </p>";
				$codeHtml .= "<p>Bonjour</p>";
	//			$codeHtml .= "<p>Une place vous a été attribuée pour la séance de MAC avec $prenomAnimateur $nomAnimateur  le ".jourDateFr($uneSeance['dateSeance']).", rendez-vous :  {$uneSeance['lieuSeance']} à ".nationaliserHeure($uneSeance['heureSeance']). ".</p>";
				$codeHtml .= "<p>Une place vous a été attribuée pour la sortie '{$uneSeance['nomSeance']}' du ".jourDateFr($uneSeance['dateSeance']).".</p>";
				$codeHtml .= "<p>Cordialement</p>";
				$codeHtml .= "<p>Merci de ne pas répondre à ce message qui vous a été envoyé automatiquement.</p>";
				$codeHtml .= "</html>";
				envoyerCourriel($de,$destinataire,$sujet,$codeHtml);
				

	//			remonter d'un cran tous les en attente 
				$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId='".$uneSeance['idSeance']."'  AND attenteInscription>0 ORDER BY attenteInscription ASC";
				$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
				$inscription = array();
				while ($ligne = mysqli_fetch_assoc ($res)) {
					$inscription[] = $ligne;
				};

				foreach($inscription AS $positionInscription =>$uneInscription) {
					$idInscription = $uneInscription['idInscription'];
					$rangAttente = $positionInscription +1;
					$sql = "UPDATE {$GLOBALS['prefixe']}inscription SET attenteInscription = $rangAttente WHERE idInscription = $idInscription";
					$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
//echo("&nbsp; &nbsp; ".$idInscription." en attente remonté<br>\n");			

				}
				
	//			recalcul enAttente , nombrePlacesDisponibles
				$idPremiereInscriptionEnAttente = idPremiereInscriptionEnAttenteATraiter($uneSeance['idSeance'], 0, $dateAujourdhui, $dateAvantVeilleSeance);
				$nombrePlacesDisponibles = nombrePlacesDisponibles($uneSeance['idSeance']);
				
			} // fin TQ enAttente ET nombrePlacesDisponibles

	//   tant que place attribuée et nombre de places disponibles négatif
	
		} //	fin pour chaque séance
		// on libère les verrous
		$sql = "UNLOCK TABLES";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);

	//die("FIN mettreAJourInscriptions");	
	} // fin   fonction mettreAJourInscriptions()

	function idPremiereInscriptionEnAttenteATraiter($idSeance, $idClub, $dateAujourdhui, $dateAvantVeilleSeance) {


	//	if ($idClub==0) { // = semaine 2	sans quota
			$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}adherent, {$GLOBALS['prefixe']}seance 
			WHERE licenceAdherent = adherentLicence AND seanceId=idSeance 
			AND seanceId=".$idSeance." AND attenteInscription >0 
			AND (dateLimiteAttenteAdherent IS NULL OR dateLimiteAttenteAdherent<'$dateAujourdhui' OR '$dateAvantVeilleSeance'<'$dateAujourdhui') AND supprimeeSeance='N'
			ORDER BY dateHeureInscription ASC";
	//if ($idSeance==122) die($sql);
			$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
			// si au moins un en attente
			if ($inscription = mysqli_fetch_assoc ($res)) {
				return ($inscription['idInscription']);
			}
			else return(0);
	} // fin fonction idPremiereInscriptionEnAttenteATraiter

	function idDerniereInscriptionPlaceAttribueeATraiter($idSeance) {
		$sql = "SELECT idInscription FROM {$GLOBALS['prefixe']}inscription, {$GLOBALS['prefixe']}seance WHERE seanceId=idSeance AND seanceId=$idSeance AND attenteInscription IS NULL AND dateHeureAnnulationInscription IS NULL ORDER BY dateHeureInscription DESC ";
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		// si au moins un en attente
		if ($inscription = mysqli_fetch_assoc ($res))
			return $inscription['idInscription'];
		else return 0;
	} // fin idDerniereInscriptionPlaceAttribueeATraiter
	
	function enAttente($idSeance) {
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE attenteInscription >0 AND seanceId=$idSeance";
	//echo("92 : ".$sql."\n");
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		$inscription = mysqli_fetch_assoc ($res);
		if ($inscription) return TRUE;
		else return FALSE;
	}

	function rangAttente($idAdherent, $idSeance) {
		$sql = "SELECT * FROM {$GLOBALS['prefixe']}inscription WHERE seanceId= $idSeance AND attenteInscription IS NOT NULL ORDER BY dateHeureInscription";
		$res = mysqli_query ($GLOBALS['lkId'], $sql);
		$rang = 0;
		while ($uneInscription=mysqli_fetch_assoc ($res)) {
			$rang++;
			if ($uneInscription['adherentLicence'] == $idAdherent) return $rang;
		}
		return 0;
	}

	function nombrePlacesDisponibles($idSeance) {
		// calcul nbInscrits
		$sql = "SELECT COUNT(idInscription) AS nbInscrits FROM {$GLOBALS['prefixe']}inscription WHERE (attenteInscription IS NULL  OR attenteInscription=-1) AND seanceId=$idSeance AND dateHeureAnnulationInscription IS NULL";
	//echo("111 : ".$sql."\n");
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		$ligne = mysqli_fetch_assoc ($res);
		if ($ligne) $nbInscrits = $ligne['nbInscrits'];
		else $nbInscrits = 0;
		// calcul maxSeance
		$sql = "SELECT maxSeance FROM {$GLOBALS['prefixe']}seance WHERE idSeance=$idSeance";
	//echo("118 : ".$sql."\n");
		$res = mysqli_query ($GLOBALS['lkId'], $sql) or die ($sql." : ".mysqli_error($GLOBALS['lkId']));
		$ligne = mysqli_fetch_assoc ($res);
		$maxSeance = $ligne['maxSeance'];
		// nbPlacesDisponibles
		$nbPlacesDisponibles = $maxSeance-$nbInscrits;
		return $nbPlacesDisponibles;
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// fin des fonctions pour inscrire adhérents en attente	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>