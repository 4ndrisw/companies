<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Company_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $company;

    protected $contact;

    public $slug = 'company-send-to-client';

    public $rel_type = 'company';

    public function __construct($company, $contact, $cc = '')
    {
        parent::__construct();

        $this->company = $company;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->companies_model->get_attachments($this->company->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('company') . $this->company->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->company->id)
        ->set_merge_fields('client_merge_fields', $this->company->clientid, $this->contact->id)
        ->set_merge_fields('company_merge_fields', $this->company->id);
    }
}
