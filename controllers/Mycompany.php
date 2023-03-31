<?php defined('BASEPATH') or exit('No direct script access allowed');

class Mycompany extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('companies_model');
        $this->load->model('clients_model');
    }

    /* Get all companies in case user go on index page */
    public function list($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('companies', 'admin/tables/table'));
        }
        $contact_id = get_contact_user_id();
        $user_id = get_user_id_by_contact_id($contact_id);
        $client = $this->clients_model->get($user_id);
        $data['companies'] = $this->companies_model->get_client_companies($client);
        $data['companyid']            = $id;
        $data['title']                 = _l('companies_tracking');

        $data['bodyclass'] = 'companies';
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/companies/companies');
        $this->layout();
    }

    public function show($id, $hash)
    {
        check_company_restrictions($id, $hash);
        $company = $this->companies_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($company->clientid);
        }

        $identity_confirmation_enabled = get_option('company_accept_identity_confirmation');

        if ($this->input->post('company_action')) {
            $action = $this->input->post('company_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->companies_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_company_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_company_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_company_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'companies', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Company PDF generator

        $company_number = format_company_number($company->id);
        /*
        if ($this->input->post('companypdf')) {
            try {
                $pdf = company_pdf($company);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$company_number = format_company_number($company->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $company_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_company_filename', mb_strtoupper(slug_it($company_number), 'UTF-8') . '.pdf', $company);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $company_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['company_number']              = $company_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['company']                     = hooks()->apply_filters('company_html_pdf_data', $company);
        $data['bodyclass']                     = 'viewcompany';
        $data['client_company']                = $this->clients_model->get($company->clientid)->company;
        $setSize = get_option('company_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['company_members']  = $this->companies_model->get_company_members($company->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('company_number') . ' : ' . $company_number ."\r\n";
        $qrcode_data .= _l('company_date') . ' : ' . $company->date ."\r\n";
        $qrcode_data .= _l('company_datesend') . ' : ' . $company->datesend ."\r\n";
        //$qrcode_data .= _l('company_assigned_string') . ' : ' . get_staff_full_name($company->assigned) ."\r\n";
        //$qrcode_data .= _l('company_url') . ' : ' . site_url('companies/show/'. $company->id .'/'.$company->hash) ."\r\n";


        $company_path = get_upload_path_by_type('companies') . $company->id . '/';
        _maybe_create_upload_path('uploads/companies');
        _maybe_create_upload_path('uploads/companies/'.$company_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $company_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/companies/'.$company_path .'assigned-'.$company_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/companies/companyhtml');
        add_views_tracking('company', $id);
        hooks()->do_action('company_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }


    public function office($id, $hash)
    {
        check_company_restrictions($id, $hash);
        $company = $this->companies_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($company->clientid);
        }

        $identity_confirmation_enabled = get_option('company_accept_identity_confirmation');

        if ($this->input->post('company_action')) {
            $action = $this->input->post('company_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->companies_model->mark_action_state($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_company_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_company_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_company_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'companies', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Company PDF generator

        $company_number = format_company_number($company->id);
        /*
        if ($this->input->post('companypdf')) {
            try {
                $pdf = company_pdf($company);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$company_number = format_company_number($company->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $company_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_company_filename', mb_strtoupper(slug_it($company_number), 'UTF-8') . '.pdf', $company);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $company_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['company_number']              = $company_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['company']                     = hooks()->apply_filters('company_html_pdf_data', $company);
        $data['bodyclass']                     = 'viewcompany';
        $data['client_company']                = $this->clients_model->get($company->clientid)->company;
        $setSize = get_option('company_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['company_members']  = $this->companies_model->get_company_members($company->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('company_number') . ' : ' . $company_number ."\r\n";
        $qrcode_data .= _l('company_date') . ' : ' . $company->date ."\r\n";
        $qrcode_data .= _l('company_datesend') . ' : ' . $company->datesend ."\r\n";
        //$qrcode_data .= _l('company_assigned_string') . ' : ' . get_staff_full_name($company->assigned) ."\r\n";
        //$qrcode_data .= _l('company_url') . ' : ' . site_url('companies/show/'. $company->id .'/'.$company->hash) ."\r\n";


        $company_path = get_upload_path_by_type('companies') . $company->id . '/';
        _maybe_create_upload_path('uploads/companies');
        _maybe_create_upload_path('uploads/companies/'.$company_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $company_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/companies/'.$company_path .'assigned-'.$company_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/companies/company_office_html');
        add_views_tracking('company', $id);
        hooks()->do_action('company_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
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
            redirect(admin_url('companies'));
        }
        $company        = $this->companies_model->get($id);
        $company_number = format_company_number($company->id);
        
        $company->assigned_path = FCPATH . get_company_upload_path('company').$company->id.'/assigned-'.$company_number.'.png';
        $company->acceptance_path = FCPATH . get_company_upload_path('company').$company->id .'/'.$company->signature;
        
        $company->client_company = $this->clients_model->get($company->clientid)->company;
        $company->acceptance_date_string = _dt($company->acceptance_date);


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

    /* Generates company PDF and senting to email  */
    public function office_pdf($id)
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
            redirect(admin_url('companies'));
        }
        $company        = $this->companies_model->get($id);
        $company_number = format_company_number($company->id);
        
        $company->assigned_path = FCPATH . get_company_upload_path('company').$company->id.'/assigned-'.$company_number.'.png';
        $company->acceptance_path = FCPATH . get_company_upload_path('company').$company->id .'/'.$company->signature;
        
        $company->client_company = $this->clients_model->get($company->clientid)->company;
        $company->acceptance_date_string = _dt($company->acceptance_date);


        try {
            $pdf = company_office_pdf($company);
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
                            'file_name' => str_replace("SCH", "SCH-UPT", mb_strtoupper(slug_it($company_number)) . '.pdf'),
                            'company'  => $company,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }
}
