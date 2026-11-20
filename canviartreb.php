<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysql_select_db($database_LLUIS, $LLUIS);
$query_TREBS = "SELECT * FROM TREBALLADORS WHERE CODITREBALLADOR='".$_POST['CODITREBALLADOR']."'";
$TREBS = mysql_query($query_TREBS, $LLUIS) or die(mysql_error());
$row_TREBS = mysql_fetch_assoc($TREBS);
$totalRows_TREBS = mysql_num_rows($TREBS);

$_SESSION[CODITREB] = $_POST['CODITREBALLADOR'];
$_SESSION[NOMTREB] = $row_TREBS['NOMTREBALLADOR'];

header("Location:llistat.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<body>
</body>
</html>
<?php mysql_free_result($TREBS); ?>