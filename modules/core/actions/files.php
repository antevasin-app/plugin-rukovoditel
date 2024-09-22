<?php

namespace Antevasin;

if ( !empty( $app_module_action ) )
{
    install( $core );
} 

function install( $module )
{
    $data = $module->get_data();
    $action = $data['action'];
    $module_name = $data['module_name'];
    // print_rr("installing source files for module $module_name - action is $action");
    // print_rr($data);
    $file_url = $data['file_url'];
    $private = $data['private'];
    $temp_dir = 'tmp' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR;
    if ( file_exists( $temp_dir ) ) remove_dir( $temp_dir );
    mkdir( $temp_dir, 0711 );
    $zip_filename = ( ( $module_name == 'core' ) ? PLUGIN_NAME : $module_name );
    $local_zip_file = $temp_dir . $zip_filename . '_' . time() . '.zip';
    $local_zip_resource = fopen( $local_zip_file, "w+" );
    $headers = array();
    $module_path = PLUGIN_PATH . "modules/$module_name/";
    $source = $module->get_module_info( $module_path )->source;
    if ( $private )
    {
        // print_rr('need to add key to header for curl request');
        $token = $data['source_token'];
        $headers = array(
            'Authorization: token ' . $token
        );
        // print_rr($headers);
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
        die("Curl Error :- " . curl_error( $ch ));
    }   
    $zip = new \ZipArchive;
    $res = $zip->open( $local_zip_file, \ZipArchive::CREATE );
    if ( $res === true )  
    {
        $zip->extractTo( $temp_dir );
        $source_path = str_replace( '/', '-', $source );
        // $unzipped_dir = "$source_path-{$zip->comment}";
        $unzipped_dir = $zip->getNameIndex( 0 );
        $dir_to_zip = $temp_dir . $zip_filename;
        // if ( $action == 'download' && file_exists( $dir_to_zip ) ) remove_dir( $dir_to_zip );
        rename( $temp_dir . $unzipped_dir, $dir_to_zip );
        if ( $zip->close() === false ) print_rr('failed to close zip file');
        $new_zip = new \ZipArchive;
        $new_zip_filename = $temp_dir . $zip_filename . '.zip';
        
        $new_zip->open( $new_zip_filename, \ZipArchive::CREATE );
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $dir_to_zip ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );    
        foreach ( $files as $file )
        {
            // Skip directories (they would be added automatically)
            if ( !$file->isDir() )
            {
                $real_path = $file->getRealPath();
                $path = $temp_dir . $zip_filename . DIRECTORY_SEPARATOR;
                $parts = explode( $path, $real_path );
                // print_rr($parts);
                // Add file to archive
                $new_zip->addFile( $real_path, $parts[1] );
            }
        }       
        $install_dir = ( $module_name == 'core' ) ? PLUGIN_PATH . $zip_filename : PLUGIN_PATH . 'modules/' . $zip_filename;
        // Zip archive is created only after closing object
        $new_zip->close();        
        if ( $action == 'download' )
        {
            $file_url = 'http://localhost/plugin/tmp/plugin/' . $zip_filename . '.zip';
            die( '{"success":"downloading file", "download_url":"' . $file_url . '"}' ); 
        }
        else
        {
            $install_zip = new \ZipArchive;
            $install_zip->open( $new_zip_filename, \ZipArchive::CREATE );
            $install_zip->extractTo( $install_dir );
        }
        $install_zip->close();
        // print_rr($local_zip_file); print_rr($source_path); print_rr($unzipped_dir); print_rr($dir_to_zip); print_rr($zip); die(print_rr($new_zip_filename));        
        redirect_to( $data['redirect_to'] );
    }
    else 
    {
        echo 'failed, code:' . $res;
    }
}

function remove_dir( $dir )
{
    $it = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
    $files = new \RecursiveIteratorIterator( $it, \RecursiveIteratorIterator::CHILD_FIRST );
    foreach( $files as $file ) 
    {
        if ( $file->isDir() )
        {
            rmdir( $file->getPathname() );
        } 
        else 
        {
            unlink( $file->getPathname() );
        }
    }
    rmdir( $dir );
}