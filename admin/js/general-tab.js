jQuery(function ($) {

  $(document).on('click', '#sso_main_col_settings_tenant_id_show', function () {
    show_secrets($('#sso_main_col_settings_tenant_id'), $('#sso_main_col_settings_tenant_id_show'));
  });

  $(document).on('click', '#sso_main_col_settings_client_id_show', function () {
    show_secrets($('#sso_main_col_settings_client_id'), $('#sso_main_col_settings_client_id_show'));
  });

  $(document).on('click', '#sso_main_col_settings_client_secret_show', function () {
    show_secrets($('#sso_main_col_settings_client_secret'), $('#sso_main_col_settings_client_secret_show'));
  });

  $(document).on('click', '#sso_main_col_settings_update', function () {
    update_settings();
  });

  function show_secrets(input_ele, show_ele) {
    if (show_ele.is(':checked')) {
      input_ele.attr('type', 'text');
    } else {
      input_ele.attr('type', 'password');
    }
  }

  function update_settings() {

    // Fetch values
    let enabled = $('#sso_main_col_settings_enabled').prop('checked') ? 1 : 0;
    let tenant_id = $('#sso_main_col_settings_tenant_id').val();
    let client_id = $('#sso_main_col_settings_client_id').val();
    let client_secret = $('#sso_main_col_settings_client_secret').val();

    // Update submission form
    $('#sso_main_col_settings_form_enabled').val(enabled);
    $('#sso_main_col_settings_form_tenant_id').val(tenant_id);
    $('#sso_main_col_settings_form_client_id').val(client_id);
    $('#sso_main_col_settings_form_client_secret').val(client_secret);

    // Post submission form
    $('#sso_main_col_settings_form').submit();
  }

});
