<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午3:45
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class Text extends Message {
    /**
     * Message type.
     *
     * @var string
     */
    protected $type = 'text';

    protected $hasAt = true;

    /**
     * Properties.
     *
     * @var array
     */
    protected $properties = ['content'];

    /**
     * Text constructor.
     *
     * @param string $content
     */
    public function __construct(string $content) {
        parent::__construct(compact('content'));
    }

    /**
     * @return array
     */
    public function toXmlArray() {
        return [
            'text' => [
                'content' => $this->get('content')
            ]
        ];
    }
}