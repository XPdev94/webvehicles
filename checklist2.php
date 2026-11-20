<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
if (isset($_GET['error'])){$msg ='FALTEN CAMPS PER COMPLETAR';}
mysql_select_db($database_LLUIS, $LLUIS);

if($ANT==""){$query_PREGUNTES = "SELECT RESP1.*, PREG.TIPOARTICULO , PREG.DESCRIPCION FROM CHECKINGVEHICLESRESPOSTES AS RESP1 LEFT JOIN CHECKINGVEHICLESPREGUNTES AS PREG ON RESP1.IDVEHICLECHECKING=PREG.IDVEHICLECHECKING WHERE RESP1.IDVEHICLETREB=".$MAXID." ORDER BY PREG.TIPUSCHECKING, PREG.ORDEN";}
else{$query_PREGUNTES = "SELECT RESP1.*, PREG.TIPOARTICULO , PREG.DESCRIPCION, RESP2.CADUCITAT AS CADUCAANT FROM CHECKINGVEHICLESRESPOSTES AS RESP1 LEFT JOIN CHECKINGVEHICLESPREGUNTES AS PREG ON RESP1.IDVEHICLECHECKING=PREG.IDVEHICLECHECKING LEFT JOIN (SELECT CADUCITAT, IDVEHICLECHECKING FROM CHECKINGVEHICLESRESPOSTES WHERE IDVEHICLETREB=".$ANT.") AS RESP2 ON RESP1.IDVEHICLECHECKING=RESP2.IDVEHICLECHECKING WHERE RESP1.IDVEHICLETREB=".$MAXID." ORDER BY RESP1.RESPOSTA, PREG.TIPUSCHECKING, PREG.ORDEN";}

$PREGUNTES = mysql_query($query_PREGUNTES, $LLUIS) or die(mysql_error());
$row_PREGUNTES = mysql_fetch_assoc($PREGUNTES);
$totalRows_PREGUNTES = mysql_num_rows($PREGUNTES);

$query_VEHICLESTREBS = "SELECT VEHICLESTREBS.*, CHECKINGVEHICLESTIPUSINCIDENCIA.DESCRIPCION AS TIPUSINCIDENCIA, CHECKINGVEHICLESTIPUSREVISIO.DESCRIPCION AS TIPUSREVISIO FROM VEHICLESTREBS LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA ON VEHICLESTREBS.IDVEHICLEINC=CHECKINGVEHICLESTIPUSINCIDENCIA.IDVEHICLEINC LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO ON VEHICLESTREBS.IDTIPUSREVISIO=CHECKINGVEHICLESTIPUSREVISIO.IDTIPUSREVISIO WHERE VEHICLESTREBS.IDVEHICLETREB=".$MAXID;
$VEHICLESTREBS = mysql_query($query_VEHICLESTREBS, $LLUIS) or die(mysql_error());
$row_VEHICLESTREBS = mysql_fetch_assoc($VEHICLESTREBS);
$totalRows_VEHICLESTREBS = mysql_num_rows($VEHICLESTREBS);

$query_TIPUSREVISIO = "SELECT * FROM CHECKINGVEHICLESTIPUSREVISIO ORDER BY DESCRIPCION";
$TIPUSREVISIO = mysql_query($query_TIPUSREVISIO, $LLUIS) or die(mysql_error());
$row_TIPUSREVISIO = mysql_fetch_assoc($TIPUSREVISIO);
$totalRows_TIPUSREVISIO = mysql_num_rows($TIPUSREVISIO);

$query_TIPUSINCIDENCIA = "SELECT * FROM CHECKINGVEHICLESTIPUSINCIDENCIA ORDER BY DESCRIPCION";
$TIPUSINCIDENCIA = mysql_query($query_TIPUSINCIDENCIA, $LLUIS) or die(mysql_error());
$row_TIPUSINCIDENCIA = mysql_fetch_assoc($TIPUSINCIDENCIA);
$totalRows_TIPUSINCIDENCIA = mysql_num_rows($TIPUSINCIDENCIA);

$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$row_VEHICLESTREBS['CODIEMPRESA']." AND CODIOBRA='".$row_VEHICLESTREBS['CODIOBRA']."'";
$OBRES = mysql_query($query_OBRES, $LLUIS) or die(mysql_error());
$row_OBRES = mysql_fetch_assoc($OBRES);
$totalRows_OBRES = mysql_num_rows($OBRES);

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
 dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
 dayNamesShort: ['Dom','Lun','Mar','Mié','Juv','Vie','Sáb'],
 dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
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
<script>
        function saveScrollPosition() {
            // Guardar la posición de desplazamiento actual en sessionStorage
            sessionStorage.setItem("scrollPosition", window.scrollY);
        }

        function restoreScrollPosition() {
            // Recuperar y restablecer la posición de desplazamiento desde sessionStorage
            var scrollPosition = sessionStorage.getItem("scrollPosition");
            if (scrollPosition !== null) {
                window.scrollTo(0, parseInt(scrollPosition));
                sessionStorage.removeItem("scrollPosition"); // Limpiar la posición guardada
            }
        }

        // Llamar a la función de restaurar posición cuando la página se cargue completamente
        document.addEventListener("DOMContentLoaded", restoreScrollPosition);
    </script>
</head>
<body>
<h1><?php echo $NOMTREB; ?></h1>
<h1><?php echo "CHECKLIST ".$row_OBRES['MATRICULA']." "; ?></h1>
<h2>las preguntas contestadas saldran al final</h2>
<h2><font color=red><?php echo $msg; ?></font></h2>
<div class="envoltori" id="content">
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
	<td><form onsubmit="saveScrollPosition()" method="post" action="gravarresposta.php?RESPOSTA=-1&ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>"><input type="submit" value="SI"></form></td>
    <td><form onsubmit="saveScrollPosition()" method="post" action="gravarresposta.php?RESPOSTA=0&ID=<?php echo $row_PREGUNTES['IDCHECKINGRESPOSTA']; ?>"><input type="submit" value="NO"></form></td>
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
<?php } while ($row_PREGUNTES = mysql_fetch_assoc($PREGUNTES)); ?>
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
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar la respuesta aquí
            $respuesta = $_POST['respuesta'];
            // Realiza cualquier acción necesaria con la respuesta

            // Redirigir para evitar reenvío del formulario al recargar la página
            echo '<script>
                    saveScrollPosition();
                    window.location.href = "checklist2.php";
                  </script>';
            exit();
        }
?>
</div>
</body>
</html>
<?php
mysql_free_result($PREGUNTES);
mysql_free_result($VEHICLESTREBS);
mysql_free_result($TIPUSREVISIO);
mysql_free_result($TIPUSINCIDENCIA);
mysql_free_result($OBRES);
?>