<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午10:16
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class SingleActionCard extends Message {
    /**
     * Message type.
     *
     * @var string
     */
    protected $type = 'actionCard';

    protected $hasAt = false;

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [
        'title',
        'text',
        'hideAvatar',
        'btnOrientation',
        'singleTitle',
        'singleURL'
    ];

    public function toXmlArray()
    {
        return [
            'actionCard' => [
                'title' => $this->get('title'),
                'text' => $this->get('text'),
                'hideAvatar' => $this->get('hideAvatar'),
                'btnOrientation' => $this->get('btnOrientation'),
                'singleTitle' => $this->get('singleTitle'),
                'singleURL' => $this->get('singleURL')
            ],
        ];
    }
}