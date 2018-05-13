<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午9:41
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class Markdown extends Message {
    /**
     * Message type.
     *
     * @var string
     */
    protected $type = 'markdown';

    protected $hasAt = true;

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = [
        'title',
        'text'
    ];

    public function toXmlArray()
    {
        return [
            'markdown' => [
                'title' => $this->get('title'),
                'text' => $this->get('text'),
            ],
        ];
    }
}