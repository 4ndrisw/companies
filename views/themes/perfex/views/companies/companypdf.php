<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('company_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $company_number . '</b>';

if (get_option('show_state_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . company_state_color_pdf($state) . ');text-transform:uppercase;">' . format_company_state($state, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$organization_info = '<div style="color:#424242;">';
    $organization_info .= format_organization_info();
$organization_info .= '</div>';

// Company to
$company_info = '<b>' . _l('company_to') . '</b>';
$company_info .= '<div style="color:#424242;">';
//$company_info .= format_customer_info($company, 'company', 'billing');
$company_info .= '</div>';

$organization_info .= '<p><strong>'. _l('company_members') . '</strong></p>';

$CI = &get_instance();
$CI->load->model('companies_model');
$company_members = $CI->companies_model->get_company_members($company->userid,true);
$i=1;
foreach($company_members as $member){
  $organization_info .=  $i.'. ' .$member['firstname'] .' '. $member['lastname']. '<br />';
  $i++;
}


if (!empty($company->expirydate)) {
    $company_info .= _l('company_data_expiry_date') . ': ' . _d($company->expirydate) . '<br />';
}

if (!empty($company->reference_no)) {
    $company_info .= _l('reference_no') . ': ' . $company->reference_no . '<br />';
}
/*
if ($company->program_id != 0 && get_option('show_program_on_company') == 1) {
    $company_info .= _l('program') . ': ' . get_program_name_by_id($company->program_id) . '<br />';
}
*/


$left_info  = $swap == '1' ? $company_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $company_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
//$items = get_company_items_table_data($company, 'company', 'pdf');

//$tblhtml = $items->table();
$tblhtml = '';
$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->SetFont($font_name, '', $font_size);

$client_info = '<div style="text-align:center;">';
    $client_info .= $company->company .'<br />';

$client_info .= '</div>';
$assigned_info ='';

$left_info  = $swap == '1' ? $client_info : $assigned_info;
$right_info = $swap == '1' ? $assigned_info : $client_info;
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

