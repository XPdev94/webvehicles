<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
mysql_select_db($database_LLUIS, $LLUIS);
$query_OBRES = "SELECT * FROM OBRES WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$CODIOBRA."'";
$OBRES = mysql_query($query_OBRES, $LLUIS) or die(mysql_error());
$row_OBRES = mysql_fetch_assoc($OBRES);
$totalRows_OBRES = mysql_num_rows($OBRES);

$query_ITV = "SELECT * FROM ITV WHERE CODIEMPRESA=".$CODIEMPRESA." AND CODIOBRA='".$CODIOBRA."'";
$ITV = mysql_query($query_ITV, $LLUIS) or die(mysql_error());
$row_ITV = mysql_fetch_assoc($ITV);
$totalRows_ITV = mysql_num_rows($ITV);
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
<h1>REGISTRAR VEHICULO</h1>
<h2><font color="red">Para guardar fotos primero SELECCIONAR (de la camera o galeria) y después GUARDAR</font></h2>
<div class="envoltori">

<table border=1 bgcolor="#66FF99" cellpadding="0">
  <tr><td><strong>CODIGO</strong></td><td><?php echo $CODIOBRA;?></td></tr>
  <tr><td><strong>MARCA</strong></td><td><?php echo $row_OBRES['MARCA'];?></td></tr>
  <tr><td><strong>MODELO</strong></td><td><?php echo $row_OBRES['MODEL'];?></td></tr>
  <tr><td><strong>MATRICULA</strong></td><td><?php echo $row_OBRES['MATRICULA'];?></td></tr>
  <tr><td><strong>PROXIMA ITV</strong></td><td><?php echo date("d-m-y",strtotime($row_ITV['PROXIMITV']));if($row_ITV['PROXIMITV']<date('Y-m-d')){echo " **CADUCAT**";}?></td></tr>
<form method="post" action="pujararxiu.php" enctype="multipart/form-data">
   <tr><td><strong>FOTO</strong></td>
   <td><input type="file" name="arxiu"/>
  <input type="submit" name="enviar" value="GUARDAR" /></td></tr>
</form>
</table>
<?php if($ARXIUPUJAT=="SI"){?>
<h3><font color="red">FOTO GUARDADA</font></h3>
<?php }?>
<h3><form method="post" action="final.php"><input type="submit" value="ACABAR"></form></h3>
<h2><font color="red"><?php echo $msg?></font></h2>
</div>
</body>
</html>
<?php
mysql_free_result($OBRES);
mysql_free_result($ITV);
?>