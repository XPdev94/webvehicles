<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_LLUIS = "localhost";
$database_LLUIS = "qce122";
$username_LLUIS = "qce122";
$password_LLUIS = "2630";
// $LLUIS = mysql_pconnect($hostname_LLUIS, $username_LLUIS, $password_LLUIS) or trigger_error(mysql_error(),E_USER_ERROR);
$LLUIS = mysqli_connect($hostname_LLUIS, $username_LLUIS, $password_LLUIS, $database_LLUIS) or trigger_error(mysqli_connect_error(), E_USER_ERROR);
?>