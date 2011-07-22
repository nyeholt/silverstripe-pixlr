<?php
  // vim :set ts=2 sw=2 expandtab

  /*
   * I am placing this code in the Public Domain. Do with it as you will.
	 * This software comes with no guarantees or warranties but with
	 * plenty of well-wishing instead!
   */

  // This piece of code's sole purpose is providing a small demo app for SUPA.
  // Please do not use this as a reference for your implementation 
  // as good coding style was left out for simplicitys sake!

  session_start();
  $sess = session_id();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html14/loose.dtd">
<html>
<head>
  <title>SUPA - the Screenshot Upload Applet</title>
  <script type="text/javascript" src="Supa.js"></script>
</head>
<body>
  <strong>I strongly recommend the latest version of the <a href="http://www.java.com">Java plugin</a></strong><br>

  <form name="form" action="#none">

    <!-- This button triggers the clipboard-paste -->
    <input type="button" value="paste image from clipboard" onclick="return paste();"><br>

    <!-- This is the applet that will receive the image from the clipboard -->
    Image preview:
    <div style="border: 1px solid">
      <applet id="SupaApplet"
              archive="Supa.jar"
              code="de.christophlinder.supa.SupaApplet" 
              width="200" 
              height="200">
        <!--param name="clickforpaste" value="true"-->
        <param name="imagecodec" value="png">
        <param name="encoding" value="base64">
        <param name="previewscaler" value="fit to canvas">
        <!--param name="trace" value="true"-->
        Applets disabled :(
      </applet> 
    </div>

    <!-- the value of this input element is POSTed, too -->
    Other param: <input name="otherparam" value="foobar">(will be used as a filename prefix in the demo application)<br>

    <!-- Control buttons. Please note: there's no submit! -->
    <input type="button" value="upload" onclick="return upload();">
    <input type="button" value="clear" onclick="document.getElementById( 'SupaApplet' ).clear(); return false;">
  </form>

  <script type="text/javascript">
  <!--
    function paste() {
      var s = new supa();
      // Call the paste() method of the applet.
      // This will paste the image from the clipboard into the applet :)
      try {
        var applet = document.getElementById( "SupaApplet" );

        if( !s.ping( applet ) ) {
          throw "SupaApplet is not loaded (yet)";
        }

        var err = applet.pasteFromClipboard(); 
        switch( err ) {
          case 0:
            /* no error */
            break;
          case 1: 
            alert( "Unknown Error" );
            break;
          case 2:
            alert( "Empty clipboard" );
            break;
          case 3:
            alert( "Clipboard content not supported. Only image data is supported." );
            break;
          case 4:
            alert( "Clipboard in use by another application. Please try again in a few seconds." );
            break;
          default:
            alert( "Unknown error code: "+err );
        }
      } catch( e ) {
        alert(e);
        throw e;
      }

      return false;
    }

    function upload() {
      // Get the base64 encoded data from the applet and POST it via an AJAX 
      // request. See the included Supa.js for details
      var s = new supa();
      var applet = document.getElementById( "SupaApplet" );

      try { 
        var result = s.ajax_post( 
          applet,       // applet reference
          "upload.php", // call this url
          "screenshot", // this is the name of the POSTed file-element
          "screenshot.jpg", // this is the filename of tthe POSTed file
          { form: document.forms["form"] } // elements of this form will get POSTed, too
        );
        if( result.match( "^OK" ) ) {
          var url = result.substr( 3 );
          window.open( url, "_blank" );
        } else {
          alert( result );
        }

      } catch( ex ) {
        if( ex == "no_data_found" ) {
          alert( "Please paste an image first" );
        } else {
          alert( ex );
        }
      }

      return false; // prevent changing the page
    }
  //-->
  </script>

</body>
</html>

