<?php

namespace Owl\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    public static $standard_port = [
        'ftp' => 21,
        'ssh' => 22,
        'smtp' => 25,
        'http' => 80,
        'pop3' => 110,
        'https' => 443,
    ];

    protected $scheme;
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $path;
    protected $query;
    protected $fragment;

    public function __construct($uri = '')
    {
        $parsed = array_merge([
            'scheme' => '',
            'host' => '',
            'port' => null,
            'user' => '',
            'pass' => '',
            'path' => '',
            'query' => '',
            'fragment' => '',
        ], parse_url($uri));

        parse_str($parsed['query'], $parsed['query']);

        foreach ($parsed as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        if (!$authority = $this->getHost()) {
            return '';
        }

        if ($user_info = $this->getUserInfo()) {
            $authority = $user_info . '@' . $authority;
        }

        if ($port = $this->getPort()) {
            $authority = $authority . ':' . $port;
        }

        return $authority;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        $user_info = $this->user;

        if ($user_info !== '' && $this->pass) {
            $user_info .= ':' . $this->pass;
        }

        return $user_info;
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        $port = $this->port;
        if ($port === null) {
            return;
        }

        $scheme = $this->getScheme();

        if (isset(self::$standard_port[$scheme]) && $port === self::$standard_port[$scheme]) {
            return null;
        }

        return $port;
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->path ?: '/';
    }

    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        $query = '';

        if ($this->query) {
            $query = http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        $uri = clone $this;
        $uri->scheme = $scheme;

        return $uri;
    }

    public function withoutScheme()
    {
        return $this->withScheme('');
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->user = $user;
        $uri->pass = $password;

        return $uri;
    }

    public function withoutUserInfo()
    {
        return $this->withUserInfo('', '');
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        $uri = clone $this;
        $uri->host = $host;

        return $uri;
    }

    public function withoutHost()
    {
        return $this->withHost('');
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        $uri = clone $this;
        $uri->port = ($port === null ? null : (int) $port);

        return $uri;
    }

    public function withoutPort()
    {
        return $this->withPort(null);
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        $uri = clone $this;
        $uri->path = $path;

        return $uri;
    }

    public function withoutPath()
    {
        return $this->withPath('');
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        if (is_string($query)) {
            parse_str($query, $query);
        }

        $uri = clone $this;
        $uri->query = $query ?: [];

        return $uri;
    }

    public function addQuery(array $query)
    {
        $query = array_merge($this->query, $query);

        $uri = clone $this;
        $uri->query = $query;

        return $uri;
    }

    /**
     * @example
     * $uri->withoutQuery();                // without all
     * $uri->withoutQuery(['foo', 'bar']);
     * $uri->withoutQuery('foo', 'bar');
     *
     * @param array|mixed $keys
     *
     * @return self
     */
    public function withoutQuery($keys = null)
    {
        $query = $this->query;

        if (!$keys) {
            $query = [];
        } else {
            $keys = is_array($keys)
            ? $keys
            : func_get_args();

            foreach ($keys as $key) {
                unset($query[$key]);
            }
        }

        $uri = clone $this;
        $uri->query = $query;

        return $uri;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException('Invalid URI fragment');
        }

        $uri = clone $this;
        $uri->fragment = $fragment;

        return $uri;
    }

    public function withoutFragment()
    {
        return $this->withFragment('');
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $uri = '';

        if ($scheme = $this->getScheme()) {
            $uri = $scheme . ':';
        }

        if ($authority = $this->getAuthority()) {
            $uri .= '//' . $authority;
        } else {
            $uri = '';
        }

        $uri .= $this->getPath();

        if ($query = $this->getQuery()) {
            $uri .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }
}
