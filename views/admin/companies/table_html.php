<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
   _l('the_number_sign'),
   _l('company'),
   _l('contact_primary'),
   array(
      'name'=>_l('company_primary_email'),
      'th_attrs'=>array('class'=>'not_visible')
   ),
   array(
      'name'=>_l('company_primary_email'),
      'th_attrs'=>array('class'=> (isset($client) ? 'not_visible' : ''))
   ),
   _l('company_siup'),
   _l('clients_list_phone'),
   //_l('company_dt_table_heading_expirydate'),
   //_l('reference_no'),
   //_l('preffered_company'),
   _l('customer_active'));

$custom_fields = get_custom_fields('company',array('show_on_table'=>1));

foreach($custom_fields as $field){
   array_push($table_data,$field['name']);
}

$table_data = hooks()->apply_filters('companies_table_columns', $table_data);

render_datatable($table_data, isset($class) ? $class : 'companies');
