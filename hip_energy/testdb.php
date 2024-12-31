<html>
 <head>
  <title>PHP-Test</title>
 </head>

<body bgcolor="#FFFFFF">
<p style="color:orange">
<font size="7">  
HTML_FONT
 
<?php

$host='sql108.infinityfree.com'; //mysql host name
$user='if0_37852817';  //mysql username   
$pass='BkgzaebxbJ';   //mysql password
$db='if0_37852817_hipgeneraldb'; //mysql database

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