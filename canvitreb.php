<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
if($CODIUSUARI==NULL){header ('Location:index.php?error');}

mysql_select_db($database_LLUIS, $LLUIS);

$query_TREBS = "SELECT * FROM TREBALLADORS WHERE CODIEMPRESA = ".$CODIEMPRESA." ORDER BY NOMTREBALLADOR";
$TREBS = mysql_query($query_TREBS, $LLUIS) or die(mysql_error());
$row_TREBS = mysql_fetch_assoc($TREBS);
$totalRows_TREBS = mysql_num_rows($TREBS);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<body>
<h2><?php echo $NOMEMPRESA." - ".$NOMUSUARI;  ?></h2>
<table>
<?php do { ?>
  <tr>
    <td><?php echo $row_TREBS['NOMTREBALLADOR']; ?> </td>
	<td><form method="post" action="canviartreb.php"><input name="submit" type="submit" value="CANVIAR"></td>
  <input type="hidden" name="CODITREBALLADOR" value="<?php echo $row_TREBS['CODITREBALLADOR'];?>">
</form>
  </tr>
  <?php } while ($row_TREBS = mysql_fetch_assoc($TREBS)); ?>
</table>  
</body>
</html>
<?php
mysql_free_result($TREBS);
?>