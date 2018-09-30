<?php
// Set your redirect locations
$authenticated_url = '';
$error_url = '';

// If user is already authenticated, & cookies are set
if(isset($_COOKIE['mg_sso_profile']) && isset($_COOKIE['mg_sso_token'])) {

    // Redirect to application
    header ("Location: " . $authenticated_url );

} else {

    // Set params
    $client_id = ""; // Client App ID (from Microsoft)
    $client_secret = ""; // Client App Secrect (from Microsoft)
    $client_space = ""; // Microsoft Domain ID
    $client_redirect = ""; // Where to redirect after authenticated
    $scopes = ""; // Scopes of data requested (ex. 'openid+profile+user.read')

    // Get Auth Code from MS API
    if(isset($_GET["code"])) {

        // Build API Token Url
        $url = "https://login.microsoftonline.com/" . $client_space . "/oauth2/v2.0/token";
        // Url passed parameters
        $fields = array(
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "scope" => $scopes,
            "grant_type" =>
            "authorization_code",
            "code" => $_GET["code"],
            "redirect_uri" => $client_redirect
        );

        // For each API Url field
        foreach($fields as $key=>$value) {
          $fields_string .= $key . "=" . $value . "&";
        }
        // Trim, prep query string
        rtrim($fields_string, "&");

        // Make HTTP request
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);

        // If result has Access Token
        if ($result->access_token) {

            // Set cookies for access and refresh tokens
            setcookie("mg_sso_token", $result->access_token, time() + 3600);
            setcookie("mg_sso_refresh_token", $result->refresh_token, time() + 3600);

            // Make HTTP Request for Graph
            $url = "https://graph.microsoft.com/v1.0/me";
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_HTTPHEADER, array("Authorization: bearer " . $result->access_token, "Host: graph.microsoft.com"));
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            $user = curl_exec($ch);
            $profile = json_decode($user);
            curl_close($ch);

            // If user is authenticated
            if ($user && !empty($profile)) {

                // Set cookie for response
                setcookie("mg_sso_profile", $user, time() + 3600);
                // Redirect to application
                header ("Location: " . $authenticated_url);

            } else {

                // Redirect user to error
                header ("Location: " . $error_url);

            }

        }

    } else {

    	// Redirect back to login
    	header("Location: https://login.microsoftonline.com/" . $client_space . "/oauth2/v2.0/authorize?client_id=" . $client_id . "&scope=" . $scopes . "&resource_mode=query&response_type=code&redirect_uri=" . $client_redirect);

    }

}

?>
