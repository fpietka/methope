<?php

namespace Unit\Tests;

use PDO;
use Metope\QueryBuilder;
use PHPUnit_Framework_TestCase;

class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
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

        $plain = "SELECT * FROM foo WHERE bar != TRUE";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', true, '!=')->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo WHERE bar IS NOT NULL";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->where('bar', null, '!=')->assemble();

        $this->assertEquals($plain, $generated);
    }

    public function testJoin()
    {
        $pdo = new PDO('sqlite:dummy.db');

        $plain = "SELECT * FROM foo INNER JOIN bar ON foo.bar_id = bar.id";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->join('bar', array('bar_id', 'id'))->assemble();

        $this->assertEquals($plain, $generated);

        $plain = "SELECT * FROM foo INNER JOIN bar ON foo.bar_id = bar.id WHERE bar.foobar = 'foobar'";
        $generated = (new QueryBuilder($pdo))->select('*')->from('foo')->join('bar', array('bar_id',
            'id'))->where('bar.foobar', 'foobar')->assemble();

        $this->assertEquals($plain, $generated);
    }
}
