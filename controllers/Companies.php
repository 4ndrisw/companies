<?php

use app\services\companies\CompaniesPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Companies extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('companies_model');
        $this->load->model('clients_model');
        $this->load->model('staff_model');
    }

    /* Get all companies in case user go on index page */
    public function index($id = '')
    {
        $this->list_companies($id);
    }

    /* List all companies datatables */
    public function list_companies($id = '')
    {
        if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && get_option('allow_staff_view_companies_assigned') == '0') {
            access_denied('companies');
        }

        $isPipeline = $this->session->userdata('company_pipeline') == 'true';

        $data['company_states'] = $this->companies_model->get_states();
        if ($isPipeline && !$this->input->get('state') && !$this->input->get('filter')) {
            $data['title']           = _l('companies_pipeline');
            $data['bodyclass']       = 'companies-pipeline companies-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['companyid'] = $id;
            } else {
                $data['companyid'] = $this->session->flashdata('companyid');
            }

            $this->load->view('admin/companies/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('state') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }
            
            $data['companyid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('companies');
            $data['bodyclass']             = 'companies-total-manual';
            $data['companies_years']       = $this->companies_model->get_companies_years();
            $data['companies_sale_agents'] = $this->companies_model->get_sale_agents();
            if($id){
                $this->load->view('admin/companies/manage_small_table', $data);

            }else{
                $this->load->view('admin/companies/manage_table', $data);

            }

        }
    }

    public function table($client_id = '')
    {
        if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && get_option('allow_staff_view_companies_assigned') == '0') {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('companies', 'admin/tables/table',[
            'client_id' => $client_id,
        ]));
    }

    /* Add new company or update existing */
    public function company($id = '')
    {
        if ($this->input->post()) {
            $company_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($company_data['save_and_send_later'])) {
                unset($company_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (!has_permission('companies', '', 'create')) {
                    access_denied('companies');
                }
                $company_data['is_company'] = '1';
                $next_company_number = get_option('next_company_number');
                $_format = get_option('company_number_format');
                $_prefix = get_option('company_prefix');
                
                $prefix  = isset($company->prefix) ? $company->prefix : $_prefix;
                $number_format  = isset($company->number_format) ? $company->number_format : $_format;
                $number  = isset($company->number) ? $company->number : $next_company_number;

                $company_data['prefix'] = $prefix;
                $company_data['number_format'] = $number_format;
                $date = date('Y-m-d');
                
                //$company_data['formatted_number'] = company_number_format($number, $format, $prefix, $date);
                //var_dump($company_data);
                //die();
                $id = $this->companies_model->add($company_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('company')));

                    $redUrl = admin_url('companies/list_companies/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_company_pipeline_autoload($id) ? $redUrl : admin_url('companies/list_companies/')
                    );
                }
            } else {
                if (has_permission('companies', '', 'edit') || 
                   (has_permission('companies', '', 'edit_own') && is_staff_related_to_company($id))
                   ) {
                    $success = $this->companies_model->update($company_data, $id);
                    if ($success) {
                        set_alert('success', _l('updated_successfully', _l('company')));
                    }
                    if ($this->set_company_pipeline_autoload($id)) {
                        redirect(admin_url('companies/list_companies/'));
                    } else {
                        redirect(admin_url('companies/list_companies/' . $id));
                    }
                }else{
                    access_denied('companies');
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_company');
        } else {
            $company = $this->companies_model->get($id);

            if (!$company || !user_can_view_company($id)) {
                blank_page(_l('company_not_found'));
            }

            $data['company'] = $company;
            $data['edit']     = true;
            $title            = _l('edit', _l('company_lowercase'));
        }
        $data['inspector_staff_data'] = get_inspector_staff_data();
        $data['company_states'] = $this->companies_model->get_states();
        $data['title']             = $title;
        $this->load->view('admin/companies/company', $data);
    }
    

    public function clear_signature($id)
    {
        if (has_permission('companies', '', 'delete')) {
            $this->companies_model->clear_signature($id);
        }

        redirect(admin_url('companies/list_companies/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (has_permission('companies', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'companies', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('company'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_company_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'companies', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->companies_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->companies_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all company data used when user click on company number in a datatable left side*/
    public function get_company_data_ajax($id, $to_return = false)
    {
        if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && get_option('allow_staff_view_companies_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No company found');
        }

        $company = $this->companies_model->get($id);

        if (!$company || !user_can_view_company($id)) {
            echo _l('company_not_found');
            die;
        }

        // $data = prepare_mail_preview_data($template_name, $company->clientid);
        $data['title'] = 'Form add / Edit Staff';
        $data['activity']          = $this->companies_model->get_company_activity($id);
        $data['company']          = $company;
        $data['inspector_staff_id']          = $company->inspector_staff_id;
        $data['members']           = $this->staff_model->get('', ['active' => 1, 'client_id'=>$id]);
        $data['company_states'] = $this->companies_model->get_states();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'company']);

        $staff_id = get_staff_user_id();
        $current_user = get_client_type($staff_id);
        $company_id = $current_user->client_id;

        $data['current_staff']['staff_id'] = $staff_id;
        $data['current_staff']['current_user'] = $current_user;
        $data['current_staff']['company_id'] = $company_id;

        if(function_exists('get_inspector_staffs')){
            $data['inspector_staffs']        = get_inspector_staffs($company->inspector_id);
        }
        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/companies/company_preview_template', $data);
        } else {
            return $this->load->view('admin/companies/company_preview_template', $data, true);
        }
    }

    public function get_companies_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->companies_model->get_companies_total($this->input->post());
            /*
            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'companies');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'companies');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['companies_years'] = $this->companies_model->get_companies_years();

            if (
                count($data['companies_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['companies_years'], 'year', date('Y'))
            ) {
                array_unshift($data['companies_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            */

            unset($data['totals']['currencyid']);
            $this->load->view('admin/companies/companies_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && (has_permission('companies', '', 'view') || has_permission('companies', '', 'view_own'))) {
            $this->companies_model->add_note($this->input->post(), 'company', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if ((has_permission('companies', '', 'view') || has_permission('companies', '', 'view_own'))) {
            $data['notes'] = $this->companies_model->get_notes($id, 'company');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_state($state, $id)
    {
        if (!has_permission('companies', '', 'edit') || !has_permission('companies', '', 'edit_own')) {
            access_denied('companies');
        }
        $success = $this->companies_model->mark_action_state($state, $id);
        if ($success) {
            set_alert('success', _l('company_state_changed_success'));
        } else {
            set_alert('danger', _l('company_state_changed_fail'));
        }
        if ($this->set_company_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('companies/list_companies/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_company($id);
        if (!$canView) {
            access_denied('Companies');
        } else {
            if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && $canView == false) {
                access_denied('Companies');
            }
        }

        $success = $this->companies_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_company_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('companies/list_companies/' . $id));
        }
    }

    /* Send company to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_company($id);
        if (!$canView) {
            access_denied('companies');
        } else {
            if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && $canView == false) {
                access_denied('companies');
            }
        }

        try {
            $success = $this->companies_model->send_company_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('company_sent_to_client_success'));
        } else {
            set_alert('danger', _l('company_sent_to_client_fail'));
        }
        if ($this->set_company_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('companies/list_companies/' . $id));
        }
    }

    /* Convert company to invoice */
    public function convert_to_invoice($id)
    {
        if (!has_permission('invoices', '', 'create')) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No company found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $invoiceid = $this->companies_model->convert_to_invoice($id, false, $draft_invoice);
        if ($invoiceid) {
            set_alert('success', _l('company_convert_to_invoice_successfully'));
            redirect(admin_url('invoices/list_invoices/' . $invoiceid));
        } else {
            if ($this->session->has_userdata('company_pipeline') && $this->session->userdata('company_pipeline') == 'true') {
                $this->session->set_flashdata('companyid', $id);
            }
            if ($this->set_company_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('companies/list_companies/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('companies', '', 'create')) {
            access_denied('companies');
        }
        if (!$id) {
            die('No company found');
        }
        $new_id = $this->companies_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('company_copied_successfully'));
            if ($this->set_company_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('companies/company/' . $new_id));
            }
        }
        set_alert('danger', _l('company_copied_fail'));
        if ($this->set_company_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('companies/company/' . $id));
        }
    }

    /* Delete company */
    public function delete($id)
    {
        if (!has_permission('companies', '', 'delete')) {
            access_denied('companies');
        }
        if (!$id) {
            redirect(admin_url('companies/list_companies'));
        }
        $success = $this->companies_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_company_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('company')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('company_lowercase')));
        }
        redirect(admin_url('companies/list_companies'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'companies', get_acceptance_info_array(true));
        }

        redirect(admin_url('companies/list_companies/' . $id));
    }

    /* Generates company PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_company($id);
        if (!$canView) {
            access_denied('Companies');
        } else {
            if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && $canView == false) {
                access_denied('Companies');
            }
        }
        if (!$id) {
            redirect(admin_url('companies/list_companies'));
        }
        $company        = $this->companies_model->get($id);
        $company_number = format_company_number($company->id);

        try {
            $pdf = company_pdf($company);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('company_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($company_number)) . '.pdf',
                            'company'  => $company,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (has_permission('companies', '', 'view') || has_permission('companies', '', 'view_own') || get_option('allow_staff_view_companies_assigned') == '1') {
            $data['company_states'] = $this->companies_model->get_states();
            $this->load->view('admin/companies/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_company($id);
        if (!$canView) {
            access_denied('Companies');
        } else {
            if (!has_permission('companies', '', 'view') && !has_permission('companies', '', 'view_own') && $canView == false) {
                access_denied('Companies');
            }
        }

        $data['userid']       = $id;
        $data['company'] = $this->get_company_data_ajax($id, true);
        $this->load->view('admin/companies/pipeline/company', $data);
    }

    public function update_pipeline()
    {
        if (has_permission('companies', '', 'edit') || has_permission('companies', '', 'edit_own')) {
            $this->companies_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'company_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('companies/list_companies'));
        }
    }

    public function pipeline_load_more()
    {
        $state = $this->input->get('state');
        $page   = $this->input->get('page');

        $companies = (new CompaniesPipeline($state))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($companies as $company) {
            $this->load->view('admin/companies/pipeline/_kanban_card', [
                'company' => $company,
                'state'   => $state,
            ]);
        }
    }

    public function set_company_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('company_pipeline')
                && $this->session->userdata('company_pipeline') == 'true') {
            $this->session->set_flashdata('companyid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('company_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('company_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }
/*
    public function get_staff($userid='')
    {
        $this->app->get_table_data(module_views_path('companies', 'admin/tables/staff'));
    }
*/
    public function table_staffs($client_id,$company = true)
    {
        if (
            !has_permission('companies', '', 'view')
            && !has_permission('companies', '', 'view_own')
            && get_option('allow_staff_view_companies_assigned') == 0
        ) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('companies', 'admin/tables/staff'), array('client_id'=>$client_id));
    }
    
    public function confirm_registration($client_id)
    {
        if (!is_admin()) {
            access_denied('Customer Confirm Registration, ID: ' . $client_id);
        }
        $this->companies_model->confirm_registration($client_id);
        set_alert('success', _l('customer_registration_successfully_confirmed'));
        redirect($_SERVER['HTTP_REFERER']);
    }

    /*
    public function get_relation_data()
    {
        if ($this->input->post()) {
            $type = $this->input->post('type');
            //$data = get_relation_data($type, '', $this->input->post('extra'));
            $data = apps_get_relation_data($type, '', $this->input->post('extra'));
            if ($this->input->post('rel_id')) {
                $rel_id = $this->input->post('rel_id');
            } else {
                $rel_id = '';
            }

            $relOptions = apps_init_relation_options($data, $type, $rel_id);
            echo json_encode($relOptions);
            die;
        }
    }
    */

    public function get_inspector_assignments($id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('companies', 'admin/tables/assignments'), [
                'id'       => $id,
                'rel_type' => $rel_type,
            ]);
        }
    }

    public function get_assignments($id, $rel_type)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('assignments', [
                'id'       => $id,
                'rel_type' => $rel_type,
            ]);
        }
    }


    /* Since version 1.0.2 add client assignment */
    public function add_assignment($rel_id_id, $rel_type)
    {
        $message    = '';
        $alert_type = 'warning';
        if ($this->input->post()) {
            $success = $this->companies_model->add_assignment($this->input->post(), $rel_id_id);
            if ($success) {
                $alert_type = 'success';
                $message    = _l('assignment_added_successfully');
            }
        }
        echo json_encode([
            'alert_type' => $alert_type,
            'message'    => $message,
        ]);
    }

    public function my_assignments()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('staff_assignments');
        }
    }

    public function assignments()
    {
        $this->load->model('staff_model');
        $data['members']   = $this->staff_model->get('', ['active' => 1]);
        $data['title']     = _l('assignments');
        $data['bodyclass'] = 'all-assignments';
        $this->load->view('admin/utilities/all_assignments', $data);
    }

    public function assignments_table()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('all_assignments');
        }
    }

    /* Since version 1.0.2 delete client assignment */
    public function delete_assignment($rel_id, $id, $rel_type)
    {
        if (!$id && !$rel_id) {
            die('No assignment found');
        }
        $success    = $this->companies_model->delete_assignment($id);
        $alert_type = 'warning';
        $message    = _l('assignment_failed_to_delete');
        if ($success) {
            $alert_type = 'success';
            $message    = _l('assignment_deleted');
        }
        echo json_encode([
            'alert_type' => $alert_type,
            'message'    => $message,
        ]);
    }

    public function get_assignment($id)
    {
        $assignment = $this->companies_model->get_assignments($id);
        if ($assignment) {
            if ($assignment->creator == get_staff_user_id() || is_admin()) {
                $assignment->date        = _dt($assignment->date);
                $assignment->description = clear_textarea_breaks($assignment->description);
                echo json_encode($assignment);
            }
        }
    }

    public function edit_assignment($id)
    {
        $assignment = $this->companies_model->get_assignments($id);
        if ($assignment && ($assignment->creator == get_staff_user_id() || is_admin()) && $assignment->isnotified == 0) {
            $success = $this->companies_model->edit_assignment($this->input->post(), $id);
            echo json_encode([
                    'alert_type' => 'success',
                    'message'    => ($success ? _l('updated_successfully', _l('assignment')) : ''),
                ]);
        }
    }



}
