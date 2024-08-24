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
                print_rr('need to add key to header for curl request');
                $token = $core->get_module_info( $module_path )->token;
                print_rr("token is $token");
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
                echo "Error :- " . curl_error( $ch );
            }
            print_rr("curl results is $result");
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

function install( string $source_file, string $dest_dir, string $source ) : bool
{
    $zip = new \ZipArchive();
    $resource = $zip->open( $source_file );
    if ( $resource !== true ) 
    {
        printf('Could not open zip file "%s", error: %d', $source_file, $resource);
        return false;
    }    
    $test = str_replace( '/', '-', $source );
    print_rr($test);
    // Read every file from archive
    for ( $i = 0; $i < $zip->numFiles; $i++ ) 
    {
        $file_stats = $zip->statIndex( $i );
        $filename = basename( $file_stats['name'] );
        // print_rr($source); print_rr($filename);
        if ( str_starts_with( $filename, str_replace( '/', '-', $source ) ) )
        {
            $top_level_dir = $file_stats['name'];
            print_rr("top level directory $top_level_dir");
            continue;
        }
        $foldername = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $dest_dir );
        print_rr("foldername is $foldername - desitcation directory is $dest_dir");
        // $absoluteFilename = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $dest_dir . $filename);
        if ( !is_dir( $foldername ) && !mkdir( $foldername, 0711, true ) && !is_dir( $foldername ) ) 
        {
            print_rr("Could not create directory $foldername");
            return false;
        }

        // Skip if entry is a directory
        if ( $filename[strlen($filename) - 1] === DIRECTORY_SEPARATOR ) 
        {
            print_rr("Skipping directory $filename");
            continue;
        }

        // Extract file
        $file_path_ = str_replace( $top_level_dir, '', $file_stats['name'] );
        $file_path = $file_stats['name'];
        print_rr("foldername is $foldername - filename is {$file_stats['name'] } - file path is $file_path - adj file path is $file_path_"); 
        if ( $zip->extractTo( $foldername, $file_path ) === false ) 
        {
            print_rr("Could not extract file $file_path to $foldername");
            continue;
        }
    }
    $zip->close();
    return true;
}