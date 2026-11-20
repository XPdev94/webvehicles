<?php require_once('Connections/LLUIS.php'); ?>
<?php session_start();
$_SESSION['NOMUSUARI'] = $_POST['NOMUSUARI'];
$_SESSION['PASSWORD'] = $_POST['PASSWORD'];

header('Location:validar.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo $TITOL; ?> </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
</body>
</html>