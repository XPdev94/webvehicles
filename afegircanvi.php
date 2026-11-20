<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$CODIOBRA = $_SESSION['CODIOBRA'] ?? '';
$CODIEMPRESA = $_SESSION['CODIEMPRESA'] ?? 0;
$EMPRESATREB = $_SESSION['EMPRESATREB'] ?? '';
$CODITREB = $_SESSION['CODITREB'] ?? '';
$SUPERVISOR = $_SESSION['SUPERVISOR'] ?? 0;
$NOMTREB = $_SESSION['NOMTREB'] ?? '';

mysqli_select_db($LLUIS, $database_LLUIS);

$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$CODIOBRA."'";
$OBRES = mysqli_query($LLUIS, $query_OBRES) or die(mysqli_error($LLUIS));
$row_OBRES = mysqli_fetch_assoc($OBRES);
$totalRows_OBRES = mysqli_num_rows($OBRES);

$query_ITV = "SELECT * FROM ITV WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$CODIOBRA."'";
$ITV = mysqli_query($LLUIS, $query_ITV) or die(mysqli_error($LLUIS));
$row_ITV = mysqli_fetch_assoc($ITV);
$totalRows_ITV = mysqli_num_rows($ITV);

if($SUPERVISOR>0){$query_TIPUSREVISIO = "SELECT * FROM CHECKINGVEHICLESTIPUSREVISIO ORDER BY DESCRIPCION";}
else{$query_TIPUSREVISIO = "SELECT * FROM CHECKINGVEHICLESTIPUSREVISIO WHERE ESSUPERVISOR=0 ORDER BY DESCRIPCION";}
$TIPUSREVISIO = mysqli_query($LLUIS, $query_TIPUSREVISIO) or die(mysqli_error($LLUIS));
$row_TIPUSREVISIO = mysqli_fetch_assoc($TIPUSREVISIO);
$totalRows_TIPUSREVISIO = mysqli_num_rows($TIPUSREVISIO);

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
$msg="";
$nograbar=0;
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "afegir")) {
	if($_POST['TIPUSREVISIO']==0){$msg="S'HA D'INFORMAR EL TIPUS DE REVISIO";$nograbar=1;}
	if($_POST['TIPUSREVISIO']<>0&&$_POST['TIPUSREVISIO']<>3&&$_POST['KMS']==0){$msg="S'HA D'INFORMAR ELS KMS";$nograbar=1;}
	if($nograbar==0){
	echo "CONSTRUYENDO CHECKLIST";	
	$insertSQL = sprintf("INSERT INTO VEHICLESTREBS (EMPRESATREB, CODITREB, CODIOBRA, CODIEMPRESA, OBSERVACIONS, IDTIPUSREVISIO, QUILOMETRES) VALUES (%s, %s, %s, %s, %s, %s, %s)",
					  GetSQLValueString($EMPRESATREB, "text"),
					  GetSQLValueString($CODITREB, "text"),
					  GetSQLValueString($CODIOBRA, "text"),
					  GetSQLValueString($CODIEMPRESA, "int"),
					  GetSQLValueString($_POST['OBSERVACIONS'], "text"),
					  GetSQLValueString($_POST['TIPUSREVISIO'], "int"),
   					GetSQLValueString($_POST['KMS'], "int"));
	mysqli_select_db($LLUIS, $database_LLUIS);
	$Result1 = mysqli_query($LLUIS, $insertSQL) or die(mysqli_error($LLUIS));
	if($_POST['TIPUSREVISIO']==3){header ('Location:afegirincidencia.php');}
	if($_POST['TIPUSREVISIO']==5){header ('Location:final.php');}
	else{header ('Location:afegirchecklist.php');}
	}
}	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
.envoltori{
	display:block;
	padding:1px;
	margin:1px auto;
	width:95%;
	font-size:2em;
}
.envoltori input{
	font-size:1.25em;
}
.envoltori select{
	font-size:1.25em;
}

.envoltori textarea{
	font-size:1.25em;
}
</style>
</head>
<body>
<h1><?php echo $NOMTREB; ?></h1>
<h1>REGISTRAR VEHICULO</h1>
<div class="envoltori">
<table border=1 bgcolor="#66FF99" cellpadding="0">
<form method="post" name="afegir" >
  <tr><td><strong>CODIGO</strong></td><td><?php echo $CODIOBRA;?></td></tr>
  <tr><td><strong>MARCA</strong></td><td><?php echo $row_OBRES['MARCA'];?></td></tr>
  <tr><td><strong>MODELO</strong></td><td><?php echo $row_OBRES['MODEL'];?></td></tr>
  <tr><td><strong>MATRICULA</strong></td><td><?php echo $row_OBRES['MATRICULA'];?></td></tr>
  <tr><td><strong>PROXIMA ITV</strong></td><td><?php echo date("d-m-y",strtotime($row_ITV['PROXIMITV']));if($row_ITV['PROXIMITV']<date('Y-m-d')){echo " **CADUCAT**";}?></td></tr>
<tr><td><strong>TIPO REVISION</strong></td>
<td><select name="TIPUSREVISIO" id="TIPUSREVISIO"><?php do {  ?>
     <option value="<?php echo $row_TIPUSREVISIO['IDTIPUSREVISIO']?>"><?php echo $row_TIPUSREVISIO['DESCRIPCION']?></option>
      			<?php } while ($row_TIPUSREVISIO = mysqli_fetch_assoc($TIPUSREVISIO));
    $rows = mysqli_num_rows($TIPUSREVISIO);
    if($rows > 0) {
       mysqli_data_seek($TIPUSREVISIO, 0);
	   $row_TIPUSREVISIO = mysqli_fetch_assoc($TIPUSREVISIO);
    } ?></select></td></tr>
<tr><td><strong>KMS</strong></td><td> <input name="KMS" id="KMS" value="0" size="5"></td></tr>
<tr><td><strong>OBSERVACIONES</strong></td><td><textarea name="OBSERVACIONS" cols="30"></textarea></td></tr>
</table>
<h2><input name="submit" type="submit" value="CONTINUAR"></h2>
<input type="hidden" name="MM_insert" value="afegir">
</form>
<h2><form method="post" action="llistat.php"><input type="submit" value="CANCELAR"></form></h2>
<h2><font color="red"><?php echo $msg?></font></h2>
<h3>tarda unos segundos en gravar</h3>
</div>
</body>
</html>
<?php
mysqli_free_result($OBRES);
mysqli_free_result($ITV);
?>