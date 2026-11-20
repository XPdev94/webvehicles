<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysql_select_db($database_LLUIS, $LLUIS);
$updateSQL = "UPDATE CHECKINGVEHICLESRESPOSTES SET CADUCITAT=null WHERE IDCHECKINGRESPOSTA=".$_GET['ID'];
$Result1 = mysql_query($updateSQL, $LLUIS) or die(mysql_error());
header("Location:checklist.php");
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