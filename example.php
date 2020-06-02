<?php
require_once "vendor/autoload.php";

use Asaas\SSO\ApiClient;

$config = [ 
		'sso_server_api_url' => 'http://localhost/asaas.accounts/public/api/application/v1',
		'sso_server_login_url' => 'http://localhost/asaas.accounts/public/login',
		'sso_server_logout_url' => 'http://localhost/asaas.accounts/public/logout',
		'sso_redirect_url' => 'http://127.0.0.1:8000/sso/login',
		'sso_application_id' => 'p8DIkWP9A4OySQITuDZYxc1NW256Gxz61587312382',
		'sso_application_secret' => 'r05ERrzDajJ8AcZ0tAH14LxOz8h5e1jORkbw2Ed2Hjd0oK9XK4an9Ss4VEdmVCfYmCMH5FWd77zyDU1Oti8yT745ynC6NdyvI15lK1PigBic8b5jESaKbMgxFfudYCSd5Ymdc0VMgkCHjDGWIJSqtsLgvK0n0oVPpBaBJ69nGGfAgaisi7TE5l0GsNpUOC4pZd2q5MuyJf0EJo4pfTHaaD07qLQlWJ1r3MDxLW6mntjqHzMCWhf9gkZrgE',
		'sso_access_token' => 'cvOzqk0GxOOGtHNw3dIghu531acyKPaCCzyqObr5j4oXo3DQZtH4kM4j8siKu7iKQFsPOMhirb4duhAAAPGhfsiPc7TzPR8mvG6vKkN396z4es2tvE9uuFXWVbMRcy13kJ76ddhyOYVKoTMXFxOn2vZeSvNHy2VsG6xLvo0bmCtNmaeVONZbxCLPNpLfRIgjliP9nsuwngzSHCpnzmy8GI8V733LvicprPfoO1YbOpSn2FQItpAxWR8BNI',
		'sso_application_encryption_key' => 'EbX6EbEjTiZxhw7w',  
    ];


$apiClient = new ApiClient($config);

print_r($apiClient->askForAccessToken());
