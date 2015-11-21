<?php

$hubspotApiKey = getenv('HUBSPOT_API_KEY');
$thankYouPageUrl = getenv('THANK_YOU_PAGE_URL');
$linkedinClientId = getenv('LINKEDIN_APP_CLIENT_ID');
$linkedinClientSecret = getenv('LINKEDIN_APP_CLIENT_SECRET');



$urlOfThisFile = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

$encodedUrlOfThisFile = urlencode($urlOfThisFile);



if(!isset($_GET['code'])){
	
	$randomStateValue = mt_rand(100000, 999999);
	
	header ("Location: https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=$linkedinClientId&redirect_uri=$encodedUrlOfThisFile&state=$randomStateValue&scope=r_basicprofile%20r_emailaddress");
	exit;	
}



$code = $_GET['code'];

$url = "https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code&code=$code&redirect_uri=$encodedUrlOfThisFile&client_id=$linkedinClientId&client_secret=$linkedinClientSecret";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Content-Type: application/x-www-form-urlencoded"));
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, "");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$responseJson = curl_exec($ch);
curl_close($ch);

$responseArray = json_decode($responseJson, true);

$accessToken = $responseArray['access_token'];



$url = "https://api.linkedin.com/v1/people/~:(first-name,last-name,email-address,num-connections,summary)?format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Authorization: Bearer $accessToken"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$responseJson = curl_exec($ch);
curl_close($ch);

$responseArray = json_decode($responseJson, true);

$firstName = "";
$lastName = "";
$email = "";
$numberOfLinkedinConnections = "";
$linkedinBio = "";

if(isset($responseArray['firstName'])){
	$firstName = $responseArray['firstName'];
}
if(isset($responseArray['lastName'])){
	$lastName = $responseArray['lastName'];
}
if(isset($responseArray['emailAddress'])){
	$email = $responseArray['emailAddress'];
}
if(isset($responseArray['numConnections'])){
	$numberOfLinkedinConnections = $responseArray['numConnections'];
}
if(isset($responseArray['summary'])){
	$linkedinBio = $responseArray['summary'];
}



$propertiesArray = array(
	'properties' => array(
		array(
			'property' => 'email',
			'value' => $email
		),
		array(
			'property' => 'firstname',
			'value' => $firstName
		),
		array(
			'property' => 'lastname',
			'value' => $lastName
		),
		array(
			'property' => 'linkedinconnections',
			'value' => $numberOfLinkedinConnections
		),
		array(
			'property' => 'linkedinbio',
			'value' => $linkedinBio
		)
	)
);

$propertiesJson = json_encode($propertiesArray);

$url = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/$email/?hapikey=$hubspotApiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $propertiesJson);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$response = curl_exec($ch);
curl_close($ch);

header ("Location: $thankYouPageUrl");
exit;

?>
