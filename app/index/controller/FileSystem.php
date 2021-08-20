<?php

namespace app\index\controller;

use app\Request;
use inis\utils\File;

class FileSystem extends Base
{
    protected $File;
    
    // 构造器
    public function __construct()
    {
        $this->File = new File();
    }
    
    /** 
     * @name 获取文件信息
     */
    public function getDir(Request $request)
    {
        if ($request->isPost())
        {
            $param = $request->param();
            
            // 被获取的路径
            $path = (empty($param['path'])) ? './' : $param['path'];
            // 文件图片路径
            $ico_path = '/index/assets/svg/filesystem/';
            
            // 返回指定路径的文件夹信息，其中包含指定路径中的文件和目录
            $dir_info = $this->File->dirInfo($path);
            
            // 去除 . ..
            unset($dir_info[0]);
            unset($dir_info[1]);
            
            $file_info = [];
            
            // 文件类型
            $obtain = ['svg','jpg','jpeg','png','gif','ttf','woff','woff2','php','js','css','json','html','doc','docx','txt'];
            
            // 封装路径文件和文件夹数据
            foreach ($dir_info as $val) {
                
                $arr['name'] = $val;
                $arr['info'] = $this->File->listInfo($path.$val);
                
                if ($arr['info']['type'] == 'dir') $arr['info']['ico'] = $ico_path.'yellow-folder.svg';
                else if (in_array($arr['info']['ext'],$obtain)) foreach ($obtain as $val) {
                    
                    if ($arr['info']['ext'] == $val) $arr['info']['ico'] = $ico_path.$val.'.svg';
                    
                } else $arr['info']['ico'] = $ico_path.'other.svg';
                
                array_push($file_info,$arr);
            }
            
            // 重新排序
            if (!empty($file_info)) {
                
                $dir_data  = [];
                $file_data = [];
                
                foreach ($file_info as $key => $val) {
                    if ($val['info']['type'] == 'dir') array_push($dir_data,$val);
                    else array_push($file_data,$val);
                }
                
                $file_info = array_merge($dir_data,$file_data);
            }
            
            $data = ['path'=>$path,'info'=>$file_info];
            $code = 200;
            $msg  = 'ok';
            
            return $this->create($data,$code,$msg);
        }
    }
    
    /** 
     * @name 编辑文件名称
     */
    public function editName(Request $request)
    {
        if ($request->isPost())
        {
            $param = $request->param();
            
            $old = $param['path'].$param['old_name'];
            $new = $param['path'].$param['new_name'];
            
            $this->File->rename($old,$new);
            
            $data = [];
            $code = 200;
            $msg  = 'ok';
            
            return $this->create($data,$code,$msg);
        }
    }
    
    /** 
     * @name 文件详情信息
     */
    public function fileInfo(Request $request)
    {
        if ($request->isPost())
        {
            $param = $request->param();
            
            $data = [];
            
            if (!empty($param['file']) and !empty($param['type'])) {
                
                $type = $param['type'];
                
                // 文件路径 + 文件名
                $path_file = $param['path'].$param['file'];
                
            } else {
                
                $type = 'dir';
                $path_file = $param['path'];
            }
            
            if ($type == 'dir') $data['size'] = $this->File->getDirInfo($path_file);
            else $data['size'] = $this->File->openInfo($path_file);
            
            $data['other'] = $this->File->listInfo($path_file);
            
            $code = 200;
            $msg  = 'ok';
            
            return $this->create($data,$code,$msg);
        }
    }
    
    /** 
     * @name 新建文件或文件夹
     */
    public function addFile(Request $request)
    {
        if ($request->isPost())
        {
            $param = $request->param();
            
            $file = (empty($param['file'])) ? '新建文件' : $param['file'];
            $path = (empty($param['path'])) ? './' : $param['path'];
            $type = (empty($param['type'])) ? true : $param['type'];
            
            $path_file = $path.$file;
            
            // 新建文件或文件夹
            if ($type == 'true') $this->File->createFile($path_file);
            else $this->File->createDir($path_file);
            
            // 设置权限
            $this->File->changeFile($path_file,'mode', 0755);
            
            $data = [];
            $code = 200;
            $msg  = 'ok';
            
            return $this->create($data,$code,$msg);
        }
    }
    
    /** 
     * @name 删除文件或文件夹
     */
    public function delFile(Request $request)
    {
        if ($request->isPost())
        {
            $param = $request->param();
            
            $data = [];
            $code = 400;
            $msg  = 'ok';
            
            if (empty($param['file'])) $msg = '请选择需要被删除的文件！';
            else {
                
                $path_file = $param['path'].$param['file'];
                
                // 删除文件
                if ($param['type'] == 'file') {
                    $this->File->unlinkFile($path_file);
                } else {  // 删除文件夹
                    $this->File->removeDir($path_file,true);
                }
                $code = 200;
            }
            
            return $this->create($data,$code,$msg);
        }
    }
    
    /** 
     * @name 上传文件
     */
    public function uploadFileOne(Request $request)
    {
        $name = explode('.', $_FILES['file']['name']);
        array_pop($name);
        $name = implode('.',$name);
        // $tmp_name = $_FILES['file']['tmp_name'];
        // $error = $_FILES['file']['error'];
        
        $param = $request->param();
        
        $path = explode('/',$param['path']);
        foreach ($path as $key => $val) {
            if ($val == '.' or $val == '..') unset($path[$key]);
        }
        $path = implode('/',$path);
        $path = (empty($path)) ? '/' : $path;
        
        $rule = 'file';
        
        $upload = (new Tool())->upload('file', ['public', $path, [$name]], 'one', $rule);
        
        if ($upload['code'] == 200){
            $this->File->changeFile($param['path'].$_FILES['file']['name'],'mode', 0755);
        }
        
        return $upload;
    }
    
    // END
}