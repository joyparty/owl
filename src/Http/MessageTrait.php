<?php

namespace Owl\Http;

use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    protected $immutability = true;

    protected $protocol_version = '1.1';

    protected $headers = [];

    protected $attributes = [];

    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocol_version;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        $result = $this->immutability ? clone $this : $this;
        $result->protocol_version = $version;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        $name = strtolower($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        $name = strtolower($name);

        if (!$this->hasHeader($name)) {
            return [];
        }

        $value = $this->headers[$name];

        return is_array($value) ? $value : [$value];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        $value = $this->getHeader($name);

        return $value ? implode(',', $value) : '';
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $result = $this->immutability ? clone $this : $this;
        $name = strtolower($name);

        $result->headers[$name] = $value;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        if ($values = $this->getHeader($name)) {
            $values[] = $value;
        } else {
            $values = $value;
        }

        return $this->withHeader($name, $values);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $name = strtolower($name);

        unset($result->headers[$name]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        $result = $this->immutability ? clone $this : $this;
        $result->body = $body;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name])
             ? $this->attributes[$name]
             : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        unset($this->attributes[$name]);

        return $this;
    }
}
