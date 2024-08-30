<?php

namespace Antevasin;

// print_rr($core->get_data());
$action = $core->get_data()['action'];
$module_name = $core->get_data()['module_name'];
$file_url = $core->get_data()['file_url'];
$private = $core->get_data()['private'];
$local_zip_file = 'tmp/' . $module_name . '.zip';
$local_zip_resource = fopen( $local_zip_file, "w+" );
// print_rr($core->get_info());

switch ( $action )
{
    case 'download':
        if ( $module_name == 'core' )
        {
            $source = $core->get_info()->source;
        }
        else
        {
            $headers = array();
            $module_path = PLUGIN_PATH . "modules/$module_name/";
            $source = $core->get_module_info( $module_path )->source;
            if ( $private )
            {
                // print_rr('need to add key to header for curl request');
                $token = $core->get_module_info( $module_path )->token;
                // print_rr("token is $token");
                $headers = array(
                    'Authorization: token ' . $token
                );
            }
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $file_url );
            curl_setopt( $ch, CURLOPT_FAILONERROR, true );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt( $ch, CURLOPT_BINARYTRANSFER,true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 ); 
            curl_setopt( $ch, CURLOPT_FILE, $local_zip_resource );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'Antevasin-App' );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );            
            $result = curl_exec($ch);
            if( !$result ) {
                // handle errors
                die("Error :- " . curl_error( $ch ));
            }
            // print_rr("curl result is $result");
        }
        break;
    default:
        $heading = '';
}
$tmp_zip_file = 'tmp/' . $module_name . '.zip';
$dest_dir = 'tmp/' . $module_name;
install( $tmp_zip_file, $dest_dir, $source );
print_rr($tmp_zip_file); print_rr($dest_dir); die(print_rr($private));
print_rr($action); print_rr($module_name); print_rr($file_url); die(print_rr($private));

$type = 'module'; // $_GET['type'];

$update_path = '';

$file_update_url = $antevasin_modules->core->module_app_path . 'file_update&token=' . $app_session_token;
if ( $type == 'module' )
{
    $update_path = '/modules/' . $module_name;
    if ( !isset( $antevasin_modules->{$module_name} ) )
    {
        $licence_key = $antevasin_modules->core->config['key'];
        $antevasin_modules_info = $antevasin_plugin->get_available_modules_info( $licence_key );        
        $module_info = $antevasin_modules_info[$module_name];
    }
    else 
    {
        $module_info = $antevasin_modules->{$module_name}->get_module_info();
    } 
    $title = $module_info['title'];
    $source = $module_info['source'];    
}       
else if ( $type == 'plugin' )
{
    $plugin_info = $antevasin_plugin->get_plugin_info();
    $title = $plugin_info['title'];
    $source = $plugin_info['source'];
}
$warning = '';
$dest = '/plugins/' . $antevasin_plugin->plugin_name . $update_path ;
switch ( $action )
{
    case 'update':
        $heading = 'Update ' . $title . ' ' . ucwords( $type ). ' Source Files';
        $warning = 'All existing files in the directory <b>' . $dest . '</b> will be overwritten';
        break;
    case 'install':
        $heading = 'Install ' . $title . ' ' .  ucwords( $type ) . ' Source Files';
        break;
    case 'upgrade':
        $heading = 'Update ' . $title . ' ' .  ucwords( $type ) . ' Source Files to New Version';
        $warning = 'All existing module files in the directory <b>' . $dest . '</b> will be overwritten';
        break;            
    default:
        $heading = '';
}

function install_( string $source_file, string $dest_dir, string $source )
{
    $fnNoExt = basename( $source_file, ".zip") ;
    $dest_dir = 'tmp/';
    $zip = new \ZipArchive;
    print_rr($zip);
    // $destination = 'tmp';
    if ( $zip->open( $source_file, \ZipArchive::CREATE ) === true )
    {
        if ( !is_dir( $dest_dir ) )
        {
            // mkdir( $dest_dir, 0711 );
        }
        $zip->extractTo( $destination );
        $zip->close();
    } 
    else 
    {
        return false;
    }
}

function install( string $source_file, string $dest_dir, string $source )
{
    $zip = new \ZipArchive;
    // $source_file = 'C:\xampp\htdocs\plugin\tmp\readme.zip';
    $source_file = 'tmp\readme.zip';
    // $source_file = 'tmp\test.zip';
    $dest_dir = 'tmp';
    // print_rr($source_file);
    $res = $zip->open( $source_file, \ZipArchive::CREATE );
    // print_rr($res);
    if ( $res === true )  
    {
        // print_rr($zip);
        // $zip->renameIndex( 1, '3/testes.txt' );
        // $zip->addFromString( 'antevasin-app-module-hauora-e0e502feeb0d7ec7a2f2cf8e4223b1f07e81e715/added.txt', 'my added file content goes here' );
        // print_rr($zip);
        for ( $i = 0; $i < $zip->numFiles; $i++ ) 
        {
            $file_stats = $zip->statIndex( $i );
            $filename = $zip->getNameIndex($i);
            $file_info = pathinfo($filename);
            // print_rr($filename); print_rr($file_info);
            // $filename = str_replace( 'antevasin-app-module-hauora-e0e502feeb0d7ec7a2f2cf8e4223b1f07e81e715/', '', $file_stats['name'] );
            // $file_stats['name'] = $filename;
            // print_rr($file_stats);
        }
        if ( file_exists( 'tmp/hauora' ) )
        {
            print_rr('file exists');
            $backup_file_path = 'tmp/hauora_' . time();
            $backup_filename = basename( $backup_file_path );
            $backups_dir = 'tmp/backups';
            rename( 'tmp/hauora', $backup_file_path );
            if ( !file_exists( $backups_dir ) )
            {
                print_rr('ss dir does not exist so create it');
                mkdir( $backups_dir, 0711 );
            }
            print_rr('ss now exists so move folder there');
            print_rr($backup_file_path); print_rr($backup_filename);
            rename( $backup_file_path, "$backups_dir/$backup_filename" );            
        }        
        $zip->extractTo( $dest_dir );
        rename( 'tmp/antevasin-app-module-hauora-e0e502feeb0d7ec7a2f2cf8e4223b1f07e81e715', 'tmp/hauora' );
        // $new_zip = new \ZipArchive;
        // $new_res = $new_zip->open( $source_file, \ZipArchive::CREATE );
        // if ( $new_res === TRUE)  
        // {
        //     $new_zip->extractTo( $dest_dir );
        // }
    } 
    else 
    {
        echo 'failed, code:' . $res;
    }
    if ( $zip->close() === false )
    {
        print_rr('failed to close zip file');
    }
}

function install__( string $source_file, string $dest_dir, string $source ) : bool
{
    $zip = new \ZipArchive();
    $resource = $zip->open( $source_file );
    if ( $resource !== true ) 
    {
        printf('Could not open zip file "%s", error: %d', $source_file, $resource);
        return false;
    }    
    $file_source = str_replace( '/', '-', $source );
    print_rr("source file is $source_file - destination dir is $dest_dir - source is $source - file source string is $file_source");
    // Read every file from archive
    for ( $i = 0; $i < $zip->numFiles; $i++ ) 
    {
        $file_stats = $zip->statIndex( $i );
        $filename = $file_stats['name'];
        // print_rr($file_stats);
        if ( $filename == 'antevasin-app-module-hauora-e0e502feeb0d7ec7a2f2cf8e4223b1f07e81e715/actions/hauora.php' )
        {
            $filename = $zip->getNameIndex($i);
            $file_info = pathinfo($filename);
            print_rr($filename); print_rr($file_info);
            $zip->renameName( 'antevasin-app-module-hauora-e0e502feeb0d7ec7a2f2cf8e4223b1f07e81e715/actions/hauora.php', 'actions/hauora.php' );
            // print_rr("my file $filename - file is $file");
            // if ( $zip->extractTo( 'tmp', $filename ) === false ) 
            // {
            //     print_rr("Could not extract file $file to ");
            //     continue;
            // }  
            
        }
        // $filename = $file_stats['name'];
        // $regex = '/antevasin-app-module-hauora-[A-Za-z0-9]*\/$/';
        // if ( preg_match( $regex, $filename ) )
        // {
        //     print_rr("match $filename");
        //     $zip->extractTo( 'tmp', $filename );
        // }
        // print_rr("directory separator is " . DIRECTORY_SEPARATOR);
        // $file_path = str_replace( $base_dir, $dest_dir . '/', $filename );
        // // print_rr("file path is $file_path - filename is $filename - base dir is $base_dir");
        // // $foldername = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $dest_dir );
        // if ( preg_match( '/.*\/$/', $file_path ) ) 
        // {
        //     // print_rr('is a directory');
        //     if ( !is_dir( $file_path ) && !mkdir( $file_path, 0711, true ) ) 
        //     {
        //         print_rr("Could not create directory $file_path");
        //         return false;
        //     }
        // }
        // else
        // {
        //     print_rr("is a file - file path is $file_path");
        //     $directory = dirname( $file_path ) . '/';
        //     $file = basename( $file_path );
        //     print_rr("directory is $directory - file is $file - filename is $filename");
        //     if ( $zip->extractTo( $directory, $filename ) === false ) 
        //     {
        //         print_rr("Could not extract file $file to $directory");
        //         continue;
        //     }        
        // }

        // print_rr($file_stats); print_rr($filename); die('pause');
        // $zip->extractTo( $foldername, $file_path );
        // if ( str_starts_with( $filename, str_replace( '/', '-', $source ) ) )
        // {
        //     $top_level_dir = $file_stats['name'];
        //     print_rr("top level directory $top_level_dir");
        //     continue;
        // }
        // $foldername = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $dest_dir );
        // print_rr("foldername is $foldername - desitcation directory is $dest_dir");
        // // $absoluteFilename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $dest_dir . $filename);
        // if ( !is_dir( $foldername ) && !mkdir( $foldername, 0711, true ) && !is_dir( $foldername ) ) 
        // {
        //     print_rr("Could not create directory $foldername");
        //     return false;
        // }

        // // Skip if entry is a directory
        // if ( $filename[strlen($filename) - 1] === DIRECTORY_SEPARATOR ) 
        // {
        //     print_rr("Skipping directory $filename");
        //     continue;
        // }

        // // Extract file
        // $file_path_ = str_replace( $top_level_dir, '', $file_stats['name'] );
        // $file_path = $file_stats['name'];
        // print_rr("foldername is $foldername - filename is {$file_stats['name'] } - file path is $file_path - adj file path is $file_path_"); 
        // if ( $zip->extractTo( $foldername, $file_path ) === false ) 
        // {
        //     print_rr("Could not extract file $file_path to $foldername");
        //     continue;
        // }
    }
    $zip->extractTo( $dest_dir );
    $zip->close();
    return true;
}