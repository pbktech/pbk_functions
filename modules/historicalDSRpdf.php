<?php
global $wpdb;
$rests=$wpdb->get_results("SELECT fileName,fileID,date FROM pbc2.pbc_google_files where fileType='DSR' ORDER BY date desc");
foreach ( $rests as $rest ){
  $files[date("Y",strtotime($rest->date))][date("m",strtotime($rest->date))][$rest->fileID]=$rest->fileName;
}
foreach($files as $year => $months){
  $ret.='
  <div class="accordion" id="accordion'.$year.'">
    <div class="card">
      <div class="card-header" id="heading'.$year.'">
        <h2 class="mb-0">
          <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse'.$year.'">
            '.$year.'
          </button>
        </h2>
      </div>
      <div id="collapse'.$year.'" class="collapse" aria-labelledby="headingOne" data-parent="#accordion'.$year.'" >
    ';
    foreach($months as $month => $days){
      if(($month % 2) == 0){$bgColor= "bg-secondary";}else {$bgColor= "bg-light";}
      $ret.='
      <div class="accordion" id="accordion'.$year.'-'.$month.'">
        <div class="card">
          <div class="card-header '.$bgColor.'" id="heading'.$year.'-'.$month.'">
            <h2 class="mb-0">
              <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse'.$year.'-'.$month.'">
                '.date("F",strtotime($year."-".$month."-01")).'
              </button>
            </h2>
          </div>
          <div id="collapse'.$year.'-'.$month.'" class="collapse" aria-labelledby="headingOne" data-parent="#accordion'.$year.'-'.$month.'">
          ';
          foreach($days as $f=>$name){
            $ret.="<div class=\"card-body\"><a href='https://drive.google.com/a/theproteinbar.com/file/d/".$f."/view?usp=drivesdk' target='_blank'>" . $name . "</a></div>";

          }
        $ret.=  '
          </div>
        </div>
        </div>
        ';
    }
  $ret.=  '
      </div>
    </div>
  </div>
  ';
}
