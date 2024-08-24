<?php

namespace Antevasin;

echo ajax_modal_template_header( $heading );
echo form_tag( 'install_confirmation_form', url_for( $file_update_url, '&name=' . $module_name . '&type=' . $_GET['type'] . '&source=' . $source ) );
echo input_hidden_tag( 'redirect_to', url_for( $antevasin_modules->core->module_app_path . 'index' ) );
?>
<div class="modal-body" >
    <div id="modal-body-content">    
        <p><?php echo 'Are you sure you want to ' . $action . ' the ' . $title . ' ' .  ucwords( $type ) . ' files?' ?></p> 
        <?php
            if ( $type == 'plugin' )
            {
                $warning = '<p>The plugin files and core module will be updated</p><p>All other module source file will not be updated</p>';
            }   
        ?>
        <p><?php echo $warning ?></p>
    </div>
</div> 
<?php echo ajax_modal_template_footer( 'Yes' ) ?>
</form>