<?php
header( "Access-Control-Allow-Origin: *" );
header( "Access-Control-Allow-Methods: GET, POST" );
ini_set( "display_errors", 1 );

$IDE_FILE = __FILE__;

$url = ( isset( $_POST["url"] ) ? $_POST["url"] : "" );
$server = ( isset( $_POST["server"] ) ? $_POST["server"] : "" );
$func = ( isset( $_POST["func"] ) ? $_POST["func"] : "" );
$path = ( isset( $_POST["path"] ) ? $_POST["path"] : "" );
$type = ( isset( $_POST["type"] ) ? $_POST["type"] : "" ); 
$content = ( isset( $_POST["content"] ) ? $_POST["content"] : "" ); 
$new_path = ( isset( $_POST["new_path"] ) ? $_POST["new_path"] : "" ); 


$user = ( isset( $_POST["user"] ) ? $_POST["user"] : "" ); 
$password = ( isset( $_POST["password"] ) ? $_POST["password"] : "" );

$str = str_replace("*", "", $path);

$u = "user";
$p = "password";

if( $user == $u && $password == $p ){

    switch ( $func ){
    
        case "load":
            if ( is_dir($str) ){
                load( $path, $IDE_FILE, $server, $url, $user, $password );
            }else{
                $listObject = array();
                $listObject[] = "file";
                
                $ext = pathinfo( $path, PATHINFO_EXTENSION );
                if( $ext == "phtml" ) $ext = "php";
                
                $listObject[] = (object)[
                    "content" => file_get_contents( $path ),
                    "ext" => $ext,
                    "name" => basename( $path ),
                    "path" => $path,
                    "url" => $url,
                    "server" => $server,
                    "user" => $user,
                    "password" => $password
                ];
                
                echo json_encode( $listObject );
            }
        break; 
        
        case "save":
			if( file_exists($path) )
			    checkPermission( $path );
			file_put_contents( $path, $content );
			$listObject = array();
            $listObject[] = "save";
            
            $listObject[] = "Save done";
            
            echo json_encode( $listObject );
        break; 
        
        case "upload":
            $listObject = array();
            $listObject[] = "upload";
            $file = $path.$_FILES["file"]["name"];
            
			if( !file_exists($file) ){

                if( move_uploaded_file( $_FILES["file"]["tmp_name"], $file ))
                    $listObject[] = $_FILES["file"]["name"]." : 1 : Done";
                else
                    $listObject[] = "Error moving File";
			}else{
			    
                $listObject[] = "File Already exit";
			}
            echo json_encode( $listObject );
            
        break;  
        
        case "remove":
			checkPermission( $path );
			$listObject = array();
            $listObject[] = "remove";
            
            $del = ( is_dir( $path ) ) ? delTree($path) : unlink($path);
            $listObject[] = "Remove Done";
            $listObject[] = $url;
            $listObject[] = $path;
            echo json_encode( $listObject );
        break; 
		
        case "rename":
			checkPermission( $path );
			$listObject = array();
            $listObject[] = "rename";
            
            rename( $path, $new_path );
            $listObject[] = "Rename Done";
            $listObject[] = $url;
            $listObject[] = $path;
            $listObject[] = $new_path;
			
			$name = basename( $new_path );
			$listObject[] = $name;
			
			$ext = pathinfo( $new_path, PATHINFO_EXTENSION );
			$listObject[] = $ext;
			
            echo json_encode( $listObject );
        break; 
    
        case "new_file":
            $listObject = array();
            $listObject[] = "new_file";
            
            if( !file_exists($path) ){
                touch($path);
                $listObject[] = "File Created";
            }else
                $listObject[] = "File Exist";
            
            
            echo json_encode( $listObject );
        break;
        
        case "new_folder":
			$listObject = array();
            $listObject[] = "new_folder";
            
            if( !file_exists($path) ){
                mkdir($path);
                $listObject[] = "Folder Created";
            }else
                $listObject[] = "Folder Exist";
            
            echo json_encode( $listObject );
        break;

    }
}

function checkPermission( $file ){
	if( !is_writable( $file ) ) {
	    $listObject = array();
        $listObject[] = "save";
		$listObject[] = "Permission denied";
		echo json_encode( $listObject );
		exit;
	}
}

function load( $path, $IDE_FILE, $server, $url, $user, $password ){
    $current = basename( str_replace("*", "", $path) );
    $listDir = array();
    $listFile = array();
    $listObject = array();
    $listObject[] = "folder";
    
    foreach ( glob( $path ) as $file ){
        
        $mode = pathinfo( $file, PATHINFO_EXTENSION );
        if( $mode != "zip" ) $isZip = "hide";
        else $isZip = "";

        if ( is_dir($file) ) {

            $listDir[] = (object)[
                "mode" => "folder",
                "path" => $file."/*",
                "icon" => "folder",
                "function" => "folder",
                "name" => basename($file),
                "url" => $url,
                "server" => $server,
                "user" => $user,
                "iszip" => $isZip,
                "password" => $password
            ];
            
        }else{
            if( !( $IDE_FILE ==  realpath($file) ) )
            $listFile[] = (object)[
                "mode" => $mode,
                "icon" => $mode."-file",
                "path" => $file,
                "function" => "file",
                "name" => basename($file),
                "url" => $url,
                "server" => $server,
                "user" => $user,
                "iszip" => $isZip,
                "password" => $password
            ];
            
        }
    }
    
    $listObject[] = array_merge($listDir,$listFile);
    $up = str_replace("*", "", $path);

    $listObject[] = dirname($up)."/*";
    $listObject[] = $up;

    echo json_encode( $listObject );
}

function delTree($dir){
    $files = array_diff( scandir($dir), array(".","..") );
    foreach ( $files as $file ){
        ( is_dir("$dir/$file") ) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}
