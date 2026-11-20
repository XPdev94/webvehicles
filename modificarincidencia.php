<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

if($_POST['TIPUSINCIDENCIA']==0){
	header ('Location:afegirincidencia.php?error3');
}else{
	if($_POST['OBSERVACIONS']==""){
		header ('Location:afegirincidencia.php?error2');
	}else{
		mysql_select_db($database_LLUIS, $LLUIS);
		$updateSQL = "UPDATE VEHICLESTREBS SET IDVEHICLEINC='".$_POST['TIPUSINCIDENCIA']."', OBSERVACIONS='".$_POST['OBSERVACIONS']."' WHERE IDVEHICLETREB=".$MAXID;
		$Result1 = mysql_query($updateSQL, $LLUIS) or die(mysql_error());
		header ('Location:afegirfotos.php');
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<h1><?php echo $NOMTREB; ?></h1>
<h1>INCIDENCIA REGISTRADA</h1>
<h1>GRÀCIES</h1>
</body>
</html>