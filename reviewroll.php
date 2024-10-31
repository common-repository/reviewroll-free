<?php
/*
Plugin Name: ReviewRoll Free
Description: Showcase reviews and testimonials from your happiest clients to increase conversion and trust on your website.
Version: 1.1
Author: ReviewRoll 
Author URI: reviewroll.com
*/

$rvrf_famems_admin_userlevel = 'edit_posts';
$rvrf_famems_db_version = '0.2';


/****** Installlation *******/
function rvrf_famems_install() {
	global $wpdb;
	$table_famems_widget = $wpdb->prefix . "reviewroll";
	
	if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
		$db_charset = 'utf8';
	$db_charset = "CHARACTER SET ".$db_charset;
	if(defined('DB_COLLATE') && $db_collate = DB_COLLATE)
		$db_collate = "COLLATE ".$db_collate;


		//Creating the table ... fresh!		
		$sql = "CREATE TABLE " . $table_famems_widget . " (
			id int(11) NOT NULL AUTO_INCREMENT,
			widget_id varchar(50) NOT NULL,		
			sort_code varchar(255) NOT NULL,		
			status varchar(15) NOT NULL,
			PRIMARY KEY  (id)
		) {$db_charset} {$db_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		 add_option( 'rvrf_famems_db_version', $rvrf_famems_db_version );

		//var_dump($t); die;	

	global $rvrf_famems_db_version;
	$options = get_option('rvrf_famems');
	$options['db_version'] = $rvrf_famems_db_version;
	update_option('rvrf_famems', $options);
}

register_activation_hook( __FILE__, 'rvrf_famems_install' );

/****** Installlation *******/





/****** Initiation of Plugin *******/

function rvrf_famems_init() {
	load_plugin_textdomain( 'rvrf_famems', null, '/testimonial_famems/languages/' );	
}

add_action('admin_menu', 'rvrf_famems_admin_menu');

/****** Initiation of Plugin *******/


/****** Adding Plugin Menu *******/

function rvrf_famems_admin_menu()
{
	global $rvrf_famems_admin_userlevel;
	add_object_page('ReviewRoll', 'ReviewRoll', $rvrf_famems_admin_userlevel, 'rvrf_famems', 'rvrf_famems_widget_list',plugins_url('ReviewRoll/icon.png'));	
	add_submenu_page( 'rvrf_famems', 'Add ReviewRoll Widget', 'Add ReviewRoll Widget', $rvrf_famems_admin_userlevel, 'rvrf_add_famems_widget', 'rvrf_add_famems_widget' );
	
}


add_action('init', 'rvrf_famems_init');


/****** Adding Plugin Menu *******/



/****** Showing widget List *******/

function rvrf_famems_widget_list(){
	global $rvrf_famems_db_version;
	$options = get_option('rvrf_famems');
	$display = $msg = $policy_lists_list = $alternate = "";

	if($options['db_version'] != $rvrf_famems_db_version )
		rvrf_famems_install();
		
		
	if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == 'rvrf_edit_testimonial_famems') {
			$display .= "<div class=\"wrap\">\n<h2>ReviewRoll Widget Details &raquo; ".__('Edit ReviewRoll Widget', 'customer-policy')."</h2>";
			$display .=  rvrf_edit_testimonial_famems_form($_REQUEST['id']);
			$display .= "</div>";
			echo $display;
			return;
		}
		else if($_REQUEST['action'] == 'rvrf_delete_famems_widget') {
			$msg = rvrf_delete_famems_widget($_REQUEST['id']);
		}else if($_REQUEST['action'] == 'testimonial_famems_list') {
			$msg = rvrf_get_famems_widget_testi_list($_REQUEST['id']);
		}
	}
	
	if(isset($_REQUEST['submit'])) {

		//print_r($_POST); die;
	    if($_REQUEST['submit'] == __('Add ReviewRoll Widget', 'skills-image')) {
			extract($_REQUEST);				
			$msg = rvrf_edit_famems_widget('', sanitize_text_field($name), sanitize_text_field($status));			
		}else if($_REQUEST['submit'] == __('Update ReviewRoll Widget', 'skills-image')) {
			extract($_REQUEST);			
			$msg = rvrf_edit_famems_widget(sanitize_text_field($id), sanitize_text_field($name), sanitize_text_field($status));			
		}
	}	
	
	if($msg)
		$display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";	
		
	$display .= "<div class=\"wrap\">";
	
	$num_policys = rvrf_famems_lists_count();
	$link = get_bloginfo('wpurl')."/wp-admin/admin.php?page=rvrf_add_famems_widget";
	$display .= "<h2>ReviewRoll Widget <a href=\"{$link}\" class=\"add-new-h2\">".__('Add new', 'company-manager')."</a></h2>";
	  global $wpdb;
$table_famems_widget = $wpdb->prefix . "reviewroll";
	$sql = "SELECT * FROM " . $table_famems_widget;
	$option_selected = array (
		'id' => '',		
		'name' => '',				
	);
	if(isset($_REQUEST['orderby'])) {
		$sql .= " ORDER BY " . sanitize_text_field($_REQUEST['orderby']) . " " . sanitize_text_field($_REQUEST['order']);
		$option_selected[sanitize_text_field($_REQUEST['orderby'])] = " selected=\"selected\"";
		$option_selected[sanitize_text_field($_REQUEST['order'])] = " selected=\"selected\"";
	}
	else {
		$sql .= " ORDER BY id DESC";
		$option_selected['id'] = " selected=\"selected\"";
		$option_selected['DESC'] = " selected=\"selected\"";
	}

	if(isset($_REQUEST['paged']) && $_REQUEST['paged'] && is_numeric($_REQUEST['paged']))
		$paged = $_REQUEST['paged'];
	else
		$paged = 1;

	$limit_per_page = 20;
	$total_pages = ceil($num_policys / $limit_per_page);
	if($paged > $total_pages) $paged = $total_pages;
	$admin_url = get_bloginfo('wpurl'). "/wp-admin/admin.php?page=rvrf_famems";
	if(isset($_REQUEST['orderby']))
		$admin_url .= "&orderby=".sanitize_text_field($_REQUEST['orderby'])."&order=".sanitize_text_field($_REQUEST['order']);
    if($num_policys > $limit_per_page) {
		$page_nav = chain_pull_lists_pagenav($total_pages, $paged, 2, 'paged', $admin_url);
	} else{ 
		$page_nav = '';
	}
	$start = ($paged - 1) * $limit_per_page;
	$sql .= " LIMIT {$start}, {$limit_per_page}";
	// Get all the testimonials from the database
	$famemswidget_testi = $wpdb->get_results($sql);

	if(count($famemswidget_testi) > 0){
	
	foreach($famemswidget_testi as $famemswidget_testi_data) {
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$famemswidget_testi_list .= "<tr{$alternate}>";
		$famemswidget_testi_list .= "<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"bulkcheck[]\" value=\"".$famemswidget_testi_data->id."\" /></th>";
		$famemswidget_testi_list .= "<td>" . $famemswidget_testi_data->id . "</td>";		
		$famemswidget_testi_list .= "<td>";
		$famemswidget_testi_list .= wptexturize(nl2br(make_clickable($famemswidget_testi_data->widget_id)));
    	$famemswidget_testi_list .= "<div class=\"row-actions\"><span class=\"edit\">
    	<a href=\"{$admin_url}&action=rvrf_edit_testimonial_famems&amp;id=".$famemswidget_testi_data->id."\" class=\"edit\">".__('Edit', 'skills-image')."</a></span> |  <span class=\"trash\"><a href=\"{$admin_url}&action=rvrf_delete_famems_widget&amp;id=".$famemswidget_testi_data->id."\" onclick=\"return confirm( '".__('Are you sure you want to delete this record?', 'customer-policy')."');\" class=\"delete\">".__('Delete', 'customer-policy')."</a></span></div>";
		$famemswidget_testi_list .= "</td>";					
		$famemswidget_testi_list .= '<td>[ReviewRollWidget code='.  $famemswidget_testi_data->sort_code.']</td>';		
		$famemswidget_testi_list .= '<td>'. $famemswidget_testi_data->status.'</td>';		
		$famemswidget_testi_list .= '<td><a href="'.$admin_url.'&action=testimonial_famems_list&amp;id='.$famemswidget_testi_data->widget_id.'" >Testimonials List</a></td>';		
		$famemswidget_testi_list .= "</tr>";
	}
	}else{
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$famemswidget_testi_list .= "<tr{$alternate}>";
		$famemswidget_testi_list .= "<td colspan=\"8\">" . __('No ReviewRoll widget records in the database', 'customer-policy') . "</td>";	  
	    $famemswidget_testi_list .= "</tr>";	
	}
	
		$chain_pull_lists_count = rvrf_famems_lists_count();
		$rvrf_famems_lists_count = rvrf_famems_lists_count();

		$display .= "<form id=\"policy_lists\" method=\"post\" action=\"".get_bloginfo('wpurl')."/wp-admin/admin.php?page=rvrf_famems\">";
		$display .= "<div class=\"tablenav\">";
		$display .= "<div class=\"alignleft actions\">";
		$display .= "<select name=\"bulkaction\">";
		$display .= 	"<option value=\"0\">".__('Bulk Actions', 'customer-policy')."</option>";
		$display .= 	"<option value=\"delete\">".__('Delete', 'customer-policy')."</option>";		
		$display .= "</select>";
		$display .= "<input type=\"submit\" name=\"bulkactionsubmit\" value=\"".__('Apply', 'customer-policy')."\" class=\"button-secondary\" />";
		$display .= "&nbsp;&nbsp;&nbsp;";
		$display .= __('Sort by: ', 'customer-policy');
		$display .= "<select name=\"orderby\">";
		$display .= "<option value=\"id\"{$option_selected['id']}>ID</option>";		
		//$display .= "<option value=\"phone\"{$option_selected['phone']}>".__('Phone', 'customer-policy')."</option>";
		$display .= "<option value=\"name\"{$option_selected['company_name']}>".__('Name', 'customer-policy')."</option>";
		//$display .= "<option value=\"policy_no\"{$option_selected['policy_no']}>".__('Policy No', 'customer-policy')."</option>";		
		$display .= "</select>";
		$display .= "<select name=\"order\"><option{$option_selected['ASC']}>ASC</option><option{$option_selected['DESC']}>DESC</option></select>";
		$display .= "<input type=\"submit\" name=\"orderbysubmit\" value=\"".__('Go', 'customer-policy')."\" class=\"button-secondary\" />";
		$display .= "</div>";
		$display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d ReviewRoll Widget', '%d ReviewRoll Widget', $rvrf_famems_lists_count, 'customer-policy'), $testimonial_famems_lists_count).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
		$display .= "<div class=\"clear\"></div>";
		$display .= "</div>";

		$display .= "<table class=\"widefat\">";
		$display .= "<thead><tr>
			<th class=\"check-column\"><input type=\"checkbox\" onclick=\"policy_lists_checkAll(document.getElementById('policy_lists'));\" /></th>
			<th>ID</th>
			<th>".__('ReviewRoll Widget', 'customer-policy')."</th>			
			<th>".__('Sort Code', 'customer-policy')."</th>			
			<th>".__('Status', 'customer-policy')."</th>			
			<th>".__('Action', 'customer-policy')."</th>			
		</tr></thead>";
		$display .= "<tfoot><tr>
			<th class=\"check-column\"><input type=\"checkbox\" onclick=\"policy_lists_checkAll(document.getElementById('policy_lists'));\" /></th>
			<th>ID</th>
			<th>".__('ReviewRoll Widget', 'customer-policy')."</th>						
			<th>".__('Sort Code', 'customer-policy')."</th>						
			<th>".__('Status', 'customer-policy')."</th>			
			<th>".__('Action', 'customer-policy')."</th>			
		</tr></tfoot>";
		$display .= "<tbody id=\"the-list\">{$famemswidget_testi_list}</tbody>";
		$display .= "</table>";

		$display .= "<div class=\"tablenav\">";
		$display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d ReviewRoll Widget', '%d ReviewRoll Widget', $rvrf_famems_lists_count, 'customer-policy'), $testimonial_famems_lists_count).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
		$display .= "<div class=\"clear\"></div>";
		$display .= "</div>";

		$display .= "</form>";
		$display .= "<br style=\"clear:both;\" />";	
	
	$display .= "</div>";
	echo $display;
}	
/****** Showing widget List *******/



/****** count of widget List *******/
function rvrf_famems_lists_count($condition = "")
{
	global $wpdb;
	$table_famems_widget = $wpdb->prefix . "reviewroll";
	$sql = "SELECT COUNT(*) FROM " . $table_famems_widget . " ".$condition;
	$count = $wpdb->get_var($sql);
	return $count;
}
/****** count of widget List *******/


/****** Testimonials/Review  List  from ReviewRoll.com *******/
function rvrf_get_famems_widget_testi_list($id)
{

$ch = curl_init( 'http://reviewroll.com/testimonial/core_widget/ajax_request.php?event=fetchTestimonials&id='.$id );

# Return response instead of printing.
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

# Send request.
$result = curl_exec($ch);
curl_close($ch);
# Print response.
//echo "<pre>";
//print_r(json_decode($result));

$TestiList = json_decode($result);
$display = '';
$display .= "<h2>Testimonials List Widget </h2>";
$display .= "<div class=\"wrap\">";
$display .= "<table class=\"widefat\">";
		$display .= "<thead><tr>
			<th>".__('Widget ID', 'customer-policy')."</th>			
			<th>".__('Date Time', 'customer-policy')."</th>			
			<th>".__('Testimonials', 'customer-policy')."</th>			
			<th>".__('Image', 'customer-policy')."</th>			
			<th>".__('Rating', 'customer-policy')."</th>			
			<th>".__('Name', 'customer-policy')."</th>			
			<th>".__('Email', 'customer-policy')."</th>			
			<th>".__('Relationship', 'customer-policy')."</th>			
			<th>".__('Status', 'customer-policy')."</th>			
			<th>".__('Action', 'customer-policy')."</th>			
		</tr></thead>";


if(count($TestiList) > 0){
	
	foreach($TestiList as $TestiList_data) {
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$display .= "<tr{$alternate}>";
			$display .= "<td>" . $TestiList_data->widget_unique_id . "</td>";		
		$display .= "<td>" . date('m/d/Y',strtotime($TestiList_data->date_time)) . "</td>";		
		$display .= "<td>";
		$display .= wptexturize(nl2br(($TestiList_data->testimonial)));
    	$display .= "</td>";			
		$display .= '<td><img height="50" width="50" src="'.$TestiList_data->files.'"></td>';		
		$display .= '<td>'.$TestiList_data->rating.' Star</td>';		
		$display .= '<td>'.$TestiList_data->name.'</td>';		
		$display .= '<td>'.$TestiList_data->email.'</td>';		
		$display .= '<td>'.$TestiList_data->relationship.'</td>';		
		$display .= '<td>'.$TestiList_data->status.'</td>';		
		$display .= '<td>';
		if($TestiList_data->status=='Pending'){ 
        	$display .= '<button onclick="UpdateStatus('.$TestiList_data->id.',\'Approve\')" class="btn btn-sm btn-primary">Approve </button>
                     	<button  onclick="UpdateStatus('.$TestiList_data->id.',\'Rejected\')" class="btn btn-sm btn-primary">Reject </button>';
        }elseif($TestiList_data->status=='Approve'){ 
        	$display .=  '<button onclick="UpdateStatus('.$TestiList_data->id.',\'Rejected\')" class="btn btn-sm btn-primary">Reject </button>';
        }elseif($TestiList_data->status=='Rejected'){
             $display .=  '<button onclick="UpdateStatus('.$TestiList_data->id.',\'Approve\')" class="btn btn-sm btn-primary">Approve </button>';
        }
        '</td>';


		$display .= "</tr>";
	}
	}else{
		if($alternate) $alternate = "";
		else $alternate = " class=\"alternate\"";
		$display .= "<tr{$alternate}>";
		$display .= "<td colspan=\"10\">" . __('No testimonials records in the database', 'customer-policy') . "</td>";	  
	    $display .= "</tr>";	
	}




		$display .= "<tfoot><tr>
			<th>".__('Widget ID', 'customer-policy')."</th>			
			<th>".__('Date Time', 'customer-policy')."</th>			
			<th>".__('Testimonials', 'customer-policy')."</th>			
			<th>".__('Image', 'customer-policy')."</th>			
			<th>".__('Rating', 'customer-policy')."</th>			
			<th>".__('Name', 'customer-policy')."</th>			
			<th>".__('Email', 'customer-policy')."</th>			
			<th>".__('Relationship', 'customer-policy')."</th>			
			<th>".__('Status', 'customer-policy')."</th>			
			<th>".__('Action', 'customer-policy')."</th>			
		</tr></tfoot>";
		$display .= "<tbody id=\"the-list\">{$famemswidget_testi_list}</tbody>";
		$display .= "</table></div>";
		$display .= "<div class=\"tablenav\">";
		$display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d Testimonials', '%d Testimonials', count($TestiList), 'customer-policy'), count($TestiList)).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
		$display .= "<div class=\"clear\"></div>";
		$display .= "</div>";
		$display .= '<script>
           
	function UpdateStatus(id,status){
	
			alert("Please Upgrade to Pro Version of Plugin.");
	}
	
	
    </script>';
echo $display; die;


}
/****** Testimonials/Review  List  from ReviewRoll.com *******/


/****** Fetching widget details *******/
function testimonial_famems_data($id)
{
	global $wpdb;
	$table_famems_widget = $wpdb->prefix . "reviewroll";
	 $sql = "SELECT *
		FROM " . $table_famems_widget . "
		WHERE id = {$id}";
	$data = $wpdb->get_row($sql, ARRAY_A);
	 //print_r($data); die;
	return $data;
}

/****** Fetching widget details *******/

/****** Edit widget details *******/
function rvrf_edit_testimonial_famems_form($id = 0){ 	
	$submit_value = __('Add ReviewRoll Widget', 'customer-policy');
	$form_name = "add_famems_widget";
	$action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=rvrf_famems#addnew";
	
	if($id) {
		$form_name = "rvrf_edit_testimonial_famems";
		$testimonial_famems_data = testimonial_famems_data($id);
		
		foreach($testimonial_famems_data as $key => $value)
			$testimonial_famems_data[$key] = $testimonial_famems_data[$key];

		extract($testimonial_famems_data);		

		//print_r(extract($testimonial_famems_data)); die;
		 $name = htmlspecialchars($widget_id);	
			//$address = htmlspecialchars($address);		
		$hidden_input = "<input type=\"hidden\" name=\"id\" value=\"{$id}\" />";
		$submit_value = __('Update ReviewRoll Widget', 'customer-policy');
		$back = "<input type=\"submit\" name=\"submit\" value=\"".__('Back', 'customer-policy')."\" />&nbsp;";
		$action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=rvrf_famems";
		
		if($status=='Active'){ $active = 'selected="selected"';}else{ $active = '';}
		if($status=='Inactive'){ $inactive = 'selected="selected"';}else{ $inactive = '';}
	}



	$name_label = __('ReviewRoll Widget Code', 'customer-policy');
	$status_label = __('Status', 'customer-policy');
	$display .='<form name="{$form_name}" method="post" action="'.$action_url.'" enctype="multipart/form-data">'.
	$hidden_input.
	'<table class="form-table" cellpadding="5" cellspacing="2">
		
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="fax">'.$name_label.'</label></th>
			<td><input type="text" id="name" name="name" value="'.$name.'" style="width:200px;"/></td>
		</tr>		
		<tr class="form-field form-required">
			<th style="text-align:left;" scope="row" valign="top"><label for="fax">'.$status_label.'</label></th>
			<td><select name="status" style="width:200px;">
				<option value="Active" '.$active.'>Active</option>
				<option value="Inactive" '.$inactive.'>Inactive</option>			
			</select></td>
		</tr>
		
	</table>
	<p class="submit"><input name="submit" value="'.$submit_value.'" type="submit" class="button button-primary" />'.$back.'</p>
</form>';

	return $display;
}

/****** Edit widget details *******/


/****** Edit DB activity widget details *******/

function rvrf_edit_famems_widget($id, $name, $status){
	global $wpdb;
	$table_famems_widget = $wpdb->prefix . "reviewroll";
	$id = esc_html($id);
	$name = esc_html($name);
	$status = esc_html($status);
	if(!$name) return __('ReviewRoll Widget Code is blank. Nothing added to the database.', 'skills-image');
	$table_name = $table_famems_widget;
		if($id !=''){
		$results = $wpdb->query(

			$wpdb->update( 
			$table_name, 
				array( 
					'widget_id' => $name,	
					'sort_code' => $name,	
					'status' => $status	
				), 
				array( 'id' => $id ), 
				array( 
					'%s',	
					'%s',	
					'%s'	
				), 
				array( '%d' ) 
			)

		);

			

		}else{
	
		$results = $wpdb->query( $wpdb->prepare( 
			"
				INSERT INTO ".$table_name."
				( widget_id, sort_code, status )
				VALUES ( %s, %s, %s )
			", 
		        array(
				$name, 
				$name, 
				$status
			) 
		) );

		}
		if(FALSE === $results)
			return __('There was an error in the MySQL query', 'customer-policy');
		else
			return __('Changes saved', 'customer-policy');
 }
/****** Edit DB activity widget details *******/


/****** Delete widget details *******/ 
 function rvrf_delete_famems_widget($id)
{
	if($id) {
		global $wpdb;
		$table_famems_widget = $wpdb->prefix . "reviewroll";
		$sql = "DELETE from " . $table_famems_widget .
			" WHERE id = " . $id;
		if(FALSE === $wpdb->query($sql))
			return __('There was an error in the MySQL query', 'customer-policy');
		else
			return __('Record deleted', 'customer-policy');
	}
	else return __('This Record cannot be deleted', 'customer-policy');
}
/****** Delete widget details *******/ 


/****** Add new lnk Population for widget *******/ 
function rvrf_add_famems_widget(){
	$display .= "<div id=\"addnew\" class=\"wrap\">\n<h2>".__('Add new', 'customer-policy')."</h2>";
	$display .= rvrf_edit_testimonial_famems_form();
	$display .= "</div>";
echo $display;
}
/****** Add new lnk Population for widget *******/ 



/****** Sort code definition of reviewroll *******/ 
add_shortcode('ReviewRollWidget', function($atts){
 extract( shortcode_atts(
            array(
                'code' => ''   // DEFAULT SLUG SET TO EMPTY
            ), $atts )
    );

$output = "<div id='famemswidget'></div>
<script type='text/javascript'>
	var widget_unit_id= '".$code."'; //Please don't change it.
  (function() {
    var fms = document.createElement('script'); fms.type = 'text/javascript'; fms.async = true;
    fms.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://') + 'reviewroll.com/testimonial/core_widget/widget.js';
    var s = document.getElementsByTagName('script')[0]; 
	s.parentNode.insertBefore(fms, s);
  })();
</script>";
    return $output;
} );
/****** Sort code definition of reviewroll *******/ 


?>