<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id',$company->userid); ?>
<?php echo form_hidden('_attachment_sale_type','company'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_company" aria-controls="tab_company" role="tab" data-toggle="tab">
                     <?php echo _l('company'); ?>
                     </a>
                  </li>
                  <!--
                  <li role="presentation">
                     <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php //echo $company->userid; ?>,'company'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                     <?php //echo _l('tasks'); ?>
                     </a>
                  </li>
                  -->

                  <li role="presentation">
                     <a href="#tab_staffs" onclick="initDataTable('.table-staffs', admin_url + 'companies/table_staffs/' + <?php echo $company->userid ;?> + '/' + 'company', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_staffs" role="tab" data-toggle="tab">
                     <?php echo _l('company_staffs'); ?>
                     <?php
                        $total_staffs = total_rows(db_prefix().'staff',
                          array(
                           'is_not_staff'=>1,
                           //'staff'=>get_staff_user_id(),
                           'client_type'=>'company',
                           'client_id'=>$company->userid
                           )
                          );
                        if($total_staffs > 0){
                          echo '<span class="badge">'.$total_staffs.'</span>';
                        }
                        ?>
                     </a>
                  </li>

                  <li role="presentation">
                     <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                     <?php echo _l('company_view_activity_tooltip'); ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_assignments" onclick="initDataTable('.table-assignments', admin_url + 'companies/get_inspector_assignments/' + <?php echo $company->userid ;?> + '/' + 'company', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_assignments" role="tab" data-toggle="tab">
                     <?php echo _l('inspector_assignments'); ?>
                     <?php
                        $total_assignments = total_rows(db_prefix().'assignments',
                          array(
                           'isnotified'=>0,
                           'staff'=>get_staff_user_id(),
                           'rel_type'=>'company',
                           'rel_id'=>$company->userid
                           )
                          );
                        if($total_assignments > 0){
                          echo '<span class="badge">'.$total_assignments.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <?php if ( $this->acl->has_acl_permission('access_reminders') ) { ?>
                      <li role="presentation">
                        <a href="#tab_reminders" onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo $company->userid ;?> + '/' + 'company', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                        <?php echo _l('company_reminders'); ?>
                        <?php
                           $total_reminders = total_rows(db_prefix().'reminders',
                             array(
                              'isnotified'=>0,
                              'staff'=>get_staff_user_id(),
                              'rel_type'=>'company',
                              'rel_id'=>$company->userid
                              )
                             );
                           if($total_reminders > 0){
                             echo '<span class="badge">'.$total_reminders.'</span>';
                           }
                           ?>
                        </a>
                     </li>    
                  <?php } ?>

                  <?php if ( $this->acl->has_acl_permission('notes') ) { ?>
                  <li role="presentation" class="tab-separator">
                     <a href="#tab_notes" onclick="get_sales_notes(<?php echo $company->userid; ?>,'companies'); return false" aria-controls="tab_notes" role="tab" data-toggle="tab">
                     <?php echo _l('company_notes'); ?>
                     <span class="notes-total">
                        <?php if($totalNotes > 0){ ?>
                           <span class="badge"><?php echo $totalNotes; ?></span>
                        <?php } ?>
                     </span>
                     </a>
                  </li>
                  <?php } ?>


                  <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                     <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                     <?php } else { ?>
                     <?php echo _l('emails_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>" class="tab-separator">
                     <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-eye"></i>
                     <?php } else { ?>
                     <?php echo _l('view_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;">
                     <i class="fa fa-expand"></i></a>
                  </li>
               </ul>
            </div>
         </div>
         <div class="row mtop10">
            <div class="col-md-6">
               <?php echo format_company_state($company->active,'mtop5');  ?>
               <div class="h4 mtop5"><?php echo $company->company;  ?></div>
            </div>
            <div class="col-md-6">
               <div class="visible-xs">
                  <div class="mtop10"></div>
               </div>
               <div class="pull-right _buttons">
                  <?php if(staff_can('edit', 'companies') || staff_can('edit_own', 'companies')){ ?>
                  <a href="<?php echo admin_url('companies/company/'.$company->userid); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('edit_company_tooltip'); ?>" data-placement="bottom"><i class="fa-solid fa-pen-to-square"></i></a>
                  <?php } ?>
                  <div class="btn-group">
                     <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
                     <ul class="dropdown-menu dropdown-menu-right">
                        <li class="hidden-xs"><a href="<?php echo admin_url('companies/pdf/'.$company->userid.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                        <li class="hidden-xs"><a href="<?php echo admin_url('companies/pdf/'.$company->userid.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                        <li><a href="<?php echo admin_url('companies/pdf/'.$company->userid); ?>"><?php echo _l('download'); ?></a></li>
                        <li>
                           <a href="<?php echo admin_url('companies/pdf/'.$company->userid.'?print=true'); ?>" target="_blank">
                           <?php echo _l('print'); ?>
                           </a>
                        </li>
                     </ul>
                  </div>
                  <?php
                     $_tooltip = _l('company_sent_to_email_tooltip');
                     $_tooltip_already_send = '';
                     if($company->active == 1){
                        $_tooltip_already_send = _l('company_already_send_to_client_tooltip', time_ago($company->dateactivated));
                     }
                     ?>

                  <div class="btn-group">
                     <button type="button" class="btn btn-default pull-left dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <?php echo _l('more'); ?> <span class="caret"></span>
                     </button>
                     <ul class="dropdown-menu dropdown-menu-right">
                        
                        <?php hooks()->do_action('after_company_view_as_client_link', $company); ?>
                        
                        <li>
                           <a href="#" data-toggle="modal" data-target="#sales_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
                        </li>

                        <?php if(staff_can('create', 'companies')){ ?>
                        <li>
                           <a href="<?php echo admin_url('companies/copy/'.$company->userid); ?>">
                           <?php echo _l('copy_company'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <?php if(staff_can('delete', 'companies')){ ?>
                        <?php
                           if((get_option('delete_only_on_last_company') == 1 && is_last_company($company->userid)) || (get_option('delete_only_on_last_company') == 0)){ ?>
                        <li>
                           <a href="<?php echo admin_url('companies/delete/'.$company->userid); ?>" class="text-danger delete-text _delete"><?php echo _l('delete_company_tooltip'); ?></a>
                        </li>
                        <?php
                           }
                           }
                           ?>
                     </ul>
                  </div>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
         <hr class="hr-panel-heading" />
         <div class="tab-content">
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_company">
               <?php if(isset($company->scheduled_email) && $company->scheduled_email) { ?>
                     <div class="alert alert-warning">
                        <?php echo _l('invoice_will_be_sent_at', _dt($company->scheduled_email->scheduled_at)); ?>
                        <?php if(staff_can('edit', 'companies') || $company->addedfrom == get_staff_user_id()) { ?>
                           <a href="#"
                           onclick="edit_company_scheduled_email(<?php echo $company->scheduled_email->id; ?>); return false;">
                           <?php echo _l('edit'); ?>
                        </a>
                     <?php } ?>
                  </div>
               <?php } ?>
               <div id="company-preview">
                  <div class="row">
                     <?php if($company->active == 4 && !empty($company->acceptance_firstname) && !empty($company->acceptance_lastname) && !empty($company->acceptance_email)){ ?>
                     <div class="col-md-12">
                        <div class="alert alert-info mbot15">
                           <?php echo _l('accepted_identity_info',array(
                              _l('company_lowercase'),
                              '<b>'.$company->acceptance_firstname . ' ' . $company->acceptance_lastname . '</b> (<a href="mailto:'.$company->acceptance_email.'">'.$company->acceptance_email.'</a>)',
                              '<b>'. _dt($company->acceptance_date).'</b>',
                              '<b>'.$company->acceptance_ip.'</b>'.(is_admin() ? '&nbsp;<a href="'.admin_url('companies/clear_acceptance_info/'.$company->userid).'" class="_delete text-muted" data-toggle="tooltip" data-title="'._l('clear_this_information').'"><i class="fa fa-remove"></i></a>' : '')
                              )); ?>
                        </div>
                     </div>
                     <?php } ?>
                     <div class="col-md-6 col-sm-6">
                        <h4 class="bold">
                           <a href="<?php echo admin_url('companies/company/'.$company->userid); ?>">
                           <span id="company-number">
                           <?php echo format_company_number($company->userid); ?>
                           </span>
                           </a>
                        </h4>
                        <address>
                           <?php echo format_company_info($company); ?>
                        </address>
                     </div>
                     <div class="col-sm-6 text-right">

                     </div>
                  </div>

               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_tasks">
               <?php init_relation_tasks_table(array('data-new-rel-id'=>$company->userid,'data-new-rel-type'=>'company')); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_staffs">
                <?php if (has_permission('pengguna', '', 'create')) { ?>
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('companies/staff/add/'. $company->userid); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_staff'); ?>
                    </a>
                </div>
                <?php } ?>
               <hr />
               <?php 
               //render_datatable(array( _l( 'staff_description'), _l( 'staff_date'), _l( 'staff_staff'), _l( 'staff_is_notified')), 'staffs'); 

                        $table_data = [
                            _l('staff_dt_name'),
                            _l('staff_dt_email'),
                            _l('staff_dt_last_Login'),
                            _l('staff_dt_active'),
                        ];
                        render_datatable($table_data, 'staffs');
               ?>
               <?php //$this->load->view('admin/includes/modals/staff',array('id'=>$company->userid,'name'=>'company','member'=>$member,'staff_title'=>_l('company_set_staff_title'))); ?>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_assignments">
               <?php if(is_admin() || $current_staff['company_id'] == $company->inspector_id ) { ?>
                  <a href="#" data-toggle="modal" class="btn btn-info" data-target=".assignment-modal-company-<?php echo $company->userid; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('company_set_assignment_title'); ?></a>
               <?php } ?>
               <hr />
               <?php render_datatable(array( _l( 'assignment_description'), _l( 'assignment_date'), _l( 'assignment_staff'), _l( 'assignment_is_notified')), 'assignments'); ?>
               <?php $this->load->view('admin/includes/modals/assignment',array('id'=>$company->userid,'name'=>'company','members'=>isset($members) ? $members : [],'assignment_title'=>_l('company_set_assignment_title'))); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
               <?php
                  $this->load->view('admin/includes/emails_tracking',array(
                     'tracked_emails'=>
                     get_tracked_emails($company->userid, 'company'))
                  );
                  ?>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_reminders">
               <a href="#" data-toggle="modal" class="btn btn-info" data-target=".reminder-modal-company-<?php echo $company->userid; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('company_set_reminder_title'); ?></a>
               <hr />
               <?php render_datatable(array( _l( 'reminder_description'), _l( 'reminder_date'), _l( 'reminder_staff'), _l( 'reminder_is_notified')), 'reminders'); ?>
               <?php $this->load->view('admin/includes/modals/reminder',array('id'=>$company->userid,'name'=>'company','members'=>isset($members) ? $members : [],'reminder_title'=>_l('company_set_reminder_title'))); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
               <?php
                  $this->load->view('admin/includes/emails_tracking',array(
                     'tracked_emails'=>
                     get_tracked_emails($company->userid, 'company'))
                  );
                  ?>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_notes">
               <?php echo form_open(admin_url('companies/add_note/'.$company->userid),array('id'=>'companies-notes','class'=>'companies-notes-form')); ?>
               <?php echo render_textarea('description'); ?>

               <div class="text-right">
                  <button type="submit" class="btn btn-info mtop15 mbot15"><?php echo _l('company_add_note'); ?></button>
               </div>
               <?php echo form_close(); ?>
               <hr />
               <div class="panel_s mtop20 no-shadow" id="sales_notes_area">
               </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_activity">
               <div class="row">
                  <div class="col-md-12">
                     <div class="activity-feed">
                        <?php foreach($activity as $activity){
                           $_custom_data = false;
                           ?>
                        <div class="feed-item" data-sale-activity-id="<?php echo $activity['id']; ?>">
                           <div class="date">
                              <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($activity['date']); ?>">
                              <?php echo time_ago($activity['date']); ?>
                              </span>
                           </div>
                           <div class="text">
                              <?php if(is_numeric($activity['staffid']) && $activity['staffid'] != 0){ ?>
                              <a href="<?php echo admin_url('profile/'.$activity["staffid"]); ?>">
                              <?php echo staff_profile_image($activity['staffid'],array('staff-profile-xs-image pull-left mright5'));
                                 ?>
                              </a>
                              <?php } ?>
                              <?php
                                 $additional_data = '';
                                 if(!empty($activity['additional_data'])){
                                  $additional_data = unserialize($activity['additional_data']);
                                  $i = 0;
                                  foreach($additional_data as $data){
                                    if(strpos($data,'<original_active>') !== false){
                                      $original_active = get_string_between($data, '<original_active>', '</original_active>');
                                      $additional_data[$i] = format_company_state($original_active,'',false);
                                    } else if(strpos($data,'<new_active>') !== false){
                                      $new_active = get_string_between($data, '<new_active>', '</new_active>');
                                      $additional_data[$i] = format_company_state($new_active,'',false);
                                    } else if(strpos($data,'<active>') !== false){
                                      $active = get_string_between($data, '<active>', '</active>');
                                      $additional_data[$i] = format_company_state($active,'',false);
                                    } else if(strpos($data,'<custom_data>') !== false){
                                      $_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
                                      unset($additional_data[$i]);
                                    }
                                    $i++;
                                  }
                                 }
                                 $_formatted_activity = _l($activity['description'],$additional_data);
                                 if($_custom_data !== false){
                                 $_formatted_activity .= '<br />';
                                 $_formatted_activity .= '<p>';
                                 $_formatted_activity .= $_custom_data;
                                 $_formatted_activity .= '</p>';
                                 }
                                 if(!empty($activity['full_name'])){
                                 $_formatted_activity = $activity['full_name'] . ' - ' . $_formatted_activity;
                                 }
                                 echo $_formatted_activity;
                                 if(is_admin()){
                                 echo '<a href="#" class="pull-right text-danger" onclick="delete_sale_activity('.$activity['id'].'); return false;"><i class="fa fa-remove"></i></a>';
                                 }
                                 ?>
                           </div>
                        </div>
                        <?php } ?>
                     </div>
                  </div>
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_views">
               <?php
                  $views_activity = get_views_tracking('company',$company->userid);
                  if(count($views_activity) === 0) {
                     echo '<h4 class="no-mbot">'._l('not_viewed_yet',_l('company_lowercase')).'</h4>';
                  }
                  foreach($views_activity as $activity){ ?>
               <p class="text-success no-margin">
                  <?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
               </p>
               <p class="text-muted">
                  <?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
               </p>
               <hr />
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</div>


<?php if (isset($company)) { ?>
<?php if (has_permission('customers', '', 'create') || has_permission('customers', '', 'edit')) { ?>
<div class="modal fade" id="customer_admins_assign" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('clients/assign_admins/' . $company->userid)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('assign_admin'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
               $selected = [];
               foreach ($inspector_staffs as $c_admin) {
                   array_push($selected, $c_admin['staffid']);
               }
               echo render_select('customer_admins[]', $inspector_staffs, ['staffid', ['firstname', 'lastname']], '', $selected, [], [], '', '', false); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<?php } ?>
<?php } ?>

<script>
   init_items_sortable(true);
   init_btn_with_tooltips();
   init_datepicker();
   init_selectpicker();
   init_form_assignment();
   init_form_reminder();
   init_tabs_scrollable();
   init_companies_note();
   <?php if($send_later) { ?>
      company_company_send(<?php echo $company->userid; ?>);
   <?php } ?>
</script>
<?php //$this->load->view('admin/companies/company_send_to_client'); ?>
