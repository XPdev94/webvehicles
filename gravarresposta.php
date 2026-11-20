<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysqli_select_db($LLUIS, $database_LLUIS);
$updateSQL = "UPDATE CHECKINGVEHICLESRESPOSTES SET RESPOSTA=".$_GET['RESPOSTA']." WHERE IDCHECKINGRESPOSTA=".$_GET['ID'];
echo $updateSQL;
$Result1 = mysqli_query($LLUIS, $updateSQL) or die(mysqli_error($LLUIS));
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