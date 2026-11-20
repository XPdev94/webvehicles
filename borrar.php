<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
if($CODIUSUARI==NULL){header ('Location:index.php?error');}

$deleteSQL = "DELETE FROM VEHICLESTREBS WHERE IDVEHICLETREB=".$_GET['IDVEHICLETREB'];
mysql_select_db($database_LLUIS, $LLUIS);
$Result1 = mysql_query($deleteSQL, $LLUIS) or die(mysql_error());
header("Location:llistat.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
</body>
</html>