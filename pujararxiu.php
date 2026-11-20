<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysql_select_db($database_LLUIS, $LLUIS);
$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$CODIOBRA."'";
$OBRES = mysql_query($query_OBRES, $LLUIS) or die(mysql_error());
$row_OBRES = mysql_fetch_assoc($OBRES);
$totalRows_OBRES = mysql_num_rows($OBRES);

$arxiu=$_FILES['arxiu']['name'];
$tamany=$_FILES['arxiu']['size'];
$ruta2 = pathinfo($arxiu);
$extensio=strtoupper($ruta2['extension']);
$nomarxiu=date("Ymdgis")."-".$NOMUSUARI."-". $row_OBRES['MATRICULA'].".".$extensio;


function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

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


if($tamany>10485760){header ('Location:afegirfotos.php?error1');}
else{
	if($arxiu<>''){
	move_uploaded_file($_FILES['arxiu']['tmp_name'],'fotos/'.$nomarxiu);
	$updateSQL="UPDATE VEHICLESTREBS SET TEFOTOGRAFIES=-1 WHERE IDVEHICLETREB=".$MAXID;
	$Result1 = mysql_query($updateSQL, $LLUIS) or die(mysql_error());
   }   
   $_SESSION[ARXIUPUJAT] = "SI";
   header ('Location:afegirfotos.php');
}   
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
</body>
</html>