<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$NOMUSUARI = $_SESSION['NOMUSUARI'] ?? $_POST['NOMUSUARI'] ?? '';
$PASSWORD = $_SESSION['PASSWORD'] ?? $_POST['PASSWORD'] ?? '';

mysqli_select_db($LLUIS, $database_LLUIS);
$query_USUARIS = "SELECT TREBALLADORS.*, SUPERVISORS.IDSUPERVISOR FROM TREBALLADORS LEFT JOIN SUPERVISORS ON TREBALLADORS.CODIEMPRESA=SUPERVISORS.CODIEMPRESA AND TREBALLADORS.CODITREBALLADOR=SUPERVISORS.CODITREB WHERE WEBNOMUSUARI = '".$NOMUSUARI."'";
$USUARIS = mysqli_query($LLUIS, $query_USUARIS) or die(mysqli_error($LLUIS));
$row_USUARIS = mysqli_fetch_assoc($USUARIS);
$totalRows_USUARIS = mysqli_num_rows($USUARIS);
echo $totalRows_USUARIS;
if($row_USUARIS['WEBPASSWORD']==$PASSWORD&&$NOMUSUARI<>""){
	$_SESSION['CODIUSUARI'] = $row_USUARIS['CODIUSUARI'];
	if($row_USUARIS['CODIEMPRESA']==400){$_SESSION[CODIEMPRESA] = 6;}
	else{$_SESSION['CODIEMPRESA'] = $row_USUARIS['CODIEMPRESA'];}
	$_SESSION['NOMEMPRESA'] = $row_USUARIS['NOMEMPRESA'];
	$_SESSION['CODITREB'] = $row_USUARIS['CODITREBALLADOR'];
	$_SESSION['NOMTREB'] = $NOMUSUARI;
	$_SESSION['EMPRESATREB'] = $row_USUARIS['EMPRESATREB'];
	$_SESSION['SUPERVISOR'] = $row_USUARIS['IDSUPERVISOR'];
	$_SESSION['CODICANAL'] = $row_USUARIS['CODICANAL'];
	$_SESSION['ARXIUPUJAT'] = "NO";
	header ('Location:llistat.php');
}else{header ('Location:index.php?error');}
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
<?php mysqli_free_result($USUARIS); ?>