<?php

declare(strict_types=1);

namespace Tests\Core\Parameter;

use PHPUnit\Framework\TestCase;
use Owl\Parameter\Validator;

class ValidatorTest extends TestCase
{
    /** @var Validator */
    private $validator;

    public function testRequired()
    {
        $this->execute(['foo' => 'bar'], ['foo' => ['type' => 'string']]);
        $this->execute([], ['foo' => ['type' => 'string', 'required' => false]]);

        $this->tryExecute(
            [],
            ['foo' => ['type' => 'string']],
            'test "required" rule failed'
        );
    }

    public function testAllowEmpty()
    {
        $this->execute(['foo' => ''], ['foo' => ['allow_empty' => true]]);
        $this->execute(['foo' => ''], ['foo' => ['type' => 'array', 'allow_empty' => true]]);

        $this->tryExecute(
            ['foo' => ''],
            ['foo' => ['allow_empty' => false]],
            'test "allow_empty" rule failed'
        );

        $this->tryExecute(
            ['foo' => []],
            ['foo' => ['type' => 'array', 'allow_empty' => false]],
            'test "allow_empty" rule failed'
        );
    }

    public function testEquals()
    {
        $this->execute(['foo' => 1], ['foo' => ['eq' => '1']]);
        $this->execute(['foo' => 1], ['foo' => ['eq' => 1]]);

        $this->execute(['foo' => ''], ['foo' => ['eq' => '1', 'allow_empty' => true]]);

        $this->tryExecute(
            ['foo' => 'baz'],
            ['foo' => ['eq' => 'bar']],
            'test "eq" rule failed'
        );

        $this->execute(['foo' => 1], ['foo' => ['enum_eq' => ['0', '1']]]);

        $this->tryExecute(
            ['foo' => 2],
            ['foo' => ['enum_eq' => ['0', '1']]],
            'test "enum_eq" rule failed'
        );
    }

    public function testSame()
    {
        $this->execute(['foo' => '1'], ['foo' => ['same' => '1']]);
        $this->execute(['foo' => 1], ['foo' => ['same' => 1]]);

        $this->execute(['foo' => ''], ['foo' => ['same' => '1', 'allow_empty' => true]]);

        $this->tryExecute(
            ['foo' => '1'],
            ['foo' => ['same' => 1]],
            'test "same" rule failed'
        );

        $this->execute(['foo' => 1], ['foo' => ['enum_same' => [0, 1]]]);

        $this->tryExecute(
            ['foo' => 1],
            ['foo' => ['enum_same' => ['0', '1']]],
            'test "enum_same" rule failed'
        );
    }

    public function testRegexp()
    {
        $this->execute(['foo' => 'aab'], ['foo' => ['regexp' => '/^a+b$/']]);

        $this->tryExecute(
            ['foo' => 'abb'],
            ['foo' => ['regexp' => '/^a+b$/']],
            'test "regexp" rule fails'
        );
    }

    public function testInteger()
    {
        $this->execute(['foo' => 1], ['foo' => ['type' => 'integer']]);
        $this->execute(['foo' => '1'], ['foo' => ['type' => 'integer']]);
        $this->execute(['foo' => -1], ['foo' => ['type' => 'integer', 'allow_negative' => true]]);
        $this->execute(['foo' => '-1'], ['foo' => ['type' => 'integer', 'allow_negative' => true]]);

        $this->tryExecute(
            ['foo' => 'bar'],
            ['foo' => ['type' => 'integer']],
            'test "integer" type failed'
        );

        $this->tryExecute(
            ['foo' => 1.1],
            ['foo' => ['type' => 'integer']],
            'test "integer" type failed'
        );

        $this->tryExecute(
            ['foo' => -1],
            ['foo' => ['type' => 'integer', 'allow_negative' => false]],
            'test "allow_negative" rule failed'
        );

        // test type alias
        $this->tryExecute(
            ['foo' => 'aa'],
            ['foo' => ['type' => 'int']],
            'test type alias failed'
        );
    }

    public function testNumeric()
    {
        $this->execute(['foo' => 1.1], ['foo' => ['type' => 'numeric']]);
        $this->execute(['foo' => '1.1'], ['foo' => ['type' => 'numeric']]);
        $this->execute(['foo' => -1.1], ['foo' => ['type' => 'numeric', 'allow_negative' => true]]);
        $this->execute(['foo' => '-1.1'], ['foo' => ['type' => 'numeric', 'allow_negative' => true]]);

        $this->tryExecute(
            ['foo' => 'bar'],
            ['foo' => ['type' => 'numeric']],
            'test "numeric" type failed'
        );

        $this->tryExecute(
            ['foo' => -1.1],
            ['foo' => ['type' => 'numeric', 'allow_negative' => false]],
            'test "allow_negative" rule failed'
        );
    }

    public function testBoolean()
    {
        $this->execute(['foo' => true], ['foo' => ['type' => 'boolean']]);
        $this->execute(['foo' => false], ['foo' => ['type' => 'boolean']]);

        $this->tryExecute(
            ['foo' => 'bar'],
            ['foo' => ['type' => 'boolean']],
            'test "boolean" type failed'
        );
    }

    public function testArray()
    {
        $this->execute(
            [
                'a' => [
                    'b' => 1,
                    'c' => [
                        'd' => 'foo@bar.com',
                    ],
                    'd' => [
                        [
                            'e' => 1,
                        ],
                        [
                            'e' => 2,
                        ],
                    ],
                ],
            ],
            [
                'a' => [
                    'type' => 'array',
                    'keys' => [
                        'b' => ['type' => 'integer'],
                        'c' => [
                            'type' => 'array',
                            'keys' => [
                                'd' => ['type' => 'email'],
                            ],
                        ],
                        'd' => [
                            'type' => 'array',
                            'value' => [
                                'type' => 'array',
                                'keys' => [
                                    'e' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->tryExecute(
            [
                'a' => [
                    'b' => 1,
                ],
            ],
            [
                'a' => [
                    'type' => 'array',
                    'keys' => [
                        'b' => ['type' => 'integer'],
                        'c' => ['type' => 'integer'],
                    ],
                ],
            ],
            'array "keys" rule fails'
        );

        $this->tryExecute(
            [
                'a' => [
                    [
                        'b' => 1,
                    ],
                ],
            ],
            [
                'a' => [
                    'type' => 'array',
                    'value' => [
                        'type' => 'array',
                        'keys' => [
                            'b' => ['type' => 'integer'],
                            'c' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            'array "value" rule fails'
        );

        $this->tryExecute(
            [
                'a' => 1,
            ],
            [
                'a' => [
                    'type' => 'json',
                ],
            ],
            'test "json" fails'
        );

        $this->tryExecute(
            [
                'a' => [1, 2, 3, 'b'],
            ],
            [
                'a' => [
                    'type' => 'array',
                    'value' => ['type' => 'integer'],
                ],
            ],
            'test array "value" fails'
        );
    }

    public function testJson()
    {
        $this->execute(
            [
                'a' => json_encode([
                    'b' => 1,
                    'c' => [
                        'd' => 'foo@bar.com',
                    ],
                    'd' => [
                        [
                            'e' => 1,
                        ],
                        [
                            'e' => 2,
                        ],
                    ],
                ]),
            ],
            [
                'a' => [
                    'type' => 'json',
                    'keys' => [
                        'b' => ['type' => 'integer'],
                        'c' => [
                            'type' => 'array',
                            'keys' => [
                                'd' => ['type' => 'email'],
                            ],
                        ],
                        'd' => [
                            'type' => 'array',
                            'value' => [
                                'type' => 'array',
                                'keys' => [
                                    'e' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->tryExecute(
            [
                'a' => json_encode([
                    'b' => 1,
                ]),
            ],
            [
                'a' => [
                    'type' => 'json',
                    'keys' => [
                        'b' => ['type' => 'integer'],
                        'c' => ['type' => 'integer'],
                    ],
                ],
            ],
            'json "keys" rule fails'
        );

        $this->tryExecute(
            [
                'a' => json_encode([
                    [
                        'b' => 1,
                    ],
                ]),
            ],
            [
                'a' => [
                    'type' => 'json',
                    'value' => [
                        'type' => 'array',
                        'keys' => [
                            'b' => ['type' => 'integer'],
                            'c' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            'json "value" rule fails'
        );
    }

    public function testObject()
    {
        $this->execute(
            [
                'a' => new \stdClass(),
            ],
            [
                'a' => [
                    'type' => 'object',
                    'instanceof' => '\stdClass',
                ],
            ]
        );

        $this->tryExecute(
            [
                'a' => 1,
            ],
            [
                'a' => [
                    'type' => 'object',
                    'instanceof' => '\stdClass',
                ],
            ],
            'test "object" fails'
        );
    }

    public function testURL()
    {
        $options = ['url' => ['type' => 'url']];

        $test = [
            'http://192.168.1.1/',
            'http://192.168.1.1/#',
            'http://192.168.1.1/#foobar',
            'http://192.168.1.1/foo/bar',
            'http://192.168.1.1/foo/bar?',
            'http://192.168.1.1/foo/bar?a=b',
            'http://192.168.1.1/foo/bar?a=b#',
            'http://192.168.1.1/foo/bar?a=b#c/d',
            'http://192.168.1.1:888/foo/bar?a=b#c/d',
            'http://user@www.example.com/foo/bar?a=b#c/d',
            'http://user@www.example.com./foo/bar?a=b#c/d',
            'http://user:pass@www.example.com:80/foo/bar?a=b#c/d',
            'http://user:pass@www.example.com.:80/foo/bar?a=b#c/d',
        ];

        foreach ($test as $value) {
            $this->execute(['url' => $value], $options);
        }
    }

    public function testURI()
    {
        $options = ['foo' => ['type' => 'uri']];

        $test = [
            '/',
            '/?',
            '/foo/bar',
            '/foo/bar?a=b',
            '/foo/bar?a=b#',
            '/foo/bar?a=b#c/d',
        ];

        foreach ($test as $value) {
            $this->execute(['foo' => $value], $options);
        }
    }

    public function testEmail()
    {
        $test = [
            'foobar@example.cn',
            'foobar@example.com',
            'foobar@example.com.cn',
            'foobar@example.online',
            'foo-bar@example.com',
            'foo+bar@example.com',
            'foo_bar@example.com',
        ];

        foreach ($test as $value) {
            $this->execute(['email' => $value], ['email' => ['type' => 'email']]);
        }
    }

    public function testIPV4()
    {
        $this->execute([
            'valid_ip1' => '0.0.0.0',
            'valid_ip2' => '255.199.99.9',
            'valid_ip3' => '255.255.255.255',
        ], [
            'valid_ip1' => ['type' => 'ipv4'],
            'valid_ip2' => ['type' => 'ipv4'],
            'valid_ip3' => ['type' => 'ipv4'],
        ]);

        $this->tryExecute(['invalid_ip' => '0.0.0'], ['invalid_ip' => ['type' => 'ipv4']], 'test invalid IP1 failed');
        $this->tryExecute(['invalid_ip' => '01.9.9.9'], ['invalid_ip' => ['type' => 'ipv4']], 'test invalid IP2 failed');
        $this->tryExecute(['invalid_ip' => '256.255.255.255'], ['invalid_ip' => ['type' => 'ipv4']], 'test invalid IP3 failed');
    }

    public function testAllowTags()
    {
        $this->execute(
            [
                'a' => 'foo bar',
            ],
            [
                'a' => ['type' => 'string'],
            ]
        );

        $this->execute(
            [
                'a' => '<p>test</p>',
            ],
            [
                'a' => ['type' => 'string', 'allow_tags' => true],
            ]
        );

        $this->tryExecute(
            [
                'a' => '<p>test</p>',
            ],
            [
                'a' => ['type' => 'string'],
            ],
            'test "allow_tags" fails'
        );
    }

    protected function setUp(): void
    {
        $this->validator = new \Owl\Parameter\Validator();
    }

    private function tryExecute($values, array $options, $message)
    {
        try {
            $this->validator->execute($values, $options);

            $this->fail($message);
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }
    }

    private function execute(array $values, array $options)
    {
        $result = $this->validator->execute($values, $options);

        $this->assertTrue($result);
    }
}
