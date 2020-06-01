<?php
// headHTML.inc.php
?>

	<head>
		<meta content="text/html; charset=UTF-8" http-equiv="content-type">
		<meta name="Author" content="Michel Delord">
		<meta name="Description" content="inscriptions sorties">
		<meta name="Keywords" content="">
		<title><?php echo($GLOBALS['titrePageCourt']); ?></title>

		<link rel="shortcut icon" href="images/picto Rando.png">

		<link rel="stylesheet" type="text/css" href="css/mac.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">


		<script type="text/javascript" src="js/mac.js"></script>

		<script>
			function CheckRadio(name) { 
				//recupere tous les objets qui ont le nom "name" 
				var objs=document.getElementsByName(name); 
				//Pour chaques objets.... 
				for(i=0;i<objs.length;i++) { 
					//Si l'objet en cours en coché on renvoie true 
					if (objs[i].checked==true) 
						return true; 
				} 
				//Si on arrive ici, aucun radio-bouton n'est coché, on renvoie false 
				return false; 
			}
		</script>

<!--		
		<link rel="stylesheet" type="text/css" href="css/normalize.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

		<script src="js/odm.js"></script>
		<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
		<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
-->
<!--		
		<link rel="stylesheet" href="css/datepicker.css" type="text/css" />
		<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/base/jquery-ui.css"/>		
		
		<link rel="stylesheet" href="js/jquery-ui.min.css"> 
		<script src="http://code.jquery.com/jquery.min.js"></script> 
		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
		<script type="text/javascript" src="js/jquery.js"></script> 
		<script type="text/javascript" src="js/datepicker.js"></script>
		<script type="text/javascript" src="js/eye.js"></script>
		<script type="text/javascript" src="js/utils.js"></script>
		<script type="text/javascript" src="js/layout.js?ver=1.0.2"></script>
		<link rel="stylesheet" href="/resources/demos/style.css">
   
		<script type="text/javascript" src="js/jquery-1.11.2.min.js"></script>
-->

	</head>
