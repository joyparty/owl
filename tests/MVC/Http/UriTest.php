<?php
namespace Tests\Http;

class UriTest extends \PHPUnit_Framework_TestCase
{
    public function testGetter()
    {
        $this->assertMethods('', [
            'getScheme' => '',
            'getUserInfo' => '',
            'getAuthority' => '',
            'getHost' => '',
            'getPort' => null,
            'getPath' => '/',
            'getQuery' => '',
            'getFragment' => '',
            '__toString' => '/',
        ]);

        $this->assertMethods('http://www.example.com', [
            'getScheme' => 'http',
            'getUserInfo' => '',
            'getAuthority' => 'www.example.com',
            'getHost' => 'www.example.com',
            'getPort' => null,
            'getPath' => '/',
            'getQuery' => '',
            'getFragment' => '',
            '__toString' => 'http://www.example.com/',
        ]);

        $this->assertMethods('http://foo:bar@www.example.com:80/p1?a=b&c=d#f', [
            'getScheme' => 'http',
            'getUserInfo' => 'foo:bar',
            'getAuthority' => 'foo:bar@www.example.com',
            'getHost' => 'www.example.com',
            'getPort' => null,
            'getPath' => '/p1',
            'getQuery' => 'a=b&c=d',
            'getFragment' => 'f',
            '__toString' => 'http://foo:bar@www.example.com/p1?a=b&c=d#f',
        ]);

        $this->assertMethods('http://foo:bar@www.example.com:88/p1?a=b&c=d#f', [
            'getScheme' => 'http',
            'getUserInfo' => 'foo:bar',
            'getAuthority' => 'foo:bar@www.example.com:88',
            'getHost' => 'www.example.com',
            'getPort' => 88,
            'getPath' => '/p1',
            'getQuery' => 'a=b&c=d',
            'getFragment' => 'f',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b&c=d#f',
        ]);

        $this->assertMethods('/p1?a=b&c=d#f', [
            'getScheme' => '',
            'getUserInfo' => '',
            'getAuthority' => '',
            'getHost' => '',
            'getPort' => null,
            'getPath' => '/p1',
            'getQuery' => 'a=b&c=d',
            'getFragment' => 'f',
            '__toString' => '/p1?a=b&c=d#f',
        ]);

        $this->assertMethods('//u:p@example.com/foo', [
            'getScheme' => '',
            'getUserInfo' => 'u:p',
            'getAuthority' => 'u:p@example.com',
            'getHost' => 'example.com',
            'getPort' => null,
            'getPath' => '/foo',
            'getQuery' => '',
            'getFragment' => '',
            '__toString' => '//u:p@example.com/foo',
        ]);
    }

    public function testSetter()
    {
        $uri = new \Owl\Http\Uri('http://foo:bar@www.example.com:88/p1?a=b&c=d#f');

        $this->assertMethods($uri->withScheme('https'), [
            'getScheme' => 'https',
            '__toString' => 'https://foo:bar@www.example.com:88/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withHost('example.com'), [
            'getHost' => 'example.com',
            '__toString' => 'http://foo:bar@example.com:88/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withPort(80), [
            'getPort' => null,
            '__toString' => 'http://foo:bar@www.example.com/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withPath('/p2'), [
            'getPath' => '/p2',
            '__toString' => 'http://foo:bar@www.example.com:88/p2?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withQuery('x=y'), [
            'getQuery' => 'x=y',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?x=y#f',
        ]);

        $this->assertMethods($uri->withQuery(['x' => 'y']), [
            'getQuery' => 'x=y',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?x=y#f',
        ]);

        $this->assertMethods($uri->withQuery(''), [
            'getQuery' => '',
            '__toString' => 'http://foo:bar@www.example.com:88/p1#f',
        ]);

        $this->assertMethods($uri->withQuery([]), [
            'getQuery' => '',
            '__toString' => 'http://foo:bar@www.example.com:88/p1#f',
        ]);

        $this->assertMethods($uri->withFragment('fff'), [
            'getFragment' => 'fff',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b&c=d#fff',
        ]);

        $this->assertMethods($uri->withFragment(''), [
            'getFragment' => '',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b&c=d',
        ]);

        $this->assertMethods($uri->addQuery(['foo' => 'bar']), [
            'getQuery' => 'a=b&c=d&foo=bar',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b&c=d&foo=bar#f',
        ]);

        // test rfc 3986
        $this->assertMethods($uri->withQuery(['foo' => 'b a r', 'f' => 'b a z']), [
            'getQuery' => 'foo=b%20a%20r&f=b%20a%20z',
        ]);
    }

    public function testWithout()
    {
        $uri = new \Owl\Http\Uri('http://foo:bar@www.example.com:88/p1?a=b&c=d#f');

        $this->assertMethods($uri->withoutScheme(), [
            'getScheme' => '',
            '__toString' => '//foo:bar@www.example.com:88/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withoutUserInfo(), [
            'getAuthority' => 'www.example.com:88',
            '__toString' => 'http://www.example.com:88/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withoutHost(), [
            'getHost' => '',
            '__toString' => '/p1?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withoutPath(), [
            'getPath' => '/',
            '__toString' => 'http://foo:bar@www.example.com:88/?a=b&c=d#f',
        ]);

        $this->assertMethods($uri->withoutQuery(), [
            'getQuery' => '',
            '__toString' => 'http://foo:bar@www.example.com:88/p1#f',
        ]);

        $this->assertMethods($uri->withoutQuery(['c']), [
            'getQuery' => 'a=b',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b#f',
        ]);

        $this->assertMethods($uri->withoutQuery('c'), [
            'getQuery' => 'a=b',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b#f',
        ]);

        $this->assertMethods($uri->withoutFragment(), [
            'getFragment' => '',
            '__toString' => 'http://foo:bar@www.example.com:88/p1?a=b&c=d',
        ]);
    }

    private function assertMethods($uri, $asserts)
    {
        if (is_string($uri)) {
            $uri = new \Owl\Http\Uri($uri);
        }

        foreach ($asserts as $method => $value) {
            $this->assertSame($value, $uri->$method(), sprintf('$uri->%s()', $method));
        }
    }
}
