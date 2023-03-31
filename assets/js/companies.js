// Init single company
function init_company(userid) {
    load_small_table_item(userid, '#company', 'companyid', 'companies/get_company_data_ajax', '.table-companies');
}


// Validates company add/edit form
function validate_company_form(selector) {

    selector = typeof (selector) == 'undefined' ? '#company-form' : selector;

    appValidateForm($(selector), {
        clientid: {
            required: {
                depends: function () {
                    var customerRemoved = $('select#clientid').hasClass('customer-removed');
                    return !customerRemoved;
                }
            }
        },
        date: 'required',
        institution_id: 'required',
        inspector_id: 'required',
        number: {
            required: true
        }
    });

    $("body").find('input[name="number"]').rules('add', {
        remote: {
            url: admin_url + "companies/validate_company_number",
            type: 'post',
            data: {
                number: function () {
                    return $('input[name="number"]').val();
                },
                isedit: function () {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function () {
                    return $('input[name="number"]').data('original-number');
                },
                date: function () {
                    return $('body').find('.company input[name="date"]').val();
                },
            }
        },
        messages: {
            remote: app.lang.company_number_exists,
        }
    });

}


// Get the preview main values
function get_company_item_preview_values() {
    var response = {};
    response.description = $('.main textarea[name="description"]').val();
    response.long_description = $('.main textarea[name="long_description"]').val();
    response.qty = $('.main input[name="quantity"]').val();
    return response;
}


// From company table mark as
function company_mark_as(state_id, company_id) {
    var data = {};
    data.state = state_id;
    data.companyid = company_id;
    $.post(admin_url + 'companies/update_company_state', data).done(function (response) {
        //table_companies.DataTable().ajax.reload(null, false);
        reload_companies_tables();
    });
}

// Reload all companies possible table where the table data needs to be refreshed after an action is performed on task.
function reload_companies_tables() {
    var av_companies_tables = ['.table-companies', '.table-rel-companies'];
    $.each(av_companies_tables, function (i, selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}



function companies_ajax_search(type, selector, server_data, url) {
  var ajaxSelector = $("body").find(selector);
  if (ajaxSelector.length) {
    var options = {
      ajax: {
        url:
          typeof url == "undefined"
            ? admin_url + "companies/get_relation_data"
            : url,
        data: function () {
          var data = {};
          data.type = type;
          data.rel_id = "";
          data.q = "{{{q}}}";
          if (typeof server_data != "undefined") {
            jQuery.extend(data, server_data);
          }
          return data;
        },
      },
      locale: {
        emptyTitle: app.lang.search_ajax_empty,
        statusInitialized: app.lang.search_ajax_initialized,
        statusSearching: app.lang.search_ajax_searching,
        statusNoResults: app.lang.not_results_found,
        searchPlaceholder: app.lang.search_ajax_placeholder,
        currentlySelected: app.lang.currently_selected,
      },
      requestDelay: 500,
      cache: false,
      preprocessData: function (processData) {
        var bs_data = [];
        var len = processData.length;
        for (var i = 0; i < len; i++) {
          var tmp_data = {
            value: processData[i].id,
            text: processData[i].name,
          };
          if (processData[i].subtext) {
            tmp_data.data = {
              subtext: processData[i].subtext,
            };
          }
          bs_data.push(tmp_data);
        }
        return bs_data;
      },
      preserveSelectedPosition: "after",
      preserveSelected: true,
    };
    if (ajaxSelector.data("empty-title")) {
      options.locale.emptyTitle = ajaxSelector.data("empty-title");
    }
    ajaxSelector.selectpicker().ajaxSelectPicker(options);
  }
}

function init_companies_note(rel_id) {

  $("body").on("submit", "#companies-notes", function () {
    var form = $(this);
    if (form.find('textarea[name="description"]').val() === "") {
      return;
    }

    $.post(form.attr("action"), $(form).serialize()).done(function (rel_id) {
      // Reset the note textarea value
      form.find('textarea[name="description"]').val("");
      // Reload the notes
      if (form.hasClass("companies-notes-form")) {
        get_sales_notes(rel_id, "companies");
      }
    });
    return false;
  });

};


// Validate the form assignment
function init_form_assignment(rel_type) {
  var forms = !rel_type
    ? $('[id^="form-assignment-"]')
    : $("#form-assignment-" + rel_type);

  $.each(forms, function (i, form) {
    $(form).appFormValidator({
      rules: {
        date: "required",
        //staff: "required",
        description: "required",
      },
      submitHandler: assignmentFormHandler,
    });
  });
}

// New task assignment custom function
function new_task_assignment(id) {
  var $container = $("#newTaskassignmentToggle");
  if (
    !$container.is(":visible") ||
    ($container.is(":visible") && $container.attr("data-edit") != undefined)
  ) {
    $container.slideDown(400, function () {
      fix_task_modal_left_col_height();
    });

    $("#taskassignmentFormSubmit").html(app.lang.create_assignment);
    $container
      .find("form")
      .attr("action", admin_url + "tasks/add_assignment/" + id);

    $container.find("#description").val("");
    $container.find("#date").val("");
    $container
      .find("#staff")
      .selectpicker(
        "val",
        $container.find("#staff").attr("data-current-staff")
      );
    $container.find("#notify_by_email").prop("checked", false);
    if ($container.attr("data-edit") != undefined) {
      $container.removeAttr("data-edit");
    }
    if (!$container.isInViewport()) {
      $("#task-modal").animate(
        {
          scrollTop: $container.offset().top + "px",
        },
        "fast"
      );
    }
  } else {
    $container.slideUp();
  }
}

// Edit assignment function
function edit_assignment(id, e) {
  requestGetJSON("companies/get_assignment/" + id).done(function (response) {
    var $container = $(
      ".assignment-modal-" + response.rel_type + "-" + response.rel_id
    );
    var actionURL = admin_url + "companies/edit_assignment/" + id;
    if ($container.length === 0 && $("body").hasClass("all-assignments")) {
      // maybe from view all assignments?
      $container = $(".assignment-modal--");
      $container.find('input[name="rel_type"]').val(response.rel_type);
      $container.find('input[name="rel_id"]').val(response.rel_id);
    } else if ($("#task-modal").is(":visible")) {
      $container = $("#newTaskassignmentToggle");

      if ($container.attr("data-edit") && $container.attr("data-edit") == id) {
        $container.slideUp();
        $container.removeAttr("data-edit");
      } else {
        $container.slideDown(400, function () {
          fix_task_modal_left_col_height();
        });
        $container.attr("data-edit", id);
        if (!$container.isInViewport()) {
          $("#task-modal").animate(
            {
              scrollTop: $container.offset().top + "px",
            },
            "fast"
          );
        }
      }
      actionURL = admin_url + "tasks/edit_assignment/" + id;
      $("#taskassignmentFormSubmit").html(app.lang.save);
    }

    $container.find("form").attr("action", actionURL);
    // For focusing the date field
    $container.find("form").attr("data-edit", true);
    $container.find("#description").val(response.description);
    $container.find("#date").val(response.date);
    $container.find("#staff").selectpicker("val", response.staff);
    $container
      .find("#notify_by_email")
      .prop("checked", response.notify_by_email == 1 ? true : false);
    if ($container.hasClass("modal")) {
      $container.modal("show");
    }
  });
}

// Handles assignment modal form
function assignmentFormHandler(form) {
  form = $(form);
  var data = form.serialize();
  $.post(form.attr("action"), data).done(function (data) {
    data = JSON.parse(data);
    if (data.message !== "") {
      alert_float(data.alert_type, data.message);
    }
    form.trigger("reinitialize.areYouSure");
    if ($("#task-modal").is(":visible")) {
      _task_append_html(data.taskHtml);
    }
    reload_assignments_tables();
  });

  if ($("body").hasClass("all-assignments")) {
    $(".assignment-modal--").modal("hide");
  } else {
    $(
      ".assignment-modal-" +
        form.find('[name="rel_type"]').val() +
        "-" +
        form.find('[name="rel_id"]').val()
    ).modal("hide");
  }

  return false;
}

// Reloads assignments table eq when assignment is deleted
function reload_assignments_tables() {
  var available_assignments_table = [
    ".table-assignments",
    ".table-assignments-leads",
    ".table-my-assignments",
  ];

  $.each(available_assignments_table, function (i, table) {
    if ($.fn.DataTable.isDataTable(table)) {
      $("body").find(table).DataTable().ajax.reload();
    }
  });
}

