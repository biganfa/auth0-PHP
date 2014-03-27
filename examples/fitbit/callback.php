<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '../../vendor/autoload.php';
require_once 'config.php';

use Auth0SDK\Auth0;

$auth0 = new Auth0(array(
    'domain'        => $auth0_cfg['domain'],
    'client_id'     => $auth0_cfg['client_id'],
    'client_secret' => $auth0_cfg['client_secret'],
    'redirect_uri'  => $auth0_cfg['redirect_uri'],
    'debug'         => true
));

$auth0->setDebugger(function($info) {
    file_put_contents("php://stdout", sprintf("\n[%s] %s:%s [%s]: %s\n",
        date("D M j H:i:s Y"), 
        $_SERVER["REMOTE_ADDR"],
        $_SERVER["REMOTE_PORT"], 
        "---",
        $info
    ));
});

$token = $auth0->getAccessToken();

// Get the user info from auth0
$userInfo = $auth0->getUserInfo();
$fitbitIdentity = $userInfo['result']['identities'][0];
if ($fitbitIdentity['provider'] != 'fitbit')
   die('The provider is not fitbit');

?>
<h1>User info from auth0</h1>
<pre>
<?php var_dump($userInfo); ?>
</pre>

<?php
require_once 'OAuthSimple.php';

$oauthObject = new OAuthSimple();
// The oauth credentials takes information from the configured application and information of the user, that
// we get from auth0
$signatures = array( 'consumer_key'     => $fitbit_cfg['consumer_key'],
                     'shared_secret'    => $fitbit_cfg['consumer_secret'],
		     'oauth_secret'     => $fitbitIdentity['access_token_secret'],
		     'oauth_token'      => $fitbitIdentity['access_token']);

// Url to the fitbit API to get the logged in user activities for the 26 of march of 2014
$url = 'https://api.fitbit.com/1/user/'.$fitbitIdentity['user_id'].'/activities/date/2014-03-26.json';

// Sign the url with the oauth credentials
$result = $oauthObject->sign(array(
        'path'      => $url,
        'signatures'=> $signatures));

// We create the HTTP call with the Authorization header
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  'Authorization: ' . $result['header']
));

// Make the call and interpret the result (we are not checking for errors here)
$r = json_decode(curl_exec($ch));

?>
<h1>Fitbit result</h1>
<pre>
<?php var_dump($r); ?>
</pre>

