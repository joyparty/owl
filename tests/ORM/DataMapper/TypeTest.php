<?php

declare(strict_types=1);

namespace Tests\ORM\DataMapper;

use Owl\DataMapper\Type;
use Owl\DataMapper\Type\Common;
use Owl\DataMapper\Type\Complex;
use Owl\DataMapper\Type\Datetime;
use Owl\DataMapper\Type\Integer;
use Owl\DataMapper\Type\JSON;
use Owl\DataMapper\Type\Number;
use Owl\DataMapper\Type\Text;
use Owl\DataMapper\Type\UUID;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testNormalizeAttribute()
    {
        $attribute = Type::normalizeAttribute(['primary_key' => true]);
        $this->assertFalse($attribute['allow_null']);
        $this->assertTrue($attribute['refuse_update']);
        $this->assertTrue($attribute['strict']);

        $attribute = Type::normalizeAttribute(['protected' => true]);
        $this->assertTrue($attribute['strict']);

        $attribute = Type::normalizeAttribute(['default' => 'foo', 'allow_null' => true]);
        $this->assertNull($attribute['default']);

        $attribute = Type::normalizeAttribute(['pattern' => '/\d+/']);
        $this->assertTrue(isset($attribute['regexp']));
        $this->assertFalse(isset($attribute['pattern']));
    }

    public function testCommon()
    {
        $type = $this->getType(null);
        $this->assertInstanceOf(Common::class, $type);

        $type = $this->getType('undefined type name');
        $this->assertInstanceOf(Common::class, $type);

        $attribute = ['foo' => 'bar'];
        $this->assertSame($attribute, $type->normalizeAttribute($attribute));
        $this->assertSame('foo', $type->normalize('foo', []));
        $this->assertSame('foo', $type->store('foo', []));
        $this->assertSame('foo', $type->restore('foo', []));
        $this->assertSame('foo', $type->toJSON('foo', []));

        $this->assertSame('foo', $type->getDefaultValue(['default' => 'foo']));
    }

    public function testNumber()
    {
        $type = $this->getType('number');
        $this->assertInstanceOf(Number::class, $type);
        $this->assertInstanceOf(Common::class, $type);

        $this->assertSame(1.11, $type->normalize('1.11', []));

        $this->assertInstanceOf(Number::class, $this->getType('numeric'));
    }

    public function testInteger()
    {
        $type = $this->getType('integer');
        $this->assertInstanceOf(Integer::class, $type);
        $this->assertInstanceOf(Common::class, $type);

        $this->assertSame(1, $type->normalize('1.11', []));
    }

    public function testString()
    {
        $type = $this->getType('string');
        $this->assertInstanceOf(Text::class, $type);
        $this->assertInstanceOf(Common::class, $type);

        $this->assertSame('1.11', $type->normalize(1.11, []));
    }

    public function testUUID()
    {
        $type = $this->getType('uuid');
        $this->assertInstanceOf(UUID::class, $type);
        $this->assertInstanceOf(Common::class, $type);

        $attribute = $type->normalizeAttribute(['primary_key' => true]);
        $this->assertTrue($attribute['auto_generate']);

        $re = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/';
        $this->assertRegExp($re . 'i', $type->getDefaultValue(['auto_generate' => true]));
        $this->assertRegExp($re, $type->getDefaultValue(['auto_generate' => true, 'upper' => true]));
    }

    public function testDateTime()
    {
        $type = $this->getType('datetime');
        $this->assertInstanceOf(Datetime::class, $type);
        $this->assertInstanceOf(Common::class, $type);

        $now = new \DateTimeImmutable();
        $this->assertSame($now, $type->normalize($now, []));

        $this->assertInstanceOf(\DatetimeInterface::class, $type->normalize('now', []));

        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2}[+\-]\d{1,2}(?::\d{1,2})?$/', $type->store($now, []));
        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $type->store($now, ['format' => 'Y-m-d']));
        $this->assertRegExp('/^\d+$/', $type->store($now, ['format' => 'U']));

        $this->assertInstanceOf(\DateTimeImmutable::class, $type->restore('2014-01-01T00:00:00+0', []));

        $ts = 1388534400;
        $time = $type->restore($ts, ['format' => 'U']);

        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals($ts, $time->getTimestamp());

        $this->expectException(\UnexpectedValueException::class);
        $type->normalize($ts, ['format' => 'c']);
    }

    public function testJSON()
    {
        $type = $this->getType('json');
        $this->assertInstanceOf(JSON::class, $type);
        $this->assertInstanceOf(Complex::class, $type);

        $json = ['foo' => 'bar'];
        $this->assertEquals($json, $type->normalize($json, []));
        $this->assertEquals($json, $type->normalize(json_encode($json), []));

        $this->assertNull($type->store([], []));
        $this->assertEquals(json_encode($json), $type->store($json, []));

        $this->expectException(\UnexpectedValueException::class);
        $type->restore('{"a"', []);

        $this->assertSame([], $type->getDefaultValue([]));
        $this->assertSame([], $type->getDefaultValue(['allow_null' => true]));
    }

    public function testComplexValuesTrim()
    {
        $type = $this->getType('json');

        $values = ['foo' => null, 'bar' => 1];
        $this->assertSame('{"bar":1}', $type->store($values, ['trim_values' => true]));
        $this->assertSame('{"foo":null,"bar":1}', $type->store($values, ['trim_values' => false]));
    }

    public function testRestoreNull()
    {
        $expect = [
            'mixed' => null,
            'string' => null,
            'integer' => null,
            'numerci' => null,
            'uuid' => null,
            'datetime' => null,
            'json' => [],
            'pg_array' => [],
            'pg_hstore' => [],
        ];

        foreach ($expect as $type => $value) {
            $this->assertSame($value, $this->getType($type)->restore(null, []));
        }
    }

    protected function getType($name)
    {
        return Type::getInstance()->get($name);
    }
}
