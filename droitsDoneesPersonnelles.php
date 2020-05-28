<?php
// droitsDoneesPersonnelles.php
	session_start();

	include('init.inc.php');
	
	// gérer liste d'attente avec courriel(s) éventuel(s)
	


/////////////////////////////////////////////////////////////////////
// MAIN
/////////////////////////////////////////////////////////////////////
{
	if (isset($_POST['newAction'])) {
		switch ($_POST['newAction']) {
			case "envoyer":
				envoyer();
				break;
			case "terminer" :
				break;
		}	// fin case	
	}
	else { // pas de POST : initial
		afficherFormulaire();
	}
}
// fin MAIN
/////////////////////////////////////////////////////////////////////

	function afficherFormulaire() {
		include('param.inc.php');
		$GLOBALS['titrePage'] = "Exercice des droits sur les données personnelles  <br>de {$_SESSION['prenomActeur']} {$_SESSION['nomActeur']}";
		$GLOBALS['titrePageCourt'] = "Données personnelles";
?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim(); document.getElementById('message').focus();">
	<form method="POST" name="formDroitsd" id="formDroits" action="droitsDoneesPersonnelles.php" >
		<input type="hidden" name="newAction" id="newAction" value="envoyer">

		<div id="haut" >
<?php
		include("divEnTete.inc.php");
?>
		</div>

		<div id="content">
			<table style="width: 900px; margin: auto;">
				<tbody>
					<tr>
						<td>expéditeur :
						</td>
						<td>
							<input type="text" name="de" style="width: 250px;" readonly="readonly" value="<?php echo $_SESSION['courrielActeur'];?>">
						</td>
					</tr>
					<tr>
						<td>destinataire :
						</td>
						<td>
							<input type="text" name="destinataire" style="width: 250px;" readonly="readonly" value="<?php echo $GLOBALS['courrielAdmin'];?>">
						</td>
						</td>
					</tr>
					<tr>
						<td>sujet :
						</td>
						<td>
							<input type="text" name="sujet" style="width: 500px;" readonly="readonly" value="Exercice des droits d'opposition, d'accès et de rectification sur les données personnelles">
						</td>
					</tr>
					<tr>
						<td>votre message :
						</td>
						<td>
							<textarea name="message" id="message" style="width: 760px; height: 300px;"></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		</div>
			
		<div id="bas">
			<hr>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td style="width: 50%; text-align: center; font-size: large;">
							<button style="width: 150px; font-size: large;" onClick="javascript:window.close();">Abandonner</button>
						</td>
						<td style="width: 50%; text-align: center; font-size: large;">
							<button type="submit" style="width: 150px; font-size: large;">Envoyer</button>
						</td>
					</tr>
				<tbody>
			</table>
			<hr>
		</div>
		</form>
	</body>
</html>

<?php 
	}
	// fin function afficherFormulaire
	
	function envoyer() {
		envoyerCourriel($_POST['de'],$_POST['destinataire'],$_POST['sujet'],$_POST['message']);
		
		include('param.inc.php');
		
		$GLOBALS['titrePage'] = "Exercice des droits sur les données personnelles  <br>de {$_SESSION['prenomActeur']} {$_SESSION['nomActeur']}";
		$GLOBALS['titrePageCourt'] = "Données personnelles";
?>
<!DOCTYPE html>
<html lang="fr-fr">
<?php
		include("headHTML.inc.php");
?>

	<body onLoad="redim(); document.getElementById('message').focus();">

		<div id="haut" >
<?php
		include("divEnTete.inc.php");
?>
		</div>

		<div id="content">
			<br><br><br>
			<p style="text-align: center; font-size: large;">
				Votre message a été envoyé.
			</p>
			<br>
			<br>
			<p style="text-align: center; font-size: large;">
				<button style="width: 150px; font-size: large;" onClick="javascript:window.close();">Retour</button>
			</p>
		</div>
			
		
	</body>
</html>

<?php 
	}
	// fin function envoyer

?>