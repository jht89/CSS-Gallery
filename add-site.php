<?php 

$html="";
/*
Template Name: Add Site to Gallery
*/


if($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["action"]) &&  $_POST["action"] == "new_site") {
	error_reporting (E_ALL ^ E_NOTICE);
	session_start();
	//$_SESSION['random_key']='';
	//only assign a new timestamp if the session variable is empty
	if (!isset($_SESSION['random_key']) || strlen($_SESSION['random_key'])==0){
		$_SESSION['random_key'] = strtotime(date('Y-m-d H:i:s')); //assign the timestamp to the session variable
		$_SESSION['user_file_ext']= "";
	}
	// Photo Upload constants
	$uploads = wp_upload_dir(); 
	$upload_dir = $uploads["url"]; 	
	$upload_dir = substr_replace($upload_dir, "", 0, 32);		
	$upload_path = $upload_dir."/";		
	$large_image_prefix = "resize_"; 			
	$thumb_image_prefix = "thumbnail_";			
	$large_image_name = $large_image_prefix.$_SESSION['random_key'];
	$thumb_image_name = $thumb_image_prefix.$_SESSION['random_key'];
	$max_file = "3"; 						
	$max_width = "500";						
	$thumb_width = "100";						
	$thumb_height = "100";
	$allowed_image_types = array('image/pjpeg'=>"jpg",'image/jpeg'=>"jpg",'image/jpg'=>"jpg", 'image/png' =>"png");
	$allowed_image_ext = array_unique($allowed_image_types); // do not change this
	$image_ext = "";	// initialise variable, do not change this.
	foreach ($allowed_image_ext as $mime_type => $ext) {
		$image_ext.= strtoupper($ext)." ";
	}

	//Form data
	$contact_name= $_POST["contact_name"];
	$site_name= $_POST["site_name"];
	$description= $_POST["description"];
	$contact_email= $_POST["contact_email"];
	$url= $_POST["url"];
	$premium= $_POST["premium"];
	$freemium_url = $_POST["freemium_url"];


	//Image Functions
	function resizeImage($image,$width,$height,$scale) {
		list($imagewidth, $imageheight, $imageType) = getimagesize($image);
		$imageType = image_type_to_mime_type($imageType);
		$newImageWidth = ceil($width * $scale);
		$newImageHeight = ceil($height * $scale);
		$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($image); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($image); 
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($image); 
				break;
		}
		imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);
		
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$image); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$image,90); 
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$image);  
				break;
		}
		
		chmod($image, 0777);
		return $image;
	}
//You do not need to alter these functions
	function resizeThumbnailImage($thumb_image_name, $image, $width, $height, $start_width, $start_height, $scale){
		list($imagewidth, $imageheight, $imageType) = getimagesize($image);
		$imageType = image_type_to_mime_type($imageType);
		
		$newImageWidth = ceil($width * $scale);
		$newImageHeight = ceil($height * $scale);
		$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
		switch($imageType) {
			case "image/gif":
				$source=imagecreatefromgif($image); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source=imagecreatefromjpeg($image); 
				break;
			case "image/png":
			case "image/x-png":
				$source=imagecreatefrompng($image); 
				break;
		}
		imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
		switch($imageType) {
			case "image/gif":
				imagegif($newImage,$thumb_image_name); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage,$thumb_image_name,90); 
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$thumb_image_name);  
				break;
		}
		chmod($thumb_image_name, 0777);
		return $thumb_image_name;
	}
	//You do not need to alter these functions
	function getHeight($image) {
		$size = getimagesize($image);
		$height = $size[1];
		return $height;
	}
	//You do not need to alter these functions
	function getWidth($image) {
		$size = getimagesize($image);
		$width = $size[0];
		return $width;
	}

	//Image Locations
	$large_image_location = $upload_path.$large_image_name.$_SESSION['user_file_ext'];
	$thumb_image_location = $upload_path.$thumb_image_name.$_SESSION['user_file_ext'];
	if(!is_dir($upload_dir)){
		mkdir($upload_dir, 0777);
		chmod($upload_dir, 0777);
	}


	//Check to see if any images with the same name already exist
	if (file_exists($large_image_location)){
		if(file_exists($thumb_image_location)){
			$thumb_photo_exists = "<img src=\"".$upload_path.$thumb_image_name.$_SESSION['user_file_ext']."\" alt=\"Thumbnail Image\"/>";
		}else{
			$thumb_photo_exists = "";
		}
		$large_photo_exists = "<img src=\"".$upload_path.$large_image_name.$_SESSION['user_file_ext']."\" alt=\"Large Image\"/>";
	} else {
		$large_photo_exists = "";
		$thumb_photo_exists = "";
	}

   if (isset($_POST["submit"])) {  
        //Get the file information
		$userfile_name = $_FILES['image']['name'];
              $userfile_tmp = $_FILES['image']['tmp_name'];	
		$userfile_size = $_FILES['image']['size'];
		$userfile_type = $_FILES['image']['type'];
		$filename = basename($_FILES['image']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));

		//Only process if the file is a JPG, PNG or GIF and below the allowed limit
		if((!empty($_FILES["image"])) && ($_FILES['image']['error'] == 0)) {
			foreach ($allowed_image_types as $mime_type => $ext) {
				//loop through the specified image types and if they match the extension then break out
				//everything is ok so go and check file size
				if($file_ext==$ext && $userfile_type==$mime_type){
					$error = "";
					break;
				}else{
					$error = "Only <strong>".$image_ext."</strong> images accepted for upload<br />";
				}
			}
			//check if the file size is above the allowed limit
			if ($userfile_size > ($max_file*1048576)) {
				$error.= "Images must be under ".$max_file."MB in size";
			}
		
		}elseif(isset($_POST["picture"])){
			$uploaded=$_POST["picture"];
		}else{
			$error= "Select an image for upload";
		}

	//Everything is ok, so we can upload the image.
		if (strlen($error)==0){
			if (isset($_FILES['image']['name'])){
				//this file could now has an unknown file extension (we hope it's one of the ones set above!)
				$large_image_location = $large_image_location.".".$file_ext;;
				$thumb_image_location = $thumb_image_location.".".$file_ext;;
				
				//put the file ext in the session so we know what file to look for once its uploaded
				$_SESSION['user_file_ext']=".".$file_ext;
				
				move_uploaded_file($userfile_tmp, $large_image_location);
				chmod($large_image_location, 0777);
				
				$width = getWidth($large_image_location);
				$height = getHeight($large_image_location);
				//Scale the image if it is greater than the width set above
				if ($width > $max_width){
					$scale = $max_width/$width;
					$uploaded = resizeImage($large_image_location,$width,$height,$scale);
				}else{
					$scale = 1;
					$uploaded = resizeImage($large_image_location,$width,$height,$scale);
				}
				 
			}
		}
  
    }

	$html.=<<<EOT
	Please check that the details you have entered are correct before submitting.<br />

	<strong>Your Name:</strong> {$contact_name} <br /><strong>E-mail:</strong>{$contact_email}<br /><strong>Website URL:</strong> {$url}<br /> <strong>Website Title:</strong> {$site_name}<br /> <strong>Website Description:</strong> {$description}<br /> <strong>Listing Type:</strong> {$premium} <br />
	<strong>Screenshot:</strong> <img src="http://www.technographics.co.uk/{$uploaded}"  /> {$error}

	<form action="" method="POST" name="edit" id="edit">
	<input type="hidden" name="contact_name" id="contact_name" value="{$contact_name}" />
	<input type="hidden" name="contact_email" id="contact_email" value="{$contact_email}" />
	<input type="hidden" name="site_name" id="site_name" value="{$site_name}" />
	<input type="hidden" name="image" id="image" value="http://www.technographics.co.uk/{$uploaded}" />
	<input type="hidden" name="url" id="url" value="{$url}" />
	<input type="hidden" id="description" name="description" value="{$description}" />
	<input type="hidden" id="premium" name="premium" value="{$premium}" />
	<input type="hidden" id="freemium_url" name="freemium_url" value="{$freemium_url}" />
	<input type="hidden" name="action" value="edit" />
	<input type="submit" value="Edit" />
	</form>
	<form action="" method="POST" name="process" id="process">
	<input type="hidden" name="contact_name" id="contact_name" value="{$contact_name}" />
	<input type="hidden" name="contact_email" id="contact_email" value="{$contact_email}" />
	<input type="hidden" name="site_name" id="site_name" value="{$site_name}" />
	<input type="hidden" name="image" id="image" value="http://www.technographics.co.uk/{$uploaded}" />
	<input type="hidden" name="url" id="url" value="{$url}" />
	<input type="hidden" id="description" name="description" value="{$description}" />
	<input type="hidden" id="premium" name="premium" value="{$premium}" />
	<input type="hidden" id="freemium_url" name="freemium_url" value="{$freemium_url}" />
	<input type="hidden" name="action" value="process" />
	<input type="submit" value="Continue" />
	</form>
EOT;
	
}elseif('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['action']) &&  $_POST['action'] == "edit"){

	$html=<<<EOT

	<form action="" enctype="multipart/form-data" method="POST" id="process" name="process" onsubmit="return formCheck(this);">
EOT;
	if($_POST["premium"]=="free"){
		$html.=<<<EOT
	
		<label for="premium">Listing Type: </label><select name="premium" id="premium" />
		<option value="free" selected="selected">Free</option>
		<option value="freemium">Freemium</option>
		<option value="premium">Premium</option>
		</select><br /><br />
EOT;
	}
	elseif($_POST["premium"]=="freemium"){
		$html.=<<<EOT
	
		<label for="premium">Listing Type: </label><select name="premium" id="premium" />
		<option value="free">Free</option>
		<option value="freemium" selected="selected">Freemium</option>
		<option value="premium">Premium</option>
		</select><br /><br />
EOT;
	}
	else{
		$html.=<<<EOT
	
		<label for="premium">Listing Type: </label><select name="premium" id="premium" />
		<option value="free">Free</option>
		<option value="freemium">Freemium</option>
		<option value="premium" selected="selected">Premium</option>
		</select><br /><br />
EOT;
	}
	
	$html.=<<<EOT
		<label for="contact_name">Your Name:</label><input type="text" name="contact_name" id="contact_name" value="{$_POST["contact_name"]}" /><br /><br />
		<label for="contact_email">E-mail: </label><input type="text" name="contact_email" id="contact_email" value="{$_POST["contact_email"]}"/><br /><br />
		<label for="url">Website URL: </label><input type="text" name="url" id="url" value="{$_POST["url"]}" /><br /><br />
		<label for="site_name">Website Title: </label><input type="text" name="site_name" id="site_name" value="{$_POST["site_name"]}" /><br /><br />
		<label for="freemium_url">Location of Reciprocal Link (Freemium submission only):</label><input type="text" name="freemium_url" id="freemium_url" value="{$_POST["freemium_url"]}" /><br /><br />
		<label for="description">Description: </label><textarea name="description" id="description" value="{$_POST["description"]}"></textarea><br /><br />
		<label for="image">Screenshot: </label><img src="{$_POST["image"]}" /><input name="image" type="file"><input type="hidden" id="picture" name="picture" value="{$_POST["image"]}" /> <br /><br /><br /><br />
		<input type="hidden" name="action" value="new_site" />
		<input name="submit" id="submit" type="submit" value="Submit" />
	</form>
EOT;
}elseif('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_POST['action']) &&  $_POST['action'] == "process"){
	$contact_name= $_POST["contact_name"];
	$site_name= $_POST["site_name"];
	$description= $_POST["description"];
	$contact_email= $_POST["contact_email"];
	$image= $_POST['image'];
	$url= $_POST["url"];
	$premium= $_POST["premium"];
	$freemium_url = $_POST["freemium_url"];
	
	$my_listing = array(
		'post_title' => $site_name,
		'post_content' => $description,
		'post_status' => 'pending',
		'post_type' => 'listing',
	);
	$post_id=wp_insert_post($my_listing, false);
	$gallery_url=get_bloginfo('url');
	if ($post_id != 0){
		add_post_meta($post_id, 'url', $url);
		add_post_meta($post_id,'listing type' , $premium);
		add_post_meta($post_id, 'contact_name', $contact_name);
		add_post_meta($post_id, 'contact_email', $contact_email);
		add_post_meta($post_id, 'freemium_url', $freemium_url);
		
		$wp_filetype = wp_check_filetype(basename($image), null );
		$wp_upload_dir = wp_upload_dir();
  		$attachment = array(
     			'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
     			'post_mime_type' => $wp_filetype['type'],
     			'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
     			'post_content' => $site_name,
     			'post_status' => 'inherit'
 		 );
  		$attach_id = wp_insert_attachment( $attachment, $image, $post_id );
  		require_once(ABSPATH . 'wp-admin/includes/image.php');
  		$attach_data = wp_generate_attachment_metadata( $attach_id, $image );
  		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post_id, $attach_id );

		$html.=<<<EOT
		Thank you for submitting your site to this gallery. Your site will now be reviewed to see if it meets our standards before it will placed in the gallery.
			<a href="{$gallery_url}"> Click here</a> to return to the home page.
EOT;
	}else{
		
		$html.=<<<EOT
		There was an error with your submission, please contact the website admin who will look into it.
		<a href="{$gallery_url}"> Click here</a> to return to the home page.
EOT;
	}
}else{
$html.=<<<EOT

Please complete the following form and then click submit.

<form action="" id="new_site" name="new_site" enctype="multipart/form-data" method="POST" onsubmit="return formCheck(this);">
<label for="premium">Listing Type: </label><select name="premium" id="premium" />
<option value="free" selected="selected">Free</option>
<option value="freemium">Freemium</option>
<option value="premium">Premium</option>
</select><br /><br />
<label for="contact_name">Your Name:</label><input type="text" name="contact_name" id="contact_name" /><br /><br />
<label for="contact_email">E-mail: </label><input type="text" name="contact_email" id="contact_email" /><br /><br />
<label for="url">Website URL: </label><input type="text" name="url" id="url" value="http://"/><br /><br />
<label for="site_name">Website Title: </label><input type="text" name="site_name" id="site_name" /><br /><br />
<label for="freemium_url">Location of reciprocal URL: (Freemium submission only) </label><input type="text" name="freemium_url" id="freemium_url" value="http://"/><br /><br />
<label for="description">Description: </label><textarea name="description" id="description"></textarea><br /><br />
<label for="image">Screenshot: </label><input name="image" type="file"> <br /><br />
<input type="hidden" name="action" value="new_site" />
<input name="submit" id="submit" type="submit" value="Submit" />
</form>
EOT;

}


?>
<script language="JavaScript">
/***********************************************
* Required field(s) validation v1.10- By NavSurf
* Visit Nav Surf at http://navsurf.com
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

function formCheck(formobj){
	// Enter name of mandatory fields
	var fieldRequired = Array("contact_name", "site_name", "url", "description", "contact_email", "image", "premium");
	// Enter field description to appear in the dialog box
	var fieldDescription = Array("Contact Name", "Site Name", "Site URL", "Description", "Contact Email", "Screenshot", "Listing Type");
	// dialog message
	var alertMsg = "Please complete the following fields:\n";
	
	var l_Msg = alertMsg.length;
	
	for (var i = 0; i < fieldRequired.length; i++){
		var obj = formobj.elements[fieldRequired[i]];
		if (obj){
			switch(obj.type){
			case "select-one":
				if (obj.selectedIndex == -1 || obj.options[obj.selectedIndex].text == ""){
					alertMsg += " - " + fieldDescription[i] + "\n";
				}
				break;
			case "select-multiple":
				if (obj.selectedIndex == -1){
					alertMsg += " - " + fieldDescription[i] + "\n";
				}
				break;
			case "text":
			case "textarea":
				if (obj.value == "" || obj.value == null){
					alertMsg += " - " + fieldDescription[i] + "\n";
				}
				break;
			default:
			}
			if (obj.type == undefined){
				var blnchecked = false;
				for (var j = 0; j < obj.length; j++){
					if (obj[j].checked){
						blnchecked = true;
					}
				}
				if (!blnchecked){
					alertMsg += " - " + fieldDescription[i] + "\n";
				}
			}
		}
	}

	if (alertMsg.length == l_Msg){
		return true;
	}else{
		alert(alertMsg);
		return false;
	}
}
</script>
<?php
get_header();
echo $html;
get_footer();
?>