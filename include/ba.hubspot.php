<?php
//Takes Data from BA submission and passes it to HubSpot

class BA_HubSpot
{
	var $root;
	var $options;
	
	public function __construct(&$root)
	{
		$this->root = $root;		
		$this->options = get_option( 'b_a_options' );	
		
		//on cf7 or gf submission, if logic active and BA Pro is active, check to see if submission
		//is from a form matched to a goal
		if (isset($this->options['send_to_hubspot']) && $this->root->is_pro()){
			//Contact Form 7!
			add_action("wpcf7_before_send_mail", array( $this, "capture_cf7_form_submissions") );	
			
			//Gravity Forms!
			add_action("gform_after_submission", array( $this, "capture_gf_form_submissions"), 10, 2 );
		}
	}
	
	// Looks for Goals that are hooked to this form
	// If any are found, send this submission off for processing
	// Contact Form 7
	function capture_cf7_form_submissions ($WPCF7_ContactForm)
	{
		$form_id = $WPCF7_ContactForm->id();
		$goal = $this->find_goal_by_cf7_form_id($form_id);
		
		//this form is matched to a goal -- do something!
		if ($goal) {
			$this->add_to_hubspot($WPCF7_ContactForm);
		}
	}
	
	// Passed a CF7 Form ID, finds Goals matched to this Form
	function find_goal_by_cf7_form_id($form_id)
	{
		$goal_selector = 'cf7_' . intval($form_id);
		$conditions = array('post_type' => 'b_a_goal', 
							'meta_key' => '_goal_selector',
							'meta_value' => $goal_selector,
							);
		$posts = get_posts($conditions);
		if ($posts) {
			return $posts[0];
		} else {
			return FALSE;
		}
	}	
	
	// Looks for Goals that are hooked to this form
	// If any are found, send this submission off for processing
	// Gravity Forms
	function capture_gf_form_submissions ($entry, $form)
	{
		$form_id = $form['id'];
		$goal = $this->find_goal_by_gf_form_id($form_id);
		if ($goal) {
			$this->add_to_hubspot(false, $entry, $form);
		}
	}
	
	// Passed a GF Form ID, finds Goals matched to this Form
	function find_goal_by_gf_form_id($form_id)
	{
		$goal_selector = 'gform_' . intval($form_id);
		$conditions = array('post_type' => 'b_a_goal', 
							'meta_key' => '_goal_selector',
							'meta_value' => $goal_selector,
							);
		$posts = get_posts($conditions);
		if ($posts) {
			return $posts[0];
		} else {
			return FALSE;
		}
	}
	
	//This array maps common field names to their Hubspot equivalents
	//TBD: Build a user interface that allows custom control over these field mappings
	//Result is filterable via ba_hubspot_field_mappings so the array can be modified
	function loadHubSpotFieldMappings(){
		$fieldMappings = array(
			'first-name' 	=> 'FirstName',
			'last-name' 	=> 'LastName',
			'your-email' 	=> 'Email',						
			'email' 		=> 'Email',						
			'your-phone' 	=> 'Phone',
			'phone' 		=> 'Phone',
			'phonenumber' 	=> 'Phone',
			'your-fax' 		=> 'Fax',						
			'fax' 			=> 'Fax',						
			'your-company' 	=> 'Your Company',
			'company' 		=> 'Your Company',
			'your-address' 	=> 'Address',
			'address' 		=> 'Address',
			'job-title' 	=> 'JobTitle',
			'your-city'		=> 'City',
			'city'		 	=> 'City',
			'your-state'	=> 'State',
			'state'		 	=> 'State',
			'your-zip'		=> 'ZipCode',
			'your-zipcode'	=> 'ZipCode',
			'zipcode'		=> 'ZipCode',
			'your-country'	=> 'Country',
			'country'		=> 'Country',
			'your-website'	=> 'Website',
			'website'		=> 'Website',
			'your-message'	=> 'Message',
			'message'		=> 'Message',
		);
		
		return apply_filters('ba_hubspot_field_mappings', $fieldMappings);
	}
	
	//This array contains default Gravity Form fields that are passed with every submission
	//and that we don't want sent to Hubspot
	//TBD: Build a user interface that allows custom control over these field mappings
	//Result is filterable via ba_gform_default_fields so the array can be modified	
	function loadGFormDefaultFields(){			
		//default gravity form fields that are including with every submission that we don't want going to hubspot
		$gform_defaults = array(
			'id',
			'form_id',
			'date_created',
			'is_starred',
			'is_read',
			'ip',
			'source_url',
			'post_id',
			'currency',
			'payment_status',
			'payment_date',
			'transaction_id',
			'payment_amount',
			'payment_method',
			'is_fulfilled',
			'created_by',
			'transaction_type',
			'user_agent',
			'status'				
		);
		
		return apply_filters('ba_gform_default_fields', $gform_defaults);
	}
	
	//passed an array of gravity forms submission data
	//loads labels from field ids and builds array keyed by field labels
	//will skip any fields in the gform_defaults array
	function normalizeGFormData($data = array(), $gf_form){	
		//default gravity form fields that are including with every submission that we don't want going to hubspot
		$gform_defaults = $this->loadGFormDefaultFields();
		
		//replace field IDs with field Labels before mapping to HubSpot equivalents
		foreach($data as $gform_key => $gform_value){
			//skip the gform defaults that aren't relevant to our hubspot submission
			if(in_array($gform_key, $gform_defaults)){
				continue;
			} else {
				//inspired by http://stackoverflow.com/questions/17664490/get-gravity-forms-input-labels-instead-of-value
				$field = GFFormsModel::get_field($gf_form, $gform_key);
				if(is_array(rgar($field, "inputs"))){ // For the "1.1" etc ID's
					foreach($field["inputs"] as $input){
						if ( $input['id'] == $gform_key ) { 
							$label = $input['label'];
							
							$normalized_data[$label] = $gform_value;
						}
					}
				} else {
					$label = GFFormsModel::get_label($field);
					
					$normalized_data[$label] = $gform_value;
				}
			}
		}
		
		return $normalized_data;
	}
	
	function buildHubSpotPostString($data = array(), $form_title = ''){		
		$strPost = "";
	
		//load array of common HS field mappings
		$fieldMappings = $this->loadHubSpotFieldMappings();
		
		foreach ($fieldMappings as $form_field => $hubspot_field) {
			// if the field appears in the POSTed data, pass it along to hubspot.
			if(isset($data[$form_field])) {
				$strPost .= "&{$hubspot_field}=" . urlencode($data[$form_field]);
				unset($data[$form_field]);
			}
		}
		
		// add any other fields that didn't match a special HubSpot field as HubSpot Custom Fields
		if (count($data) > 0) {
			foreach ($data as $field_name => $field_value) {
				// skip fields that start with '_wp' (e.g., _wp_nonce, _wpcf7_is_ajax_call, etc) or recaptcha
				if (strpos($field_name, '_wp') === 0 || strpos($field_name, 'recaptcha') === 0) {
					continue;			
				}
				
				// allow hidden field to override form_guid
				if ($field_name == 'form_guid') {
					//if $field_value is an array (shouldn't be), strip it down to its 1st value
					if (is_array($field_value)) {
						$field_value = $field_value[0];
					}
					
					// sanitize GUID: only alphanumeric, hyphen, and underscore allowed (will be part of an URL)
					$clean_field_value = preg_replace('/[^a-zA-Z0-9_-]$/s', '', $field_value);
					$form_guid = $clean_field_value;
					
					// dont pass this form field in the post data
					continue;
				}
				
				// sanitize the field name, implode arrays, urlencode, and add to the the POST string
				$field_name = str_replace('-', '', $field_name);
				$field_name = urlencode($field_name);
				if (is_array($field_value)) {
					$field_value = implode(',', $field_value);
				}
				$strPost .= "&{$field_name}=" . urlencode($field_value);
			} // next field
		}

		// Add the new information required by the Hubspot3 Forms API (hx_context) to strPost
		$hubspotutk = isset($_COOKIE['hubspotutk']) ? $_COOKIE['hubspotutk'] : "no-cookie-present"; //grab the cookie from the visitors browser, should be present if the HubSpot script is on the site TBD: check if required
		$ip_addr = $_SERVER['REMOTE_ADDR']; //IP address too.
		$hs_context = array(
			'hutk' => $hubspotutk,
			'ipAddress' => $ip_addr,
			'pageName' => $form_title,
			//'pageUrl' => 'http://www.example.com/form-page',
			//'pageName' => 'Example Title',
		);
		$hs_context_json = json_encode($hs_context);	
		$strPost .= "&hs_context=" . urlencode($hs_context_json);

		// add in the form's title for tracking (title is also being submitted as the pageName in hs_context, as of Hubspot3 integration)
		$strPost .= "&FormTitle=" . urlencode($form_title);
		
		// if the front of the string starts with &, chop it off
		if (strpos($strPost, '&') === 0) {
			$strPost = substr($strPost, 1);
		}
		
		return $strPost;
	}
	
	//collate data and send to hubspot
	//won't send if form is on blacklist
	function add_to_hubspot($cf7 = false, $gf_entry = false, $gf_form = false)
	{		
		if (isset($this->options['portal_id']) && isset($this->options['form_guid'])) {
			/*
			Portal ID is now called HUB ID
			http://help.hubspot.com/articles/KCS_Article/Account/Where-can-I-find-my-HUB-ID
			*/
			$portal_id = $this->options['portal_id'];
			/*
			http://help.hubspot.com/articles/KCS_Article/Forms/How-do-I-find-the-form-GUID
			*/
			$form_guid = $this->options['form_guid']; 
		} else {
			//no settings with hubspot info?
			//we cant do anything!
			return;
		}
		
		// build the POST string by looking for any specifically named form fields that match up to special hubspot fields
		$strPost = "";
		
		//if its a cf7 submission
		if($cf7 && !$gf_entry && !$gf_form){			
			/* CF7 Form Processing */
			$submission = WPCF7_Submission::get_instance();
			$data = $submission->get_posted_data();	
			
			/* Grab the Form's Title */
			$form_title = $cf7->title();
		
			/* Don't submit specific Forms to hubspot! */
			$titles = explode(",",$this->options['hubspot_blacklist']);
			if (in_array($form_title, $titles)) {
				return;
			}
			
			//builds the HS post string from the submitted data
			$strPost = $this->buildHubSpotPostString($data, $form_title);
			
			/* End CF7 Specifics */
		} else if(!$cf7 && $gf_entry && $gf_form){//if its a gf submission		
			/* GF Form Processing */
			$data = $gf_entry;
			
			/* Grab the Form's Title */
			$form_title = $gf_form['title'];
		
			/* Don't submit specific Forms to hubspot! */
			$titles = explode(",",$this->options['hubspot_blacklist']);
			if (in_array($form_title, $titles)) {
				return;
			}
			
			//reformat our gform submission data for processing
			$data = $this->normalizeGFormData($data, $gf_form);
			
			//builds the HS post string from the submitted data
			$strPost = $this->buildHubSpotPostString($data, $form_title);	
			
			/* End GF Specifics */
		} else {//if its neither, not sure why we are even here, return
			return;
		}

		// values for $portal_id and $form_guid are set at the top of the function
		// $form_guid can be overriden by a hidden field in the form
		$endpoint = 'https://forms.hubspot.com/uploads/form/v2/' . $portal_id . '/' . $form_guid . '';		
		
		//intialize cURL and send POST data
		//TBD: replace this with wordpress equivalents
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $strPost);
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		curl_close($ch);	
		
		//END HubSpot Lead Submission
	}
}//END BA_HubSpot
?>