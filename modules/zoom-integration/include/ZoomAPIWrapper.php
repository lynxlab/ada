<?php

/*
==============================================================================
MIT License

Copyright (c) 2020 Ben Jefferson

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
==============================================================================

https://github.com/skwirrel/ZoomAPIWrapper/blob/master/ZoomAPIWrapper.php

This is a simple wrapper class to handle authenticating requests to the Zoom APIs

Usage:

$zoom = new ZoomAPIWrapper( '<your API key>', '<your API secret>' );

// It is up to you to use the right method, path and specify the path parameters
// to match the {placeholders} in the path.
// You can find all the details of method, path, placholders and body content in the Zoom
// API reference docs here: https://marketplace.zoom.us/docs/api-reference/zoom-api
// $queryParameters,$requestParameters,$bodyContent are all optional

$response = $zoom->doRequest($method,$path,$queryParameters,$requestParameters,$bodyContent);

if ($response === false) {
    // There was an error before the request was event sent to the api
    echo "Errors:".implode("\n",$zoom->requestErrors())
} esle {
    printf("Response code: %d\n",$zoom->responseCode());
    print_r($response);
}

*/

namespace Lynxlab\ADA\Module\ZoomIntegration;

class ZoomAPIWrapper
{
    private $errors;
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    private $tokenUrl;
    private $timeout;
    private $responseCode;

    public const S2STOKEN = 'ZOOMS2S_SESSTOKEN';

    public function __construct($apiKey, $apiSecret, $options = [])
    {
        $this->apiKey = $apiKey;

        $this->apiSecret = $apiSecret;

        $this->baseUrl = 'https://api.zoom.us/v2';
        $this->tokenUrl = 'https://zoom.us';
        $this->timeout = 30;

        // Store any options if they map to valid properties
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function urlsafeB64Encode($string)
    {
        return str_replace('=', '', strtr(base64_encode($string), '+/', '-_'));
    }

    private function generateAccessToken()
    {
        $token = $this->doRequest('POST', '/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => ZOOMCONF_S2S_ACCOUNTID,
        ]);
        $token['expire_ts'] = time() + $token['expires_in'];
        return $token;
    }

    private function generateJWT()
    {
        if (isset($_SESSION[self::S2STOKEN])) {
            // check token exp
            $validtoken = time() < $_SESSION[self::S2STOKEN]['expire_ts'];
        } else {
            $validtoken = false;
        }
        if (!$validtoken) {
            $_SESSION[self::S2STOKEN] = $this->generateAccessToken();
        }
        return $_SESSION[self::S2STOKEN]['access_token'];
    }

    private function headers()
    {
        return [
            'Authorization: Bearer ' . $this->generateJWT(),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private function pathReplace($path, $requestParams)
    {
        $errors = [];
        $path = preg_replace_callback('/\\{(.*?)\\}/', function ($matches) use ($requestParams, $errors) {
            if (!isset($requestParams[$matches[1]])) {
                $this->errors[] = 'Required path parameter was not specified: ' . $matches[1];
                return '';
            }
            return rawurlencode($requestParams[$matches[1]]);
        }, $path);

        if (count($errors)) {
            $this->errors = array_merge($this->errors, $errors);
        }
        return $path;
    }

    public function doRequest($method, $path, $queryParams = [], $pathParams = [], $body = '')
    {

        if (is_array($body)) {
            // Treat an empty array in the body data as if no body data was set
            if (!count($body)) {
                $body = '';
            } else {
                $body = json_encode($body);
            }
        }

        $this->errors = [];
        $this->responseCode = 0;

        $path = $this->pathReplace($path, $pathParams);

        if (count($this->errors)) {
            return false;
        }

        // Check if it's a call to token endpoint
        $isToken = stripos($path, 'token') !== false;

        $method = strtoupper($method);
        $url = ($isToken ? $this->tokenUrl : $this->baseUrl) . $path;

        // Add on any query parameters
        if (count($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        // Check if it's a call to token endpoint
        if ($isToken) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ":" . $this->apiSecret);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'])) {
            // All except DELETE can have a payload in the body
            if ($method != 'DELETE' && strlen($body)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $result = curl_exec($ch);

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return json_decode($result, true);
    }

    // Returns the errors responseCode returned from the last call to doRequest
    public function requestErrors()
    {
        return $this->errors;
    }

    // Returns the responseCode returned from the last call to doRequest
    public function responseCode()
    {
        return $this->responseCode;
    }
}
