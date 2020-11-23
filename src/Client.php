<?php

namespace Onetoweb\MyBusiness;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\RequestOptions;
use Onetoweb\MyBusiness\Exception\RequestException;

/**
 * MyBusiness Api Client
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 */
class Client
{
    /**
     * @var string
     */
    private $username;
    
    /**
     * @var string
     */
    private $password;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * @var callable
     */
    private $tokenUpdateCallback;
    
    /**
     * @var GuzzleClient
     */
    private $client;
    
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(string $baseUri, string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        
        $this->client = new GuzzleClient([
            'base_uri' => $baseUri
        ]);
    }
    
    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }
    
    /**
     * @param Token $token
     * 
     * @return void
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @param callable $tokenUpdateCallback
     * 
     * @return void
     */
    public function setTokenUpdateCallback(callable $tokenUpdateCallback): void
    {
        $this->tokenUpdateCallback = $tokenUpdateCallback;
    }
    
    /**
     * @return void
     */
    public function getAccessToken():  void
    {
        try {
            
            $response = $this->client->request('POST', 'MyAuth/create', [
                RequestOptions::JSON => [
                    'username' => $this->username,
                    'password' => md5($this->password),
                ]
            ]);
            
            $token = json_decode($response->getBody()->getContents());
            
            $this->updateToken($token);
            
        } catch (GuzzleRequestException $guzzleRequestException) {
            
            $this->handleGuzzleRequestException($guzzleRequestException);
            
        }
    }
    
    /**
     * @return void 
     */
    public function refreshAccessToken():  void
    {
        if ($this->token == null) {
            
            $this->getAccessToken();
            
        } else {
            
            try {
                
                $response = $this->client->request('GET', 'MyAuth/refresh', [
                    RequestOptions::HEADERS => [
                        'refreshtoken' => $this->token->getRefreshToken()
                    ]
                ]);
                
                $token = json_decode($response->getBody()->getContents());
                
                $this->updateToken($token);
                
            } catch (GuzzleRequestException $guzzleRequestException) {
                
                $this->handleGuzzleRequestException($guzzleRequestException);
                
            }
        }
    }
    
    /**
     * @param \stdClass $token
     * 
     * @return void
     */
    private function updateToken(\stdClass $token): void
    {
        $expiresTime = (time() + $token->expiresin);
        
        $expires = new \DateTime();
        $expires->setTimestamp($expiresTime);
        
        // set token
        $this->token = new Token($token->accesstoken, $token->refreshtoken, $expires);
        
        // token update callback
        if ($this->tokenUpdateCallback) {
            ($this->tokenUpdateCallback)($this->token);
        }
    }
    
    /**
     * @param GuzzleRequestException $guzzleRequestException
     * 
     * @throws RequestException if the GuzzleRequestException has response
     * @throws GuzzleRequestException if the GuzzleRequestException has no response
     * 
     * @return mixed void|array empty array on 404
     */
    private function handleGuzzleRequestException(GuzzleRequestException $guzzleRequestException): void
    {
        if ($guzzleRequestException->hasResponse()) {
            
            $message = $guzzleRequestException->getResponse()->getBody()->getContents();
            
            throw new RequestException($message, $guzzleRequestException->getCode(), $guzzleRequestException);
            
        }
        
        throw new $guzzleRequestException;
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     * 
     * @return mixed null|array
     */
    public function get(string $endpoint, array $query = []): ?array
    {
        return $this->request('GET', $endpoint, [], $query);
    }
    
    /**
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * 
     * @return mixed null|array
     */
    public function post(string $endpoint, array $data = [], array $query = []): ?array
    {
        return $this->request('POST', $endpoint, $data, $query);
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     * 
     * @return mixed null|array
     */
    public function delete(string $endpoint, array $query = []): ?array
    {
        return $this->request('DELETE', $endpoint, [], $query);
    }
    
    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * 
     * @return mixed null|array
     */
    public function request(string $method, string $endpoint, array $data = [], array $query = []): ?array
    {
        if ($this->token == null or $this->token->isExpired()) {
            $this->refreshAccessToken();
        }
        
        $options = [
            RequestOptions::HEADERS => [
                'accesstoken' => $this->token->getAccessToken(),
                'Cache-Control' => 'no-cache',
                'Connection' => 'close',
                'Content-Type' => 'application/json',
            ]
        ];
        
        if (count($data) > 0) {
            $options[RequestOptions::JSON] = $data;
        }
        
        if (count($query) > 0) {
            $endpoint .= '?'.http_build_query($query);
        }
        
        try {
            
            $response = $this->client->request($method, $endpoint, $options);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (GuzzleRequestException $guzzleRequestException) {
            
            $this->handleGuzzleRequestException($guzzleRequestException);
            
        }
    }
}