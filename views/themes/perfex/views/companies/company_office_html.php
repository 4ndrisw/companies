<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="company-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop company-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_company_number($company->id); ?>
                     </span>
                  </h3>
                  <h4 class="company-html-state mtop7">
                     <?php echo format_company_state($company->state,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">
                  <?php echo form_open(site_url('companies/office_pdf/'.$company->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="companypdf" class="btn btn-default action-button download mright5 mtop7" value="companypdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() || is_staff_member()){ ?>
                  <a href="<?php echo site_url('clients/companies/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>
   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold company-html-number"><?php echo format_company_number($company->id); ?></h4>
               <address class="company-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold company_to"><?php echo _l('company_office_to'); ?>:</span>
               <address class="company-html-customer-billing-info">
                  <?php echo format_office_info($company->office, 'office', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($company->include_shipping == 1 && $company->show_shipping_on_company == 1){ ?>
               <span class="bold company_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="company-html-customer-shipping-info">
                  <?php echo format_office_info($company->office, 'office', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-12 text-left transaction-html-info-col-left">
               <p class="company_to"><?php echo _l('company_opening'); ?>:</p>
               <span class="company_to"><?php echo _l('company_client'); ?>:</span>
               <address class="company-html-customer-billing-info">
                  <?php echo format_customer_info($company, 'company', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($company->include_shipping == 1 && $company->show_shipping_on_company == 1){ ?>
               <span class="bold company_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="company-html-customer-shipping-info">
                  <?php echo format_customer_info($company, 'company', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>



            <div class="col-md-6">
               <div class="container-fluid">
                  <?php if(!empty($company_members)){ ?>
                     <strong><?= _l('company_members') ?></strong>
                     <ul class="company_members">
                     <?php 
                        foreach($company_members as $member){
                          echo ('<li style="list-style:auto" class="member">' . $member['firstname'] .' '. $member['lastname'] .'</li>');
                         }
                     ?>
                     </ul>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-6 text-right">
               <p class="no-mbot company-html-date">
                  <span class="bold">
                  <?php echo _l('company_data_date'); ?>:
                  </span>
                  <?php echo _d($company->date); ?>
               </p>
               <?php if(!empty($company->expirydate)){ ?>
               <p class="no-mbot company-html-expiry-date">
                  <span class="bold"><?php echo _l('company_data_expiry_date'); ?></span>:
                  <?php echo _d($company->expirydate); ?>
               </p>
               <?php } ?>
               <?php if(!empty($company->reference_no)){ ?>
               <p class="no-mbot company-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $company->reference_no; ?>
               </p>
               <?php } ?>
               <?php if($company->program_id != 0 && get_option('show_program_on_company') == 1){ ?>
               <p class="no-mbot company-html-program">
                  <span class="bold"><?php echo _l('program'); ?>:</span>
                  <?php echo get_program_name_by_id($company->program_id); ?>
               </p>
               <?php } ?>
               <?php $pdf_custom_fields = get_custom_fields('company',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
                  foreach($pdf_custom_fields as $field){
                    $value = get_custom_field_value($company->id,$field['id'],'company');
                    if($value == ''){continue;} ?>
               <p class="no-mbot">
                  <span class="bold"><?php echo $field['name']; ?>: </span>
                  <?php echo $value; ?>
               </p>
               <?php } ?>
            </div>
         </div>
         <div class="row">
            <div class="col-md-12">
               <div class="table-responsive">
                  <?php
                     $items = get_company_items_table_data($company, 'company');
                     echo $items->table();
                  ?>
               </div>
            </div>


            <div class="row mtop25">
               <div class="col-md-12">
                  <div class="col-md-6 text-center">
                     <div class="bold"><?php echo get_option('invoice_company_name'); ?></div>
                     <div class="qrcode text-center">
                        <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_company_upload_path('company').$company->id.'/assigned-'.$company_number.'.png')); ?>" class="img-responsive center-block company-assigned" alt="company-<?= $company->id ?>">
                     </div>
                     <div class="assigned">
                     <?php if($company->assigned != 0 && get_option('show_assigned_on_companies') == 1){ ?>
                        <?php echo get_staff_full_name($company->assigned); ?>
                     <?php } ?>

                     </div>
                  </div>
                     <div class="col-md-6 text-center">
                       <div class="bold"><?php echo $client_company; ?></div>
                       <?php if(!empty($company->signature)) { ?>
                           <div class="bold">
                              <p class="no-mbot"><?php echo _l('company_signed_by') . ": {$company->acceptance_firstname} {$company->acceptance_lastname}"?></p>
                              <p class="no-mbot"><?php echo _l('company_signed_date') . ': ' . _dt($company->acceptance_date) ?></p>
                              <p class="no-mbot"><?php echo _l('company_signed_ip') . ": {$company->acceptance_ip}"?></p>
                           </div>
                           <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                           <?php if($company->signed == 1 && has_permission('companies','','delete')){ ?>
                              <a href="<?php echo admin_url('companies/clear_signature/'.$company->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                              </a>
                           <?php } ?>
                           </p>
                           <div class="customer_signature text-center">
                              <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_company_upload_path('company').$company->id.'/'.$company->signature)); ?>" class="img-responsive center-block company-signature" alt="company-<?= $company->id ?>">
                           </div>
                       <?php } ?>
                     </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

