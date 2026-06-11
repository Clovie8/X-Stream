<?php

class Token
{
    private static function addCountries($url, $a, $b)
    {
        $tempUrl = $url;
        if ($a != null) {
            $separator = (parse_url($tempUrl, PHP_URL_QUERY) === null) ? '?' : '&';
            $tempUrl .= $separator . 'token_countries=' . $a;
        }
        if ($b != null) {
            $separator = (parse_url($tempUrl, PHP_URL_QUERY) === null) ? '?' : '&';
            $tempUrl .= $separator . 'token_countries_blocked=' . $b;
        }
        return $tempUrl;
    }

    public static function signUrl($url, $securityKey, $expirationTime = 3600, $userIp = null, $isDirectory = false, $pathAllowed = '', $countriesAllowed = null, $countriesBlocked = null)
    {
       
        
        $parameterData = "";
        $parameterDataUrl = "";
        $signaturePath = "";
        $hashableBase = "";
        $token = "";
        
        $expires = time() + $expirationTime;
        $url = self::addCountries($url, $countriesAllowed, $countriesBlocked);
        
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $host = $parsedUrl['host'] ?? '';
        $scheme = $parsedUrl['scheme'] ?? 'https';
        
        // Parse query parameters
        $parameters = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parameters);
        }
        
        if ($pathAllowed != "") {
            $signaturePath = $pathAllowed;
            $parameters['token_path'] = $signaturePath;
        } else {
            $signaturePath = urldecode($path);
        }
        
        // Sort parameters
        ksort($parameters);
        
        foreach ($parameters as $key => $value) {
            if ($value == "") {
                continue;
            }
            if ($parameterData != "") {
                $parameterData .= "&";
            }
            $parameterData .= $key . "=" . $value;
            $parameterDataUrl .= "&" . $key . "=" . urlencode($value);
        }
        
        $hashableBase = $securityKey . $signaturePath . $expires . ($userIp != null ? $userIp : "") . $parameterData;
        $token = hash('sha256', $hashableBase, true);
        $token = base64_encode($token);
        $token = str_replace(["\n", "+", "/", "="], ["", "-", "_", ""], $token);
        
        $baseUrl = $scheme . "://" . $host;
        
        if ($isDirectory) {
            return $baseUrl . "/bcdn_token=" . $token . $parameterDataUrl . "&expires=" . $expires . $path;
        } else {
            return $baseUrl . $path . "?token=" . $token . $parameterDataUrl . "&expires=" . $expires;
        }
    }
}
?>