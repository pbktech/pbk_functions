<?php
global $wp;
$ret.=  "
  <div>
    <form method='post' action='".home_url( $wp->request )."' enctype=\"multipart/form-data\" >
      <input id=\"fileupload\" type=\"file\" name=\"userfile\" /><br />
      <input type='submit' value='Upload' />
    </form>
  </div>
</div>
";
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_FILES) && count($_FILES)!=0){
    $uploadfile = "/var/www/html/c2.theproteinbar.com/RaupCWYghyVCKxyP6Vwa/" . $_FILES['userfile']['name'];
    move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
    if(file_exists($uploadfile)) {
      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($uploadfile);
      $reader->setReadDataOnly(true);
      $spreadsheet = $reader->load($uploadfile);
      echo "<pre>";
      pritn_r($spreadsheet);
      echo "</pre>";
    }
  }
}
