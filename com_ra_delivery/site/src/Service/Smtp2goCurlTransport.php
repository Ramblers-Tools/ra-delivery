<?php

namespace Ramblers\Component\Ra_delivery\Site\Service;

defined('_JEXEC') or die;

class Smtp2goCurlTransport implements Smtp2goTransportInterface
{
    public function post(
        string $url,
        string $body,
        array $headers,
        int $connectTimeout,
        int $requestTimeout
    ): array {
        $curlHeaders = [];

        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name . ': ' . $value;
        }

        $curl = curl_init();

        if ($curl === false) {
            return ['status' => 0, 'body' => '', 'error' => 'Unable to initialise HTTP transport'];
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'utf-8',
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $requestTimeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $curlHeaders,
        ]);

        $responseBody = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'status' => $status,
            'body' => is_string($responseBody) ? $responseBody : '',
            'error' => $responseBody === false ? $error : '',
        ];
    }
}
