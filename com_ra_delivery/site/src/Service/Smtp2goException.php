<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

class Smtp2goException extends \RuntimeException
{
    private int $httpStatus;
    private string $providerCode;
    private string $requestId;

    public function __construct(
        string $message,
        int $httpStatus = 0,
        string $providerCode = '',
        string $requestId = ''
    ) {
        parent::__construct($message, $httpStatus);
        $this->httpStatus = $httpStatus;
        $this->providerCode = $providerCode;
        $this->requestId = $requestId;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
