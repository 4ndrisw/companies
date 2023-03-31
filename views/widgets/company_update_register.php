<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('companies/companies_model');
    //$companies = $CI->companies_model->get_companies_this_week(get_staff_user_id());
    $staff_id = get_staff_user_id();
    $current_user = get_client_type($staff_id);
    $company_id = $current_user->client_id;

?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('company_update_register'); ?>">
    <?php if(staff_can('view', 'companies') || staff_can('view_own', 'companies')) { ?>
    <div class="panel_s companies-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('company_update_register'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($companies)) { ?>



            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_company_available',["7"]) ; ?> </h4>
                
                    <?php 
                    echo 'staff_id : ' . $staff_id. '<br />';
                    var_dump($staff_id);
                    echo '<br />company_id : ' . $company_id. '<br />';

                    ?>

                    <h4><?php echo _l('company_not_ready_to_use_apps',["7"]) ; ?> </h4>
                    <a href="<?= admin_url() .'companies/company/' .$company_id ?>">Update</a>



                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
