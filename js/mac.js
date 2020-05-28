

		window.onresize = redim;

		function redimmensionner() {
//			alert("on va recharger !");
//			location.reload(true);
//			location.replace("<?php echo($_SERVER['PHP_SELF'] );?>");
			redim();
		}
	
		function hauteur(obj) {
			if(obj.offsetHeight)          {return(obj.offsetHeight);}
			else if(obj.style.pixelHeight){return(obj.style.pixelHeight);}
		}

		function hauteurWindow() {
			if (window.innerHeight)  {return(window.innerHeight);}
			else if(document.body.clientHeight)  {return(document.body.clientHeight);}
		}
		function largeurWindow() {
			if (window.innerWidth)  {return(window.innerWidth);}
			else if(document.body.clientWidth)  {return(document.body.clientWidth);}
		}

		function reposAttendre() {
			attendre = document.getElementById("attendre");
			var largeurTotale = largeurWindow();
			var hauteurTotale = hauteurWindow();
			var left =Math.floor(largeurTotale/2)-50;
			var top =Math.floor(hauteurTotale/2)-50;
			attendre.style.left = left+"px";
			attendre.style.top = top+"px";
			attendre.style.display = 'inline';
		}
		
		function redim() {
		// redim de la fenêtre
			if(document.getElementById("haut") != null && document.getElementById("content") != null && document.getElementById("bas") != null) {
				var haut = document.getElementById("haut"); 
				var content = document.getElementById("content");
				var bas = document.getElementById("bas");
				
				var paddpx = document.body.style.paddingTop;
				var padd = paddpx.substr(0,paddpx.length-2);
				padd = parseInt(padd);
				if (hauteurWindow()<650)  {//768
/*
 					haut.style.position = "static";
					content.style.position = "static";
					content.style.width = "100%";
					bas.style.position = "static";
*/
//alert("Attention la fenêtre est trop petite !");
				}
					// on fixe les largeurs pour obtenir des hauteurs justes
					var largeurTotale = largeurWindow()-10; //-20 -10  pour tenir compte du padding-left
					if(navigator.userAgent.indexOf('MSIE')>0) {
						largeurTotale = largeurTotale -10;
					}
					var largeurDivPx = String(largeurTotale)+'px'; // -8 pour tenir compte de la marge gauche de body
					var hauteurTotale = hauteurWindow();
					var hauteurHaut = hauteur(haut) ;
					var hauteurBas = hauteur(bas)+10;
					var hauteurContent = hauteurTotale-hauteurHaut-hauteurBas-10; // -30 -0!
					if (hauteurContent<20) {
						alert("Attention la fenêtre n'est pas assez grande. Veuillez l'agrandir.");
					}	
					var posContentPx = String(hauteurHaut+padd)+'px'; 
					var posBasPx = String(hauteurTotale-hauteurBas)+'px';
					var hauteurContentPx = String(hauteurContent)+'px'; 
					if (hauteurContent>0) {
						content.style.height = hauteurContentPx;
					}
					haut.style.width = largeurDivPx;
					content.style.width = largeurDivPx;
					bas.style.width = largeurDivPx;			
					document.body.style.overflow="hidden";
//					content.style.top = posContentPx;
//					content.style.bottom = posBasPx;
//					bas.style.top = posBasPx;
			}
		// redim de la fiche
			if (document.getElementById("fiche")) { // si la fiche existe
				var fiche = document.getElementById("fiche"); 
				var barre = document.getElementById("barre"); 
				var conteneur = document.getElementById("conteneur"); 
				// hauteur de la fiche
				var hauteurFiche = hauteur(fiche);
				// hauteur de la barre
				var hauteurBarre = hauteur(barre);
				// hauteur du conteneur
				var hauteurConteneur = hauteurFiche - hauteurBarre-20;
				var hauteurConteneurPx = String(hauteurConteneur)+'px'; 
				// position du conteneur
	//			var topConteneurPx = String(hauteurBarre)+'px';
	//			conteneur.style.top = topConteneurPx;
				conteneur.style.top = '5px';
				conteneur.style.height = hauteurConteneurPx;
				fiche.style.display = 'none';
			}
		// redim de l'attestation
			if (document.getElementById("attestation")) { // si l'attestation existe
				var attestation = document.getElementById("attestation"); 
				var barreAttestation = document.getElementById("barreAttestation"); 
				var conteneurAttestation = document.getElementById("conteneurAttestation"); 
				// hauteur de la fiche
				var hauteurAttestation = hauteur(attestation);
				// hauteur de la barre
				var hauteurBarreAttestation = hauteur(barreAttestation);
				// hauteur du conteneur
				var hauteurConteneurAttestation = hauteurAttestation - hauteurBarreAttestation-20;
				var hauteurConteneurAttestationPx = String(hauteurConteneurAttestation)+'px'; 
				// position du conteneur
	//			var topConteneurPx = String(hauteurBarreAttestation)+'px';
	//			conteneur.style.top = topConteneurAttestationPx;
				conteneurAttestation.style.top = '5px';
				conteneurAttestation.style.height = hauteurConteneurAttestationPx;
	//			attestation.style.display = 'none';
			}
		} // fin redim
/*
		// impression d'une div
		var gAutoPrint = true;
		function processPrint(printMe){
			if (document.getElementById != null){
			var html = '<HTML>\n<HEAD>\n';
// à revoir :
				html += "\n<style type='text/css'>	 body {font-family:sans-serif; font-size:small; text-align: justify; }  </style>\n";

			html += '\n</HE' + 'AD>\n<BODY>\n';
			var printReadyElem = document.getElementById(printMe);

			if (printReadyElem != null) html +=  printReadyElem.innerHTML;
			else{
			alert("Erreur, rien à imprimer.");
			return;
			}

			html += '\n</BO' + 'DY>\n</HT' + 'ML>';
			var printWin = window.open("","processPrint", config='height=600, width=800, toolbar=yes, menubar=yes, scrollbars=yes, resizable=yes, location=yes, directories=yes, status=yes');
			printWin.document.open();
			printWin.document.write(html);
			printWin.document.close();

			if (gAutoPrint) printWin.print();
			} 
			else alert("Navigateur non supporté.");
		}

		// affiche une attente à la place de la page
		function afficherWait() {
			var wait = '<div id="attendre" class="attendre"><p><img alt="" src="images/waitblue.gif" style=" text-align:center; font-size:medium;"></p>	<p><br></p><p>Veuillez patienter ; 	votre mot de passe est en cours d\'expédition.</p></div>';
		document.body.innerHTML = wait;
		}
*/
		function sleep(seconds){
			var waitUntil = new Date().getTime() + seconds*1000;
			while(new Date().getTime() < waitUntil) true;
		}
		
		function imprimer(image){
			printImage=window.open(image);
			//sleep(3);
			//printImage.print();
			//printImage.close();
		}
