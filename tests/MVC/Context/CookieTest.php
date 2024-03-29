<?php

declare(strict_types=1);

namespace Tests\MVC\Context;

use Owl\Context\Cookie;
use Owl\Http\Request;
use Owl\Http\Response;
use PHPUnit\Framework\TestCase;
use Tests\MVC\Mock\Cookie as MockCookie;
use Tests\MVC\Mock\Http\Response as MockResponse;

class CookieTest extends TestCase
{
    protected function setUp(): void
    {
        MockCookie::getInstance()->reset();
    }

    public function testCookieContext()
    {
        $config_list = [
            '明文' => [
                'request' => Request::factory(),
                'response' => new MockResponse(),
                'token' => 'test',
                'sign_salt' => 'fdajkfldsjfldsf',
            ],
            '明文+压缩' => [
                'request' => Request::factory(),
                'response' => new MockResponse(),
                'token' => 'test',
                'sign_salt' => 'fdajkfldsjfldsf',
                'zip' => true,
            ],
        ];

        $cookies = MockCookie::getInstance();
        foreach ($config_list as $msg => $config) {
            $cookies->reset();

            $handler = new Cookie($config);
            $handler->set('test', 'abc 中文');

            $handler = new Cookie(array_merge($config, [
                'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
                'response' => new MockResponse(),
            ]));

            $this->assertEquals('abc 中文', $handler->get('test'), $msg);
        }
    }

    public function testCookieEncryptWithMcrypt()
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0 || !extension_loaded('mcrypt')) {
            $this->markTestSkipped('php版本高于7.1, 或者没有加载mcrypt模块，无法测试cookie mcrypt加密功能');
        }

        $crypt = [
            'ciphers' => [MCRYPT_RIJNDAEL_256, MCRYPT_BLOWFISH, MCRYPT_CAST_256],
            'mode' => [MCRYPT_MODE_ECB, MCRYPT_MODE_CBC, MCRYPT_MODE_CFB, MCRYPT_MODE_OFB, MCRYPT_MODE_NOFB],
        ];

        $config_default = [
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
            'encrypt_extension' => 'mcrypt',
        ];

        foreach ($crypt['ciphers'] as $cipher) {
            foreach ($crypt['mode'] as $mode) {
                $config = array_merge($config_default, [
                    'request' => Request::factory(),
                    'response' => new MockResponse(),
                    'encrypt' => ['uf43jrojfosdf', $cipher, $mode],
                ]);

                MockCookie::getInstance()->reset();

                $handler = new Cookie($config);
                $handler->set('test', 'abc 中文');

                $handler = new Cookie(array_merge($config, [
                    'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
                    'response' => new MockResponse(),
                ]));

                $this->assertEquals('abc 中文', $handler->get('test'), "MCRYPT, cipher: {$cipher} mode: {$mode} 加密解密失败");
            }
        }
    }

    public function testCookieEncryptWithOpenssl()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('没有加载mcrypt模块，无法测试cookie openssl加密功能');
        }

        $config_default = [
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
            'encrypt_extension' => 'openssl',
        ];

        $methods = [
            'aes-128-cbc',
            'aes-192-cbc',
            'aes-256-cbc',
            'aes-128-ctr',
            'aes-192-ctr',
            'aes-256-ctr',
        ];

        foreach ($methods as $method) {
            $config = array_merge($config_default, [
                'request' => Request::factory(),
                'response' => new MockResponse(),
                'encrypt' => ['9302j4lfjdowrj3o', $method],
            ]);

            MockCookie::getInstance()->reset();

            $handler = new Cookie($config);
            $handler->set('test', 'abc 中文');

            $handler = new Cookie(array_merge($config, [
                'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
                'response' => new MockResponse(),
            ]));

            $this->assertEquals('abc 中文', $handler->get('test'), "OPENSSL, method: {$method} 加密解密失败");
        }
    }

    // 数字签名
    public function testCookieContextSign()
    {
        $config = [
            'request' => Request::factory(),
            'response' => new MockResponse(),
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
        ];

        $handler = new Cookie($config);
        $handler->set('test', 'abc');

        $handler = new Cookie(array_merge($config, [
            'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
            'response' => new MockResponse(),
        ]));

        $handler->setConfig('sign_salt', 'r431oj0if31jr3');
        $this->assertNull($handler->get('test'), 'salt没有起作用');

        $cookies_data = $handler->getConfig('response')->getCookies();
        $cookies_data['test'] = '0' . $cookies_data['test'];

        $handler = new Cookie(array_merge($config, [
            'request' => Request::factory(['cookies' => $cookies_data]),
            'response' => new MockResponse(),
        ]));

        $this->assertNull($handler->get('test'), '篡改cookie内容');
    }

    // 从自定义方法内计算sign salt
    public function testCookieContextSignSaltFunc()
    {
        $salt_func = function ($string) {
            $context = json_decode($string, true) ?: [];

            return isset($context['id']) ? $context['id'] : 'rj102jrojfoe';
        };

        $config = [
            'request' => Request::factory(),
            'response' => new MockResponse(),
            'token' => 'test',
            'sign_salt' => $salt_func,
        ];

        $handler = new Cookie($config);

        $id = uniqid();
        $handler->set('id', $id);

        $handler = new Cookie(array_merge($config, [
            'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
            'response' => new MockResponse(),
        ]));

        $this->assertEquals($id, $handler->get('id'), '自定义sign salt没有起作用');
    }

    // 地址绑定
    public function testBindIpCookieContext()
    {
        $config = [
            'request' => Request::factory([
                'ip' => '192.168.1.1',
            ]),
            'response' => new MockResponse(),
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
            'bind_ip' => true,
        ];

        $handler = new Cookie($config);
        $handler->set('test', 'abc');

        $handler = new Cookie(array_merge($config, [
            'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies(), 'ip' => '192.168.1.3']),
            'response' => new MockResponse(),
        ]));

        $this->assertEquals('abc', $handler->get('test'), '同子网IP取值');

        $handler = new Cookie(array_merge($config, [
            'request' => Request::factory(['cookies' => $handler->getConfig('response')->getCookies(), 'ip' => '192.168.2.1']),
            'response' => new Response(),
        ]));
        $this->assertNull($handler->get('test'), '不同子网IP取值');
    }
}
