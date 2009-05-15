<?php
/**
 * 文件存储类
 * @author wjzhangq <wjzhangq@126.com>
 */

class storage
{
    constant MAX_SIZE = 2097152; //2M
    var $db = null;
    
    /**
     * 构造函数
     *
     */
    function __constuct()
    {
        $this->db = init_db();
    }
    
    /**
     * 将指定文件存储到数据库
     * @param $path     string    存储文件路径
     * @param $filename string    文件原始名称
     * 
     * @return $key
     */
    function store($path, $filename='')
    {
        
        //检查是否是文件
        is_file($path) or throw(new Exception('the target is not a file(' . $path . ')'));
        strlen($filename) > 0  or $filename = basename($path);
        
        //获取信息
        $info = array(
            'filename'=> $filename,
            'filetype'=> $this->filetype($filename),
            'size' => filesize($path),
            'hash' => md5_file($path),
            'id' => 0,
            'clip' => 1,
        );
        $info['clip'] = ceil($info['clip'] / self::MAX_SIZE); //修正clip
        
        //检查是否上传过
        $id = $this->exists_by_info($info);
        
        if ($id)
        {
            //引用次数+1
            $this[$id]['num'] ++;
            $info['id'] = $id; //修正id
            $key = $this->gen_key($info);
        }
        else
        {
            //将附加信息插入storage表
            $this->db['storage'][] = $info;
            $id = key($this->db['storage']);
            
            $info['id'] = $id; //修正id
            $key = $this->gen_key($info);
            
            //保存数据
            $this->store_data($path, $key);
        }
        
        return $key;
    }
    
    /**
     * 将文件上传到storage_data 表
     * @param $path     string    存储文件路径
     * @param $info     array    文件原始名称
     * 
     * @return $key
     */
    private function store_data($path, $key)
    {
        $target_path = $this->gen_path($key);
        
        if (is_uploaded_file($path))
        {
            move_uploaded_file($path, $target_path) or throw(new Exception('move upload file failure!'));
        }
        else
        {
            rename($path, $target_path) or throw(new Exception('move file failure!'));
        }
        
        $clip = ceil(filesize($target_path) / self::MAX_SIZE);
        $fp = fopen($target_path, 'r') or throw(new Exception('open file fail!'));
        for ($i=0; $i < $clip; $i++)
        {
            $sql = 'INSERT INTO `storage_data` SET id=' . $id . ', clip = ' . $i . ', data=\'' . addslashes(fread($fp, self::MAX_SIZE)) . '\'';
            $this->db->query($sql);
            unset($sql);
        }
        fclose($fp);
    }
}

?>