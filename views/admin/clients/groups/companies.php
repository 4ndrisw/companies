<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('companies'); ?></h4>
	<?php if(has_permission('companies','','create')){ ?>
		<a href="<?php echo admin_url('companies/company?customer_id='.$client->userid); ?>" class="btn btn-info mbot15<?php if($client->active == 0){echo ' disabled';} ?>"><?php echo _l('create_new_company'); ?></a>
	<?php } ?>
	<?php if(has_permission('companies','','view') || has_permission('companies','','view_own') || get_option('allow_staff_view_companies_assigned') == '1'){ ?>
		<a href="#" class="btn btn-info mbot15" data-toggle="modal" data-target="#client_zip_companies"><?php echo _l('zip_companies'); ?></a>
	<?php } ?>
	<div id="companies_total"></div>
	<?php
	$this->load->view('admin/companies/table_html', array('class'=>'companies-single-client'));
	//$this->load->view('admin/clients/modals/zip_companies');
	?>
<?php } ?>
