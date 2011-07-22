<?
  /*
   * I am placing this code in the Public Domain. Do with it as you will.
   * This software comes with no guarantees or warranties but with
   * plenty of well-wishing instead!
   */
  
  // This piece of code's sole purpose is providing a small demo app for SUPA.
  // Please do not use this as a reference for your implementation 
  // as good coding style was left out for simplicitys sake!

	// the result of this script should be text/plain,
	// consisting of either "OK:" <file-url>
	//                   or "ERROR:" <errormessage>
	//
	// "client side" debuggin is easy: just print your stuff. The demo
	// application treats everything not starting with OK as an error and will
	// display it via javascript:alert()

	// the uploaded image will be put into the subdir "data" of this script
	define( FILESTORE_PATH, realpath(dirname($_SERVER['SCRIPT_FILENAME']) )."/data" );
	define( FILESTORE_URLPREFIX, "data" ); 

	// As the request is started via ajax, the session context would be available
	session_start(); 

	// this script returns plain error messages or the URL to the uploaded image
	header('Content-Type: text/plain');

	//print_r( $_FILES );
	//echo "ERROR: ".FILESTORE_PATH;
	//echo "ERROR: ".FILESTORE_URLPREFIX;
	//die();

	// see if there's a file at all
	if( ! $_FILES['screenshot'] ) {
		echo "ERROR: NO FILE (screenshot)";
		exit;
	}

  if( $_FILES['screenshot']['error'] ) {
    echo "PHP upload error: ".$_FILES['screenshot']['error'];
    exit;
  }

	// generate filename
  $filename = "default_filename.jpg";
  if( $_FILES['screenshot']['name'] ) {
    $filename = $_FILES['screenshot']['name'];
  }

  // demonstrate the automatic form posting by appending
  // otherparam to the filename
	if( $_POST['otherparam'] ) {
		$filename = $_POST['otherparam']."-".$filename;
	}

	// full path to the uploaded file
	$file= FILESTORE_PATH."/".$filename;

	// Files are uploaded base64 encoded.
	// so: base64 decode the uploaded file and copy to destination
	$fh = fopen( $_FILES['screenshot']['tmp_name'], "r" );
	if( !$fh ) {
		echo "ERROR: could not read temporary file";
		//die();
	}
	$data = fread( $fh, filesize( $_FILES['screenshot']['tmp_name'] ) );
	fclose( $fh );

	//TODO: optimize for memory usage?
	$fh = fopen( $file, "w" );
	if( !$fh ) {
		echo "ERROR: could not open destination file";
		die();
	}
	fwrite( $fh, base64_decode( $data ) );
	fclose( $fh );

	// remove temporary uploaded file
	if( is_uploaded_file( $_FILES['screenshot']['tmp_name'] ) ) {
		unlink( $_FILES['screenshot']['tmp_name'] );
	}

	// all done
	echo "OK:".FILESTORE_URLPREFIX."/".$filename;
?>

