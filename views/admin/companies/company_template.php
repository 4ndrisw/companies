<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s accounting-template company">
   <div class="panel-body">
      <?php if(isset($company)){ ?>
      <?php echo format_company_state($company->active); ?>
      <hr class="hr-panel-heading" />
      <?php } ?>
      <div class="row">
          <?php if (isset($company_request_id) && $company_request_id != '') {
              echo form_hidden('company_request_id',$company_request_id);
          }
          ?>
         <div class="col-md-6">
            <div class="f_client_id">
             <div class="form-group name-placeholder">
               <div class="row">
                 <div class="col-md-12">
                    <?php $value = (isset($company) ? $company->company : ''); ?>
                    <?php $attrs = (isset($company) ? array() : array('autofocus' => true)); ?>
                    <?php echo render_input('company', 'company', $value, 'text', $attrs); ?>
                    <div id="company_exists_info" class="hide"></div>
                  </div>
                 </div>
              </div>
            </div>

            <?php
               $next_company_number = get_option('next_company_number');
               $format = get_option('company_number_format');

                if(isset($company)){
                  $format = $company->number_format;
                }

               $prefix = get_option('company_prefix');

               if ($format == 1) {
                 $__number = $next_company_number;
                 if(isset($company)){
                   $__number = $company->number;
                   $prefix = '<span id="prefix">' . $company->prefix . '</span>';
                 }
               } else if($format == 2) {
                 if(isset($company)){
                   $__number = $company->number;
                   $prefix = $company->prefix;
                   $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' . date('Y',strtotime($company->dateactivated)).'</span>/';
                 } else {
                   $__number = $next_company_number;
                   $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                 }
               } else if($format == 3) {
                  if(isset($company)){
                   $yy = date('y',strtotime($company->dateactivated));
                   $__number = $company->number;
                   $prefix = '<span id="prefix">'. $company->prefix . '</span>';
                 } else {
                  $yy = date('y');
                  $__number = $next_company_number;
                }
               } else if($format == 4) {
                  if(isset($company)){
                   $yyyy = date('Y',strtotime($company->dateactivated));
                   $mm = date('m',strtotime($company->dateactivated));
                   $__number = $company->number;
                   $prefix = '<span id="prefix">'. $company->prefix . '</span>';
                 } else {
                  $yyyy = date('Y');
                  $mm = date('m');
                  $__number = $next_company_number;
                }
               }

               $_company_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit = isset($company) ? 'true' : 'false';
               $data_original_number = isset($company) ? $company->number : 'false';
               ?>
            <div class="form-group">
               <label for="number"><?php echo _l('company_add_edit_number'); ?></label>
               <div class="input-group">
                  <span class="input-group-addon">
                  <?php if(isset($company)){ ?>
                  <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_company_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $company->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('companies/update_number_settings/'.$company->userid); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>"><i class="fa fa-cog"></i></a>
                   <?php }
                    echo $prefix;
                  ?>
                 </span>
                  <input type="text" name="number" class="form-control" value="<?php echo $_company_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>">
                  <?php if($format == 3) { ?>
                  <span class="input-group-addon">
                     <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                  </span>
                  <?php } else if($format == 4) { ?>
                   <span class="input-group-addon">
                     <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                     /
                     <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                  </span>
                  <?php } ?>
               </div>
            </div>
            <!--
            <div class="row">
               <div class="col-md-12">
                  <div class="f_client_id">
                   <div class="form-group select-placeholder">
                      <label for="clientid" class="control-label"><?php echo _l('company_select_customer'); ?></label>
                      <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($company) && empty($company->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                           <?php 
                              /*
                              $selected = (isset($company) ? $company->clientid : '');
                              if($selected == ''){
                               $selected = (isset($customer_id) ? $customer_id: '');
                              }
                              if($selected != ''){
                                $rel_data = get_relation_data('customer',$selected);
                                $rel_val = get_relation_values($rel_data,'customer');
                                echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                              }
                              */
                           ?>
                      </select>
                    </div>
                  </div>
               </div>
            </div>
            -->

            <div class="row">
               <div class="col-md-12">
                  <div class="f_institution_id">
                      <div class="form-group select-placeholder" id="institution_id_wrapper">
                        <div class="form-group select-placeholder">
                          <label for="institution_id" class="control-label"><?php echo _l('customer_select_institution_id'); ?></label>
                          <select id="institution_id" name="institution_id" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($peralatan) && empty($peralatan->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                         <?php $institution_id = (isset($company) ? $company->institution_id : '');
                           if($institution_id == ''){
                             $institution_id = (isset($institution) ? $institution: '');
                           }
                           if($institution_id != ''){
                              $rel_data = apps_get_relation_data('institutions',$institution_id);
                              $rel_val = apps_get_relation_values($rel_data,'institutions');
                              echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                           } ?>
                          </select>
                        </div>
                      </div>
                  </div>

                  <div class="f_inspector_id">
                      <div class="form-group select-placeholder" id="inspector_id_wrapper">
                        <div class="form-group select-placeholder">
                          <label for="inspector_id" class="control-label"><?php echo _l('customer_select_inspector_id'); ?></label>
                          <select id="inspector_id" name="inspector_id" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($peralatan) && empty($peralatan->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                         <?php $inspector_id = (isset($company) ? $company->inspector_id : '');
                           if($inspector_id == ''){
                             $inspector_id = (isset($inspector) ? $inspector: '');
                           }
                           if($inspector_id != ''){
                              $rel_data = apps_get_relation_data('inspectors',$inspector_id);
                              $rel_val = apps_get_relation_values($rel_data,'inspectors');
                              echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                           } ?>
                          </select>
                        </div>
                      </div>
                  </div>
                  
                  <div class="f_inspector_staff_id">
                      <div class="form-group select-placeholder" id="inspector_staff_id_wrapper">
                        <div class="form-group select-placeholder">
                          <label for="inspector_staff_id" class="control-label"><?php echo _l('customer_select_inspector_staff_id'); ?></label>
                          <select id="inspector_staff_id" name="inspector_staff_id" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($company) && empty($company->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                         <?php $inspector_staff_id = (isset($company) ? $company->inspector_staff_id : '');
                           if($inspector_staff_id == ''){
                             $inspector_staff_id = (isset($inspector) ? $inspector: '');
                           }
                           if($inspector_staff_id != ''){
                              $rel_data = apps_get_relation_data('inspector_staff',$inspector_staff_id);
                              $rel_val = apps_get_relation_values($rel_data,'inspector_staff');
                              echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                           } ?>
                          </select>
                        </div>
                      </div>
                  </div>
               </div>
            </div>


            <div class="row">
               <div class="col-md-6">
                 <?php $value = (isset($company) ? $company->siup : ''); ?>
                 <?php echo render_input('siup','siup',$value); ?>
               </div>
               <div class="col-md-6">
                 <?php $value = (isset($company) ? $company->vat : ''); ?>
                 <?php echo render_input('vat','vat',$value); ?>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <?php if (get_option('company_use_bpjs_kesehatan_field') == 1) {
                     $value = (isset($company) ? $company->bpjs_kesehatan : '');
                     echo render_input('bpjs_kesehatan', 'bpjs_kesehatan', $value);
                  } ?>
               </div>
               <div class="col-md-6">
                  <?php if (get_option('company_use_bpjs_ketenagakerjaan_field') == 1) {
                     $value = (isset($company) ? $company->bpjs_ketenagakerjaan : '');
                     echo render_input('bpjs_ketenagakerjaan', 'bpjs_ketenagakerjaan', $value);
                  } ?>
               </div>
            </div>

            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($company) ? $company->phone : ''); ?>
                  <?php echo render_input('phone', 'client_phone', $value); ?>
               </div>

              <div class="col-md-6">
                 <div class="form-group select-placeholder">
                    <label class="control-label"><?php echo _l('company_state'); ?></label>
                    <select class="selectpicker display-block mbot15" name="state" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                       <?php foreach($company_states as $state){ ?>
                       <option value="<?php echo $state; ?>" <?php if(isset($company) && $company->state == $state){echo 'selected';} ?>><?php echo format_company_state($state,'',false); ?></option>
                       <?php } ?>
                    </select>
                 </div>
              </div>
             </div>

            <div class="clearfix mbot15"></div>
            <?php $rel_id = (isset($company) ? $company->userid : false); ?>
            <?php
                  if(isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
             ?>
            <?php //echo render_custom_fields('company',$rel_id); ?>
         </div>
         <div class="col-md-6">
            <div class="no-shadow">
              <div class="row">
                 <div class="col-md-12">
                    <?php $value = (isset($company) ? $company->address : ''); ?>
                    <?php echo render_textarea('address', 'client_address', $value); ?>
                    <?php $value = (isset($company) ? $company->city : ''); ?>
                    <?php echo render_input('city', 'client_city', $value); ?>
                    <?php $value = (isset($company) ? $company->state : ''); ?>
                    <?php echo render_input('state', 'client_state', $value); ?>
                    <?php $value = (isset($company) ? $company->zip : ''); ?>
                    <?php echo render_input('zip', 'client_postal_code', $value); ?>
                    <?php $countries = get_all_countries();
                    $customer_default_country = get_option('customer_default_country');
                    $selected = (isset($company) ? $company->country : $customer_default_country);
                    echo render_select('country', $countries, array('country_id', array('short_name')), 'clients_country', $selected, array('data-none-selected-text' => _l('dropdown_non_selected_tex')));
                    ?>

                 </div>
              </div>
            </div>
         </div>
      </div>
   </div>
   <div class="row">
    <div class="col-md-12 mtop5">
      <div class="panel-body bottom-transaction">
        <div class="btn-bottom-toolbar text-right">
          <div class="btn-group dropup">
            <button type="button" class="btn-tr btn btn-info company-form-submit transaction-submit">
              <?php echo _l('submit'); ?>
            </button>
          <button type="button"
            class="btn btn-info dropdown-toggle"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-right width200">
            <li>
              <a href="#" class="company-form-submit save-and-send transaction-submit">
                <?php echo _l('save_and_send'); ?>
              </a>
            </li>
            <?php if(!isset($company)) { ?>
              <li>
                <a href="#" class="company-form-submit save-and-send-later transaction-submit">
                  <?php echo _l('save_and_send_later'); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="btn-bottom-pusher"></div>
  </div>
</div>
</div>
