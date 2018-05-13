<?php
/**
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午4:40
 */
namespace Fanly\Log2dingding\Dingtalk;

use Exception;
use Fanly\Log2dingding\Dingtalk\Messages\Message;
use Fanly\Log2dingding\Dingtalk\Messages\Text;
use Fanly\Log2dingding\Support\Client;
use InvalidArgumentException;

class Messager {
    protected $message;

    /**
     * @var array
     */
    protected $atMobiles = [];

    protected $isAtAll = false;

    /**
     * @var string
     */
    protected $accesstoken;

    /**
     * @var bool
     */
    protected $secretive = false;

    /**
     * @var \Fanly\Msgrobot\Support\Client
     */
    protected $client;

    /**
     * MessageBuilder constructor.
     *
     * @param \Fanly\Msgrobot\Support\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set message to send.
     *
     * @param string|Message $message
     *
     * @return \Fanly\Msgrobot\Dingtalk\Messenger
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function message($message)
    {
        if (is_string($message) || is_numeric($message)) {
            $message = new Text($message);
        }

        if (!($message instanceof Message)) {
            throw new InvalidArgumentException('Invalid message.');
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @param int $agentId
     *
     * @return \Fanly\Msgrobot\Dingtalk\Messenger
     */
    public function accessToken(string $accesstoken)
    {
        $this->accesstoken = $accesstoken;

        return $this;
    }

    public function atMobiles($atMobiles) {
        $this->atMobiles = $atMobiles;

        return $this;
    }

    public function isAtAll($isAtAll) {
        $this->isAtAll = $isAtAll;

        return $this;
    }

    public function send($message = null)
    {
        if ($message) {
            $this->message($message);
        }

        if (empty($this->message)) {
            throw new Exception('No message to send.');
        }

        $at = $this->message->hasAt ? [
            'atMobiles' => $this->atMobiles,
            'isAtAll' => $this->isAtAll
        ] : [];
        $message = $this->message->transformForJsonRequest([], $at);

        $this->client->httpPostJson($this->accesstoken, $message);
    }

    /**
     * Return property.
     *
     * @param string $property
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        throw new InvalidArgumentException(sprintf('No property named "%s"', $property));
    }
}