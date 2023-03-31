<?php

use app\services\utilities\Arr;
use app\services\AbstractKanban;
use app\services\companies\CompaniesPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

//class Companies_model extends App_Model
class Companies_model extends Clients_Model
{
    private $states;
    private $contact_columns;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->states = hooks()->apply_filters('before_set_company_states', [
            1,
            2,
            5,
            3,
            4,
        ]);

        $this->load->model('clients_model');
        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'program_emails', 'ticket_emails', 'is_primary']);

        $this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }

    private function check_zero_columns($data)
    {
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }

        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }

        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }

        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        return $data;
    }

    /**
     * Get unique sale agent for companies / Used for filters
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'companies JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'companies.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,'. db_prefix() . 'clients.userid as userid,');

        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {

            $this->db->where(db_prefix() . 'clients.userid', $id);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            if ($client && get_option('company_requires_vat_number_field') == 0) {
                $client->vat = null;
            }

            $this->load->model('email_schedule_model');
            $client->scheduled_email = $this->email_schedule_model->get($id, 'company');

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('company', 'asc');
        $result = $this->db->get(db_prefix() . 'clients')->result_array();
        return $result;
    }

    /**
     * Get company states
     * @return array
     */
    public function get_states()
    {
        return $this->states;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $company = $this->db->get(db_prefix() . 'companies')->row();

        if ($company) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'companies', ['signature' => null]);

            if (!empty($company->signature)) {
                unlink(get_upload_path_by_type('company') . $id . '/' . $company->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Copy company
     * @param mixed $id company id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_company                       = $this->get($id);
        $new_company_data               = [];
        $new_company_data['clientid']   = $_company->clientid;
        $new_company_data['program_id'] = $_company->program_id;
        $new_company_data['number']     = get_option('next_company_number');
        $new_company_data['date']       = _d(date('Y-m-d'));
        $new_company_data['expirydate'] = null;

        if ($_company->expirydate && get_option('company_due_after') != 0) {
            $new_company_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('company_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_company_data['show_quantity_as'] = $_company->show_quantity_as;
        $new_company_data['currency']         = $_company->currency;
        $new_company_data['subtotal']         = $_company->subtotal;
        $new_company_data['total']            = $_company->total;
        $new_company_data['adminnote']        = $_company->adminnote;
        $new_company_data['adjustment']       = $_company->adjustment;
        $new_company_data['discount_percent'] = $_company->discount_percent;
        $new_company_data['discount_total']   = $_company->discount_total;
        $new_company_data['discount_type']    = $_company->discount_type;
        $new_company_data['terms']            = $_company->terms;
        $new_company_data['sale_agent']       = $_company->sale_agent;
        $new_company_data['reference_no']     = $_company->reference_no;
        // Since version 1.0.6
        $new_company_data['billing_street']   = clear_textarea_breaks($_company->billing_street);
        $new_company_data['billing_city']     = $_company->billing_city;
        $new_company_data['billing_state']    = $_company->billing_state;
        $new_company_data['billing_zip']      = $_company->billing_zip;
        $new_company_data['billing_country']  = $_company->billing_country;
        $new_company_data['shipping_street']  = clear_textarea_breaks($_company->shipping_street);
        $new_company_data['shipping_city']    = $_company->shipping_city;
        $new_company_data['shipping_state']   = $_company->shipping_state;
        $new_company_data['shipping_zip']     = $_company->shipping_zip;
        $new_company_data['shipping_country'] = $_company->shipping_country;
        if ($_company->include_shipping == 1) {
            $new_company_data['include_shipping'] = $_company->include_shipping;
        }
        $new_company_data['show_shipping_on_company'] = $_company->show_shipping_on_company;
        // Set to unpaid state automatically
        $new_company_data['state']     = 1;
        $new_company_data['clientnote'] = $_company->clientnote;
        $new_company_data['adminnote']  = '';
        $new_company_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_company->items as $item) {
            $new_company_data['newitems'][$key]['description']      = $item['description'];
            $new_company_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_company_data['newitems'][$key]['qty']              = $item['qty'];
            $new_company_data['newitems'][$key]['unit']             = $item['unit'];
            $new_company_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_company_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_company_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_company_data['newitems'][$key]['rate']  = $item['rate'];
            $new_company_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_company_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_company_data);
        if ($id) {
            $custom_fields = get_custom_fields('company');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_company->id, $field['id'], 'company', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'company',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_company->id, 'company');
            handle_tags_save($tags, $id, 'company');

            log_activity('Copied company ' . format_company_number($_company->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs companies totals state
     * @param array $data
     * @return array
     */
    public function get_companies_total($data)
    {
        $states            = $this->get_states();
        $has_permission_view = has_permission('companies', '', 'view');
        $this->load->model('currencies_model');

        $sql = 'SELECT';
        foreach ($states as $company_state) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'companies WHERE state=' . $company_state;
            //$sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $company_state . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $state => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['state']        = $state;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * @param array $_POST data
     * @param client_request is this request from the customer area
     * @return integer Insert ID
     * Add new client to database
     */
    public function add($data,  $withContact = false)
    {
        $contact_data = [];

        foreach ($this->contact_columns as $field) {
            if (isset($data[$field])) {
                $contact_data[$field] = $data[$field];
                // Phonenumber is also used for the company profile
                if ($field != 'phonenumber') {
                    unset($data[$field]);
                }
            }
        }

        if (isset($data['groups_in'])) {
            $groups_in = $data['groups_in'];
            unset($data['groups_in']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['hash'] = app_generate_hash();

        if (is_staff_logged_in()) {
            $data['addedfrom'] = get_staff_user_id();
        }

        $groups_in     = Arr::pull($data, 'groups_in') ?? [];
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        // From customer profile register
        if (isset($data['contact_phonenumber'])) {
            $contact_data['phonenumber'] = $data['contact_phonenumber'];
            unset($data['contact_phonenumber']);
        }

        // New filter action
        $data = hooks()->apply_filters('before_company_added', $data);

        if (isset($data['role'])) {
            $contact_data['role'] = $data['role'];
            unset($data['role']);
        }
            unset($data['permissions']);

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_company_name_exist($data['company']);
            if($company_name_exist){
                return;
            }
            $this->db->insert(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        $userid = $this->db->insert_id();
        if ($userid) {

            if (count($custom_fields) > 0) {
                $_custom_fields = $custom_fields;
                // Possible request from the register area with 2 types of custom fields for contact and for comapny/customer
                if (count($custom_fields) == 2) {
                    unset($custom_fields);
                    $custom_fields['customers']                = $_custom_fields['customers'];
                    $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                } elseif (count($custom_fields) == 1) {
                    if (isset($_custom_fields['contacts'])) {
                        $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                        unset($custom_fields);
                    }
                }

                handle_custom_fields_post($userid, $custom_fields);
            }
            $contact_data['client_id'] = $userid;
            $contact_data['client_type'] = 'company';
            $contact_data['is_primary'] = '1';

            /**
             * Used in Import, Lead Convert, Register
             */
            if ($withContact == true) {
                //$contact_id = $this->add_staff($contact_data, $userid, $withContact);
                $contact_id = $this->add_staff($contact_data, $userid, $withContact);
            }

            // Update next company number in settings
            $this->db->where('name', 'next_company_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            $log = 'ID: ' . $userid;

            if ($log == '' && isset($contact_id)) {
                $log = get_staff_full_name($contact_id);
            }

            $isStaff = null;
            if (!is_client_logged_in() && is_staff_logged_in()) {
                $log .= ', From Staff: ' . get_staff_user_id();
                $isStaff = get_staff_user_id();
            }
            $company = $this->get($userid);
            if ($company->assigned != 0) {
                if ($company->assigned != get_staff_user_id()) {
                    $notified = add_notification([
                        'description'     => 'not_company_already_created',
                        'touserid'        => get_staff_user_id(),
                        'fromuserid'      => get_staff_user_id(),
                        'link'            => 'company/list_company/' . $insert_id .'#' . $insert_id,
                        'additional_data' => serialize([
                            $company->subject,
                        ]),
                    ]);
                    if ($notified) {
                        pusher_trigger_notification([get_staff_user_id()]);
                    }
                }
            }
            hooks()->do_action('after_company_added', $userid);

            log_activity('New company Created [' . $log . ']', $isStaff);
        }

        return $userid;
    }

    /**
     * Add new staff member
     * @param array $data staff $_POST data
     */
   /**
     * Add new staff member
     * @param array $data staff $_POST data
     */
    public function add_staff($data)
    {
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        // First check for all cases if the email exists.
        $data = hooks()->apply_filters('before_create_staff_member', $data);

        $this->db->where('email', $data['email']);
        $email = $this->db->get(db_prefix() . 'staff')->row();

        if ($email) {
            die('Email already exists');
        }

        $data['admin'] = 0;

        if (is_admin()) {
            if (isset($data['administrator'])) {
                $data['admin'] = 1;
                unset($data['administrator']);
            }
        }

        $send_welcome_email = true;
        $original_password  = $data['password'];
        if (!isset($data['send_welcome_email'])) {
            $send_welcome_email = false;
        } else {
            unset($data['send_welcome_email']);
        }

        $data['password']    = app_hash_password($data['password']);
        $data['datecreated'] = date('Y-m-d H:i:s');
        if (isset($data['departments'])) {
            $departments = $data['departments'];
            unset($data['departments']);
        }

        $permissions = [];
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['is_not_staff'] = 1;

        $this->db->insert(db_prefix() . 'staff', $data);
        $staffid = $this->db->insert_id();
        log_activity('$staffid ' .$staffid ? $staffid : '--');
        if ($staffid) {
            $slug = $data['firstname'] . ' ' . $data['lastname'];

            if ($slug == ' ') {
                $slug = 'unknown-' . $staffid;
            }

            if ($send_welcome_email == true) {
                send_mail_template('staff_created', $data['email'], $staffid, $original_password);
            }

            $this->db->where('staffid', $staffid);
            $this->db->update(db_prefix() . 'staff', [
                'media_path_slug' => slug_it($slug),
            ]);

            if (isset($custom_fields)) {
                handle_custom_fields_post($staffid, $custom_fields);
            }
            if (isset($departments)) {
                foreach ($departments as $department) {
                    $this->db->insert(db_prefix() . 'staff_departments', [
                        'staffid'      => $staffid,
                        'departmentid' => $department,
                    ]);
                }
            }

            // Delete all staff permission if is admin we dont need permissions stored in database (in case admin check some permissions)
            $this->update_permissions($data['admin'] == 1 ? [] : $permissions, $staffid);

            log_activity('New Staff Member Added [ID: ' . $staffid . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']');

            // Get all announcements and set it to read.
            $this->db->select('announcementid');
            $this->db->from(db_prefix() . 'announcements');
            $this->db->where('showtostaff', 1);
            $announcements = $this->db->get()->result_array();
            foreach ($announcements as $announcement) {
                $this->db->insert(db_prefix() . 'dismissed_announcements', [
                    'announcementid' => $announcement['announcementid'],
                    'staff'          => 1,
                    'userid'         => $staffid,
                ]);
            }
            hooks()->do_action('staff_member_created', $staffid);

            return $staffid;
        }

        return false;
    }

    public function update_permissions($permissions, $id)
    {
        $this->db->where('staff_id', $id);
        $this->db->delete('staff_permissions');

        $is_staff_member = is_staff_member($id);

        foreach ($permissions as $feature => $capabilities) {
            foreach ($capabilities as $capability) {

                // Maybe do this via hook.
                if ($feature == 'leads' && !$is_staff_member) {
                    continue;
                }

                $this->db->insert('staff_permissions', ['staff_id' => $id, 'feature' => $feature, 'capability' => $capability]);
            }
        }

        return true;
    }

    /**
     * Add new contact
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register, convert to lead
     */
    public function add_contact($data, $customer_id, $not_manual_request = false)
    {
        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }

        $data['email_verified_at'] = date('Y-m-d H:i:s');

        $send_welcome_email = true;

        if (isset($data['donotsendwelcomeemail'])) {
            $send_welcome_email = false;
        }

        if (defined('CONTACT_REGISTERING')) {
            $send_welcome_email = true;

            // Do not send welcome email if confirmation for registration is enabled
            if (get_option('customers_register_require_confirmation') == '1') {
                $send_welcome_email = false;
            }

            // If client register set this contact as primary
            $data['is_primary'] = 1;

            if (is_email_verification_enabled() && !empty($data['email'])) {
                // Verification is required on register
                $data['email_verified_at']      = null;
                $data['email_verification_key'] = app_generate_hash();
            }
        }

        if (isset($data['is_primary'])) {
            $data['is_primary'] = 1;
            $this->db->where('userid', $customer_id);
            $this->db->update(db_prefix() . 'contacts', [
                'is_primary' => 0,
            ]);
        } else {
            $data['is_primary'] = 0;
        }

        $password_before_hash = '';
        $data['client_id']       = $customer_id;
        if (isset($data['password'])) {
            $password_before_hash = $data['password'];
            $data['password']     = app_hash_password($data['password']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');

        if (!$not_manual_request) {
            $data['invoice_emails']     = isset($data['invoice_emails']) ? 1 :0;
            $data['estimate_emails']    = isset($data['estimate_emails']) ? 1 :0;
            $data['credit_note_emails'] = isset($data['credit_note_emails']) ? 1 :0;
            $data['contract_emails']    = isset($data['contract_emails']) ? 1 :0;
            $data['task_emails']        = isset($data['task_emails']) ? 1 :0;
            $data['project_emails']     = isset($data['project_emails']) ? 1 :0;
            $data['ticket_emails']      = isset($data['ticket_emails']) ? 1 :0;
        }

        $data['email'] = trim($data['email']);

        $data = hooks()->apply_filters('before_create_contact', $data);

        $this->db->insert(db_prefix() . 'staff', $data);
        $contact_id = $this->db->insert_id();

        if ($contact_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($contact_id, $custom_fields);
            }
            // request from admin area
            if (!isset($permissions) && $not_manual_request == false) {
                $permissions = [];
            } elseif ($not_manual_request == true) {
                $permissions         = [];
                $_permissions        = get_staff_permissions();
                $default_permissions = @unserialize(get_option('default_contact_permissions'));
                if (is_array($default_permissions)) {
                    foreach ($_permissions as $permission) {
                        if (in_array($permission['id'], $default_permissions)) {
                            array_push($permissions, $permission['id']);
                        }
                    }
                }
            }

            if ($not_manual_request == true) {
                // update all email notifications to 0
                $this->db->where('id', $contact_id);
                $this->db->update(db_prefix() . 'contacts', [
                    'invoice_emails'     => 0,
                    'estimate_emails'    => 0,
                    'credit_note_emails' => 0,
                    'contract_emails'    => 0,
                    'task_emails'        => 0,
                    'project_emails'     => 0,
                    'ticket_emails'      => 0,
                ]);
            }
            foreach ($permissions as $permission) {
                $this->db->insert(db_prefix() . 'contact_permissions', [
                    'userid'        => $contact_id,
                    'permission_id' => $permission,
                ]);

                // Auto set email notifications based on permissions
                if ($not_manual_request == true) {
                    if ($permission == 6) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['project_emails' => 1, 'task_emails' => 1]);
                    } elseif ($permission == 3) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['contract_emails' => 1]);
                    } elseif ($permission == 2) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['estimate_emails' => 1]);
                    } elseif ($permission == 1) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['invoice_emails' => 1, 'credit_note_emails' => 1]);
                    } elseif ($permission == 5) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['ticket_emails' => 1]);
                    }
                }
            }

            if ($send_welcome_email == true && !empty($data['email'])) {
                send_mail_template(
                    'customer_created_welcome_mail',
                    $data['email'],
                    $data['userid'],
                    $contact_id,
                    $password_before_hash
                );
            }

            if ($send_set_password_email) {
                $this->authentication_model->set_password_email($data['email'], 0);
            }

            if (defined('CONTACT_REGISTERING')) {
                $this->send_verification_email($contact_id);
            } else {
                // User already verified because is added from admin area, try to transfer any tickets
                $this->load->model('tickets_model');
                $this->tickets_model->transfer_email_tickets_to_contact($data['email'], $contact_id);
            }

            log_activity('Contact Created [ID: ' . $contact_id . ']');

            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;
        }

        return false;
    }

    /**
     * Get company surveyors id
     * @param mixed $id item id
     * @return object
     */

    public function get_company_surveyors($id ='')
    {
        if($id){
            $this->db->where('surveyor_id', $id);
        }

        return $this->db->get(db_prefix() . 'company_surveyors')->row();
    }

    public function get_company_companies($id ='')
    {
        if($id){
            $this->db->where('company_id', $id);
        }

        return $this->db->get(db_prefix() . 'company_companies')->row();
    }

    public function check_company_name_exist($company){
        $this->db->select('company');
        $this->db->where('company', $company);
        $result = $this->db->get(db_prefix(). 'clients')->num_rows();
        if($result>0){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */

    public function update($data, $id, $client_request = false)
    {
        $updated = false;
        $data    = $this->check_zero_columns($data);
        $origin = $this->get($id);
        unset($data['inspector_staff_id']);
        $data = hooks()->apply_filters('before_client_updated', $data, $id);

        $groups_in                     = Arr::pull($data, 'groups_in') ?? false;

        //trigger exception in a "try" block
        try {
            $company_name_exist = $this->check_company_name_exist($data['company']);
            if($company_name_exist && ($origin->company!=$data['company'])){
                return;
            }
            $this->db->where('userid', $id);
            $this->db->update(db_prefix() . 'clients', $data);
        }

        //catch exception
        catch(Exception $e) {
          echo 'Message: ' .$e->getMessage();
        }


        if ($this->db->affected_rows() > 0) {
            $updated = true;
            $company = $this->get($id);

            $fields = array('company', 'vat','siup', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'phonenumber');
            $custom_data = '';
            foreach ($fields as $field) {
                if ($origin->$field != $company->$field) {
                    $custom_data .= str_replace('_', ' ', $field) .' '. $origin->$field . ' to ' .$company->$field .'<br />';
                }
            }
            $this->log_company_activity($origin->userid, 'company_activity_changed', false, serialize([
                '<custom_data>'. $custom_data .'</custom_data>',
            ]));
        }

        if ($this->client_groups_model->sync_customer_groups($id, $groups_in)) {
            $updated = true;
        }

        hooks()->do_action('client_updated', [
            'id'                            => $id,
            'data'                          => $data,
            'update_all_other_transactions' => $update_all_other_transactions,
            'groups_in'                     => $groups_in,
            'updated'                       => &$updated,
        ]);

        if ($updated) {
            log_activity('Customer Info Updated [ID: ' . $id . ']');
        }

        return $company;
    }

    public function mark_action_state($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'companies', [
            'state' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $company = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $company->addedfrom);
                $this->db->or_where('staffid', $company->sale_agent);
                $staff_company = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($company->clientid)
                    : get_staff_user_id();

                if ($action == 4) {
                    if (get_option('company_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_company_activity($id, 'company_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_company_activity($id, 'company_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission companies
                    $contacts = $this->clients_model->get_staffs($company->clientid, ['active' => 1, 'company_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('company_accepted_to_customer', $company, $contact);
                    }

                    foreach ($staff_company as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_company_customer_accepted',
                            'link'            => 'companies/list_companies/' . $id,
                            'additional_data' => serialize([
                                format_company_number($company->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('company_accepted_to_staff', $company, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('company_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_company as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_company_customer_declined',
                            'link'            => 'companies/list_companies/' . $id,
                            'additional_data' => serialize([
                                format_company_number($company->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined company
                        send_mail_template('company_declined_to_staff', $company, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_company_activity($id, 'company_activity_client_declined', true);
                    hooks()->do_action('company_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'companies', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked company
                $this->log_company_activity($id, 'company_activity_marked', false, serialize([
                    '<state>' . $action . '</state>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get company attachments
     * @param mixed $company_id
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($company_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $company_id);
        }
        $this->db->where('rel_type', 'company');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete company attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('company') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('company Attachment Deleted [companyID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('company') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('company') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('company') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete company items and all connections
     * @param mixed $id companyid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_company') == 1 && $simpleDelete == false) {
            if (!is_last_company($id)) {
                return false;
            }
        }
        $company = $this->get($id);
        if (!is_null($company->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_company_delete_error' => true,
            ];
        }
        hooks()->do_action('before_company_deleted', $id);

        $number = format_company_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'companies');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($company->short_link)) {
                app_archive_short_link($company->short_link);
            }

            if (get_option('company_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_company_number = get_option('next_company_number');
                if ($current_next_company_number > 1) {
                    // Decrement next company number to
                    $this->db->where('name', 'next_company_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                    'company_id' => $id,
                ]) > 0) {
                $this->db->where('company_id', $id);
                $company = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $company->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'company_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'company');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="company" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'company');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'company');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'company');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'company');
            $this->db->delete(db_prefix() . 'company_activity');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'company');
            $this->db->delete('scheduled_emails');

            // Get related tasks
            $this->db->where('rel_type', 'company');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('companies Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set company to sent when email is successfuly sended to client
     * @param mixed $id companyid
     */
    public function set_company_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'companies', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_company_activity($id, 'invoice_company_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update company state to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'companies', [
            'state' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'company');
        $this->db->delete('scheduled_emails');
    }

    /**
     * Send expiration reminder to customer
     * @param mixed $id company id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $company        = $this->get($id);
        $company_number = format_company_number($company->id);
        set_mailing_constant();
        $pdf              = company_pdf($company);
        $attach           = $pdf->Output($company_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'companies', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_staffs($company->clientid, ['active' => 1, 'company_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('company_expiration_reminder', $company, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $company_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($company->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_company_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_company_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send company to client
     * @param mixed $id companyid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach company pdf or not
     * @return boolean
     */
    public function send_company_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $company = $this->get($id);

        if ($template_name == '') {
            $template_name = $company->sent == 0 ?
                'company_send_to_customer' :
                'company_send_to_customer_already_sent';
        }

        $company_number = format_company_number($company->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the company via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['scheduled_email_contacts'])) {
            $send_to = $GLOBALS['scheduled_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_staffs(
                $company->clientid,
                ['active' => 1, 'company_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $state_auto_updated = false;
        $state_now          = $company->state;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update state to sent in case when user sends the company is with state draft
            if ($state_now == 1) {
                $this->db->where('id', $company->id);
                $this->db->update(db_prefix() . 'companies', [
                    'state' => 2,
                ]);
                $state_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_company = $this->get($company->id);
                set_mailing_constant();
                $pdf = company_pdf($_pdf_company);

                $attach = $pdf->Output($company_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_staff($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, $company, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_company_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $company_number . '.pdf'),
                            'company'  => $_pdf_company,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_company_sent($id, $emails_sent);
            hooks()->do_action('company_sent', $id);

            return true;
        }

        if ($state_auto_updated) {
            // company not send to customer but the state was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $company->id);
            $this->db->update(db_prefix() . 'companies', [
                'state' => 1,
            ]);
        }

        return false;
    }

    /**
     * All company activity
     * @param mixed $id companyid
     * @return array
     */
    public function get_company_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'company');
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'company_activity')->result_array();
    }

    /**
     * Log company activity to database
     * @param mixed $id companyid
     * @param string $description activity description
     */
    public function log_company_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'company_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'company',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_state($data['state'], $data['companyid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'companies', $data['state']);
    }

    /**
     * Get company unique year for filtering
     * @return array
     */
    public function get_companies_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'companies ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_company'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_company']) && ($data['show_shipping_on_company'] == 1 || $data['show_shipping_on_company'] == 'on')) {
                $data['show_shipping_on_company'] = 1;
            } else {
                $data['show_shipping_on_company'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($state, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('companies_model::do_kanban_query', '2.9.2', 'companiesPipeline class');

        $kanBan = (new companiesPipeline($state))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }


    public function confirm_registration($client_id)
    {
        $contact_id = get_primary_staff_client_id($client_id);
        log_activity('$contact_id =' .$contact_id);

        $this->db->where('userid', $client_id);
        $this->db->update(db_prefix() . 'clients', ['active' => 1, 'registration_confirmed' => 1]);

        $this->db->where('staffid', $contact_id);
        $this->db->update(db_prefix() . 'staff', ['active' => 1]);

        $contact = $this->get_staff($contact_id);

        if ($contact) {
            send_mail_template('company_registration_confirmed', $contact);

            return true;
        }

        return false;
    }

    /**
     * Get single contacts
     * @param  mixed $id contact id
     * @return object
     */
    public function get_staff($staffid)
    {
        $this->db->where('staffid', $staffid);

        return $this->db->get(db_prefix() . 'staff')->row();
    }

    /**
     * Add assignment
     * @since  Version 1.0.2
     * @param mixed $data All $_POST data for the assignment
     * @param mixed $id   relid id
     * @return boolean
     */
    public function add_assignment($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } //isset($data['notify_by_email'])
        else {
            $data['notify_by_email'] = 0;
        }
        $data['date']        = to_sql_date($data['date'], true);
        $data['description'] = nl2br($data['description']);
        $data['creator']     = get_staff_user_id();
        $this->db->insert(db_prefix() . 'assignments', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($data['rel_type'] == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_activity_new_assignment_created', false, serialize([
                    get_staff_full_name($data['staff']),
                    _dt($data['date']),
                    ]));
            }
            log_activity('New assignment Added [' . ucfirst($data['rel_type']) . 'ID: ' . $data['rel_id'] . ' Description: ' . $data['description'] . ']');

            return true;
        } //$insert_id
        return false;
    }

    public function edit_assignment($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } else {
            $data['notify_by_email'] = 0;
        }

        $data['date']        = to_sql_date($data['date'], true);
        $data['description'] = nl2br($data['description']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'assignments', $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get all assignments or 1 assignment if id is passed
     * @since Version 1.0.2
     * @param  mixed $id assignment id OPTIONAL
     * @return array or object
     */
    public function get_assignments($id = '')
    {
        $this->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'assignments.staff', 'left');
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'assignments.id', $id);

            return $this->db->get(db_prefix() . 'assignments')->row();
        } //is_numeric($id)
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'assignments')->result_array();
    }

    /**
     * Remove client assignment from database
     * @since Version 1.0.2
     * @param  mixed $id assignment id
     * @return boolean
     */
    public function delete_assignment($id)
    {
        $assignment = $this->get_assignments($id);
        if ($assignment->creator == get_staff_user_id() || is_admin()) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'assignments');
            if ($this->db->affected_rows() > 0) {
                log_activity('assignment Deleted [' . ucfirst($assignment->rel_type) . 'ID: ' . $assignment->id . ' Description: ' . $assignment->description . ']');

                return true;
            } //$this->db->affected_rows() > 0
            return false;
        } //$assignment->creator == get_staff_user_id() || is_admin()
        return false;
    }


    public function get_company_members($id, $with_name = false)
    {
        if ($with_name) {
            $this->db->select('firstname,lastname,email,staffid');
        } else {
            $this->db->select('email,staffid');
        }
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.client_id=' . db_prefix() . 'clients.userid');
        $this->db->where('userid', $id);

        return $this->db->get(db_prefix() . 'clients')->result_array();
    }
}
