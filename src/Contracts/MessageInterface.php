<?php
/**
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午2:59
 */
namespace Fanly\Log2dingding\Contracts;

interface MessageInterface {
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return bool
     */
    public function getHasAt(): bool;

    /**
     * @return mixed
     */
    public function transformForJsonRequest(): array;

    /**
     * @return string
     */
    public function transformToXml(): string;
}