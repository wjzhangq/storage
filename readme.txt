数据库存储操作方式


$path 为要转存的文件路径， $filename上传时文件名, $key 为数据库中文件主键
$key = Storage::store($path, $filename="")


isset(Storage[$key]) 判断主键为$key的文件是否存在

print_r(Storage[$key]) 显示： array('key'='a', 'url'='http://xxx/a.jpg', 'path'= 'tmp/a.jpg', 'filetype'=>'jpg', 'size'=>100 )

unset(Storage[$key]) 删除主键为$key的文件


$path = Storage::restore($key); 将文件从数据库中还原
