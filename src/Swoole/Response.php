<?php

namespace Owl\Swoole;

use Owl\Http\IteratorStream;
use Owl\Http\Response as BaseResponse;

class Response extends BaseResponse
{
    protected $swoole_response;

    public function __construct($swoole_response)
    {
        $this->swoole_response = $swoole_response;

        parent::__construct();
    }

    protected function send()
    {
        $response = $this->swoole_response;

        $status = $this->getStatusCode();
        if ($status && $status !== 200) {
            $response->status($status);
        }

        foreach ($this->headers as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            $response->header($key, $value);
        }

        foreach ($this->cookies as list($name, $value, $expire, $path, $domain, $secure, $httponly)) {
            $response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }

        $body = $this->getBody();
        if ($body instanceof IteratorStream) {
            foreach ($body->iterator() as $string) {
                $response->write($string);
            }

            $response->end('');
        } else {
            $response->end((string) $body);
        }
    }
}
