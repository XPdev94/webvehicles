<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();

$MAXID = $_SESSION['MAXID'] ?? 0;
$ANT = $_SESSION['ANT'] ?? '';
$NOMTREB = $_SESSION['NOMTREB'] ?? '';
$msg = '';

if (isset($_GET['error'])){$msg ='FALTEN CAMPS PER COMPLETAR';}
mysqli_select_db($LLUIS, $database_LLUIS);

if($ANT==""){$query_PREGUNTES = "SELECT RESP1.*, PREG.TIPOARTICULO , PREG.DESCRIPCION FROM CHECKINGVEHICLESRESPOSTES AS RESP1 LEFT JOIN CHECKINGVEHICLESPREGUNTES AS PREG ON RESP1.IDVEHICLECHECKING=PREG.IDVEHICLECHECKING WHERE RESP1.IDVEHICLETREB=".$MAXID." ORDER BY PREG.TIPUSCHECKING, PREG.ORDEN";}
else{$query_PREGUNTES = "SELECT RESP1.*, PREG.TIPOARTICULO , PREG.DESCRIPCION, RESP2.CADUCITAT AS CADUCAANT FROM CHECKINGVEHICLESRESPOSTES AS RESP1 LEFT JOIN CHECKINGVEHICLESPREGUNTES AS PREG ON RESP1.IDVEHICLECHECKING=PREG.IDVEHICLECHECKING LEFT JOIN (SELECT CADUCITAT, IDVEHICLECHECKING FROM CHECKINGVEHICLESRESPOSTES WHERE IDVEHICLETREB=".$ANT.") AS RESP2 ON RESP1.IDVEHICLECHECKING=RESP2.IDVEHICLECHECKING WHERE RESP1.IDVEHICLETREB=".$MAXID." ORDER BY RESP1.RESPOSTA, PREG.TIPUSCHECKING, PREG.ORDEN";}

$PREGUNTES = mysqli_query($LLUIS, $query_PREGUNTES) or die(mysqli_error($LLUIS));
$row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES);
$totalRows_PREGUNTES = mysqli_num_rows($PREGUNTES);

$query_VEHICLESTREBS = "SELECT VEHICLESTREBS.*, CHECKINGVEHICLESTIPUSINCIDENCIA.DESCRIPCION AS TIPUSINCIDENCIA, CHECKINGVEHICLESTIPUSREVISIO.DESCRIPCION AS TIPUSREVISIO FROM VEHICLESTREBS LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA ON VEHICLESTREBS.IDVEHICLEINC=CHECKINGVEHICLESTIPUSINCIDENCIA.IDVEHICLEINC LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO ON VEHICLESTREBS.IDTIPUSREVISIO=CHECKINGVEHICLESTIPUSREVISIO.IDTIPUSREVISIO WHERE VEHICLESTREBS.IDVEHICLETREB=".$MAXID;
$VEHICLESTREBS = mysqli_query($LLUIS, $query_VEHICLESTREBS) or die(mysqli_error($LLUIS));
$row_VEHICLESTREBS = mysqli_fetch_assoc($VEHICLESTREBS);
$totalRows_VEHICLESTREBS = mysqli_num_rows($VEHICLESTREBS);

$query_TIPUSREVISIO = "SELECT * FROM CHECKINGVEHICLESTIPUSREVISIO ORDER BY DESCRIPCION";
$TIPUSREVISIO = mysqli_query($LLUIS, $query_TIPUSREVISIO) or die(mysqli_error($LLUIS));
$row_TIPUSREVISIO = mysqli_fetch_assoc($TIPUSREVISIO);
$totalRows_TIPUSREVISIO = mysqli_num_rows($TIPUSREVISIO);

$query_TIPUSINCIDENCIA = "SELECT * FROM CHECKINGVEHICLESTIPUSINCIDENCIA ORDER BY DESCRIPCION";
$TIPUSINCIDENCIA = mysqli_query($LLUIS, $query_TIPUSINCIDENCIA) or die(mysqli_error($LLUIS));
$row_TIPUSINCIDENCIA = mysqli_fetch_assoc($TIPUSINCIDENCIA);
$totalRows_TIPUSINCIDENCIA = mysqli_num_rows($TIPUSINCIDENCIA);

$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$row_VEHICLESTREBS['CODIEMPRESA']." AND CODIOBRA='".$row_VEHICLESTREBS['CODIOBRA']."'";
$OBRES = mysqli_query($LLUIS, $query_OBRES) or die(mysqli_error($LLUIS));
$row_OBRES = mysqli_fetch_assoc($OBRES);
$totalRows_OBRES = mysqli_num_rows($OBRES);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
<script>
 $.datepicker.regional['es'] = {
 closeText: 'Cerrar',
 prevText: '< Ant',
 nextText: 'Sig >',
 currentText: 'Hoy',
 monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
 monthNamesShort: ['Ene','Feb','Mar','Abr', 'May','Jun','Jul','Ago','Sep', 'Oct','Nov','Dic'],
 dayNames: ['Domingo', 'Lunes', 'Martes', 'Mi�rcoles', 'Jueves', 'Viernes', 'S�bado'],
 dayNamesShort: ['Dom','Lun','Mar','Mi�','Juv','Vie','S�b'],
 dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S�'],
 weekHeader: 'Sm',
 dateFormat: 'dd/mm/yy',
 firstDay: 1,
 isRTL: false,
 changeMonth: true ,
 changeYear: true ,
 showMonthAfterYear: false,
 yearSuffix: ''
 };
 $.datepicker.setDefaults($.datepicker.regional['es']);
  $( function() {
    $( "#datepicker" ).datepicker();
  } );
  </script>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
.envoltori{
	display:block;
	padding:1px;
	margin:1px auto;
	width:95%;
	font-size:1.25em;
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
<h1><?php echo "CHECKLIST ".$row_OBRES['MATRICULA']." "; ?></h1>
<h2>las preguntas contestadas saldran al final</h2>
<h2><font color=red><?php echo $msg; ?></font></h2>
<div class="envoltori">
<table border=1 cellspacing="0" bgcolor="#FFFFCC">
<tr bgcolor="#33FFFF">
	<td><strong>REVISAR</strong></td>
	<td colspan="2"><strong>CORRECTO?</strong></td>
	<td colspan="2"><strong>CADUCA dd/mm/aaaa</strong></td>
</tr>
<?php do { ?>
	<tr>
	<td><strong><?php echo $row_PREGUNTES['DESCRIPCION']; ?></strong></td>
	<?php if($row_PREGUNTES['RESPOSTA']==null){?>
	<td><form method="post" action="gravarresposta.php?RESPOSTA=-1&ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>"><input type="submit" value="SI"></form></td>
    <td><form method="post" action="gravarresposta.php?RESPOSTA=0&ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>"><input type="submit" value="NO"></form></td>
	<?php }else{ ?>
    <td colspan="2" align="center"><?php if($row_PREGUNTES['RESPOSTA']==0){echo "NO";}else{echo "SI";}?></td>
    <?php } ?>
    <?php if($row_PREGUNTES['TIPOARTICULO']=='M'){
		if($row_PREGUNTES['CADUCITAT']==null){?>
            <td align="center"><form method="post" action="gravarcaducitat.php?ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>">
            <input id="datepicker" type="text" name="CADUCA" size="7" value="<?php if($row_PREGUNTES['CADUCAANT']==""){echo "";}else{echo date('d/m/Y',strtotime($row_PREGUNTES['CADUCAANT']));} ?>"></td>
            <td><input type="submit" value="GUARDAR"></td>
            </form>   
    	<?php }else{?>
            <td align="center" bgcolor="<?php if(strtotime($row_PREGUNTES['CADUCITAT'])-time()<2600000){echo "#F55A5D";}?>"><?php echo date('d/m/Y',strtotime($row_PREGUNTES['CADUCITAT']));?></td><td>
            <form method="post" action="borrarcaducitat.php?ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>">
            <input type="submit" value="CAMBIAR"></td>
            </form>   
	<?php } } ?>
    </tr>
<?php } while ($row_PREGUNTES = mysqli_fetch_assoc($PREGUNTES)); ?>
</table>
<table border=0 cellspacing="0" bgcolor="#FFFFCC">
<tr><td><strong>COMENTARIO </strong></td>
<?php if($row_VEHICLESTREBS['COMENTARI']==null){?>
	<td><form method="post" action="gravarcomentari.php"><textarea name="COMENTARI" cols="34"></textarea><input type="submit" value="GRABAR"></form></td>
<?php } else { ?>
	<td><?php echo $row_VEHICLESTREBS['COMENTARI'];?></td>
<?php } ?>
</tr></table>
<br>
<form method="post" action="finalchecklist.php"><input type="submit" value="FINAL"></form>
<h2><font color=red><?php echo $msg; ?></font></h2>
</div>
</body>
</html>
<?php
mysqli_free_result($PREGUNTES);
mysqli_free_result($VEHICLESTREBS);
mysqli_free_result($TIPUSREVISIO);
mysqli_free_result($TIPUSINCIDENCIA);
mysqli_free_result($OBRES);
?>