<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<?php
			echo form_open($this->uri->uri_string(),array('id'=>'company-form','class'=>'_transaction_form'));
			if(isset($company)){
				echo form_hidden('isedit');
			}
			?>
			<div class="col-md-12">
				<?php $this->load->view('admin/companies/company_template'); ?>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div>
</div>
</div>
<?php init_tail(); ?>
<script>
  var _institution_id = $('#client_id'),
      _institution_id_wrapper = $('#rel_id_wrapper'),
      data = {};


	$(function(){
	    apps_ajax_search("institutions", "#institution_id.ajax-search");
	    //apps_ajax_search("inspectors", "#inspector_id.ajax-search");
			validate_company_form();
			apps_ajax_inspector_id_search_by_institution_id();
			apps_ajax_inspector_staff_id_search_by_inspector_id();
			
			//$('.f_inspector_id').hide();
			$('.f_inspector_staff_id').hide();

      $("body").on("change", ".f_inspector_id #inspector_id", function () {
        var val = $(this).val();
        var inspectorStaffAjax = $('select[name="inspector_staff_id"]');
        var clonedInspectorStaffAjaxSearchSelect = inspectorStaffAjax.html("").clone();
        var inspectorStaffWrapper = $("#inspector_staff_id_wrapper");

				if(val !== "" || val > 0){
            $('.f_inspector_staff_id').show();
        }

        inspectorStaffAjax.selectpicker("destroy").remove();
        inspectorStaffAjax = clonedInspectorStaffAjaxSearchSelect;
        $("#inspector_staff_id_wrapper").append(clonedInspectorStaffAjaxSearchSelect);
        apps_ajax_inspector_staff_id_search_by_inspector_id();
      });

		  $("body").on("change", ".f_institution_id #institution_id", function () {
		    var val = $(this).val();
		    var inspectorAjax = $('select[name="inspector_id"]');
		    var clonedInspectorsAjaxSearchSelect = inspectorAjax.html("").clone();
		    var inspectorsWrapper = $("#inspector_id_wrapper");
				
				if(val !== "" || val > 0){
            $('.f_inspector_id').show();
        }

		    inspectorAjax.selectpicker("destroy").remove();
		    inspectorAjax = clonedInspectorsAjaxSearchSelect;
		    $("#inspector_id_wrapper").append(clonedInspectorsAjaxSearchSelect);
		    apps_ajax_inspector_id_search_by_institution_id();


        var val = $(this).val();
        var inspectorStaffAjax = $('select[name="inspector_staff_id"]');
        var clonedInspectorStaffAjaxSearchSelect = inspectorStaffAjax.html("").clone();
        var inspectorStaffWrapper = $("#inspector_staff_id_wrapper");
        inspectorStaffAjax.selectpicker("destroy").remove();
        inspectorStaffAjax = clonedInspectorStaffAjaxSearchSelect;
        $("#inspector_staff_id_wrapper").append(clonedInspectorStaffAjaxSearchSelect);
        apps_ajax_inspector_staff_id_search_by_inspector_id();


		    apps_ajax_inspector_staffs_search();

		 
		  });

		// Init accountacy currency symbol
		//init_currency();
		// Program ajax search
		//init_ajax_program_search_by_customer_id();
		// Maybe items ajax search
	    //init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'items/search');
	});
/*
  function company_institution_id_select() {
    var serverData = {};
    serverData.institution_id = _institution_id.val();
    data.type = _rel_type.val();
    //init_ajax_search(_rel_type.val(), _clientid, serverData);
    console.log(serverData);
    apps_ajax_search(_rel_type.val(), _clientid, serverData);

  }
*/
</script>
</body>
</html>
