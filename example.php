<?php
require_once "vendor/autoload.php";

use Asaas\SSO\ApiClient;

$config = [ 
		'sso_server_api_url' => 'http://localhost/asaas.accounts/public/api/application/v1',
		'sso_server_login_url' => 'http://localhost/asaas.accounts/public/login',
		'sso_server_logout_url' => 'http://localhost/asaas.accounts/public/logout',
		'sso_redirect_url' => 'http://127.0.0.1:8000/sso/login',
		'sso_application_id' => '08lmdNG56nt90Uf3BNZEqhImYBjTarV71589818502',
		'sso_application_secret' => '7hj2Ge4qVQS26JTKl6wlbLosGHKzFEz9MMA5517BYjfXRajFCS10TMcR9bW9yPnLpqtca3GKrADz9fVOBvKAnupt8PS6qEHrUqMv41K9oRm8b1xBiVWE09MHOdavVwK5RMPHW87NtDtn0jXy2RkfkfrsGw0TOJpKBx9wMHJr7g7RoQxfGFh6p5dZ8RaWZ846k0Ts3BJbn8zZ1HNbZaW7BX220suBPFAEJFeoHpoNjNwHMCdwdzPGCIj1d0',
		'sso_access_token' => 'cvOzqk0GxOOGtHNw3dIghu531acyKPaCCzyqObr5j4oXo3DQZtH4kM4j8siKu7iKQFsPOMhirb4duhAAAPGhfsiPc7TzPR8mvG6vKkN396z4es2tvE9uuFXWVbMRcy13kJ76ddhyOYVKoTMXFxOn2vZeSvNHy2VsG6xLvo0bmCtNmaeVONZbxCLPNpLfRIgjliP9nsuwngzSHCpnzmy8GI8V733LvicprPfoO1YbOpSn2FQItpAxWR8BNI',
		'sso_application_encryption_key' => 'Y81TZoRNoAvrqvpd',  
    ];


$apiClient = new ApiClient($config);

echo(json_encode($apiClient->login('10.29.GqlHRiOCRhKvT2sxTXOFWKh7jweSTpxoiXw77NrIz14yMfO7EKROmFaScSWxlT80QlJCtYwSs3U9BHcQKMOW8Vsm4H4I7uXHl3cJ')));
