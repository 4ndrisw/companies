<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Companies
Description: Default module for defining companies
Version: 1.0.1
Requires at least: 2.3.*
*/

define('COMPANIES_MODULE_NAME', 'companies');
define('COMPANY_ATTACHMENTS_FOLDER', 'uploads/companies/');

hooks()->add_filter('before_company_updated', '_format_data_company_feature');
hooks()->add_filter('before_company_added', '_format_data_company_feature');

hooks()->add_action('after_cron_run', 'companies_notification');
hooks()->add_action('admin_init', 'companies_module_init_menu_items');
hooks()->add_action('admin_init', 'companies_permissions');
hooks()->add_action('admin_init', 'companies_settings_tab');
hooks()->add_action('clients_init', 'companies_clients_area_menu_items');
hooks()->add_filter('get_contact_permissions', 'companies_contact_permission',10,1);

hooks()->add_action('staff_member_deleted', 'companies_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'companies_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'companies_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'companies_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'companies_add_dashboard_widget');
hooks()->add_filter('module_companies_action_links', 'module_companies_action_links');


function companies_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'companies/widgets/company_update_register',
        'container' => 'left-8',
    ];
    /*
    
    $widgets[] = [
        'path'      => 'companies/widgets/program_not_scheduled',
        'container' => 'left-8',
    ];
    */

    return $widgets;
}


function companies_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'companies', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function companies_global_search_result_output($output, $data)
{
    if ($data['type'] == 'companies') {
        $output = '<a href="' . admin_url('companies/company/' . $data['result']['id']) . '">' . format_company_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function companies_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('companies', '', 'view')) {

        // companies
        $CI->db->select()
           ->from(db_prefix() . 'companies')
           ->like(db_prefix() . 'companies.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'companies',
                'search_heading' => _l('companies'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // companies
        $CI->db->select()->from(db_prefix() . 'companies')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'companies.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'companies.clientid='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'companies',
                'search_heading' => _l('companies'),
            ];
    }

    return $result;
}

function companies_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'companies',
                'field' => 'description',
            ];

    return $tables;
}

function companies_contact_permission($permissions){
        $item = array(
            'id'         => 11,
            'name'       => _l('companies'),
            'short_name' => 'companies',
        );
        $permissions[] = $item;
      return $permissions;

}

function companies_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'view_in_inspectors' => _l('view_companies_in_inpectors'),
            'view_in_institutions' => _l('view_ompanies_in_institutions'),
            'edit'   => _l('permission_edit'),
            'edit_own'   => _l('permission_edit_own'),
            'delete' => _l('permission_delete'),
            'update_status' => _l('permission_update_status'),
    ];

    register_staff_capabilities('companies', $capabilities, _l('companies'));
}


/**
* Register activation module hook
*/
register_activation_hook(COMPANIES_MODULE_NAME, 'companies_module_activation_hook');

function companies_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(COMPANIES_MODULE_NAME, 'companies_module_deactivation_hook');

function companies_module_deactivation_hook()
{

     log_activity( 'Hello, world! . companies_module_deactivation_hook ' );
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(COMPANIES_MODULE_NAME, [COMPANIES_MODULE_NAME]);

/**
 * Init companies module menu items in setup in admin_init hook
 * @return null
 */
function companies_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('company'),
            'url'        => 'companies',
            'permission' => 'companies',
            'position'   => 57,
            ]);
    
    if (has_permission('companies', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('companies', [
                'slug'     => 'companies-tracking',
                'name'     => _l('companies'),
                'icon'     => 'fa-solid fa-industry',
                'href'     => admin_url('companies'),
                'position' => 12,
        ]);
    }
}

function module_companies_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=companies') . '">' . _l('settings') . '</a>';

    return $actions;
}

function companies_clients_area_menu_items()
{   
    // Show menu item only if client is logged in
    if (is_client_logged_in() && has_contact_permission('companies')) {
        add_theme_menu_item('companies', [
                    'name'     => _l('companies'),
                    'href'     => site_url('companies/list'),
                    'position' => 15,
                    'icon'     => 'fa-solid fa-industry',
        ]);
    }
}

/**
 * [perfex_dark_theme_settings_tab net menu item in setup->settings]
 * @return void
 */
function companies_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('companies', [
        'name'     => _l('settings_group_companies'),
        //'view'     => module_views_path(COMPANIES_MODULE_NAME, 'admin/settings/includes/companies'),
        'view'     => 'companies/companies_settings',
        'position' => 51,
        'icon'     => 'fa-solid fa-industry',
    ]);
}

$CI = &get_instance();
$CI->load->helper(COMPANIES_MODULE_NAME . '/companies');
if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='companies') || $CI->uri->segment(1)=='companies'){
    $CI->app_css->add(COMPANIES_MODULE_NAME.'-css', base_url('modules/'.COMPANIES_MODULE_NAME.'/assets/css/'.COMPANIES_MODULE_NAME.'.css'));
    $CI->app_scripts->add(COMPANIES_MODULE_NAME.'-js', base_url('modules/'.COMPANIES_MODULE_NAME.'/assets/js/'.COMPANIES_MODULE_NAME.'.js'));
}

if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='staff') && $CI->uri->segment(3)=='edit_provile'){
    $CI->app_css->add(COMPANIES_MODULE_NAME.'-css', base_url('modules/'.COMPANIES_MODULE_NAME.'/assets/css/'.COMPANIES_MODULE_NAME.'.css'));
}

