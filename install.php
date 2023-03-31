<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('install/companies.php');
require_once('install/company_activity.php');
require_once('install/company_items.php');
require_once('install/company_members.php');



$CI->db->query("
INSERT INTO `tblemailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('company', 'company-send-to-client', 'english', 'Send company to Customer', 'company # {company_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached company <strong># {company_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>company state:</strong> {company_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-already-send', 'english', 'company Already Sent to Customer', 'company # {company_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your company request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-declined-to-staff', 'english', 'company Declined (Sent to Staff)', 'Customer Declined company', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined company with number <strong># {company_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-accepted-to-staff', 'english', 'company Accepted (Sent to Staff)', 'Customer Accepted company', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted company with number <strong># {company_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-thank-you-to-customer', 'english', 'Thank You Email (Sent to Customer After Accept)', 'Thank for you accepting company', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank for for accepting the company.</span><br /> <br /><span style=\"font-size: 12pt;\">We look forward to doing business with you.</span><br /> <br /><span style=\"font-size: 12pt;\">We will contact you as soon as possible.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-expiry-reminder', 'english', 'company Expiration Reminder', 'company Expiration Reminder', '<p><span style=\"font-size: 12pt;\">Hello {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">The company with <strong># {company_number}</strong> will expire on <strong>{company_expirydate}</strong></span><br /><br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span></p>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-send-to-client', 'english', 'Send company to Customer', 'company # {company_number} created', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached company <strong># {company_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>company state:</strong> {company_state}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-already-send', 'english', 'company Already Sent to Customer', 'company # {company_number} ', '<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /> <br /><span style=\"font-size: 12pt;\">Thank you for your company request.</span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">Please contact us for more information.</span><br /> <br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-declined-to-staff', 'english', 'company Declined (Sent to Staff)', 'Customer Declined company', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) declined company with number <strong># {company_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-accepted-to-staff', 'english', 'company Accepted (Sent to Staff)', 'Customer Accepted company', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted company with number <strong># {company_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'staff-added-as-program-member', 'english', 'Staff Added as Program Member', 'New program assigned to you', '<p>Hi <br /><br />New company has been assigned to you.<br /><br />You can view the company on the following link <a href=\"{company_link}\">company__number</a><br /><br />{email_signature}</p>', '{companyname} | CRM', '', 0, 1, 0),
('company', 'company-accepted-to-staff', 'english', 'company Accepted (Sent to Staff)', 'Customer Accepted company', '<span style=\"font-size: 12pt;\">Hi</span><br /> <br /><span style=\"font-size: 12pt;\">Customer ({client_company}) accepted company with number <strong># {company_number}</strong></span><br /> <br /><span style=\"font-size: 12pt;\">You can view the company on the following link: <a href=\"{company_link}\">{company_number}</a></span><br /> <br /><span style=\"font-size: 12pt;\">{email_signature}</span>', '{companyname} | CRM', '', 0, 1, 0);
");
/*
 *
 */

// Add options for companies
add_option('delete_only_on_last_company', 1);
add_option('company_prefix', 'PRSH-');
add_option('next_company_number', 1);
add_option('default_company_assigned', 9);
add_option('company_number_decrement_on_delete', 0);
add_option('company_number_format', 4);
add_option('company_year', date('Y'));
add_option('exclude_company_from_client_area_with_draft_state', 1);
add_option('predefined_clientnote_company', '- Staf diatas untuk melakukan riksa uji pada peralatan tersebut.
- Staf diatas untuk membuat dokumentasi riksa uji sesuai kebutuhan.');
add_option('predefined_terms_company', '- Pelaksanaan riksa uji harus mengikuti prosedur yang ditetapkan perusahaan pemilik alat.
- Dilarang membuat dokumentasi tanpa seizin perusahaan pemilik alat.
- Dokumen ini diterbitkan dari sistem CRM, tidak memerlukan tanda tangan dari PT. Cipta Mas Jaya');
add_option('company_due_after', 1);
add_option('allow_staff_view_companies_assigned', 1);
add_option('show_assigned_on_companies', 1);
add_option('require_client_logged_in_to_view_company', 0);

add_option('show_program_on_company', 1);
add_option('companies_pipeline_limit', 1);
add_option('default_companies_pipeline_sort', 1);
add_option('company_accept_identity_confirmation', 1);
add_option('company_qrcode_size', '160');
add_option('company_send_telegram_message', 0);


/*

DROP TABLE `tblcompanies`;
DROP TABLE `tblcompany_activity`, `tblcompany_items`, `tblcompany_members`;
delete FROM `tbloptions` WHERE `name` LIKE '%company%';
DELETE FROM `tblemailtemplates` WHERE `type` LIKE 'company';
DELETE FROM `tblstaff` WHERE `tblstaff`.`client_type` = 'company';


*/