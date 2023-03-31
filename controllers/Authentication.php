<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Authentication extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        hooks()->do_action('clients_authentication_constructor', $this);
        $this->load->model('companies_model');
    }

    public function index()
    {
        $this->login();
    }

    // Added for backward compatibilies
    public function admin()
    {
        redirect(admin_url('authentication'));
    }

    public function login()
    {
        if (is_client_logged_in()) {
            redirect(admin_url());
        }

        $this->form_validation->set_rules('password', _l('clients_login_password'), 'required');
        $this->form_validation->set_rules('email', _l('clients_login_email'), 'trim|required|valid_email');

        if (show_recaptcha_in_customers_area()) {
            $this->form_validation->set_rules('g-recaptcha-response', 'Captcha', 'callback_recaptcha');
        }
        if ($this->form_validation->run() !== false) {
            $this->load->model('Authentication_model');

            $success = $this->Authentication_model->login(
                $this->input->post('email'),
                $this->input->post('password', false),
                $this->input->post('remember'),
                false
            );

            if (is_array($success) && isset($success['memberinactive'])) {
                set_alert('danger', _l('inactive_account'));
                redirect(admin_url('authentication'));
            } elseif ($success == false) {
                set_alert('danger', _l('client_invalid_username_or_password'));
                redirect(admin_url('authentication'));
            }

            if ($this->input->post('language') && $this->input->post('language') != '') {
                set_contact_language($this->input->post('language'));
            }

            $this->load->model('announcements_model');
            $this->announcements_model->set_announcements_as_read_except_last_one(get_contact_user_id());

            hooks()->do_action('after_contact_login');

            maybe_redirect_to_previous_url();
            redirect(admin_url());
        }
        if (get_option('allow_registration') == 1) {
            $data['title'] = _l('clients_login_heading_register');
        } else {
            $data['title'] = _l('clients_login_heading_no_register');
        }
        $data['bodyclass'] = 'customers_login';

        $this->data($data);
        $this->view('login');
        $this->layout();
    }

    public function register()
    {
        if (get_option('allow_registration') == 1 || is_staff_logged_in()) {
            redirect(admin_url());
        }
        

        $honeypot = get_option('enable_honeypot_spam_validation') == 1;

        $fields = [
            'firstname' => $honeypot ? 'firstnamemjxw' : 'firstname',
            'lastname'  => $honeypot ? 'lastnamemjxw' : 'lastname',
            'email'     => $honeypot ? 'emailmjxw' : 'email',
            'company'   => $honeypot ? 'companymjxw' : 'company',
        ];

        if (get_option('company_is_required') == 1) {
            $this->form_validation->set_rules($fields['company'], _l('client_company'), 'required');
        }

        if (is_gdpr() && get_option('gdpr_enable_terms_and_conditions') == 1) {
            $this->form_validation->set_rules(
                'accept_terms_and_conditions',
                _l('terms_and_conditions'),
                'required',
                ['required' => _l('terms_and_conditions_validation')]
            );
        }

        $this->form_validation->set_rules($fields['firstname'], _l('client_firstname'), 'required');
        $this->form_validation->set_rules($fields['lastname'], _l('client_lastname'), 'required');
        $this->form_validation->set_rules($fields['company'], _l('client_company'), 'trim|required|is_unique[' . db_prefix() . 'clients.company]');
        $this->form_validation->set_rules($fields['email'], _l('client_email'), 'trim|required|is_unique[' . db_prefix() . 'staff.email]|valid_email');
        $this->form_validation->set_rules('password', _l('clients_register_password'), 'required');
        $this->form_validation->set_rules('passwordr', _l('clients_register_password_repeat'), 'required|matches[password]');

        if (show_recaptcha_in_customers_area()) {
            $this->form_validation->set_rules('g-recaptcha-response', 'Captcha', 'callback_recaptcha');
        }

        $custom_fields = get_custom_fields('customers', [
            'show_on_client_portal' => 1,
            'required'              => 1,
        ]);

        $custom_fields_contacts = get_custom_fields('contacts', [
            'show_on_client_portal' => 1,
            'required'              => 1,
        ]);

        foreach ($custom_fields as $field) {
            $field_name = 'custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']';
            if ($field['type'] == 'checkbox' || $field['type'] == 'multiselect') {
                $field_name .= '[]';
            }
            $this->form_validation->set_rules($field_name, $field['name'], 'required');
        }

        foreach ($custom_fields_contacts as $field) {
            $field_name = 'custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']';
            if ($field['type'] == 'checkbox' || $field['type'] == 'multiselect') {
                $field_name .= '[]';
            }
            $this->form_validation->set_rules($field_name, $field['name'], 'required');
        }


        if ($this->input->post()) {
            if ($honeypot &&
                count(array_filter($this->input->post(['email', 'firstname', 'lastname', 'company']))) > 0) {
                show_404();
            }

            $calc = hash_hmac('sha256', $_SESSION['token_key'], $_SESSION['token_value']);
            
            if (!hash_equals($calc, $this->input->post('csrf_token_key'))) {
                show_404();
            }

            if ($this->form_validation->run() !== false) {
                $data      = $this->input->post();
                $countryId = is_numeric($data['country']) ? $data['country'] : 0;

                if (is_automatic_calling_codes_enabled()) {
                    $customerCountry = get_country($countryId);

                    if ($customerCountry) {
                        $callingCode = '+' . ltrim($customerCountry->calling_code, '+');

                        if (startsWith($data['contact_phonenumber'], $customerCountry->calling_code)) { // with calling code but without the + prefix
                            $data['contact_phonenumber'] = '+' . $data['contact_phonenumber'];
                        } elseif (!startsWith($data['contact_phonenumber'], $callingCode)) {
                            $data['contact_phonenumber'] = $callingCode . $data['contact_phonenumber'];
                        }
                    }
                }

                define('CONTACT_REGISTERING', true);


                $data['is_company'] = '1';
                $next_company_number = get_option('next_company_number');
                $_format = get_option('company_number_format');
                $_prefix = get_option('company_prefix');
                
                $prefix  = isset($company->prefix) ? $company->prefix : $_prefix;
                $number_format  = isset($company->number_format) ? $company->number_format : $_format;
                $number  = isset($company->number) ? $company->number : $next_company_number;

                $data['prefix'] = $prefix;
                $data['number_format'] = $number_format;
                
                $default_company_role = get_option('default_company_role');
                //include_once(FCPATH .'/application/models/Roles_model.php');
                $this->load->model('roles_model');
                $permissions = $this->roles_model->get($default_company_role)->permissions;
               
                $clientid = $this->companies_model->add([
                      'is_company'          => 1,
                      'prefix'              => $data['prefix'],
                      'number_format'       => $data['number_format'],
                      'number'              => $next_company_number,
                      'billing_street'      => $data['address'],
                      'billing_city'        => $data['city'],
                      'billing_state'       => $data['state'],
                      'billing_zip'         => $data['zip'],
                      'billing_country'     => $countryId,
                      'firstname'           => $data[$fields['firstname']],
                      'lastname'            => $data[$fields['lastname']],
                      'email'               => $data[$fields['email']],
                      'contact_phonenumber' => $data['contact_phonenumber'] ,
                      'website'             => $data['website'],
                      'title'               => $data['title'],
                      'password'            => $data['passwordr'],
                      'company'             => $data[$fields['company']],
                      'vat'                 => isset($data['vat']) ? $data['vat'] : '',
                      'phonenumber'         => $data['phonenumber'],
                      'country'             => $data['country'],
                      'city'                => $data['city'],
                      'address'             => $data['address'],
                      'zip'                 => $data['zip'],
                      'state'               => $data['state'],
                      'permissions'         => $permissions,
                      'role'                => $default_company_role,
                      'custom_fields'       => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
                      'default_language'    => (get_contact_language() != '') ? get_contact_language() : get_option('active_language'),
                ], true);

                if ($clientid) {
                    hooks()->do_action('after_client_register', $clientid);

                    if (get_option('customers_register_require_confirmation') == '1') {
                        send_customer_registered_email_to_administrators($clientid);

                        $this->clients_model->require_confirmation($clientid);
                        set_alert('success', _l('customer_register_account_confirmation_approval_notice'));
                        redirect(admin_url('authentication'));
                    }

                    $this->load->model('authentication_model');

                    $logged_in = $this->authentication_model->login(
                        $data[$fields['email']],
                        $this->input->post('password', false),
                        false,
                        false
                    );

                    $redUrl = admin_url();

                    if ($logged_in) {
                        hooks()->do_action('after_client_register_logged_in', $clientid);
                        set_alert('success', _l('clients_successfully_registered'));
                    } else {
                        set_alert('warning', _l('clients_account_created_but_not_logged_in'));
                        $redUrl = admin_url('authentication');
                    }

                    send_customer_registered_email_to_administrators($clientid);
                    redirect($redUrl);
                }
            }
        }

        $this->disableNavigation();

        $data['title']     = _l('clients_register_heading');
        $data['bodyclass'] = 'register';
        $data['honeypot']  = $honeypot;
        $data['fields']    = $fields;
        $data['is_company']    = 1;
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/register');
        $this->layout();
    }

    public function forgot_password()
    {
        if (is_client_logged_in()) {
            redirect(admin_url());
        }

        $this->form_validation->set_rules(
            'email',
            _l('customer_forgot_password_email'),
            'trim|required|valid_email|callback_contact_email_exists'
        );

        if ($this->input->post()) {
            if ($this->form_validation->run() !== false) {
                $this->load->model('Authentication_model');
                $success = $this->Authentication_model->forgot_password($this->input->post('email'));
                if (is_array($success) && isset($success['memberinactive'])) {
                    set_alert('danger', _l('inactive_account'));
                } elseif ($success == true) {
                    set_alert('success', _l('check_email_for_resetting_password'));
                } else {
                    set_alert('danger', _l('error_setting_new_password_key'));
                }
                redirect(admin_url('authentication/forgot_password'));
            }
        }
        $data['title'] = _l('customer_forgot_password');
        $this->data($data);
        $this->view('forgot_password');

        $this->layout();
    }

    public function reset_password($staff, $userid, $new_pass_key)
    {
        $this->load->model('Authentication_model');
        if (!$this->Authentication_model->can_reset_password($staff, $userid, $new_pass_key)) {
            set_alert('danger', _l('password_reset_key_expired'));
            redirect(admin_url('authentication'));
        }

        $this->form_validation->set_rules('password', _l('customer_reset_password'), 'required');
        $this->form_validation->set_rules('passwordr', _l('customer_reset_password_repeat'), 'required|matches[password]');
        if ($this->input->post()) {
            if ($this->form_validation->run() !== false) {
                hooks()->do_action('before_user_reset_password', [
                    'staff'  => $staff,
                    'userid' => $userid,
                ]);
                $success = $this->Authentication_model->reset_password(
                    0,
                    $userid,
                    $new_pass_key,
                    $this->input->post('passwordr', false)
                );
                if (is_array($success) && $success['expired'] == true) {
                    set_alert('danger', _l('password_reset_key_expired'));
                } elseif ($success == true) {
                    hooks()->do_action('after_user_reset_password', [
                        'staff'  => $staff,
                        'userid' => $userid,
                    ]);
                    set_alert('success', _l('password_reset_message'));
                } else {
                    set_alert('danger', _l('password_reset_message_fail'));
                }
                redirect(admin_url('authentication'));
            }
        }
        $data['title'] = _l('admin_auth_reset_password_heading');
        $this->data($data);
        $this->view('reset_password');
        $this->layout();
    }

    public function logout()
    {
        $this->load->model('authentication_model');
        $this->authentication_model->logout(false);
        hooks()->do_action('after_client_logout');
        redirect(admin_url('authentication'));
    }

    public function contact_email_exists($email = '')
    {
        $this->db->where('email', $email);
        $total_rows = $this->db->count_all_results(db_prefix() . 'contacts');

        if ($total_rows == 0) {
            $this->form_validation->set_message('contact_email_exists', _l('auth_reset_pass_email_not_found'));

            return false;
        }

        return true;
    }

    public function recaptcha($str = '')
    {
        return do_recaptcha_validation($str);
    }

    public function change_language($lang = '')
    {
        if (is_language_disabled()) {
            redirect(admin_url());
        }

        set_contact_language($lang);

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url());
        }
    }
}
