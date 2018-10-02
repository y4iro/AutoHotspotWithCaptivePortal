
<style type="text/css">
body {
	font-family: "Helvetica";
	background-color:#FF3D00;
	background-image: url("bg.png");
	background-size: contain;
    	background-repeat: repeat;
	background-filter: grayscale(100%);
}
#space {
	width: 100%;
	padding-top: 10px;
	padding-left: 5%;
	padding-right: 5%;
	border-style: none;
	border-bottom: 3px solid #47A063;
	font-size:18px;
}
#center {
	font-size: 30px;
	border-radius: 5px;
 	margin: 5%;
    	width: 80%;
    	height: auto;
    	padding-top: 10px;
    	padding-bottom: 10px;
    	padding-left: 5%;
    	padding-right: 5%;
    	background-color: #FFFFFF;
}
</style>
<html>
	<body>
		<br><br><br>
		<div id="center">
			<H1 align="center"> TU INFORMACI&Oacute;N HA SIDO PROCESADA CORRECTAMENTE. </H1>
			<?php
				$red = '\nnetwork={\n ssid="' . $_GET["usr"] . '"\n psk="' . $_GET["psw"] . '"\n}';
				shell_exec(" cd / && echo '" . $red . "' >> /etc/wpa_supplicant/wpa_supplicant.conf ");
				header( "refresh:3;url=rb.php" );
			?>
		</div>
	</body>
</html>
