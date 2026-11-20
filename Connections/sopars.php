<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_sopars = "localhost";
$database_sopars = "qdq183";
$username_sopars = "qdq183";
$password_sopars = "2630";
$sopars = mysql_pconnect($hostname_sopars, $username_sopars, $password_sopars) or trigger_error(mysql_error(),E_USER_ERROR); 
?>