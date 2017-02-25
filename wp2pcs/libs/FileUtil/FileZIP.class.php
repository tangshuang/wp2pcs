<?php

class FileZIP {

  var $file_count = 0 ;
  var $datastr_len   = 0;
  var $dirstr_len = 0;
  var $filedata = ''; //该变量只被类外部程序访问
  var $gzfilename;
  var $fp;
  var $dirstr='';
  var $excludes;// 添加排除，是一个数组，每一个值就是文件夹的路径
  var $includes;// 白名单，一定会被打包，即使在黑名单中，是一个数组，每一个值就是文件夹的路径

  function __construct(){
    $this->excludes = array();
    $this->includes = array();
  }

  function unix2DosTime($unixtime = 0) {
    $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);
    if($timearray['year'] < 1980) {
      $timearray['year']    = 1980;
      $timearray['mon']     = 1;
      $timearray['mday']    = 1;
      $timearray['hours']   = 0;
      $timearray['minutes'] = 0;
      $timearray['seconds'] = 0;
    }
    return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
  }

  /*
  初始化文件,建立文件目录,
  并返回文件的写入权限.
  */
	function startfile($path) {
		// 处理路径
    $this->gzfilename = $path;
		$mypathdir = array();
		do{
			$mypathdir[] = $path = dirname($path);
		}while($path != '/' && $path != '.');
		@end($mypathdir);
		do{
			$path = @current($mypathdir);
			@mkdir($path);
		}while(@prev($mypathdir));
    // 创建文件
		if($this->fp=@fopen($this->gzfilename,"w")){
			return true;
		}
		return false;
	}

	// 添加一个文件到 zip 压缩包中.
  function addfile($data, $name){
    $name = str_replace('\\', '/', $name);
    if(strrchr($name,'/')=='/') {
      return $this->adddir($name);
    }
    $dtime    = dechex($this->unix2DosTime());
    $hexdtime = '\x' . $dtime[6].$dtime[7].'\x'.$dtime[4].$dtime[5].'\x'.$dtime[2].$dtime[3].'\x'.$dtime[0].$dtime[1];
    eval('$hexdtime = "' . $hexdtime . '";');
    $unc_len = strlen($data);
    $crc     = crc32($data);
    $zdata   = gzcompress($data);
    $c_len   = strlen($zdata);
    $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
    //新添文件内容格式化:
    $datastr  = "\x50\x4b\x03\x04";
    $datastr .= "\x14\x00";            // ver needed to extract
    $datastr .= "\x00\x00";            // gen purpose bit flag
    $datastr .= "\x08\x00";            // compression method
    $datastr .= $hexdtime;             // last mod time and date
    $datastr .= pack('V', $crc);             // crc32
    $datastr .= pack('V', $c_len);           // compressed filesize
    $datastr .= pack('V', $unc_len);         // uncompressed filesize
    $datastr .= pack('v', strlen($name));    // length of filename
    $datastr .= pack('v', 0);                // extra field length
    $datastr .= $name;
    $datastr .= $zdata;
    $datastr .= pack('V', $crc);                 // crc32
    $datastr .= pack('V', $c_len);               // compressed filesize
    $datastr .= pack('V', $unc_len);             // uncompressed filesize
		//写入新的文件内容
    fwrite($this->fp,$datastr);
		$my_datastr_len = strlen($datastr);
		unset($datastr);
		//新添文件目录信息
    $dirstr  = "\x50\x4b\x01\x02";
    $dirstr .= "\x00\x00";                	// version made by
    $dirstr .= "\x14\x00";                	// version needed to extract
    $dirstr .= "\x00\x00";                	// gen purpose bit flag
    $dirstr .= "\x08\x00";                	// compression method
    $dirstr .= $hexdtime;                 	// last mod time & date
    $dirstr .= pack('V', $crc);           	// crc32
    $dirstr .= pack('V', $c_len);         	// compressed filesize
    $dirstr .= pack('V', $unc_len);       	// uncompressed filesize
    $dirstr .= pack('v', strlen($name) ); 	// length of filename
    $dirstr .= pack('v', 0 );             	// extra field length
    $dirstr .= pack('v', 0 );             	// file comment length
    $dirstr .= pack('v', 0 );             	// disk number start
    $dirstr .= pack('v', 0 );             	// internal file attributes
    $dirstr .= pack('V', 32 );            	// external file attributes - 'archive' bit set
    $dirstr .= pack('V',$this->datastr_len ); // relative offset of local header
    $dirstr .= $name;
		$this->dirstr .= $dirstr;	//目录信息
		$this -> file_count ++;
		$this -> dirstr_len += strlen($dirstr);
		$this -> datastr_len += $my_datastr_len;
  }

	// 添加一个文件夹到压缩文件中
  function adddir($name) {
		$name = str_replace("\\", "/", $name);
		$datastr = "\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00";
		$datastr .= pack("V",0).pack("V",0).pack("V",0).pack("v", strlen($name) );
		$datastr .= pack("v", 0 ).$name.pack("V", 0).pack("V", 0).pack("V", 0);
		fwrite($this->fp,$datastr);	//写入新的文件内容
		$my_datastr_len = strlen($datastr);
		unset($datastr);
		$dirstr = "\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00\x00\x00\x00\x00";
		$dirstr .= pack("V",0).pack("V",0).pack("V",0).pack("v", strlen($name) );
		$dirstr .= pack("v", 0 ).pack("v", 0 ).pack("v", 0 ).pack("v", 0 );
		$dirstr .= pack("V", 16 ).pack("V",$this->datastr_len).$name;
		$this->dirstr .= $dirstr;	//目录信息
		$this -> file_count ++;
		$this -> dirstr_len += strlen($dirstr);
		$this -> datastr_len += $my_datastr_len;
	}

	// 增加包含路径
	function include_path($path) {
	  $path = realpath($path);
	  $this->includes[] = $path;
	  if(is_dir($path)) {
      $files = $this->get_files_in_dir($path);
      foreach($files as $file) $this->include_path($file);
      unset($files);
	  }
    $this->includes = array_unique(array_filter($this->includes));
	}

	// 排除路径
	function exclude_path($path) {
	  $path = realpath($path);
	  $this->excludes[] = $path;
	  if(is_dir($path)) {
      $files = $this->get_files_in_dir($path);
      foreach($files as $file) $this->exclude_path($file);
      unset($files);
	  }
    $this->excludes = array_unique(array_filter($this->excludes));
	}

  // 执行：遍历传递过来的文件夹，并把该文件夹中的文件打包压缩
  function process($dir,$root){
    $root = realpath($root).DIRECTORY_SEPARATOR;
    $dir = realpath($dir);
    // 这个循环用来对比root和dir，假如root中只有前半段可以被去除的情况，可以通过这个循环处理好
    while(strpos($dir,$root) !== 0 && $root != '.' && $root != '/'){
      $root = dirname($root);
    }
  	// 如果传过来的参数是文件
		if(is_file($dir)){
		  if(in_array($dir,$this->excludes) && !in_array($dir,$this->includes)) return 0;
		  if(realpath($this->gzfilename) != $dir){
		    $del_path = $this->str_replace_first($root,'',$dir);
        $this->addfile(implode('',file($dir)),$del_path);
        return 1;
		  }
			return 0;
		}
		// 如果传过来的是文件夹
		elseif(is_dir($dir)) {
      $sub_file_num = 0;
		  $handle = opendir($dir);
      while($file = readdir($handle)) {
        //添加排除
        if($file == '.' || $file == '..') continue;
        $realpath = realpath($dir.DIRECTORY_SEPARATOR.$file);// 当前文件的真实地址
        $del_path = $this->str_replace_first($root,'',$realpath);// 要去掉的地址段
        if(is_dir($realpath)) {
			    $sub_file_num += $this->process($dir.DIRECTORY_SEPARATOR.$file,$root);
		    }
		    elseif(is_file($realpath) && realpath($this->gzfilename) != $realpath) {
		      if(in_array($realpath,$this->excludes) && !in_array($realpath,$this->includes)) continue;
		      $this->addfile(implode('',file($realpath)),$del_path);
			    $sub_file_num ++;
		    }
		  }
		  closedir($handle);
  		return $sub_file_num;
		}
		return 0;
	}

	// 生成最终的压缩包
  function createfile(){
		//压缩包结束信息,包括文件总数,目录信息读取指针位置等信息
		$endstr = "\x50\x4b\x05\x06\x00\x00\x00\x00" .
		pack('v', $this -> file_count) .
		pack('v', $this -> file_count) .
		pack('V', $this -> dirstr_len) .
		pack('V', $this -> datastr_len) .
		"\x00\x00";
		fwrite($this->fp,$this->dirstr.$endstr);
		fclose($this->fp);
	}

	// 获取目录中的文件列表（包括目录）
	function get_files_in_dir($dir) {
	  $files = array();
	  $dir = realpath($dir);
	  if(is_dir($dir)) {
	    $handle = opendir($dir);
	    while($path = readdir($handle)) {
	      $file = $dir.DIRECTORY_SEPARATOR.$path;
	      if($path == '.' || $path == '..') continue;
	      $files[] = $file;
	      if(is_dir($file))$files = array_merge($files,$this->get_files_in_dir($file));
      }
    }
    return array_unique(array_filter($files));
	}

	// 获取目录的递归子目录
	function get_dirs_in_dir($dir) {
	  $sub_dirs = array();
	  $dir = realpath($dir);
	  if(is_dir($dir)) {
	    $handle = opendir($dir);
	    while($path = readdir($handle)) {
	      $file = $dir.DIRECTORY_SEPARATOR.$path;
	      if($path == '.' || $path == '..' || !is_dir($file)) continue;
	      $sub_dirs[] = $file;
	      $sub_dirs = array_merge($sub_dirs,$this->get_dirs_in_dir($file));
      }
    }
    return array_unique(array_filter($sub_dirs));
	}

	// 替换字符串中第一次出现的子串
  private function str_replace_first($find,$replace,$string){
    $position = strpos($string,$find);
    if($position !== false){
      $length = strlen($find);
      $string = substr_replace($string,$replace,$position,$length);
      return $string;
    }else{
      return $string;
    }
  }

}

/**
 * 使用说明：
 *

$ZIP = new WebZip;
if($ZIP->startfile('web.zip')) { // startfile的参数就是准备生成的文件的路径，一般是相对路径，使用绝对路径没有问题
  $ZIP->excludes = array('webzip.php'); // excludes指排除，数组中的每一个元素是指要排除的文件或文件夹的路径，这个路径是相对于下方process中的路径来说的，例如下方使用了'.'这样的相对路径，那么这个地方你就应该填写'./excludes'这样的相对值，如果下方使用了'/home/var/www/html/'，那么这里就应该使用'/home/var/www/html/backup/'这样的绝对值，总而言之，这里的路径应该包含下方的第一个参数
  $ZIP->process('.','.');// process是指开始对规定的路径进行压缩打包，第一个参数是要打包的文件或文件夹，第二个路径是生成的压缩文件中的目录层级要去除的部分，一般而言两个参数是一样的，例如设置了'/home/var/www/home' 如果不设置第二个参数的话，就会发现生成的文件中包含了home/var/www/home这些目录层级，而如果把第二个参数设置为'/home/var/www/home'，那么生成的压缩文件中直接就是home目录下的文件
  $ZIP->process('/var/www/html','/var/www/html');// 建议使用绝对路径作为参数，这样才能防止文件路径判断错误
  $ZIP->createfile();
}

*/
