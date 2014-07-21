<?php

// 获取目录下的文件列表，注意，参数$path末尾最好不要带/
function get_files_in_dir($path){
	set_php_ini('limit');
	global $file_list;// 这个地方貌似有漏洞，因为之前没有声明过这个参数，这样做是否合理？
	// 经过验证，确实会遇到这个问题，即如果我两次使用get_files_in_dir函数，那么第一次中保存的$file_list将仍然存在，所以，在第一次使用完get_files_in_dir函数之后，一定要先把$file_list清空才可以。
	$path = trim($path);
	if(!file_exists($path) || !is_dir($path)){
		return null;
	}
	$dir = opendir($path);
	while($file = readdir($dir)){
		if($file == '.' || $file == '..')continue;
		$file_path = get_real_path($path.'/'.$file);
		// 这个地方要注意，要排除缓存目录，不能把缓存文件也给备份了
		if(strpos($file_path,WP2PCS_TMP_DIR) !== false){
			continue;
		}
		$file_list[] = $file_path;
		if(is_dir($file_path)){
			get_files_in_dir($file_path);
		}
	};
	closedir($dir);
	return $file_list;
}
// 为了上面这个函数准备的参数清空。
function get_files_in_dir_reset(){
	global $file_list;
	$file_list = array();
}

/*
* 打包指定目录列表中的文件
* 第一个参数为准备放入zip文件的路径数组，或某单一路径
* 第二个参数为准备作为存放zip文件的路径
* 第三个参数为zip文件路径中，准备移除的路径字串
*/
function zip_files_in_dirs($zip_local_paths,$zip_file_path,$remove_path = ''){
	if(empty($zip_local_paths)){
		return false;
	}
	$zip_file_path = trim($zip_file_path);
	if(file_exists($zip_file_path)){
		@unlink($zip_file_path);
	}
	if(!is_array($zip_local_paths)){
		if(is_string($zip_local_paths) && (is_file($zip_local_paths) || is_dir($zip_local_paths))){
			$zip_local_paths = array($zip_local_paths);
		}else{
			return false;
		}
	}

	$zip = new ZipArchive();
	if($zip->open($zip_file_path,ZIPARCHIVE::CREATE)!==TRUE){
		return false;
	}
	set_php_ini('timezone');
	foreach($zip_local_paths as $zip_local_path){
		$zip_local_path = trim($zip_local_path);
		$zip_local_path = str_replace('{year}',date('Y'),$zip_local_path);
		$zip_local_path = str_replace('{month}',date('m'),$zip_local_path);
		$zip_local_path = str_replace('{day}',date('d'),$zip_local_path);
		if(!file_exists($zip_local_path)){
			continue;
		}
		if(is_dir($zip_local_path)){
			get_files_in_dir_reset();
			$files = get_files_in_dir($zip_local_path);
			if(!empty($files))foreach($files as $file){
				$file = trim($file);
				$file_rename = str_replace($remove_path,'',$file);
				if(is_dir($file)){
					$zip->addEmptyDir($file_rename);
				}elseif(is_file($file)){
					$zip->addFile($file,$file_rename);
				}
			}
		}elseif(is_file($zip_local_path)){
			$file_rename = str_replace($remove_path,'',$zip_local_path);
			$zip->addFile($zip_local_path,$file_rename);
		}
	}
	$zip->close();

	return $zip_file_path;
}