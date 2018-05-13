<?php
/**
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午9:57
 */

namespace Fanly\Log2dingding\Dingtalk\Messages;


class FeedCard extends Message {
    /**
     * @var string
     */
    protected $type = 'feedCard';

    /**
     * @var array
     */
    protected $properties = [
        'links',
    ];

    /**
     * News constructor.
     *
     * @param array $items
     */
    public function __construct(array $links = [])
    {
        parent::__construct(compact('links'));
    }

    /**
     * @param array $data
     * @param array $aliases
     *
     * @return array
     */
    public function propertiesToArray(array $data, array $aliases = []): array
    {
        return ['links' => array_map(function ($link) {
            if ($link instanceof FeedCardLink) {
                return $link->toJsonArray();
            }
        }, $this->get('links'))];
    }

    public function toXmlArray()
    {
        $links = [];

        foreach ($this->get('links') as $link) {
            if ($link instanceof FeedCardLink) {
                $links[] = $link->toXmlArray();
            }
        }

        return [
            'feedCard' => [
                'links' => $links
            ],
        ];
    }
}