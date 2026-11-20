<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$CODIEMPRESA = $_SESSION['CODIEMPRESA'] ?? 0;
$EMPRESATREB = $_SESSION['EMPRESATREB'] ?? '';
$NOMUSUARI = $_SESSION['NOMUSUARI'] ?? '';
$buscar = $_POST['BUSCAR'] ?? '';

mysqli_select_db($LLUIS, $database_LLUIS);
$query_OBRES = "SELECT * FROM OBRES WHERE TIPOPROYECTOADOBY= '003' AND CODIEMPRESA = ".$CODIEMPRESA." AND (DESCRIPCIOOBRA LIKE '%$buscar%' OR CODIOBRA LIKE '%$buscar%')";
$OBRES = mysqli_query($LLUIS, $query_OBRES) or die(mysqli_error($LLUIS));
$row_OBRES = mysqli_fetch_assoc($OBRES);
$totalRows_OBRES = mysqli_num_rows($OBRES);

$query_VEHICLESTREBS = "SELECT VEHICLESTREBS.*, OBRES.MARCA, OBRES.MODEL, OBRES.MATRICULA, CHECKINGVEHICLESTIPUSINCIDENCIA.DESCRIPCION AS INCIDENCIA, CHECKINGVEHICLESTIPUSREVISIO.DESCRIPCION AS TIPUSREVISIO FROM VEHICLESTREBS LEFT JOIN OBRES ON VEHICLESTREBS.CODIEMPRESA=OBRES.CODIEMPRESA AND VEHICLESTREBS.CODIOBRA=OBRES.CODIOBRA LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA ON VEHICLESTREBS.IDVEHICLEINC=CHECKINGVEHICLESTIPUSINCIDENCIA.IDVEHICLEINC LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO ON VEHICLESTREBS.IDTIPUSREVISIO=CHECKINGVEHICLESTIPUSREVISIO.IDTIPUSREVISIO WHERE EMPRESATREB='".$EMPRESATREB."' ORDER BY DIA DESC LIMIT 5";
$VEHICLESTREBS = mysqli_query($LLUIS, $query_VEHICLESTREBS) or die(mysqli_error($LLUIS));
$row_VEHICLESTREBS = mysqli_fetch_assoc($VEHICLESTREBS);
$totalRows_VEHICLESTREBS = mysqli_num_rows($VEHICLESTREBS);

$codiultim = $descultim = $diaultim = '';
if ($row_VEHICLESTREBS !== null && is_array($row_VEHICLESTREBS)) {
    $codiultim = $row_VEHICLESTREBS['CODIOBRA'] ?? '';
    $descultim = $row_VEHICLESTREBS['DESCRIPCIOOBRA'] ?? '';
    $diaultim = $row_VEHICLESTREBS['DIA'] ?? '';
}

$query_CHECKING = "SELECT  vehiclestrebs.CODIOBRA, obres.MATRICULA, vehiclestrebs.DIA FROM vehiclestrebs LEFT JOIN obres ON vehiclestrebs.CODIEMPRESA=obres.CODIEMPRESA AND vehiclestrebs.CODIOBRA=obres.CODIOBRA WHERE vehiclestrebs.EMPRESATREB='".$EMPRESATREB."' AND vehiclestrebs.FINALCHECKLIST=-1 ORDER BY vehiclestrebs.DIA DESC LIMIT 5";
$CHECKING = mysqli_query($LLUIS, $query_CHECKING) or die(mysqli_error($LLUIS));
$row_CHECKING = mysqli_fetch_assoc($CHECKING);
$totalRows_CHECKING = mysqli_num_rows($CHECKING);
$dif = 0;
if ($row_CHECKING !== null && isset($row_CHECKING['DIA']) && strtotime($row_CHECKING['DIA'])) {
    $dif = (time() - strtotime($row_CHECKING['DIA'])) / 60 / 60 / 24;
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
.vehicles{
	display:block;
	padding:10px;
	margin:10px auto;
	width:60%;
	font-size:2em;
	border: 2px solid #6F6F6F;
	background-color:#FF6;
}
</style>
</head>
<body>
<h1><table><td><?php echo $NOMUSUARI; ?></td></table></h1>
<div class="envoltori">
<?php if($totalRows_VEHICLESTREBS>0){ ?>
    <table border=1 cellspacing="0">
      <tr bgcolor="#FFFF00">
        <td><strong>FECHA___</strong></td>
        <td><strong>CODIGO</strong></td>
        <td><strong>MODELO</strong></td>
        <td><strong>MATRICULA</strong></td>
        <td><strong>KMS</strong></td>
        <td><strong>TIPO REVISION</strong></td>
        <td><strong>INCIDENCIA</strong></td>
        <td></td>
      </tr>
    <?php $i=1; do { ?>
      <tr>
        <td><?php echo date("d-m-y",strtotime($row_VEHICLESTREBS['DIA'])); ?> </td>
        <td><?php echo $row_VEHICLESTREBS['CODIOBRA']; ?></td>
        <td><?php echo $row_VEHICLESTREBS['MODEL']; ?></td>
        <td><?php echo $row_VEHICLESTREBS['MATRICULA']; ?></td>
        <td><?php echo $row_VEHICLESTREBS['QUILOMETRES']; ?></td>
        <td><?php echo $row_VEHICLESTREBS['TIPUSREVISIO']; ?></td>
        <td><?php echo $row_VEHICLESTREBS['INCIDENCIA']; ?></td>
        <td><?php if($i==1){?><form method="post" action="gravarobra.php"><input name="submit" type="submit" value="MANTENER"><input type="hidden" name="CODIOBRA" value="<?php echo $codiultim;?>"></form><?php $i=0;}?></td>
      </tr>
      <?php } while ($row_VEHICLESTREBS = mysqli_fetch_assoc($VEHICLESTREBS)); ?>
    </table>  
<?php } ?>
<table border=1 bgcolor="#33FFFF">
<form method="post" name="buscar">
  <tr><td><input name="BUSCAR" id="BUSCAR" value="" size="10"></td><td><input name="submit" type="submit" value="BUSCAR"></td></tr>
  <input type="hidden" name="MM_insert" value="buscar">
</form>
</table>
<?php if($buscar <>""&&$totalRows_OBRES<>0){ ?>
<h2>VEHICULOS CON <?php echo $buscar;?></h2>
<table border=1 bgcolor="#33FFFF" cellspacing="0">
<tr bgcolor="#FFFF00">
	<td><strong>CODIGO</strong></td>
    <td><strong>MARCA</strong></td>
	<td><strong>MODELO</strong></td>
	<td><strong>MATRICULA</strong></td>
	<td></td>
</tr>
<?php do { ?>
	<tr><form method="post" action="gravarobra.php">
    <td><?php echo $row_OBRES['CODIOBRA']; ?></td>
	<td><?php echo $row_OBRES['MARCA']; ?></td>
	<td><?php echo $row_OBRES['MODEL']; ?></td>
	<td><?php echo $row_OBRES['MATRICULA']; ?></td>
	<td><input name="submit" type="submit" value="CANVIAR"></td>
	<input type="hidden" name="CODIOBRA" value="<?php echo $row_OBRES['CODIOBRA'];?>">
</form></tr>
  <?php } while ($row_OBRES = mysqli_fetch_assoc($OBRES)); ?>
<?php }else { ?>
<h3><font color="red">ESCRIBIR SOLO LOS NUMEROS DE LA MATRICULA</font></h3>
<?php } ?>
</table>
</div>  
<div class="vehicles">
<?php if($totalRows_CHECKING==0){?>
<h3><font color="red">NO HAS HECHO NINGUN CHECKING DE VEHICULO</font></h3>
<?php }else{ 
if($dif>30){?><h2><font color="red">HACE MÁS DE 30 DIAS DEL ULTIMO CHECKING</font></h2><?php } ?>
<h3>ULTIMOS CHECKINGS DE VEHICULOS</h3>
<table>
<tr>
<td><strong>FECHA____</strong></td>
<td><strong>CODIGO__</strong></td>
<td><strong>MATRICULA</strong></td>
</tr>
<?php do { ?>
<tr>
<td><?php echo date('d-m-Y',strtotime($row_CHECKING['DIA'])); ?> </td>
<td><?php echo $row_CHECKING['CODIOBRA']; ?> </td>
<td align="center"><?php echo $row_CHECKING['MATRICULA']; ?> </td>
</tr>
<?php } while ($row_CHECKING = mysqli_fetch_assoc($CHECKING)); ?>
</table>
<?php } ?>
</div>
</body>
</html>
<?php
mysqli_free_result($OBRES);
mysqli_free_result($VEHICLESTREBS);
mysqli_free_result($CHECKING);
?>