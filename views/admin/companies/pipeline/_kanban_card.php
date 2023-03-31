<?php defined('BASEPATH') or exit('No direct script access allowed');
   if ($company['state'] == $state) { ?>
<li data-company-id="<?php echo $company['id']; ?>" class="<?php if($company['invoiceid'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading"><a href="<?php echo admin_url('companies/list_companies/'.$company['id']); ?>" onclick="company_pipeline_open(<?php echo $company['id']; ?>); return false;"><?php echo format_company_number($company['id']); ?></a>
               <?php if(has_permission('companies','','edit')){ ?>
               <a href="<?php echo admin_url('companies/company/'.$company['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="inline-block full-width mbot10">
            <a href="<?php echo admin_url('clients/client/'.$company['clientid']); ?>" target="_blank">
            <?php echo $company['company']; ?>
            </a>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <span class="bold">
                  <?php echo _l('company_total') . ':' . app_format_money($company['total'], $company['currency_name']); ?>
                  </span>
                  <br />
                  <?php echo _l('company_data_date') . ': ' . _d($company['date']); ?>
                  <?php if(is_date($company['expirydate']) || !empty($company['expirydate'])){
                     echo '<br />';
                     echo _l('company_data_expiry_date') . ': ' . _d($company['expirydate']);
                     } ?>
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-paperclip"></i> <?php echo _l('company_notes'); ?>: <?php echo total_rows(db_prefix().'notes', array(
                     'rel_id' => $company['id'],
                     'rel_type' => 'company',
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($company['id'],'company');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
