<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午11:09
 */

namespace Fanly\Msgrobot\Dingtalk\Messages;


class MoreActionCard extends Message {
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
        'btns'
    ];

    public function propertiesToArray(array $data, array $aliases = []): array
    {
        $btns = [];

        foreach ($this->get('btns') as $btn) {
            if ($btn instanceof ActionCardBtn) {
                $btns[] = $btn->toXmlArray();
            }
        }
        return [
            'title' => $this->get('title'),
            'text' => $this->get('text'),
            'hideAvatar' => $this->get('hideAvatar'),
            'btnOrientation' => $this->get('btnOrientation'),
            'btns' => $btns
        ];
    }

    public function toXmlArray()
    {
        $btns = [];

        foreach ($this->get('btns') as $btn) {
            if ($btn instanceof ActionCardBtn) {
                $btns[] = $btn->toXmlArray();
            }
        }
        return [
            'actionCard' => [
                'title' => $this->get('title'),
                'text' => $this->get('text'),
                'hideAvatar' => $this->get('hideAvatar'),
                'btnOrientation' => $this->get('btnOrientation'),
                'btns' => $btns
            ],
        ];
    }
}