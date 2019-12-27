<?php
/*include_once 'Mail.php';
include_once 'Mail/mime.php';*/

require __DIR__ . '/BindParam.php';

/*
$tasks=new task_engine($mysqli);
//print $tasks->add_task(['what'=>'sendEmail', 'target'=>'jewmanfoo@jewmanfoo.com','dueDate'=>date('Y-m-d H:i:s'), 'text'=>'Something', 'subject'=>'This is my subject'], 'template'=>'something']);

$tasks->run_current_tasks();
*/

function task_dispatch ($task) {
  $t=explode('/', $task['target']);
  array_pop($t);
  $dir=implode('/', $t);
  if ($task['what']=='sendEmail') {
    print "Sending email to " . $task['target'] . "\n";
    //$mime = new Mail_mime();
    $head=((isset($task['template']) && file_exists($task['template'] . '.header'))?file_get_contents($task['template'] . '.header'):'');
    $foot=((isset($task['template']) && file_exists($task['template'] . '.footer'))?file_get_contents($task['template'] . '.footer'):'');

    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->XMailer = ' ';
      $mail->Host = SMTP_HOST;
      $mail->Port = SMTP_PORT;
      $mail->SMTPAuth = true;
      $mail->Username = SMTP_EMAIL;
      $mail->Password = SMTP_PASS;
      $mail->SMTPSecure = 'tls';

      $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
      $recip = explode(',', $task['target']);
      $mail->addAddress($recip[0], (count($recip)>1)?$recip[1]:'');
      $mail->Subject = $task['subject'];
//print "sending to: ". $recip[0] . "subject: " . $task['subject'] . "\n";

      $mail->isHTML(true);
      $mail->Body = $head . $task['text'] . $foot;
//print $head . $task['text'] . $foot . "\n\n";
      $attach = explode(',', $task['files']);
      if (!empty($attach) && is_array($attach) && count($attach)) {
//print "in mult attach\n";
  foreach($attach as $at){
    if ($at && file_exists($at)) {
//print "trying attachment $at\n";
//print $head . $task['text'] . $foot . "\n\n";
      $mail->addAttachment($at);
    }
  }
      } else {
  if(!empty($attach) && strlen($attach)) {
//print "in single attach, $attach\n";
    $mail->addAttachment($attach);
  }
      }
      $mail->send();
    } catch (Exception $e) {
      logError("Error in sendmail: " . $e->getMessage());
print "sendmail error: |" . $e->getMessage() . "|\n";
      return $e->getMessage();
    }
    /*$hdrs = array ('MIME-Version' => '1.0','From' => 'action@ribs.com','To' => $task['target'],'Subject' => $task['subject'], 'Bcc' => '');
    $body = $mime->get();
    $hdrs = $mime->headers($hdrs);
    $smtp =new Mail;
    try {
      $smtp->factory('smtp', array ('host' => $host,'port' => $port,'auth' => true,'username' => $emailname,'password' => $emailpassword))->send($task['target'], $hdrs, $body);
      return 1;
    }*/
  } elseif ($task['what']=='exec') {
    return system ("cd " . $dir . "; " . $task['target'] . " " . $task['id']);
  } elseif ($task['what']=='execBackground') {
    system ("cd " . $dir . "; " . $task['target'] . " " . $task['id'] . " &");
    return 1;
  } else {
    return 'Unknown task';
  }
}
class task_engine {
  private $mysqliSingleton;
  public $task_table;
  function __construct($mysqli, $t='pbc_tasks') {
    $this->mysqliSingleton=$mysqli;
    $this->task_table=$t;
  }
  function get_current_tasks () {
  //return array of tasks that need to run as of now but, haven't started yet
    $arr=[];
  $sth=$this->mysqliSingleton->prepare("select id, what, target from " . $this->task_table . " where dueDate<=now() and dateStarted is null and deleted<>'1' order by dueDate");
    $sth->execute ();
    $r=$sth->get_result();
    if ($r->num_rows!=0) {
      while ($row=$r->fetch_assoc()) {
  $arr[$row['id']]=$row['what'] . ':' . $row['target'];
      }
    }
    return $arr;
  }
  function run_current_tasks () {
    $sth=$this->mysqliSingleton->prepare("select * from " . $this->task_table . " where dueDate<now() and dateStarted is null and deleted<>'1' order by dueDate");
    $sth->execute ();
    $r=$sth->get_result();
    if ($r->num_rows!=0) {
      while ($row=$r->fetch_assoc()) {
  $this->update_task($row['id'], ['dateStarted'=>date('Y-m-d H:i:s'), 'running'=>1]);
  $t=task_dispatch($row);
  $this->update_task($row['id'], ['dateCompleted'=>date('Y-m-d H:i:s'), 'running'=>0, 'error'=>(($t!=1)?$t:null)]);
      }
    }

  }
  function delete_task ($id) {
    $sth=$this->mysqliSingleton->prepare("update " . $this->task_table . " set deleted='1' where id=?");
    $sth->bind_param('i', $id);
    $sth->execute ();
  }
  function reset_unfinished_tasks () {
    $sth = $this->mysqliSingleton->prepare("update " . $this->task_table . " set dateStarted = null where running <> '0' and dateStarted is not null and dateCompleted is null and error is null and deleted<>'1'");
    $sth->execute ();
  }
  function add_task($vars) {
    $sql='';
    $keys=array_keys($vars);
    $values=array_values($vars);
    $bindParam = new BindParam();
    $qmarks=[];
    for ($i=0; $i<count($keys); $i++) {
      $qMarks[]='?';
    }
    $sql='insert into ' . $this->task_table . ' (' . implode(', ', $keys) . ') values (' . implode(', ', $qMarks) . ')';
    $sth=$this->mysqliSingleton->prepare($sql);
    for ($i=0; $i<count($values); $i++) {
      $bindParam->add('s', $values[$i]);
    }
    $param=$bindParam->get();
    $type=array_shift($param);
    $sth->bind_param($type, ...$param);
    if ($sth->execute()) {
      return $this->mysqliSingleton->insert_id;
    } else {
      echo $sth->error;
      return -1;
    }
  }
  function get_task ($vars) {
    $sql='select * from ' . $this->task_table . ' where ';
    $keys=array_keys($vars);
    $values=array_values($vars);
    $bindParam = new BindParam();
    $qmarks=[];
    for ($i=0; $i<count($keys); $i++) {
      if (strpos($values[$i], '%')!==false || strpos($values[$i], '_')!==false || strpos($values[$i], '[^')!==false || strpos($values[$i], '[!')!==false) {
        $sql .= $keys[$i] . " like ? and ";
      } else {
        $sql .= $keys[$i] . " = ? and ";
    }
  }
  $sql=substr($sql, 0, -5);
  $sth=$this->mysqliSingleton->prepare($sql);
  for ($i=0; $i<count($values); $i++) {
    $bindParam->add('s', $values[$i]);
  }
  $param=$bindParam->get();
  $type=array_shift($param);
  $sth->bind_param($type, ...$param);
  $sth->execute();
  $r=$sth->get_result();
  if ($r->num_rows) {
    return $r->fetch_assoc();
  } else {
    echo $sth->error;
    return null;
  }
}
function clone_task ($id, $due_date=null) {
  if ($due_date == null) {
    $due_date=date('Y-m-d H:i:s');
  }
  $my_task=$this->get_task(['id'=>$id]);
  if (isset($my_task)) {
    if (isset($my_task['id'])) {unset($my_task['id']);}
    if (isset($my_task['dueDate'])) {unset($my_task['dueDate']);}
    if (isset($my_task['dateStarted'])) {unset($my_task['dateStarted']);}
    if (isset($my_task['dateCompleted'])) {unset($my_task['dateCompleted']);}
    if (isset($my_task['deleted'])) {unset($my_task['deleted']);}
    if (isset($my_task['dateAdded'])) {unset($my_task['dateAdded']);}
    if (isset($my_task['running'])) {unset($my_task['running']);}
    if (isset($my_task['error'])) {unset($my_task['error']);}
    $my_task['dueDate'] = $due_date;
    return $this->add_task($my_task);
  } else {
    return -1;
  }
}
function run_task ($id) {
  $sth=$this->mysqliSingleton->prepare("select * from " . $this->task_table . " where id=? and deleted<>'1'");
  $sth->bind_param('i', $id);
  $sth->execute();
  $r=$sth->get_result();
  if ($r->num_rows!=0) {
    $row=$r->fetch_assoc();
    $this->update_task($row['id'], ['dateStarted'=>date('Y-m-d H:i:s'), 'running'=>1]);
    task_dispatch($row);
    $this->update_task($row['id'], ['dateCompleted'=>date('Y-m-d H:i:s'), 'running'=>0, 'error'=>(($t!=1)?$t:null)]);
  }
}
function update_task ($id, Array $vars) {
  $sql='';
  $keys=array_keys($vars);
  $values=array_values($vars);
  $bindParam = new BindParam();
  $sql='update ' . $this->task_table . ' set ' . implode('=?, ', $keys) . '=? where id=?';
  $sth=$this->mysqliSingleton->prepare($sql);
  for ($i=0; $i<count($values); $i++) {
    $bindParam->add('s', $values[$i]);
  }
  $bindParam->add('i', $id);
  $param=$bindParam->get();
  $type=array_shift($param);
  $sth->bind_param($type, ...$param);
  $sth->execute();
  if(isset($sth->error) && $sth->error!=''){echo "<div class='alert alert-danger'>".$sth->error."</div>";}
}

}
