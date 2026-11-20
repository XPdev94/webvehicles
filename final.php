<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
$NOMTREB = $_SESSION['NOMTREB'] ?? '';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>VEHICLES OSG</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<h1><?php echo $NOMTREB; ?></h1>
<h1>REGISTRO COMPLETADO</h1>
<h1>GRACIAS</h1>
</body>
</html>
