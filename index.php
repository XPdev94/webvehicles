<?php require_once('Connections/LLUIS.php'); ?>
<?php

$msg = '';

if (isset($_GET['error'])){$msg ='USUARI O PASSWORD INCORRECTE';}
$_SESSION['TITOL'] = "REGISTRE DE VEHICLES";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style>
.envoltori{
	display:block;
	padding:40px;
	margin:50px auto;
	width:80%;
	font-size:3em;
	border: 2px solid #6F6F6F;
	background-color:#FF9
}
.envoltori input{
	font-size:1.25em;
}
</style>
</head>

<body>
<div class="envoltori">
<form name="form1" method="POST" action="validarusuari.php">
  <table border="0">
    <tr><td><strong>USUARIO:</strong></td><td><input name="NOMUSUARI" type="" id="NOMUSUARI" size="10" ></td></tr>
    <tr><td><strong>PASSWORD:</strong></td><td><input name="PASSWORD" type="password" id="PASSWORD" size="5" ></td></tr>
    <tr>
      <td><input type="submit" name="Submit2" value="VALIDAR"></td>
    <td>&nbsp;</td></tr>
  </table>
  <font color=red><p><?php echo $msg; ?></p></font>
</form>
<a href="http://www.osg.cat"></a>
</div>
</body>
</html>

