<?php
/**
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午10:22
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class ActionCardBtn extends Message {
    /**
     * Messages type.
     *
     * @var string
     */
    protected $type = 'ActionCard';

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [
        'title',
        'actionURL'
    ];

    public function toJsonArray()
    {
        return [
            'title' => $this->get('title'),
            'actionURL' => $this->get('actionURL')
        ];
    }

    public function toXmlArray()
    {
        return [
            'title' => $this->get('title'),
            'actionURL' => $this->get('actionURL')
        ];
    }
}