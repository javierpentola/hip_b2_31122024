<html>
 <head>
  <title>PHP-Test</title>
 </head>

<body bgcolor="#FFFFFF">
<p style="color:orange">
<font size="7">  
HTML_FONT
 
<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'hipgeneraldb';

$con=mysqli_connect($host,$user,$pass,$db);
if($con) {
echo "Connection successful";
}
else {
  echo "Connection error";
}
?>

</font>
</p>
  
 </body>
</html>