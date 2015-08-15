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

    /**
     * PDO connection
     */
    private $conn;

    private $parts = array();

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
            $this->parts['FROM FIELDS'] = $fields;
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
        $this->parts['FROM'] = $table;

        return $this;
    }

    public function join($table, $fields, $operation = '=', $type = 'INNER')
    {
        $join = [
            'table' => $table,
            'operation' => $operation,
            'type' => $type
        ];

        if (is_array($fields)) {
            list($join['left'], $join['right']) = $fields;
        }

        $this->parts['JOIN'][] = $join;

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

        $this->parts['WHERE'][] = [
            'mode' => $mode,
            'field' => $field,
            'operation' => $operation,
            'value' => $value
        ];

        return $this;
    }

    public function values(array $values)
    {
        switch ($this->type) {
            case self::MODE_INSERT:
            case self::MODE_UPDATE:
                $this->parts['VALUES'] = $values;
                break;
            default:
                throw new \Exception('Method not supported for mode ' . $this->type);
                break;
        }

        return $this;
    }

    /**
     * Put SQL together before execution.
     */
    public function assemble()
    {
        $sql = '';

        switch ($this->type) {
            case self::MODE_SELECT:
                $sql .= 'SELECT ';
                break;
            case self::MODE_INSERT:
                $sql .= 'INSERT INTO ';
                break;
            case self::MODE_UPDATE:
                $sql .= 'UPDATE ';
                break;
            case self::MODE_DELETE:
                $sql .= 'DELETE ';
                break;
        }

        if (isset($this->parts['FROM FIELDS'])) {
            if (is_array($this->parts['FROM FIELDS'])) {
                $sql .= implode(', ', array_map('trim', $this->parts['FROM FIELDS'])) . ' ';
            } else {
                $sql .= trim($this->parts['FROM FIELDS']) . ' ';
            }
        }

        switch ($this->type) {
            case self::MODE_SELECT:
            case self::MODE_DELETE:
                $sql .= 'FROM ' . $this->parts['FROM'] . ' ';
                break;
            case self::MODE_INSERT:
            case self::MODE_UPDATE:
                $sql .= $this->parts['FROM'] . ' ';
                break;
        }

        if (!empty($this->parts['JOIN'])) {
            foreach ($this->parts['JOIN'] as $join) {
                if (is_array($join['table'])) {
                    $right_table = array_pop($join['table']);
                    $join['table'] = array_shift($join['table']);
                } else {
                    $right_table = $this->parts['FROM'];
                }
                $sql .= $join['type'] . ' JOIN ' . $join['table'] . ' ON ';
                $sql .= $join['table'] . '.' . $join['left'];
                $sql .= ' ' . $join['operation'] . ' ';
                $sql .= $right_table . '.' . $join['right'] . ' ';
            }
        }

        if (!empty($this->parts['WHERE'])) {
            $sql .= 'WHERE ';
            $sql .= implode(' ', array_slice(array_shift($this->parts['WHERE']), 1)) . ' ';

            foreach ($this->parts['WHERE'] as $where) {
                $sql .= implode(' ', $where) . ' ';
            }
        }

        return trim($sql);
    }
}
