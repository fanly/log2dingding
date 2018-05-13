<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午3:54
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class Link extends Message {
    /**
     * Message type.
     *
     * @var string
     */
    protected $type = 'link';

    protected $hasAt = false;

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [
        'text',
        'title',
        'picUrl',
        'messageUrl'
    ];

    public function toXmlArray()
    {
        return [
            'link' => [
                'text' => $this->get('text'),
                'title' => $this->get('title'),
                'picUrl' => $this->get('picUrl'),
                'messageUrl' => $this->get('messageUrl')
            ],
        ];
    }
}