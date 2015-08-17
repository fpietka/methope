<?php

namespace Unit\Tests;

use PDO;
use Metope\QueryBuilder;
use PHPUnit_Framework_TestCase;

class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Metope\TypeAlreadySetException
     */
    public function testMultipleSetTypeFail()
    {
        $pdo = new PDO('sqlite:dummy.db');
        (new QueryBuilder($pdo))->select()->update();
    }

    /**
     * @expectedException Metope\NotAllowedMethodException
     */
    public function testValuesOnDeleteFail()
    {
        $pdo = new PDO('sqlite:dummy.db');
        (new QueryBuilder($pdo))->delete()->values([]);
    }

    /**
     * @expectedException Metope\NotAllowedMethodException
     */
    public function testValuesOnSelectFail()
    {
        $pdo = new PDO('sqlite:dummy.db');
        (new QueryBuilder($pdo))->select()->values([]);
    }

    public function testToString()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->assemble();
        $tostring = (new QueryBuilder($pdo))->select('*')->from('foo')->__toString();

        $this->assertEquals($generated, $tostring);
    }

    public function testSimpleSelect()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "SELECT * FROM foo";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT id, bar FROM foo";
        $generated = (new QueryBuilder($pdo))->select(array('id      ', 'bar'))->from('foo')->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testSelectWhere()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "SELECT * FROM foo WHERE bar = 1";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', 1)->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar IS NULL";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', null)->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar = TRUE";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', true)->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar != FALSE";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', false, '!=')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar IS NOT NULL";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', null, '!=')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar IS NOT NULL AND bar != FALSE";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', null, '!=')->where('bar', false, '!=')->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testJoin()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "SELECT * FROM foo INNER JOIN bar ON bar.id = foo.bar_id";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->join('bar', array('id', 'bar_id'))->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo INNER JOIN bar ON bar.id = foo.bar_id WHERE bar.foobar = 'foobar'";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->join('bar', array('id',
            'bar_id'))->where('bar.foobar', 'foobar')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo INNER JOIN bar ON bar.id = foo.bar_id INNER JOIN baz ON baz.bar_id = bar.id WHERE bar.foobar = 'foobar'";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->join('bar', array('id',
            'bar_id'))->join(array('baz', 'bar'), array('bar_id', 'id'))->where('bar.foobar', 'foobar')->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testInsert()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "INSERT INTO foo (name, size) VALUES ('fubar', '10')";
        $values = [
            'name' => 'fubar',
            'size' => 10
        ];
        $generated = (new QueryBuilder($pdo))->insert()->from('foo')->values($values)->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testUpdate()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "UPDATE foo SET name = 'fubar', size = '10'";
        $values = [
            'name' => 'fubar',
            'size' => 10
        ];
        $generated = (new QueryBuilder($pdo))->update()->from('foo')->values($values)->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "UPDATE foo SET name = 'fubar', size = '10' WHERE id = 1";
        $values = [
            'name' => 'fubar',
            'size' => 10
        ];
        $generated = (new QueryBuilder($pdo))->update()->from('foo')->values($values)->where('id', 1)->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testDelete()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "DELETE FROM foo";
        $generated = (new QueryBuilder($pdo))->delete()->from('foo')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "DELETE FROM foo WHERE id = 1";
        $generated = (new QueryBuilder($pdo))->delete()->from('foo')->where('id', 1)->assemble();

        $this->assertEquals($plain, $generated);
    }
}
