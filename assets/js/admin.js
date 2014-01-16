jQuery(document).ready(function($) {
	if($('.edd_pl_datepicker').length ) {
		var dateFormat = 'mm/dd/yy';
		$('.edd_pl_datepicker').datetimepicker({
			dateFormat: dateFormat,
			showOtherMonths: true,
			selectOtherMonths: true,
			showButtonPanel: true
		});
	}
    if($('#edd_settings_extensions\\[edd_purchase_limit_g_start_date\\]').length ) {
		$('#edd_settings_extensions\\[edd_purchase_limit_g_start_date\\]').datetimepicker({
			dateFormat: dateFormat,
			showOtherMonths: true,
			selectOtherMonths: true,
			showButtonPanel: true
		});
		$('#edd_settings_extensions\\[edd_purchase_limit_g_end_date\\]').datetimepicker({
			dateFormat: dateFormat,
			showOtherMonths: true,
			selectOtherMonths: true,
			showButtonPanel: true
		});
    }
    $('.edd_pl_datepicker').clearable();
	$('#edd_settings_extensions\\[edd_purchase_limit_g_start_date\\]').clearable();
	$('#edd_settings_extensions\\[edd_purchase_limit_g_end_date\\]').clearable();
});
