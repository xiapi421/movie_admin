<?php
namespace ba;

use think\facade\Log;

class Bce
{
    protected $config;
    protected $accessKeyId;
    protected $secretAccessKey;
    protected $endpoint;
    protected $protocol;

    /**
     * 构造函数，初始化配置
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        $this->accessKeyId = $config['accessKeyId'] ?? '';
        $this->secretAccessKey = $config['secretAccessKey'] ?? '';
        $this->endpoint = $config['endpoint'] ?? 'bj.bcebos.com';
        $this->protocol = $config['protocol'] ?? 'http';
    }

    /**
     * 创建存储桶
     * @param string $bucketName 存储桶名称
     * @return array 返回结果
     */
    public function createBucket($bucketName)
    {
        try {
            $url = "{$this->protocol}://{$bucketName}.{$this->endpoint}";
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $headers = [
                'Host' => $bucketName.'.'.$this->endpoint,
                'x-bce-date' => $timestamp,
            ];
            ksort($headers);
            $keys = array_keys($headers);
            //将keys全部小写
            $keys = array_map('strtolower', $keys);
            $canonicalHeaders = implode(';', $keys);

            $signature = $this->generateSignature('PUT', "/", $headers, [], $timestamp);
            $headers['Authorization'] = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800/{$canonicalHeaders}/{$signature}";
            
            $response = $this->sendRequest('PUT', $url, $headers);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '存储桶创建失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 设置存储桶访问权限
     * @param string $bucketName 存储桶名称
     * @param string $acl 访问权限，可选值：private、public-read、public-read-write
     * @return array 返回结果
     */
    public function setBucketAcl($bucketName, $acl = 'private')
    {
        try {
            $url = "{$this->protocol}://{$bucketName}.{$this->endpoint}/?acl";
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $headers = [
                'host' => $bucketName.'.'.$this->endpoint,
                'x-bce-date' => $timestamp,
                'x-bce-acl' => $acl,
            ];
            
            $signature = $this->generateSignature('PUT', "/", $headers, ['acl' => ''], $timestamp);

            //$signedHeaders = implode(';', array_keys(array_change_key_case($headers, CASE_LOWER)));
            $canonicalHeaders = '';
            
            // 规范化头信息
            ksort($headers);
            $keys = array_keys($headers);
            $canonicalHeaders = implode(';', $keys);
            

            $headers['Authorization'] = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800/{$canonicalHeaders}/{$signature}";
            // Log::info('Authorization: ' . $headers['Authorization']);
            $response = $this->sendRequest('PUT', $url, $headers);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '存储桶权限设置失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 上传文件到存储桶
     * @param string $bucketName 存储桶名称
     * @param string $objectKey 对象键（文件路径）
     * @param string $filePath 本地文件路径
     * @param array $options 上传选项
     * @return array 返回结果
     */
    public function uploadFile($bucketName, $objectKey, $filePath, $options = [])
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('文件不存在');
            }
            
            $fileContent = file_get_contents($filePath);
            $contentType = $options['contentType'] ?? mime_content_type($filePath);
            $contentMd5 = $options['contentMd5'] ?? base64_encode(md5($fileContent, true));
            $contentLength = $options['contentLength'] ?? filesize($filePath);
            
            $url = "{$this->protocol}://{$bucketName}.{$this->endpoint}/{$objectKey}";
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            
            $headers = [
                'Host' => $bucketName.'.'.$this->endpoint,
                'Content-Type' => 'text/HTML',
                'Content-Length' => $contentLength,
                'Content-MD5' => $contentMd5,
                'x-bce-date' => $timestamp,
                // 'x-bce-meta-content-type' => 'text/HTML',
            ];
            
            // 设置文件访问权限
            if (isset($options['acl'])) {
                $headers['x-bce-acl'] = $options['acl'];
            }
            
            // 设置元数据
            if (isset($options['metadata']) && is_array($options['metadata'])) {
                foreach ($options['metadata'] as $key => $value) {
                    $headers["x-bce-meta-{$key}"] = $value;
                }
            }
            ksort($headers);
            $keys = array_keys($headers);
            //将keys全部小写
            $keys = array_map('strtolower', $keys);
            $canonicalHeaders = implode(';', $keys);
            $signature = $this->generateSignature('PUT', "/{$objectKey}", $headers, [], $timestamp);
            $headers['Authorization'] = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800/{$canonicalHeaders}/{$signature}";
            
            $response = $this->sendRequest('PUT', $url, $headers, $fileContent);
            
            // 获取文件URL
            $fileUrl = $this->getFileUrl($bucketName, $objectKey);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '文件上传失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取文件URL
     * @param string $bucketName 存储桶名称
     * @param string $objectKey 对象键（文件路径）
     * @param int $expireInSeconds URL有效期（秒）
     * @return string 文件URL
     */
    public function getFileUrl($bucketName, $objectKey, $expireInSeconds = 3600)
    {
        try {
            $timestamp = time();
            $expiration = $timestamp + $expireInSeconds;
            $expirationTime = gmdate('Y-m-d\TH:i:s\Z', $expiration);
            
            $canonicalUri = "/{$bucketName}/{$objectKey}";
            $canonicalQueryString = "authorization={$this->accessKeyId}&x-bce-date=" . urlencode(gmdate('Y-m-d\TH:i:s\Z', $timestamp)) . "&expires=" . $expireInSeconds;
            
            $signature = $this->generateSignature('GET', $canonicalUri, [], ['authorization' => $this->accessKeyId, 'x-bce-date' => gmdate('Y-m-d\TH:i:s\Z', $timestamp), 'expires' => $expireInSeconds], gmdate('Y-m-d\TH:i:s\Z', $timestamp));
            
            return "{$this->protocol}://{$this->endpoint}{$canonicalUri}?{$canonicalQueryString}&signature=" . urlencode($signature);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 删除文件
     * @param string $bucketName 存储桶名称
     * @param string $objectKey 对象键（文件路径）
     * @return array 返回结果
     */
    public function deleteFile($bucketName, $objectKey)
    {
        try {
            $url = "{$this->protocol}://{$bucketName}.{$this->endpoint}/{$objectKey}";
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $headers = [
                'Host' => $bucketName.'.'.$this->endpoint,
                'x-bce-date' => $timestamp,
            ];
            ksort($headers);
            $keys = array_keys($headers);
            //将keys全部小写
            $keys = array_map('strtolower', $keys);
            $canonicalHeaders = implode(';', $keys);
            $signature = $this->generateSignature('DELETE', "/{$objectKey}", $headers, [], $timestamp);
            $headers['Authorization'] = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800/{$canonicalHeaders}/{$signature}";
            
            $response = $this->sendRequest('DELETE', $url, $headers);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '文件删除失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 删除存储桶
     * @param string $bucketName 存储桶名称
     * @return array 返回结果
     */
    public function deleteBucket($bucketName)
    {
        try {
            $url = "{$this->protocol}://{$bucketName}.{$this->endpoint}";
            $timestamp = gmdate('Y-m-d\TH:i:s\Z');
            $headers = [
                'host' => $bucketName.'.'.$this->endpoint,
                'x-bce-date' => $timestamp,
            ];
            
            // 规范化头信息
            ksort($headers);
            $keys = array_keys($headers);
            $canonicalHeaders = implode(';', $keys);
            
            // 生成签名
            $signature = $this->generateSignature('DELETE', "/", $headers, [], $timestamp);
            $headers['Authorization'] = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800/{$canonicalHeaders}/{$signature}";
            
            // 发送请求
            $response = $this->sendRequest('DELETE', $url, $headers);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '存储桶删除失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 生成签名
     * @param string $httpMethod HTTP方法
     * @param string $canonicalUri 规范URI
     * @param array $headers 请求头
     * @param array $params 查询参数
     * @param string $timestamp 时间戳
     * @return string 签名
     */
    protected function generateSignature($httpMethod, $canonicalUri, $headers = [], $params = [], $timestamp = null)
    {
        // 1. 获取需要签名的头域
        $signedHeaders = [];
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, ['host', 'content-md5', 'content-length', 'content-type']) 
                || strpos($lowerKey, 'x-bce-') === 0) {
                if (trim($value) !== '') {
                    $signedHeaders[] = $lowerKey;
                }
            }
        }
        sort($signedHeaders);
        
        // 1. 生成规范请求字符串
        $canonicalRequest = $this->getCanonicalRequest($httpMethod, $canonicalUri, $headers, $params);
        // Log::info('规范请求字符串: ' . $canonicalRequest);

        // 2. 使用SK对规范请求字符串进行签名
        $timestamp = $timestamp ?: gmdate('Y-m-d\TH:i:s\Z');
        $authStringPrefix = "bce-auth-v1/{$this->accessKeyId}/{$timestamp}/1800";
        // Log::info('认证字符串前缀: ' . $authStringPrefix);

        // 3. 生成派生密钥
        $signingKey = hash_hmac('sha256', $authStringPrefix, $this->secretAccessKey);
        // Log::info('派生密钥: ' . $signingKey);

        // 4. 生成签名摘要
        $signature = hash_hmac('sha256', $canonicalRequest, $signingKey);
        // Log::info('签名摘要: ' . $signature);
        
        return $signature;
    }

    /**
     * 获取规范请求字符串
     * @param string $httpMethod HTTP方法
     * @param string $canonicalUri 规范URI
     * @param array $headers 请求头
     * @param array $params 查询参数
     * @return string 规范请求字符串
     */
    protected function getCanonicalRequest($httpMethod, $canonicalUri, $headers = [], $params = [])
    {
        // 1. HTTP Method
        $canonicalRequest = strtoupper($httpMethod) . "\n";
        
        // 2. CanonicalURI - 需要进行 URL 编码，但保留斜杠
        $encodedUri = str_replace('%2F', '/', rawurlencode($canonicalUri));
        $canonicalRequest .= $encodedUri . "\n";
        
        // 3. CanonicalQueryString
        $canonicalQueryString = '';
        if (!empty($params)) {
            $queryParts = [];
            // 忽略 authorization 参数
            unset($params['authorization']);
            
            // 对参数进行编码和排序
            ksort($params);
            foreach ($params as $key => $value) {
                $encodedKey = $this->uriEncode($key);
                if ($value === '') {
                    $queryParts[] = $encodedKey . '=';
                } else {
                    $encodedValue = $this->uriEncode($value);
                    $queryParts[] = $encodedKey . '=' . $encodedValue;
                }
            }
            $canonicalQueryString = implode('&', $queryParts);
        }
        $canonicalRequest .= $canonicalQueryString . "\n";
        
        // 4. CanonicalHeaders
        $canonicalHeaders = '';
        $signedHeaders = [];
        
        // 规范化头信息
        ksort($headers);
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            // 选择需要签名的头域，排除 x-bce-date
            if (
                (in_array($lowerKey, ['host', 'content-md5', 'content-length', 'content-type']) 
                || strpos($lowerKey, 'x-bce-') === 0)) {
                $value = trim($value);
                if ($value !== '') {
                    // 对头部值进行编码
                    $encodedValue = $this->uriEncode($value);
                    $canonicalHeaders .= $lowerKey . ':' . $encodedValue;
                    if ($key !== array_key_last($headers)) {
                        $canonicalHeaders .= "\n";
                    }
                    $signedHeaders[] = $lowerKey;
                }
            }
        }
        
        // 添加规范头信息
        $canonicalRequest .= $canonicalHeaders;
        
        return $canonicalRequest;
    }

    /**
     * URI编码
     * @param string $input 输入字符串
     * @return string 编码后的字符串
     */
    protected function uriEncode($input)
    {
        return rawurlencode($input);
    }

    /**
     * URI编码（保留斜杠）
     * @param string $input 输入字符串
     * @return string 编码后的字符串
     */
    protected function uriEncodeExceptSlash($input)
    {
        return str_replace('%2F', '/', $this->uriEncode($input));
    }

    /**
     * 发送HTTP请求
     * @param string $method 请求方法
     * @param string $url 请求URL
     * @param array $headers 请求头
     * @param string $body 请求体
     * @return array 响应结果
     */
    protected function sendRequest($method, $url, $headers = [], $body = null)
    {
        // 记录请求信息
        // Log::info('Request URL: ' . $url);
        // Log::info('Request Method: ' . $method);
        // Log::info('Request Headers: ' . json_encode($headers, JSON_UNESCAPED_UNICODE));
        if ($body !== null) {
            // Log::info('Request Body: ' . $body);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // 设置请求头
        $headerArray = [];
        foreach ($headers as $key => $value) {
            $headerArray[] = "{$key}: {$value}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        
        // 设置请求体
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        
        // 记录响应信息
        // Log::info('Response Code: ' . $httpCode);
        // Log::info('Response Headers: ' . $responseHeaders);
        // Log::info('Response Body: ' . $responseBody);

        // 如果有错误，记录错误信息
        if (curl_errno($ch)) {
            // Log::error('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'headers' => $this->parseHeaders($responseHeaders),
            'body' => $responseBody
        ];
    }

    /**
     * 解析响应头
     * @param string $headerStr 响应头字符串
     * @return array 解析后的响应头
     */
    protected function parseHeaders($headerStr)
    {
        $headers = [];
        $headerLines = explode("\r\n", $headerStr);
        
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        return $headers;
    }
}
