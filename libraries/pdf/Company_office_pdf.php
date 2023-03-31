<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Company_office_pdf extends App_pdf
{
    protected $company;

    private $company_number;

    public function __construct($company, $tag = '')
    {
        $this->load_language($company->clientid);

        $company                = hooks()->apply_filters('company_html_pdf_data', $company);
        $GLOBALS['company_pdf'] = $company;

        parent::__construct();

        $this->tag             = $tag;
        $this->company        = $company;
        $this->company_number = format_company_number($this->company->id);

        $this->SetTitle(str_replace("SCH", "SCH-UPT", $this->company_number));
    }

    public function prepare()
    {

        $this->set_view_vars([
            'state'          => $this->company->state,
            'company_number' => str_replace("SCH", "SCH-UPT", $this->company_number),
            'company'        => $this->company,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'company';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_company_office_pdf.php';
        $actualPath = module_views_path('companies','themes/' . active_clients_theme() . '/views/companies/company_office_pdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
