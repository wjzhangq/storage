<?php
/**
 * 文件存储类
 * @author wjzhangq <wjzhangq@126.com>
 */

class storage implements ArrayAccess
{
    const MAX_SIZE = 1000000; //1M, 请 SHOW VARIABLES LIKE 'max_allowed_packet'  参看是否超过mysql 默认配置
    //const MAX_SIZE = 20;
    var $db = null;
    
    /**
     * 构造函数
     *
     */
    function __construct(&$db)
    {
        $this->db = $db;
    }
    
    /**
     * 将指定文件存储到数据库
     * @param $path     string    存储文件路径
     * @param $filename string    文件原始名称
     * 
     * @return $id
     */
    function store($path, $filename='')
    {
        
        //检查是否是文件
        if (!is_file($path))
        {
            throw(new Exception('the target is not a file(' . $path . ')'));
        }

        strlen($filename) > 0  or $filename = basename($path);

        //获取信息
        $info = array(
            'filename'=> $filename,
            'filetype'=> $this->filetype($filename),
            'size' => filesize($path),
            'hash' => md5_file($path),
            'ctime' => time(),
            'mtime' => time(),
            'cite' => 1,
            'clip' => 1,
        );
        $info['clip'] = ceil($info['size'] / self::MAX_SIZE); //修正clip

        //
        //检查是否上传过
        $post_info = $this[array('size'=>$info['size'], 'hash'=>$info['hash'])];
        
        if ($post_info)
        {
            $id = $post_info['id'];
            //已经上传过, 更新引用次数以及更新时间
            $this[$id] = array('cite'=>$post_info['cite'] + 1, 'mtime'=>time());
        }
        else
        {
            $id = $this->db['storage']->insert($info);
            //保存文件
            $this->store_data($path, $id, $info['clip']);

        }
        
        return $id;
    }
    
    function restore($id, $path)
    {
        $info = $this[$id];
        if (!$info)
        {
            throw (new Exception('file "' . $id . '" is not exist!'));
        }
        
        if (is_file($path))
        {
            $hash = md5_file($path);
            if ($info['hash'] == $hash)
            {
                return true;
            }
            else
            {
                @unlink($path);
            }
        }
        
        $fp = fopen($path, 'w');
        if (!$fp)
        {
            throw(new Exception('open file fail!'));
        }
        
        for ($i=0; $i < $info['clip']; $i++)
        {
            $sql = 'SELECT data FROM `storage_data` WHERE id=\'' . $id . '\' AND pos = \'' . $i . '\'';
            $data = $this->db->getOne($sql);
            fwrite($fp, $data);
            unset($data); 
        }
        fclose($fp);
        return true;
    }
    
    /**
     * 将文件上传到storage_data 表
     * @param $path     string    存储文件路径
     * @param $info     array    文件原始名称
     * 
     * 
     */
    private function store_data($path, $id, $clip)
    {   
        $fp = fopen($path, 'r');
        if (!$fp)
        {
            throw(new Exception('open file fail!'));
        };
        
        $sql = 'INSERT INTO `storage_data` SET id=' . $id . ', pos = ? , data = ?';
        $sth = $this->db->prepare($sql);
        for ($i=0; $i < $clip; $i++)
        {
            //$sql = 'INSERT INTO `storage_data` SET id=' . $id . ', pos = ' . $i . ', data=\'' . addslashes(fread($fp, self::MAX_SIZE)) . '\'';
            if (!$sth->execute(array(0=>$i, 1=>fread($fp, self::MAX_SIZE))))
            {
                //回滚数据
                $this->drop($id, true);
                $error = $sth->errorInfo();
                throw (new Exception($error[2]));
            }
        }
        fclose($fp);
    }
    
    
    //implements
    function offsetExists($offset)
    {
        return $this->db['storage'][$offset];
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
        //伪删除
        $this->drop($offset,false);
    }
    
    //private
    private function filetype($str)
    {
        return end(explode('.',$str));
    }
    
    function drop($id, $is_hard=false)
    {
        if ($is_hard)
        {
            //物理删除
            unset($this->db['storage'][$id]);
            unset($this->db['storage_data'][$id]);
        }
        else
        {
            //伪删除
            $info = $this[$id];
            $this[$id] = array('mtime'=>time(), 'cite'=> $info['cite'] > 1 ? $info['cite'] -1 : 0);
        }
    }
}

?>