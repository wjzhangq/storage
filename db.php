<?php

class db implements ArrayAccess
{
    public $sqls = array();
    public $pdo = null;

    function __construct($key)
    {
        $this->pdo = call_user_func_array(array(new ReflectionClass('PDO'), 'newInstance'), $args);
    }

    public function begin()
    {
        return $this->pdo->beginTransaction();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function exec($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);
        // $this->sqls[] = $sql;
        if ($args)
        {
            return $this->execute($sql, $args)->rowCount();
        }

        return $this->pdo->exec($sql);
    }

    public function execute($sql, $param=array())
    {
        if ($param)
        {
            $sth = $this->pdo->prepare($sql);
            if (!$sth->execute($param))
            {
                throw new Exception("Error sql prepare:$sql");
            }
        }
        else
        {
            if (!$sth = $this->pdo->query($sql))
            {
                throw new Exception("Error sql query:$sql");
            }
        }

        return $sth;
    }

    public function query($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);
        // $this->sqls[] = $sql;
        return $this->execute($sql, $args);
    }

    public function getOne($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);

        if (stripos($sql, 'limit') === false)
        {
            $sql .= ' LIMIT 1';
        }

        $query = $this->execute($sql, $args);

        return $query->fetchColumn();
    }

    public function getCol($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);

        $query = $this->execute($sql, $args);

        return $query->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getAll($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);

        $query = $this->execute($sql, $args);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRow($sql)
    {
        $args = func_get_args();
        $sql = array_shift($args);

        $query = $this->execute($sql, $args);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    public function getSlice($table, $fields=array(), $where=array(), $order=array(),  $limit=0)
    {
        $sql = $this->gen_sql($table, $fields, $where, $order, $limit);
        
        return $this->getAll($sql);
    }
    
    function getSliceRow($table, $fields=array(), $where=array(), $order=array())
    {
        $sql = $this->gen_sql($table, $fields, $where, $order, 1);
        
        return $this->getRow($sql);
    }
    
    function getSliceCol($table, $fields='', $where=array(), $order=array(), $limit=0)
    {
        $fields = $fields ? array(0=>$fields) : array();
        $sql = $this->gen_sql($table, $fields, $where, $order, $limit);
        
        return $this->getCol($sql);
    }
    
    function getSliceOne($table, $fields='', $where=array(), $order=array())
    {
        $fields = $fields ? array(0=>$fields) : array();

        $sql = $this->gen_sql($table, $fields, $where, $order, 1);
         
        return $this->getOne($sql); 
    }
    
    protected function gen_sql($table, $fields=array(), $where=array(), $order=array(),  $limit=0)
    {
        $sql = 'SELECT ';
        if ($fields)
        {
            $sql .= '`'. implode('`, `', $fields) . '`';
        }
        else
        {
            $sql .= ' * ';
        }
        
        $sql .= ' FROM ' . $table;
        
        if ($where)
        {
            $sql .= $this->gen_where($where);
        }
        
        if ($order)
        {
            $set1 = array();
            foreach($order as $key=>$val)
            {
                $set1[] = '`' . $key . '` ' . strtoupper($val);
            }
            $sql .= " ORDER BY " . implode(', ', $set1);
        }
        
        if ($limit)
        {
            if (is_array($limit))
            {
                $sql.=" LIMIT " . intval($limit[0]) . ', ' . intval($limit[1]);
            }
            else
            {
                $sql .=" LIMIT " . intval($limit);
            }
            
        }
        
        return $sql;
    }
    
    function gen_where($where)
    {
        $set = array();
        foreach ($where as $key => $val)
        {
            if (is_array($val))
            {
                $set[] = $key . ' IN (\'' . implode('\', \'', $val) . '\')';
            }
            else
            {
                $set[] = $key . '=\'' . $val . '\'';
            }
        }
        $sql = " WHERE " . implode(' AND ', $set);
        
        return $sql;        
    }
    
    function insert($table, $data)
    {
        $tbl_fields = $this->getfield($table);
        $fields = $values = array();
        foreach($tbl_fields as $v)
        {
            if (isset($data[$v]))
            {
                $fields[] = $v;
                $values[] = $data[$v];
            }
        }
 
        if ($fields)
        {
            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ' ) VALUES ( \'' . implode('\' ,\'', $values) . '\')';

            $this->execute($sql);

            return $this->lastInsertId();
        }
        else
        {
            return false;
        }
    }
    
    function update($table, $data, $where=array())
    {
        $tbl_fields = $this->getfield($table);
        $set = array();
        foreach($tbl_fields as $v)
        {
            if (isset($data[$v]))
            {
                $set[] = $v . '=\'' . $data[$v] . '\'';
            }
        }
        
        if ($set)
        {
            $sql = "UPDATE " . $table . ' SET ' . implode(', ', $set);
            $param = array();
            if ($where)
            {
                $sql .= $this->gen_where($where);               
            }

            return $this->execute($sql)->rowCount();
            
        }
        else
        {
            return false;
        }       
    }
    
    function getCount($table, $where=array())
    {
        $sql = 'SELECT COUNT(*) FROM ' . $table;
        if ($where)
        {
            $sql .= $this->gen_where($where);        
        }

        return $this->getOne($sql);
    }
    
    function delete($table, $where=array())
    {
        $sql = 'DELETE FROM ' . $table;
        if ($where)
        {
            $sql .= $this->gen_where($where);
        }

        return $this->execute($sql)->rowCount();
    }
    
    public function getField($table)
    {
        static $tables = array();
        if (!isset($tables[$table]))
        {
            $sql = "DESC " . $table;

            $tables[$table] = $this->getCol($sql);
        }

        return $tables[$table];
    }
    
    
    //implements
    function offsetExists($offset)
    {
        return isset($this->db['storage'][$offset]);
    }
    
    function offsetGet($offset)
    {
        return $this->db['storage'][$offset];
    }
    
    function offsetSet($offset, $value)
    {
        return $this->db['storage'][$offset] = $value;
    }
    
    function offsetUnset($offset)
    {
        return unset($this->db['storage'][$offset]);
    }
}


class db_table implements ArrayAccess
{
    var $table_name;
    var $table_fileds;
    var $table_key;
    var $db;
    
    function __construct($table_name, &$db)
    {
        $sql = "DESC " . $table_name;
        $this->table_fileds = $db->getCol($sql) or die('Can not found table' . $table_name);
        $this->db= $db;
        $this->table_name = $table_name;
        $sql = "SHOW INDEX FROM " . $table_name . " WHERE Key_name = 'PRIMARY'";
        $tmp = $db->getRow($sql);
        $this->table_key = isset($tmp['Column_name']) ? $tmp['Column_name'] : '';        
    }
    
    function insert($data)
    {
        $fields = $values = array();
        foreach($this->table_fileds as $v)
        {
            if ($this->table_key == $v) continue; //主键过滤
            
            if (isset($data[$v]))
            {
                $fields[] = $v;
                $values[] = $data[$v];
            }
        }
 
        if ($fields)
        {
            $sql = 'INSERT INTO ' . $this->table_name . ' (' . implode(', ', $fields) . ' ) VALUES ( \'' . implode('\' ,\'', $values) . '\')';

            $this->db->execute($sql);
            
            $ret_val = $this->table_key ? $this->db->lastInsertId() : true; //有主键返回主键值

            return $ret_val;
        }
        else
        {
            return false;
        }
    }
    
    
    //implements
    function offsetExists($offset)
    {
        $ret_val = false;
        if ($this->table_key)
        {
            $sql = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE " . $this->table_key . " = '" . $offset . "'";
            
            $ret_val = (bool) $this->db->getOne($sql);
        }
        
        return false;
    }
    
    function offsetGet($offset)
    {
        
        return $this->db['storage'][$offset];
    }
    
    function offsetSet($offset, $value)
    {
        return $this->db['storage'][$offset] = $value;
    }
    
    function offsetUnset($offset)
    {
        return unset($this->db['storage'][$offset]);
    }
}
?>