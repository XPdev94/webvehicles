<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysql_select_db($database_LLUIS, $LLUIS);
$query_PREGUNTES = "SELECT * FROM CHECKINGVEHICLESRESPOSTES WHERE IDVEHICLETREB= ".$MAXID." AND RESPOSTA IS NULL";
$PREGUNTES = mysql_query($query_PREGUNTES, $LLUIS) or die(mysql_error());
$row_PREGUNTES = mysql_fetch_assoc($PREGUNTES);
$i= mysql_num_rows($PREGUNTES);

$query_PREGUNTES = "SELECT * FROM CHECKINGVEHICLESRESPOSTES LEFT JOIN CHECKINGVEHICLESPREGUNTES ON CHECKINGVEHICLESRESPOSTES.IDVEHICLECHECKING=CHECKINGVEHICLESPREGUNTES.IDVEHICLECHECKING WHERE CHECKINGVEHICLESPREGUNTES.TIPOARTICULO='M' AND CHECKINGVEHICLESRESPOSTES.CADUCITAT IS NULL AND CHECKINGVEHICLESRESPOSTES.IDVEHICLETREB= ".$MAXID;
$PREGUNTES = mysql_query($query_PREGUNTES, $LLUIS) or die(mysql_error());
$row_PREGUNTES = mysql_fetch_assoc($PREGUNTES);
$i=$i+mysql_num_rows($PREGUNTES);

if($i>0){header ('Location:checklist.php?error');}
else{
	$updateSQL = "UPDATE VEHICLESTREBS SET FINALCHECKLIST=-1 WHERE IDVEHICLETREB=".$MAXID;
	$Result1 = mysql_query($updateSQL, $LLUIS) or die(mysql_error());
	header ('Location:afegirfotos.php');
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
<h1>CHECKLIST COMPLETAT</h1>
<h1>GRÀCIES</h1>
</body>
</html>
<?php mysql_free_result($PREGUNTES); ?>