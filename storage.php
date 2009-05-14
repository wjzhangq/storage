<?php
/**
 * 文件存储类
 * @author wjzhangq <wjzhangq@126.com>
 */

class storage
{
    static $db = null;
    constant MAX_SIZE = 2097152; //2M
    
    function init()
    {
        if ($db === null)
        {
            //初始化$db
        }
    }
    /**
     * 将指定文件存储到数据库
     *
     */
    function store($path, $filename='')
    {
        self::init();
        //检查是否是文件
        is_file($path) or throw(new Exception('the target is not a file(' . $path . ')'));
        strlen($filename) > 0  or $filename = basename($path);
        
        //获取信息
        $info = array(
            'filename'=> $filename,
            'filetype'=> self::filetype($filename),
            'size' => filesize($path),
            'hash' => md5_file($path),
        );
        
        //检查是否上传过
        $id = self::exists_by_info($info);
        
        if ($id)
        {
            self[$id]['num'] ++;
        }
        else
        {
            
        }
    }
}

?>