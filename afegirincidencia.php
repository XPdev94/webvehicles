<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$EMPRESATREB = $_SESSION['EMPRESATREB'] ?? '';
$CODIEMPRESA = $_SESSION['CODIEMPRESA'] ?? 0;
$NOMTREB = $_SESSION['NOMTREB'] ?? '';
$msg = '';

mysqli_select_db($LLUIS, $database_LLUIS);
if (isset($_GET['error2'])){$msg ='FALTA OBSERVACIONS';}
if (isset($_GET['error3'])){$msg ='FALTA TIPUS INCIDENCIA';}
$query_MAXVEHICLETREB = "SELECT max(IDVEHICLETREB) as MAXID FROM VEHICLESTREBS WHERE EMPRESATREB='".$EMPRESATREB."'";
$MAXVEHICLETREB = mysqli_query($LLUIS, $query_MAXVEHICLETREB) or die(mysqli_error($LLUIS));
$row_MAXVEHICLETREB = mysqli_fetch_assoc($MAXVEHICLETREB);
$totalRows_MAXVEHICLETREB = mysqli_num_rows($MAXVEHICLETREB);
$_SESSION[MAXID] =$row_MAXVEHICLETREB['MAXID'];

$query_TRIAT = "SELECT * FROM VEHICLESTREBS WHERE IDVEHICLETREB=".$row_MAXVEHICLETREB['MAXID'];;
$TRIAT = mysqli_query($LLUIS, $query_TRIAT) or die(mysqli_error($LLUIS));
$row_TRIAT = mysqli_fetch_assoc($TRIAT);
$totalRows_TRIAT = mysqli_num_rows($TRIAT);

$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$row_TRIAT['CODIOBRA']."'";
$OBRES = mysqli_query($LLUIS, $query_OBRES) or die(mysqli_error($LLUIS));
$row_OBRES = mysqli_fetch_assoc($OBRES);
$totalRows_OBRES = mysqli_num_rows($OBRES);

$query_ITV = "SELECT * FROM ITV WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$row_TRIAT['CODIOBRA']."'";
$ITV = mysqli_query($LLUIS, $query_ITV) or die(mysqli_error($LLUIS));
$row_ITV = mysqli_fetch_assoc($ITV);
$totalRows_ITV = mysqli_num_rows($ITV);

$query_TIPUSINCIDENCIA = "SELECT * FROM CHECKINGVEHICLESTIPUSINCIDENCIA ORDER BY DESCRIPCION";
$TIPUSINCIDENCIA = mysqli_query($LLUIS, $query_TIPUSINCIDENCIA) or die(mysqli_error($LLUIS));
$row_TIPUSINCIDENCIA = mysqli_fetch_assoc($TIPUSINCIDENCIA);
$totalRows_TIPUSINCIDENCIA = mysqli_num_rows($TIPUSINCIDENCIA);

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

</style>
</head>
<body>
<h1><?php echo $NOMTREB; ?></h1>
<h1>REGISTRAR VEHICLE</h1>
<div class="envoltori">
<table border=1 bgcolor="#FFCC33" cellpadding="0">
  <tr><td><strong>CODI</strong></td><td><?php echo $row_TRIAT['CODIOBRA'];?></td></tr>
  <tr><td><strong>MARCA</strong></td><td><?php echo $row_OBRES['MARCA'];?></td></tr>
  <tr><td><strong>MODEL</strong></td><td><?php echo $row_OBRES['MODEL'];?></td></tr>
  <tr><td><strong>MATRICULA</strong></td><td><?php echo $row_OBRES['MATRICULA'];?></td></tr>
  <tr><td><strong>PROXIM ITV</strong></td><td><?php echo date("d-m-y",strtotime($row_ITV['PROXIMITV']));if($row_ITV['PROXIMITV']<date('Y-m-d')){echo " **CADUCAT**";}?></td></tr>
<tr><td><strong>TIPUS REVISIO</strong></td><td>INCIDENCIA</td></tr>
<tr><td><strong>KMS</strong></td><td><?php echo $row_TRIAT['QUILOMETRES'];?></td></tr>
<tr><td><strong>TIPUS INCIDENCIA</strong></td>
<form method="post" name="afegir" action="modificarincidencia.php">
<td><select name="TIPUSINCIDENCIA" id="TIPUSINCIDENCIA"><?php do {  ?>
     <option value="<?php echo $row_TIPUSINCIDENCIA['IDVEHICLEINC']?>"><?php echo $row_TIPUSINCIDENCIA['DESCRIPCION']?></option>
      			<?php } while ($row_TIPUSINCIDENCIA = mysqli_fetch_assoc($TIPUSINCIDENCIA));
    $rows = mysqli_num_rows($TIPUSINCIDENCIA);
    if($rows > 0) {
       mysqli_data_seek($TIPUSINCIDENCIA, 0);
	   $row_TIPUSINCIDENCIA = mysqli_fetch_assoc($TIPUSINCIDENCIA);
    } ?></select></td></tr>
<tr><td><strong>OBSERVACIONS</strong></td><td><textarea name="OBSERVACIONS" cols="70"></textarea></td></tr>    
</table>
<h2><input name="submit" type="submit" value="CONTINUAR"></h2>
<input type="hidden" name="MM_insert" value="afegir">
</form>
<h2><font color="red"><?php echo $msg?></font></h2>
</div>
</body>
</html>
<?php
mysqli_free_result($MAXVEHICLETREB);
mysqli_free_result($TRIAT);
mysqli_free_result($OBRES);
mysqli_free_result($ITV);
mysqli_free_result($TIPUSINCIDENCIA);
?>