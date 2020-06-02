<?php
namespace Asaas\SSO; 

use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;

class ApiClient
{

    protected $applicationId;

    protected $applicationSecret;

    protected $ssoApiUrl; 

    protected $encrypter; 

    protected $headers;

    public function __construct($config)
    {
        $this->applicationId = $config['sso_application_id'];
        $this->applicationSecret = $config['sso_application_secret'];
        $this->ssoApiUrl = $config['sso_server_api_url'];
        $this->encrypter = new Encrypter($config['sso_application_encryption_key']);
        $this->headers = [
            'Application-Id' => $this->applicationId,
            'application-Secret' => $this->applicationSecret,
        ];
    }

    public function getUserAttributes($sessionId, $applicationToken) {

        $token = [
            'session_id' => $sessionId,
            'access_token' => $applicationToken,
        ];

        $this->updateHeaders($token);

        $options = [
            'url' => $this->ssoApiUrl . '/user',
        ];

        return $this->doApiGetRequest($options);
    }

    public function pushUser($user)
    {
        $encryptedData = $this->encrypt($user);
        $requestData = ['url' => $this->ssoApiUrl . '/user', 'body' => $encryptedData];
        return $this->askForAccessToken()->doApiPostRequest($requestData);
    }

    public function editUser($user)
    {
        $requestData = ['url' => $this->ssoApiUrl . '/user', 'body' => $this->encrypt($user)];
        return $this->askForAccessToken()->doApiPutRequest($requestData);
    }

    public function deleteUser($user)
    {
        $requestData = ['url' => $this->ssoApiUrl . '/user/permission', 'body' => $this->encrypt($user)];
        return $this->askForAccessToken()->doApiDeleteRequest($requestData);
    }

    public function askForAccessToken()
    {
        $options = [
            'url' => $this->ssoApiUrl . '/token',
        ];

        $apiResponse = $this->doApiGetRequest($options);

        $this->updateHeaders($apiResponse['data']);

        return $this;
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

    private function updateHeaders($headers)
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

    private function decrypt($text)
    {
        return $this->encrypter->decrypt($text, $this->applicationSecret);
    }

    private function encrypt($text)
    {
        return $this->encrypter->encrypt($text, $this->applicationSecret);
    }    

    private function doApiGetRequest($options)
    {
        $options['type'] = 'GET';

        return $this->doApiRequest($options);
    }

    private function doApiPostRequest($options)
    {
        $options['type'] = 'POST';

        return $this->doApiRequest($options);
    }

    private function doApiPutRequest($options)
    {
        $options['type'] = 'PUT';

        return $this->doApiRequest($options);
    }


    private function doApiDeleteRequest($options)
    {
        $options['type'] = 'DELETE';
        return $this->doApiRequest($options);
    }

    private function doApiRequest($options)
    {
        $client = new Client();
        $response = $client->request($options['type'], $options['url'],
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
}