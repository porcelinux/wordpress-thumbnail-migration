<?php
# #######################
# Script to migrate the press75-like theme thumbnails structure to the embedded wordpress thumbnails functionality
# 
#
# Alessandro del Gallo 2016
# ########################
# References
# https://codex.wordpress.org/Function_Reference/wp_insert_attachment


include( 'config.php' );


$mysqli = new mysqli( $db_srv , $db_user , $db_pass , $db_name);

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once( $siteroot . 'wp-config.php' );
require_once( $siteroot . 'wp-admin/includes/image.php' );
require_once( $siteroot . 'wp-includes/post.php');
require_once( $siteroot . 'wp-includes/functions.php');

// Thumbnails ============================
if ($mysqli->connect_errno) {
    // The connection failed. What do you want to do? 
    // You could contact yourself (email?), log the error, show a nice page, etc.
    // You do not want to reveal sensitive information

    // Let's try this:
    echo "Sorry, this website is experiencing problems.";

    // Something you should not do on a public site, but this example will show you
    // anyways, is print out MySQL error related information -- you might log this
    echo "Error: Failed to make a MySQL connection, here is why: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    
    // You might want to show them something nice, but we will simply exit
    exit;
}

// Perform an SQL query
$sql = "SELECT DISTINCT * FROM wp_postmeta WHERE meta_key LIKE '_p75_thumbnail' ";
if (!$result = $mysqli->query($sql)) {
    // Oh no! The query failed. 
    echo "Sorry, the website is experiencing problems.";

    // Again, do not do this on a public site, but we'll show you how
    // to get the error information
    echo "Error: Our query failed to execute and here is why: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $mysqli->errno . "\n";
    echo "Error: " . $mysqli->error . "\n";
    exit;
}

while ($record = $result->fetch_assoc()) {

	echo "id ".$record['meta_value']. " image\n";
	$filename_thumb = $siteroot."wp-content/thumbnails/".$record['meta_value'];
	if ( is_file($filename_thumb) ) 
		echo $filename_thumb . "\n";
	else
		echo "no thumb";
 
	$filename = $upload_dir.$record['meta_value'];
	
	if ( is_file( $filename ) ) {
		echo "skip existing file ". "\n";
		continue;
	}
	
	if ( copy ( $filename_thumb , $filename ) )
		echo "copied to ". $filename."\n";
	else
		echo "error copying";
	
	
	// The ID of the post this attachment is for.
	$parent_post_id = $record['post_id'];
	
	// Check the type of file. We'll use this as the 'post_mime_type'.
	$filetype = wp_check_filetype( basename( $filename ), null );
	
	// Get the path to the upload directory.
	$wp_upload_dir = wp_upload_dir();
	
	// Prepare an array of post data for the attachment.
	$attachment = array(
		'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);
	
	// Insert the attachment.
	$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
	
	echo "inserted image with attachment id:". $attach_id . "\n";
	
	// Generate the metadata for the attachment, and update the database record.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	
	set_post_thumbnail( $parent_post_id, $attach_id );
	
}
// VIDEO ================================
$sql = "SELECT DISTINCT * FROM wp_postmeta WHERE meta_key LIKE '_videoembed_manual' ";
if (!$result = $mysqli->query($sql)) {
    // Oh no! The query failed. 
    echo "Sorry, the website is experiencing problems.";
    // Again, do not do this on a public site, but we'll show you how
    // to get the error information
    echo "Error: Our query failed to execute and here is why: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $mysqli->errno . "\n";
    echo "Error: " . $mysqli->error . "\n";
    exit;
}
while ($record = $result->fetch_assoc()) {
	$idpost=$record['post_id'];
	preg_match_all('/(src|value)="([^"]+)"/', $record['meta_value'], $matches);
	$video_url 	= $matches[2][0];
	$query_video = "INSERT into wp_postmeta(post_id,meta_key,meta_value) VALUES('".$record['post_id']."','ondemand_video_embed','".$video_url."') ";
	echo $query_video. "\n";
	$insert_result = $mysqli->query( $query_video );
	print_r($insert_result);
}
echo "\nfinished\n";
// The script will automatically free the result and close the MySQL
// connection when it exits, but let's just do it anyways
$result->free();
$mysqli->close();
?>

