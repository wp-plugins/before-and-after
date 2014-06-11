<?php
class BA_Notification_Model
{
    /**
     * Holds the values to be used in the fields callbacks
     */
	const post_type = 'b_a_conversion';
	private $root;
	private $plugin_title;

    /**
     * Start up
     */
    public function __construct($root)
    {
		$this->root = $root;
		$this->plugin_title = $root->plugin_title;
    }
	
	public function send($goal_id, $visitor_details)
	{
		// load the goal's data
		$goal = get_post($goal_id);
		
		// check for an invalid goal
		if (!$goal) {
			 return false;
		}

		$ba_settings = get_option('b_a_options');

		// grab the "to" field from the plugin's settings
		$send_to = isset($ba_settings['email']) ? $ba_settings['email'] : '';
		
		// grab the "subject" field from the plugin's settings
		$subject = isset($ba_settings['subject']) ? $ba_settings['subject'] : '';

		// replace [goal_name] merge variable in the subject line
		$subject = str_replace('[goal_name]', $goal->post_title, $subject);
		
		// grab the "body" field from the plugin's settings
		$body = isset($ba_settings['email_body']) ? $ba_settings['email_body'] : '';

		// create the HTML table that lists the details of the conversion. maybe a <table>?
		$block = $this->generate_conversion_data_table_html($goal, $visitor_details);

		// append the HTML table of conversion data table to the email body
		$body .= "<br><br>" . $block;
		
		// setup the email headers to allow HTML email
		$headers = 'Content-type: text/html' . "\r\n";
		
		// 6: send the email using wp_mail
		$debug = false;
		if ($debug == true)
		{
			var_dump('<br />' . $send_to);
			var_dump('<br />' . $subject);
			var_dump('<br />' . $body);
			var_dump('<br />' . $headers);
			die('<br />' . 'testing: ' . $subject);
		}
		else
		{		
			$mail_success = wp_mail( $send_to, $subject, $body, $headers );
		}
		
		// 7: return true if sending the email worked, or false if not
		return $mail_success;

	}
	
	private function generate_conversion_data_table_html($goal, $visitor_details)
	{
		$html = '';
		// first build a nice array of all the labels and values we want to include
		$fieldsToSendInEmail = array('Goal Name' => $goal->post_title,
									 'Time Completed' => isset($visitor_details['friendly_date']) ? $visitor_details['friendly_date'] : '',
									 'Starting URL' => isset($visitor_details['goal_start_url']) ? $visitor_details['goal_start_url'] : '',
									 'Ending URL' => isset($visitor_details['goal_complete_url']) ? $visitor_details['goal_complete_url'] : '',
									 'Visitor IP' => isset($visitor_details['ip_address']) ? $visitor_details['ip_address'] : '',
									 'Visitor Location' => isset($visitor_details['friendly_location']) ? $visitor_details['friendly_location'] : '',
									);	
		
		// now build an HTML table with all of the labels and values included
		$html .= '<table cellpadding="0" cellspacing="0" style="border-top: 1px solid gray; border-left: 1px solid gray; border-right: 1px solid gray;">';
		foreach($fieldsToSendInEmail as $field_name => $field_value)
		{
			$html .= '<tr>';
				$html .= '<td style="padding: 10px; border-right: 1px solid gray;  border-bottom: 1px solid gray; background-color: #B0C4DE; font-weight:bold;">' . htmlentities($field_name) . '</td>';
				$html .= '<td style="padding: 10px; border-bottom: 1px solid gray;">' . htmlentities($field_value) . '</td>';				
			$html .= '</tr>';
		}
		$html .= '</table>';		
		
		// return the completed table
		return $html;
	}

}