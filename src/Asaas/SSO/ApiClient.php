<?php
namespace Asaas\SSO;

use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;

class ApiClient
{

    const VERSION = 2;

    protected $applicationId;

    protected $applicationSecret;

    protected $accessToken;

    protected $ssoApiUrl; 

    protected $encrypter; 

    protected $headers;

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->applicationId = $config['sso_application_id'];
        $this->applicationSecret = $config['sso_application_secret'];
        $this->ssoApiUrl = $config['sso_server_api_url'];
        $this->encrypter = new Encrypter($config['sso_application_encryption_key']);
        $this->headers = [
            'Application-Id' => $this->applicationId,
            'application-Secret' => $this->applicationSecret,
        ];
    }

    public function login($authorization_code, $callback = null)
    {
            $response = $this->exchangeCodeWithToken($authorization_code);
            $this->setaccessToken($response['data']['token']);

            $user = $this->getUser();

            if(is_callable($callback)){
                $callback($response);
            }

            return $user;
    }

    public function getUser($userId = null)
    {
        $endpoint = "user/$userId";
        
        if(is_null($userId))
            $endpoint = "user";
        
        $options = [
            'endpoint' => $endpoint,
        ];

        return $this->doApiGetRequest($options);
    }

    public function addUser($user)
    {
        $requestData = ['endpoint' => 'user', 'body' => json_encode($user)];
        return $this->doApiPostRequest($requestData);
    }
    
    public function editUser($user)
    {
        $requestData = ['endpoint' => 'user', 'body' => json_encode($user)];
        return $this->doApiPutRequest($requestData);
    }

    public function deleteUser($userId)
    {
        $requestData = ['endpoint' => "user/permission/$userId"];
        return $this->doApiDeleteRequest($requestData);
    }    
    
    public function updateToken()
    {
        $this->clearCurrentToken();
        $this->askForAccessToken();
    }

    public function clearAccessToken()
    {
        $this->setAccessToken(null);
    }

    public function setaccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        $token = [
            'access_token' => $accessToken,
            'authorization' => "Bearer $accessToken",
        ];

        $this->updateHeaders($token);        
    }

    public function getaccessToken()
    {
        return $this->accessToken;
    }

    public function askForAccessToken()
    {
        $options = [
            'endpoint' => 'token',
        ];

        $apiResponse = $this->doApiGetRequest($options);

        $this->updateHeaders($apiResponse['data']);

        return $this;
    }

    public function exchangeCodeWithToken($authorizationCode)
    {
        $options = [
            'endpoint' => 'token',
            'query' => [
                'authorization_code' => $authorizationCode
            ]
        ];

        $apiResponse = $this->doApiGetRequest($options);

        $this->updateHeaders($apiResponse['data']);

        return $apiResponse;
    }

    public function prepearResponseContent($response)
    {
        $response = json_decode($response, true);
        $isEncrypted = data_get($response, 'encrypted', false);
        $contentData = data_get($response, 'data', null);

        if($isEncrypted){
            $response['data'] = $this->decrypt($contentData);
        }

        return $response;
    }

    public function updateHeaders($headers)
    {
        $tmp = [];
        foreach ($headers as $key => $value) {
            $keyParts = explode('_', $key);
            $parts = [];
            foreach ($keyParts as $keyValue) {
                $parts[] = ucfirst($keyValue);
            }
            $this->headers[implode('-', $parts)] = $value;
        }
    }

    public function decrypt($text)
    {
        return $this->encrypter->decrypt($text, $this->applicationSecret);
    }

    public function encrypt($text)
    {
        return $this->encrypter->encrypt($text, $this->applicationSecret);
    }    

    public function doApiGetRequest($options)
    {
        $options['type'] = 'GET';

        return $this->doApiRequest($options);
    }

    public function doApiPostRequest($options)
    {
        $options['type'] = 'POST';

        return $this->doApiRequest($options);
    }

    public function doApiPutRequest($options)
    {
        $options['type'] = 'PUT';

        return $this->doApiRequest($options);
    }


    public function doApiDeleteRequest($options)
    {
        $options['type'] = 'DELETE';
        return $this->doApiRequest($options);
    }

    public function doApiRequest($options)
    {
        $this->updateHeaders(['server_version' => $this->getVersion()]);
        $client = new Client();
        $response = $client->request($options['type'], $this->ssoApiUrl . '/' . $options['endpoint'],
            [
                'headers' => $this->headers,
                'query' => data_get($options, 'query', null),
                'form_params' => data_get($options, 'form_params', null),
                'body' => data_get($options, 'body', null),
            ]
        );

        $response =  $response->getBody()->getContents();
        
        $result = $this->prepearResponseContent($response);

        return $result;
    }

    public function getVersion()
    {
        return self::VERSION;
    }

    public function generateRedirectUrl($extraParameters = [])
    {
        $params = array(
            'application_id' => $this->config['sso_application_id'],
            'redirect_url' => $this->config['sso_redirect_url'],
            'server_version' => $this->getVersion(),
        );

        foreach ($extraParameters as $key => $value) {
            $params[$key] = $value;
        }

        $ssoLoginUrl = $this->config['sso_server_login_url'];

        $params = http_build_query($params);

        return "{$ssoLoginUrl}?{$params}";
    }
}
