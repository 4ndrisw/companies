<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['companies/company/(:num)/(:any)'] = 'company/index/$1/$2';

/**
 * @since 2.0.0
 */
$route['companies/list'] = 'mycompany/list';
$route['companies/show/(:num)/(:any)'] = 'mycompany/show/$1/$2';
$route['companies/office/(:num)/(:any)'] = 'mycompany/office/$1/$2';
$route['companies/pdf/(:num)'] = 'mycompany/pdf/$1';
$route['companies/office_pdf/(:num)'] = 'mycompany/office_pdf/$1';
