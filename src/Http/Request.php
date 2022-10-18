<?php

namespace Owl\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @property array $server
 */
class Request implements ServerRequestInterface
{
    use MessageTrait;

    /** @var callable */
    private $client_ip_extractor = null;

    protected $get;
    protected $post;
    protected $cookies;
    protected $files;
    protected $method;
    protected $uri;
    protected $allow_client_proxy_ip = false;

    public function __construct($get = null, $post = null, $server = null, $cookies = null, $files = null)
    {
        $this->get = null === $get ? $_GET : $get;
        $this->post = null === $post ? $_POST : $post;
        $this->server = null === $server ? $_SERVER : $server;
        $this->cookies = null === $cookies ? $_COOKIE : $cookies;
        $this->files = null === $files ? $_FILES : $files;

        $this->initialize();
    }

    public function __clone()
    {
        $this->method = null;
        $this->uri = null;
    }

    /**
     * @param string|null $key
     *
     * @return mixed|array
     */
    public function get($key = null)
    {
        if (null === $key) {
            return $this->get;
        }

        return $this->get[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return mixed|array
     */
    public function post($key = null)
    {
        if (null === $key) {
            return $this->post;
        }

        return $this->post[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasGet($key)
    {
        return array_key_exists($key, $this->get);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasPost($key)
    {
        return array_key_exists($key, $this->post);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        $result = clone $this;

        $result->server['REQUEST_URI'] = $requestTarget;

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getMethod()
    {
        if (null !== $this->method) {
            return $this->method;
        }

        $method = $this->getOriginalMethod();
        if ('POST' !== $method) {
            return $this->method = $method;
        }

        $override = $this->getHeader('x-http-method-override') ?: $this->post('_method');
        if ($override) {
            if (is_array($override)) {
                $override = array_shift($override);
            }

            $method = $override;
        }

        return $this->method = strtoupper($method);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $method
     *
     * @return self
     */
    public function withMethod($method)
    {
        $result = clone $this;
        $result->method = strtoupper($method);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return Uri
     */
    public function getUri()
    {
        if ($this->uri) {
            return $this->uri;
        }

        $scheme = $this->getServerParam('HTTPS') ? 'https' : 'http';
        $user = $this->getServerParam('PHP_AUTH_USER');
        $password = $this->getServerParam('PHP_AUTH_PW');

        if ($http_host = $this->getServerParam('HTTP_HOST')) {
            if (false === strpos($http_host, ':')) {
                $host = $http_host;
                $port = 0;
            } else {
                list($host, $port) = explode(':', $http_host, 2);
                $port = intval($port);
            }
        } else {
            $host = $this->getServerParam('SERVER_NAME') ?: $this->getServerParam('SERVER_ADDR') ?: '127.0.0.1';
            $port = $this->getServerParam('SERVER_PORT');
        }

        $uri = (new Uri($this->getRequestTarget()))
            ->withScheme($scheme)
            ->withUserInfo($user, $password)
            ->withHost(strtolower($host));

        if ($port) {
            $uri = $uri->withPort($port);
        }

        return $uri;
    }

    /**
     * @inheritDoc
     *
     * @param UriInterface $uri
     * @param bool $preserveHost
     *
     * @throws
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        throw new \Exception('Request::withUri() not implemented');
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * @param string $name
     *
     * @return mixed|false
     */
    public function getServerParam($name)
    {
        $name = strtoupper($name);

        return $this->server[$name] ?? false;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * @param string $name
     *
     * @return mixed|false
     */
    public function getCookieParam($name)
    {
        return $this->cookies[$name] ?? false;
    }

    /**
     * @inheritDoc
     *
     * @param array $cookies
     *
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $result = clone $this;

        $result->cookies = $cookies;

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->get;
    }

    /**
     * @inheritDoc
     *
     * @param array $query
     *
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $result = clone $this;

        $result->get = $query;

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function getUploadedFiles()
    {
        $files = [];

        foreach (self::normalizeUploadedFiles($this->files) as $key => $file) {
            if (isset($file['name'])) {
                $files[$key] = new UploadedFile($file);
            } else {
                foreach ($file as $f) {
                    if ('' === $f['name']) {
                        continue;
                    }

                    $files[$key][] = new UploadedFile($f);
                }
            }
        }

        return $files;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        throw new \Exception('Request::withUploadedFiles() not implemented');
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        $content_type = $this->getHeaderLine('content-type');
        $method = $this->getServerParam('REQUEST_METHOD');

        if ('POST' === $method && (false !== \strpos($content_type, 'application/x-www-form-urlencoded') || false !== \strpos($content_type, 'multipart/form-data'))) {
            return $this->post;
        }

        $body = (string) $this->body;

        if ('' === $body) {
            return null;
        }

        if (false !== \strpos($content_type, 'application/json')) {
            return \Owl\safe_json_decode($body, true);
        }

        return $body;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        throw new \Exception('Request::withParsedBody() not implemented');
    }

    public function allowClientProxyIP()
    {
        $this->allow_client_proxy_ip = true;
    }

    public function disallowClientProxyIP()
    {
        $this->allow_client_proxy_ip = false;
    }

    /**
     * @param callable $func
     *
     * @return void
     */
    public function setClientIpExtractor($func)
    {
        $this->client_ip_extractor = $func;
    }

    /**
     * @return string
     */
    public function getClientIP()
    {
        if ($this->client_ip_extractor) {
            return call_user_func($this->client_ip_extractor, $this, $this->allow_client_proxy_ip);
        }

        if (!$this->allow_client_proxy_ip || !($ip = $this->getServerParam('http_x_forwarded_for'))) {
            return $this->getServerParam('remote_addr');
        }

        if (false === strpos($ip, ',')) {
            return $ip;
        }

        // private ip range, ip2long()
        $private = [
            [0, 50331647],            // 0.0.0.0, 2.255.255.255
            [167772160, 184549375],   // 10.0.0.0, 10.255.255.255
            [2130706432, 2147483647], // 127.0.0.0, 127.255.255.255
            [2851995648, 2852061183], // 169.254.0.0, 169.254.255.255
            [2886729728, 2887778303], // 172.16.0.0, 172.31.255.255
            [3221225984, 3221226239], // 192.0.2.0, 192.0.2.255
            [3232235520, 3232301055], // 192.168.0.0, 192.168.255.255
            [4294967040, 4294967295], // 255.255.255.0 255.255.255.255
        ];

        $ip_set = array_map('trim', explode(',', $ip));

        // 检查是否私有地址，如果不是就直接返回
        foreach ($ip_set as $key => $ip) {
            $long = ip2long($ip);

            if (false === $long) {
                unset($ip_set[$key]);
                continue;
            }

            $is_private = false;

            foreach ($private as $m) {
                list($min, $max) = $m;
                if ($long >= $min && $long <= $max) {
                    $is_private = true;
                    break;
                }
            }

            if (!$is_private) {
                return $ip;
            }
        }

        return array_shift($ip_set) ?: '0.0.0.0';
    }

    public function isGet(): bool
    {
        return 'GET' === $this->getMethod() || 'HEAD' === $this->getMethod();
    }

    public function isPost(): bool
    {
        return 'POST' === $this->getMethod();
    }

    public function isPut(): bool
    {
        return 'PUT' === $this->getMethod();
    }

    public function isDelete(): bool
    {
        return 'DELETE' === $this->getMethod();
    }

    public function isAjax(): bool
    {
        $val = $this->getHeader('x-requested-with');

        return $val && ('xmlhttprequest' === strtolower($val[0]));
    }

    protected function initialize()
    {
        $bodyContent = file_get_contents('php://input');
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $bodyContent);
        fseek($fp, 0);
        $this->body = new ResourceStream($fp);

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $key = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = explode(',', $value);
            }
        }
        $this->headers = $headers;

        $contentType = $this->getHeaderLine('content-type');
        if ($bodyContent &&
            in_array($this->getOriginalMethod(), ['PUT', 'PATCH', 'DELETE']) &&
            strpos($contentType, 'application/x-www-form-urlencoded') !== false
        ) {
            $this->post = array_merge($this->post, self::parseQuery($bodyContent, PHP_QUERY_RFC1738));
        }
    }

    /**
     * 构造http请求对象，供测试使用.
     *
     * @example
     * $request = Request::factory([
     *     'uri' => '/',
     *     'method' => 'post',
     *     'cookies' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'headers' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'get' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'post' => [
     *         $key => $value,
     *         ...
     *     ],
     * ]);
     *
     * @param array $options
     *
     * @return self
     */
    public static function factory(array $options = [])
    {
        $options = array_merge([
            'uri' => '/',
            'method' => 'GET',
            'cookies' => [],
            'headers' => [],
            'get' => [],
            'post' => [],
            'ip' => '',
            '_SERVER' => [],
        ], $options);

        $server = array_change_key_case($options['_SERVER'], CASE_UPPER);
        $server['REQUEST_METHOD'] = strtoupper($options['method']);
        $server['REQUEST_URI'] = $options['uri'];

        if ($options['ip']) {
            $server['REMOTE_ADDR'] = $options['ip'];
        }

        if ($query = parse_url($options['uri'], PHP_URL_QUERY)) {
            parse_str($query, $get);
            $options['get'] = array_merge($get, $options['get']);
        }

        $cookies = $options['cookies'];
        $get = $options['get'];
        $post = $options['post'];

        if ('GET' === $server['REQUEST_METHOD']) {
            $post = [];
        }

        foreach ($options['headers'] as $key => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$key] = $value;
        }

        return new self($get, $post, $server, $cookies);
    }

    private static function normalizeUploadedFiles($files): array
    {
        $result = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $i => $name) {
                    $result[$key][$i]['name'] = $name;
                    $result[$key][$i]['type'] = $file['type'][$i];
                    $result[$key][$i]['tmp_name'] = $file['tmp_name'][$i];
                    $result[$key][$i]['error'] = $file['error'][$i];
                    $result[$key][$i]['size'] = $file['size'][$i];
                }
            } else {
                $result[$key] = $file;
            }
        }

        return $result;
    }

    /**
     * @deprecated
     */
    public function getRequestURI()
    {
        return $this->getRequestTarget();
    }

    /**
     * @deprecated
     */
    public function getRequestPath(): string
    {
        return $this->getUri()->getPath();
    }

    /**
     * @deprecated
     */
    public function getExtension()
    {
        return $this->getUri()->getExtension();
    }

    /**
     * @deprecated
     */
    public function setParameter($key, $value)
    {
        return $this->withAttribute($key, $value);
    }

    /**
     * @deprecated
     */
    public function getParameter($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * @deprecated
     */
    public function getParameters()
    {
        return $this->getAttributes();
    }

    /**
     * @deprecated
     */
    public function getServer($key = null)
    {
        if (null === $key) {
            return $this->getServerParams();
        }

        return $this->getServerParam($key);
    }

    /**
     * @deprecated
     */
    public function getCookie($key)
    {
        return $this->getCookieParam($key);
    }

    /**
     * @deprecated
     */
    public function getCookies()
    {
        return $this->getCookieParams();
    }

    /**
     * @deprecated
     */
    public function getIP()
    {
        return $this->getClientIP();
    }

    private function getOriginalMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    private static function parseQuery(string $data, $urlEncoding = true): array
    {
        $result = [];

        if ($data === '') {
            return $result;
        }

        if ($urlEncoding === true) {
            $decoder = function ($value) {
                return rawurldecode(str_replace('+', ' ', $value));
            };
        } elseif ($urlEncoding === PHP_QUERY_RFC3986) {
            $decoder = 'rawurldecode';
        } elseif ($urlEncoding === PHP_QUERY_RFC1738) {
            $decoder = 'urldecode';
        } else {
            $decoder = function ($str) {
                return $str;
            };
        }

        foreach (explode('&', $data) as $kvp) {
            $parts = explode('=', $kvp, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;
            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

        return $result;
    }
}
