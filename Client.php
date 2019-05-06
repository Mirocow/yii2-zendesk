<?php

namespace mirocow\zendesk;

use Yii;
use yii\base\Component;
use yii\helpers\Json;

/**
 * Class Client
 * @author Derushev Aleksey <derushev.alexey@gmail.com>
 * @author Mirocow <mr.mirocow@gmail.com>
 * @package mirocow\zendesk
 * @see https://developer.zendesk.com/rest_api/docs/core/search
 */
class Client extends Component
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    public $apiKey;
    public $user;
    public $baseUrl;
    public $password;
    public $authType;
    public $debug;
    public $cache;
    public $useCache = true;

    /**
     * @var integer the number of seconds in which the cached value will expire. 0 means never expire.
     */
    public $cacheExpire = 30;

    /**
     * @var $ticketClass Ticket
     */
    public $ticketClass;

    /**
     * @var $commentClass Comment
     */
    public $commentClass;

    /**
     * @var $userClass User
     */
    public $userClass;

    /**
     * @var $httpClient \GuzzleHttp\Client
     */
    public $httpClient;

    /**
     * @return \GuzzleHttp\Client
     */
    public function init()
    {
        if(empty($this->ticketClass))
            $this->ticketClass = Ticket::className();

        if(empty($this->commentClass))
            $this->commentClass = Comment::className();

        if(empty($this->userClass))
            $this->userClass = User::className();

        if (!$this->httpClient) {
            $client = new \GuzzleHttp\Client([
                'base_url' => $this->baseUrl,
                'verify' => false,
                'auth'  => $this->getAuthSettings(),
                'headers' => [
                  'Content-Type' => 'application/json'
                ],
                'debug' => isset($this->debug)? $this->debug: YII_DEBUG
            ]);

            $this->httpClient = new \understeam\httpclient\Client([
                'client' => $client
            ]);
        }
    }

    /**
     * @TODO: Oauth support
     * @return array
     */
    public function getAuthSettings()
    {
        switch ($this->authType) {
            case 'basic':
                return [$this->user, $this->password, $this->authType];
                break;
            case 'digest':
                return [$this->user . '/token', $this->apiKey, 'basic'];
                break;
            default:
                $result = [];
                break;
        }

        return $result;
    }

    /**
     * @param $method
     * @param $requestUrl
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function execute($method, $requestUrl, $options = [])
    {
        try {
            return $this->httpClient->request($method, $this->baseUrl . $requestUrl, NULL, $options);
        } catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * @param $requestUrl
     * @param array $options
     * @return bool
     */
    public function get($requestUrl, $options = [])
    {
        return $this->execute(self::METHOD_GET, $requestUrl, $options);
    }

    /**
     * @param $requestUrl
     * @param array $options
     * @return bool
     */
    public function post($requestUrl, $options = [])
    {
        return $this->execute(self::METHOD_POST, $requestUrl, $options);
    }

    /**
     * @param $requestUrl
     * @param array $options
     * @return bool
     */
    public function put($requestUrl, $options = [])
    {
        return $this->execute(self::METHOD_PUT, $requestUrl, $options);
    }

    /**
     * @param $requestUrl
     * @param array $options
     * @return mixed
     */
    public function delete($requestUrl, $options = [])
    {
        return $this->execute(self::METHOD_DELETE, $requestUrl, $options);
    }
}
