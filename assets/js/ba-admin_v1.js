jQuery(function ()
{
	jQuery('#b_a_help .gp_code_example, #goal_shortcodes .gp_code_example').bind('click', function () {
		jQuery(this).trigger('focus').select();
	});
});