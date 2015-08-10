<?php

namespace Metope;

use PDO;

class QueryBuilder
{
    const MODE_SELECT = 'SELECT';
    const MODE_INSERT = 'INSERT';
    const MODE_UPDATE = 'UPDATE';
    const MODE_DELETE = 'DELETE';

    const EQUALS = '=';
    const DIFFERENT = '<>';

    private $type = null;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Output built query
     */
    public function __toString()
    {
        return $this->assemble();
    }

    private function setType($type)
    {
        if (empty($this->type)) {
            $this->type = $type;
        } else {
            throw new \Exception('Already set');
        }
    }

    public function select($fields = null)
    {
        $this->setType(self::MODE_SELECT);

        if (!empty($fields)) {
            $this->part['FROM FIELDS'] = $fields;
        }

        return $this;
    }

    public function insert()
    {
        $this->setType(self::MODE_INSERT);

        return $this;
    }

    public function update()
    {
        $this->setType(self::MODE_UPDATE);

        return $this;
    }

    public function delete()
    {
        $this->setType(self::MODE_DELETE);

        return $this;
    }

    public function from($table)
    {
        $this->part['FROM'] = $table;

        return $this;
    }

    public function join($table, $fields, $operation = '=', $type = 'INNER')
    {
        $join['table'] = $table;
        $join['operation'] = $operation;
        $join['type'] = $type;

        if (is_array($fields)) {
            list($join['left'], $join['right']) = $fields;
        }

        $this->part['JOIN'][] = $join;

        return $this;
    }

    public function where($field, $value, $operation = self::EQUALS, $additive = true)
    {
        $mode = $additive ? 'AND' : 'OR';

        if ($value === null) {
            $value = 'NULL';
            if ($operation === self::EQUALS) {
                $operation = 'IS';
            } else {
                $operation = 'IS NOT';
            }
        } elseif ($value === true) {
            $value = 'TRUE';
        } elseif ($value === false) {
            $value = 'FALSE';
        } elseif (!is_numeric($value)) {
            $value = $this->conn->quote($value);
        }

        $this->part['WHERE'] = [[
            'mode' => $mode,
            'field' => $field,
            'operation' => $operation,
            'value' => $value
        ]];

        return $this;
    }

    /**
     * Put SQL together before execution.
     */
    public function assemble()
    {
        switch ($this->type) {
            case self::MODE_SELECT:
                $sql = 'SELECT ';
                break;
            case self::MODE_INSERT:
                $sql = 'INSERT INTO ';
                break;
            case self::MODE_UPDATE:
                $sql = 'UPDATE ';
                break;
            case self::MODE_DELETE:
                $sql = 'DELETE ';
                break;
        }

        if (is_array($this->part['FROM FIELDS'])) {
            $sql .= implode(', ', array_map('trim', $this->part['FROM FIELDS'])) . ' ';
        } else {
            $sql .= trim($this->part['FROM FIELDS']) . ' ';
        }

        $sql .= 'FROM ' . $this->part['FROM'] . ' ';

        if (!empty($this->part['JOIN'])) {
            foreach ($this->part['JOIN'] as $join) {
                $sql .= $join['type'] . ' JOIN ' . $join['table'] . ' ON ';
                $sql .= $this->part['FROM'] . '.' . $join['left'];
                $sql .= ' ' . $join['operation'] . ' ';
                $sql .= $join['table'] . '.' . $join['right'] . ' ';
            }
        }

        if (!empty($this->part['WHERE'])) {
            $sql .= 'WHERE ';
            $sql .= implode(' ', array_slice(array_shift($this->part['WHERE']), 1)) . ' ';

            foreach ($this->part['WHERE'] as $where) {
                $sql .= implode(' ', $where) . ' ';
            }
        }

        return trim($sql);
    }
}
