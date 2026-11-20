<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$EMPRESATREB = $_SESSION['EMPRESATREB'] ?? '';
$CODIEMPRESA = $_SESSION['CODIEMPRESA'] ?? 0;


function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{ 
  $theValue = addslashes($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

mysqli_select_db($LLUIS, $database_LLUIS);
$query_MAXVEHICLETREB = "SELECT max(IDVEHICLETREB) as MAXID FROM VEHICLESTREBS WHERE EMPRESATREB='".$EMPRESATREB."'";
$MAXVEHICLETREB = mysqli_query($LLUIS, $query_MAXVEHICLETREB) or die(mysqli_error($LLUIS));
$row_MAXVEHICLETREB = mysqli_fetch_assoc($MAXVEHICLETREB);
$totalRows_MAXVEHICLETREB = mysqli_num_rows($MAXVEHICLETREB);
$_SESSION['MAXID'] =$row_MAXVEHICLETREB['MAXID'] ?? 0;
echo "maxid=".$row_MAXVEHICLETREB['MAXID'];

$query_TRIAT = "SELECT * FROM VEHICLESTREBS WHERE IDVEHICLETREB=".$row_MAXVEHICLETREB['MAXID'];
$TRIAT = mysqli_query($LLUIS, $query_TRIAT) or die(mysqli_error($LLUIS));
$row_TRIAT = mysqli_fetch_assoc($TRIAT);
$totalRows_TRIAT = mysqli_num_rows($TRIAT);

$query_ANTERIOR = "SELECT max(CHECKINGVEHICLESRESPOSTES.IDVEHICLETREB) as MAXID FROM VEHICLESTREBS LEFT JOIN CHECKINGVEHICLESRESPOSTES ON VEHICLESTREBS.IDVEHICLETREB=CHECKINGVEHICLESRESPOSTES.IDVEHICLETREB WHERE CODIEMPRESA=".$row_TRIAT['CODIEMPRESA']." AND CODIOBRA='".$row_TRIAT['CODIOBRA']."' AND CHECKINGVEHICLESRESPOSTES.IDVEHICLETREB<".$row_MAXVEHICLETREB['MAXID']." AND VEHICLESTREBS.FINALCHECKLIST=-1";
$ANTERIOR = mysqli_query($LLUIS, $query_ANTERIOR) or die(mysqli_error($LLUIS));
$row_ANTERIOR = mysqli_fetch_assoc($ANTERIOR);
$totalRows_ANTERIOR = mysqli_num_rows($ANTERIOR);
if($row_ANTERIOR['MAXID']==null){$ant=0;}else{$ant=$row_ANTERIOR['MAXID'];}
$_SESSION['ANT'] = $ant ?? 0;

$query_VEHICLESTREBS = "SELECT * FROM VEHICLESTREBS WHERE IDVEHICLETREB= ".$row_MAXVEHICLETREB['MAXID'];
$VEHICLESTREBS = mysqli_query($LLUIS, $query_VEHICLESTREBS) or die(mysqli_error($LLUIS));
$row_VEHICLESTREBS = mysqli_fetch_assoc($VEHICLESTREBS);
$totalRows_VEHICLESTREBS = mysqli_num_rows($VEHICLESTREBS);

$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$row_VEHICLESTREBS['CODIOBRA']."'";
$OBRES = mysqli_query($LLUIS, $query_OBRES) or die(mysqli_error($LLUIS));
$row_OBRES = mysqli_fetch_assoc($OBRES);
$totalRows_OBRES = mysqli_num_rows($OBRES);
$_SESSION['TIPUSUS'] = $row_OBRES['TIPUSUS'] ?? null;

$query_PREGUNTES = "SELECT CHECKINGVEHICLESTIPMAQ.*,CHECKINGVEHICLESRESPOSTES.CADUCITAT FROM CHECKINGVEHICLESTIPMAQ LEFT JOIN CHECKINGVEHICLESRESPOSTES ON CHECKINGVEHICLESRESPOSTES.IDVEHICLECHECKING=CHECKINGVEHICLESTIPMAQ .IDVEHICLECHECKING AND CHECKINGVEHICLESRESPOSTES.IDVEHICLETREB=".$ant." WHERE CODIGOTIPOMAQUINA = ".$row_OBRES['IDTIPUSVEHICLE'];
$PREGUNTES = mysqli_query($LLUIS, $query_PREGUNTES) or die(mysqli_error($LLUIS));
$row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES);
$totalRows_PREGUNTES = mysqli_num_rows($PREGUNTES);

do{
	$insertSQL = sprintf("INSERT INTO CHECKINGVEHICLESRESPOSTES (IDVEHICLETREB, ORDRE, TIPUSCHECKING, IDVEHICLECHECKING, CADUCITAT) VALUES (%s, %s, %s, %s, %s)",
					   GetSQLValueString($row_MAXVEHICLETREB['MAXID'], "int"),
					   GetSQLValueString($row_PREGUNTES['ORDEN'], "int"),
					   GetSQLValueString($row_PREGUNTES['TIPUSCHECKING'], "int"),
					   GetSQLValueString($row_PREGUNTES['IDVEHICLECHECKING'], "int"),
					   GetSQLValueString($row_PREGUNTES['CADUCITAT'], "date"));

	mysqli_select_db($LLUIS, $database_LLUIS);
	$Result1 = mysqli_query($LLUIS, $insertSQL) or die(mysqli_error($LLUIS));	
} while ($row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES));

if($row_OBRES['TIPUSUS']==999){
	$query_PREGUNTES = "SELECT * FROM CHECKINGVEHICLESPREGUNTES WHERE TIPUSCHECKING=2";
	$PREGUNTES = mysqli_query($LLUIS, $query_PREGUNTES) or die(mysqli_error($LLUIS));
	$row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES);
	$totalRows_PREGUNTES = mysqli_num_rows($PREGUNTES);
	
	do{
		$insertSQL = sprintf("INSERT INTO CHECKINGVEHICLESRESPOSTES (IDVEHICLETREB, ORDRE, TIPUSCHECKING, IDVEHICLECHECKING) VALUES (%s, %s, %s, %s)",
						   GetSQLValueString($row_MAXVEHICLETREB['MAXID'], "int"),
						   GetSQLValueString($row_PREGUNTES['ORDEN'], "int"),
						   GetSQLValueString($row_PREGUNTES['TIPUSCHECKING'], "int"),
						   GetSQLValueString($row_PREGUNTES['IDVEHICLECHECKING'], "int"));
	
		mysqli_select_db($LLUIS, $database_LLUIS);
		$Result1 = mysqli_query($LLUIS, $insertSQL) or die(mysqli_error($LLUIS));	
	} while ($row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES));
}
header ('Location:checklist.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<body>
</body>
</html>
<?php
mysqli_free_result($MAXVEHICLETREB);
mysqli_free_result($ANTERIOR);
mysqli_free_result($TRIAT);
mysqli_free_result($VEHICLESTREBS);
mysqli_free_result($OBRES);
mysqli_free_result($PREGUNTES);
?>