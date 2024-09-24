<?php

namespace Antevasin;

$data = $core->get_data();
$module_name = $data['module_name'];
// print_rr($data);
$action = $data['module_action'];
$dest = PLUGIN_PATH . 'modules/' . $module_name . '/';
$heading = ucwords( $action ) . ' Source Files';  
$warning = 'All existing module files in the directory <b>' . $dest . '</b> will be overwritten';
// switch ( $action )
// {
//     case 'install':
//         $heading = 'Install ' . $title . ' ' .  ucwords( $type ) . ' Source Files';
//         break;
//     case 'upgrade':
//         $heading = 'Update ' . $title . ' ' .  ucwords( $type ) . ' Source Files to New Version';
//         // $warning = 'All existing module files in the directory <b>' . $dest . '</b> will be overwritten';
//         break;            
//     default:
//         break;
// }
// $file_update_url = 'file update url';
// $source = 'source';
$type = 'module';
echo ajax_modal_template_header( $heading );
echo form_tag( 'install_confirmation_form', url_for( 'antevasin/core/files', 'action=' . $action ) );
echo input_hidden_tag( 'redirect_to', 'antevasin/core/index' );
echo input_hidden_tag( 'module_name', $module_name );
echo input_hidden_tag( 'file_url', $data['file_url'] );
echo input_hidden_tag( 'private', $data['private'] );
$title = ucwords( $module_name );
if ( $module_name == 'core' )
{
    $title = ucwords( PLUGIN_NAME );
    // $warning = '<p>The <b>' . $title . '</b> plugin files which includes the <b>Core</b> module will be updated</p><p>All other module source files will not be updated</p>';
    $warning = '<p>The <b>' . PLUGIN_PATH . '*</b> files and <b>Core</b> module files will be updated</p><p>All other module source files will not be updated</p>';
    $type = 'plugin';
}  
?>
<div class="modal-body" >
    <div id="modal-body-content">    
        <p><?php echo 'Are you sure you want to ' . $action . ' the <b>' . $title . '</b> ' .   $type . ' files?' ?></p> 
        <p><?php echo $warning ?></p>
    </div>
</div> 
<?php echo ajax_modal_template_footer( 'Yes' ) ?>
</form>