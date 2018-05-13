<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午3:50
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class FeedCardLink extends Message {
    /**
     * Messages type.
     *
     * @var string
     */
    protected $type = 'feedCard';

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [
        'title',
        'messageURL',
        'picURL'
    ];

    public function toJsonArray()
    {
        return [
            'title' => $this->get('title'),
            'messageURL' => $this->get('messageURL'),
            'picURL' => $this->get('picURL')
        ];
    }

    public function toXmlArray()
    {
        return [
            'title' => $this->get('title'),
            'messageURL' => $this->get('messageURL'),
            'picURL' => $this->get('picURL')
        ];
    }
}