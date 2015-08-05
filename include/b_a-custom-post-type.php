<?php 
/* 
Adds custom post types to Wordpress. Custom post types can have predefined custom fields.
		
*** Constructor Parameters:	

	$postType						array		Must contain name, plural is optional (assumed to be name + 's')

	$customFields					array		Each row should be an array representing a custom field, containing keys for name, title, description (optional), and type (optional)
												Valid type values are 'text' (default), 'textarea', 'select', and 'checkbox'
												If you choose 'select', you must also include a data array such as array('Label 1' => value_1, 'Label 2' => value_2)

	$removeDefaultCustomFields 		bool		(optional, default true) 
												If true, removes the normal custom fields panel from the add/edit post screen
						   
*** Example Usage:

	include('ik-custom-post-types.php');
	$postType = array('name' => 'Location', 'plural' => 'Locations', 'slug' => 'slug');
	$fields = array();
	$fields[] = array('name' => 'address', 'title' => 'Business Address', 'description' => 'Address Of The Business', 'type' => 'textarea');	
	$myCustomType = new ikCustomPostType($postType, $fields);

*** Note: Instantiate a new ikCustomPostType object for each post type.	
						   
*/

if (!class_exists('B_A_CustomPostType')) { // prevent errors if double included
class B_A_CustomPostType
{
	var $customFields = false;
	var $customPostTypeName = 'custompost';
	var $customPostTypeSingular = 'customPost';
	var $customPostTypePlural = 'customPosts';
	var $prefix = '_ikcf_';
	
	function setupCustomPostType($postType, $custom_args = array())
	{
		$singular = ucwords($postType['name']);
		$plural = isset($postType['plural']) ? ucwords($postType['plural']) : $singular . 's';

		$this->customPostTypeName = 'b_a_' . sanitize_title($singular);
		$this->customPostTypeSingular = $singular;
		$this->customPostTypePlural = $plural;
			
		if ($this->customPostTypeName != 'post' && $this->customPostTypeName != 'page')
		{		
			$labels = array
			(
				'name' => _x($plural, 'post type general name'),
				'singular_name' => _x($singular, 'post type singular name'),
				'add_new' => _x('Add New ' . $singular, strtolower($singular)),
				'add_new_item' => __('Add New ' . $singular),
				'edit_item' => __('Edit ' . $singular),
				'new_item' => __('New ' . $singular),
				'view_item' => __('View ' . $singular),
				'search_items' => __('Search ' . $plural),
				'not_found' =>  __('No ' . strtolower($plural) . ' found'),
				'not_found_in_trash' => __('No ' . strtolower($plural) . ' found in Trash'), 
				'parent_item_colon' => ''
			);
			
			$args = array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true, 
				'query_var' => true,
				'rewrite' => array( 'slug' => $postType['slug'], 'with_front' => (strlen($postType['slug'])>0) ? false : true),
				'capability_type' => 'post',
				'hierarchical' => true,
				//'show_in_menu' => 'before-and-after-settings',
				'supports' => array('title','editor','author','thumbnail','excerpt','comments','custom-fields','page-attributes'),
			); 
			$args = array_merge($args, $custom_args);
			$this->customPostTypeArgs = $args;
	
			// register hooks
			add_action( 'init', array( $this, 'registerPostTypes' ) );
		}
	}

	function registerPostTypes()
	{
	  register_post_type($this->customPostTypeName,$this->customPostTypeArgs);
	}
	
	function setupCustomFields($fields)
	{
		$this->customFields = array();
		foreach ($fields as $f)
		{
			$this->customFields[] = array
			(
				"name"			=> $f['name'],
				"title"			=> $f['title'],
				"description"	=> isset($f['description']) ? $f['description'] : '',
				"type"			=> isset($f['type']) ? $f['type'] : "text",
				"scope"			=>	array( $this->customPostTypeName ),
				"capability"	=> "edit_posts",
				"data"			=> isset($f['data']) ? $f['data'] : false
			);
		}
		// register hooks
		add_action( 'admin_menu', array( $this, 'createCustomFields' ) );
		add_action( 'save_post', array( $this, 'saveCustomFields' ), 1, 2 );
	}
	
	/**
	* Remove the default Custom Fields meta box
	*/
	function removeDefaultCustomFields( $type, $context, $post ) 
	{
		foreach ( array( 'normal', 'advanced', 'side' ) as $context ) 
		{
			//remove_meta_box( 'postcustom', 'post', $context );
			//remove_meta_box( 'postcustom', 'page', $context );
			remove_meta_box( 'postcustom', $this->customPostTypeName, $context );//RWG
		}
	}
		
	/**
	* Create the new Custom Fields meta box
	*/
	function createCustomFields() 
	{
		if ( function_exists( 'add_meta_box' ) ) 
		{
			//add_meta_box( 'my-custom-fields', 'Custom Fields', array( $this, 'displayCustomFields' ), 'page', 'normal', 'high' );
			//add_meta_box( 'my-custom-fields', 'Custom Fields', array( $this, 'displayCustomFields' ), 'post', 'normal', 'high' );
			add_meta_box( 'my-custom-fields'.md5(serialize($this->customFields)), $this->customPostTypeSingular . ' Information', array( $this, 'displayCustomFields' ), $this->customPostTypeName, 'normal', 'high' );//RWG
		}
	}

	/**
	* Display the new Custom Fields meta box
	*/
	function displayCustomFields() {
		global $post;
		?>
		<div class="form-wrap">
			<?php
			wp_nonce_field( 'my-custom-fields', 'my-custom-fields_wpnonce', false, true );
			foreach ( $this->customFields as $customField ) {
				// Check scope
				$scope = $customField[ 'scope' ];
				$output = false;
				foreach ( $scope as $scopeItem ) {
					switch ( $scopeItem ) {
						case "post": {
							// Output on any post screen
							if ( basename( $_SERVER['SCRIPT_FILENAME'] )=="post-new.php" || $post->post_type=="post" )
								$output = true;
							break;
						}
						case "page": {
							// Output on any page screen
							if ( basename( $_SERVER['SCRIPT_FILENAME'] )=="page-new.php" || $post->post_type=="page" )
								$output = true;
							break;
						}
						default:{//RWG
							if ($post->post_type==$scopeItem )
								$output = true;
							break;
						}
					}
					if ( $output ) break;
				}
				// Check capability
				if ( !current_user_can( $customField['capability'], $post->ID ) )
					$output = false;
				// Output if allowed
				if ( $output ) { ?>
					<div class="form-field form-required">
						<?php
						switch ( $customField[ 'type' ] ) {
							case "checkbox": {
								// Checkbox
								echo '<label for="' . $this->prefix . $customField[ 'name' ] .'" style="display:inline;"><b>' . $customField[ 'title' ] . '</b></label>&nbsp;&nbsp;';
								echo '<input type="checkbox" name="' . $this->prefix . $customField['name'] . '" id="' . $this->prefix . $customField['name'] . '" value="yes"';
								if ( get_post_meta( $post->ID, $this->prefix . $customField['name'], true ) == "yes" )
									echo ' checked="checked"';
								echo '" style="width: auto;" />';
								break;
							}
							case "textarea": {
								// Text area
								echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
								echo '<textarea name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" columns="30" rows="3">' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '</textarea>';
								break;
							}
							case "select": {
								// Drop Down
								echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
								echo '<select name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" columns="30" rows="3">';
								foreach($customField['data'] as $label => $value){
									$selected = "";
									if($value == htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) )){
										$selected = 'selected="SELECTED"';
									}
									echo '<option value="'.$value.'" '.$selected.'>'.$label.'</option>';
								}
								echo '</select>';
								break;
							}
							default: {
								// Plain text field
								echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
								echo '<input type="text" name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '" />';
								break;
							}
						}
						?>
						<?php if ( $customField[ 'description' ] ) echo '<p>' . $customField[ 'description' ] . '</p>'; ?>
					</div>
				<?php
				}
			} ?>
		</div>
		<?php
	}

	/**
	* Save the new Custom Fields values
	*/
	function saveCustomFields( $post_id, $post ) {
		if ( !wp_verify_nonce( $_POST[ 'my-custom-fields_wpnonce' ], 'my-custom-fields' ) )
			return;
		if ( !current_user_can( 'edit_post', $post_id ) )
			return;
		//if ( $post->post_type != 'page' && $post->post_type != 'post')//RWG
		//	return;
		foreach ( $this->customFields as $customField ) {
			if ( current_user_can( $customField['capability'], $post_id ) ) {
				if ( isset( $_POST[ $this->prefix . $customField['name'] ] ) && trim( $_POST[ $this->prefix . $customField['name'] ] ) ) {
					update_post_meta( $post_id, $this->prefix . $customField[ 'name' ], $_POST[ $this->prefix . $customField['name'] ] );
				} else {
					delete_post_meta( $post_id, $this->prefix . $customField[ 'name' ] );
				}
			}
		}
	}

	function __construct($postType, $customFields = false, $removeDefaultCustomFields = false, $custom_args = array())
	{
		$this->setupCustomPostType($postType, $custom_args);
		
		if ($customFields)
		{
			$this->setupCustomFields($customFields);
		}

		// remove the standard custom fields box if desired
		if ($removeDefaultCustomFields)
		{
			add_action( 'do_meta_boxes', array( $this, 'removeDefaultCustomFields' ), 10, 3 );
		}
				
	}
}
}
?>