<?php

require ('admin/mysql.inc');

// SETTINGS
$db_host = "localhost";
$db_username = "dbusername";
$db_password = "dbpassword";

$default_lid = "1";
$domain = "http://mailer.example.com/";
$admin_username = "admin-username";
$admin_password = "admin-login-password";
$p = "password-for-api-service";

// TODO: GET PARAMETERS FROM QUERY STRING
$email = $_GET["email"];
$makeconfirmed = $_GET["makeconfirmed"];
$name = $_GET["name"];
$city = $_GET["city"]; 
$country = $_GET["country"];
$lid = $_GET["lid"];
$unsubscribe = $_GET["unsubscribe"];


if( $_GET["password"] != $p ) {
  printf("ERROR: invalid password");
  return (0);
}

if( $email == "" ) {
  printf("ERROR: missing email.");
  return (0);
}

if( $lid == "" )
  $lid = $default_lid;



// Login to phplist as admin and save cookie using CURLOPT_COOKIEFILE
// NOTE: Must log in as admin in order to bypass email confirmation
$url = $domain . "admin/?";
$ch = curl_init();
$login_data = array();
$login_data["login"] = $admin_username;
$login_data["password"] = $admin_password;
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, ""); //Enable Cookie Parser.
//File does not need to exist - http://curl.netmirror.org/libcurl/c/libcurl-tutorial.html for more info
$result = curl_exec($ch);
#echo("Result was: $result"); //debug
if (curl_errno($ch)) {
  printf("ERROR: could not log in with admin credentials");
  return(0);
 }
  mysql_connect($db_host, $db_username , $db_password );
  mysql_select_db('phplists');
 $result = mysql_query("select id from phplist_listattr_city where name='".$city."'");
  while ($row = mysql_fetch_assoc($result)) {
    $city_id = $row['id'];
  }
  $result = mysql_query("select id from phplist_listattr_countries where name='".$country."'");
  while ($row = mysql_fetch_assoc($result)) {
    $country_id = $row['id'];
  }

if (isset($unsubscribe) && $unsubscribe == 1 ) {
  $result = mysql_query("select id from phplist_user_user where email='".$email."'");
  while ($row = mysql_fetch_assoc($result)) {
    $user_id = $row['id'];
  }

  $post_data["page"] = 'users';
  $post_data["start"] = '0'; 
  $post_data["delete"] = $user_id;
  $url = $domain . "/admin/?page=users&start=0&delete=".$user_id;
  curl_setopt($ch, CURLOPT_URL, $url);
}
 else {

// 3) Now simulate post to subscriber form.
$post_data["email"] = $email;
$post_data["emailconfirm"] = $email;
$post_data["htmlemail"] = "1";
// No longer required  $post_data["list[$lid]"] = "signup";
$post_data["list[$lid]"] = "signup";
$post_data["subscribe"] = "Subscribe";
$post_data["makeconfirmed"] = $makeconfirmed;  //If set to 1 it will confirm user bypassing confirmation email
if($name && $name != "")
  $post_data["attribute1"] = $name;
if($city_id && $city_id >0)
  $post_data["attribute2"] = $city_id;
if($country_id && $country_id >0)
  $post_data["attribute3"] = $country_id;

$url = $domain . "?p=subscribe";



curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

}

$result = curl_exec($ch);

#echo('Result was: ' .$result);
if (curl_errno($ch)) {
  printf("ERROR: could not post subscribe request");
  return(0);
}

printf("SUCCESS");
curl_close($ch);

?>
