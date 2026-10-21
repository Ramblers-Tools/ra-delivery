<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

interface Smtp2goTransportInterface
{
    /**
     * @return array{status: int, body: string, error: string}
     */
    public function post(
        string $url,
        string $body,
        array $headers,
        int $connectTimeout,
        int $requestTimeout
    ): array;
}
