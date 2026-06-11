<?php
class SimpleR2 {
    private $accessKey;
    private $secretKey;
    private $bucket;
    private $accountId;
    private $customDomain;

    public function __construct($accessKey, $secretKey, $accountId, $bucket, $customDomain) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->accountId = $accountId;
        $this->bucket = $bucket;
        $this->customDomain = $customDomain;
    }

    // Function to calculate AWS Signature (Used for both Upload and Delete)
    private function request($method, $filename, $content = '') {
        $host = "{$this->bucket}.{$this->accountId}.r2.cloudflarestorage.com";
        $endpoint = "https://{$host}/{$filename}";
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        // Headers
        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => hash('sha256', $content),
            'x-amz-date' => $amzDate,
        ];

        // For PUT (Upload), we need Content-Type
        if ($method == 'PUT') {
            $headers['content-type'] = 'image/jpeg';
        }

        ksort($headers);
        $canonicalHeaders = '';
        $signedHeaders = '';
        foreach ($headers as $key => $value) {
            $canonicalHeaders .= $key . ':' . $value . "\n";
            $signedHeaders .= $key . ';';
        }
        $signedHeaders = rtrim($signedHeaders, ';');
        $canonicalRequest = "$method\n/$filename\n\n$canonicalHeaders\n$signedHeaders\n" . hash('sha256', $content);
        
        $credentialScope = "$dateStamp/auto/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);
        
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $httpHeaders = [
            "Authorization: $authorization",
            "x-amz-date: $amzDate",
            "x-amz-content-sha256: " . $headers['x-amz-content-sha256']
        ];
        
        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            $httpHeaders[] = "Content-Type: image/jpeg";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for XAMPP
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            return "Error $httpCode: $response ($err)";
        }
    }

    public function upload($filename, $content) {
        return $this->request('PUT', $filename, $content);
    }

    public function delete($filename) {
        return $this->request('DELETE', $filename);
    }

    public function getUrl($filename) {
        return "https://{$this->customDomain}/{$filename}";
    }
}
?>