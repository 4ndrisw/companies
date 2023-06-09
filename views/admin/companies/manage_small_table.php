<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<?php $this->load->view('admin/companies/list_template'); ?>
		</div>
	</div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<script>var hidden_columns = [4,5,6,7];</script>
<?php init_tail(); ?>
<div id="convert_helper"></div>
<script>
   var companyid;
   $(function(){
     var Unit_ServerParams = {};
     $.each($('._hidden_inputs._filters input'),function(){
       Unit_ServerParams[$(this).attr('name')] = '[name="'+$(this).attr('name')+'"]';
     });
     initDataTable('.table-companies', admin_url+'companies/table', ['undefined'], ['undefined'], Unit_ServerParams, [7, 'desc']);
     init_company(companyid);
   });
   console.log(companyid);
</script>
</body>
</html>
