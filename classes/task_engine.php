<?php
/*include_once 'Mail.php';
include_once 'Mail/mime.php';*/

require __DIR__ . '/BindParam.php';
require dirname(__DIR__) . '/vendor/autoload.php';
use Google\Cloud\Logging\LoggingClient;


function task_dispatch($task, $log) {
    $t = explode('/', $task['target']);
    array_pop($t);
    $dir = implode('/', $t);
    if ($task['what'] === 'sendEmail') {
        $log->info("Dispatch: Run Command - Sending Email");
        $email = new ToastReport();
        $recip = explode(',', $task['target']);
        foreach ($recip as $r) {
            $email->reportEmail($r, $task['text'], $task['subject']);
        }
    } elseif($task['what'] === 'sendText'){
        $log->info("Dispatch: Run Command - Sending Text");
        $text = new ToastReport();
        $recip = explode(',', $task['target']);
        foreach ($recip as $r) {
            $message = $text->sendText($r, $task['text']);
            try {
                $subject = json_decode($task['subject'], true, 512, JSON_THROW_ON_ERROR);
                if($subject['type'] === 'curbside') {
                    $text->updateCurbsideText($message->sid, $subject['id']);
                }
            } catch (JsonException $e) {
                $log->info(print_r($e,true));
            }
        }
    }
    elseif ($task['what'] === 'exec') {
        $log->info("Dispatch: Run Command - cd " . $dir . "; " . $task['target'] . " " . $task['id']);
        return system("cd " . $dir . "; " . $task['target'] . " " . $task['id']);
    } elseif ($task['what'] === 'execBackground') {
        $log->info("Dispatch: Run Command - cd " . $dir . "; " . $task['target'] . " " . $task['id'] . " &");
        system("cd " . $dir . "; " . $task['target'] . " " . $task['id'] . " &");
        return 1;
    } else {
        $log->error("Dispatch: Unknown Task - " . print_r($task,true));
        return 'Unknown task';
    }
}

final class task_engine {
    public $task_table;
    private $mysqliSingleton;
    private \Google\Cloud\Logging\PsrLogger $log;

    function __construct($mysqli, string $t = 'pbc_tasks') {
        $this->mysqliSingleton = $mysqli;
        $this->task_table = $t;
        $logging = new LoggingClient([
            'projectId' => "silicon-will-769"
        ]);
        $this->log = $logging->psrLogger("taskEngine");
    }

    public function get_current_tasks(): array{
        //return array of tasks that need to run as of now but, haven't started yet
        $arr = [];
        $sth = $this->mysqliSingleton->prepare("select id, what, target from " . $this->task_table . " where dueDate<=now() and dateStarted is null and deleted<>'1' order by dueDate");
        $sth->execute();
        $r = $sth->get_result();
        if ($r->num_rows != 0) {
            while ($row = $r->fetch_assoc()) {
                $arr[$row['id']] = $row['what'] . ':' . $row['target'];
            }
        }
        return $arr;
    }

    public function run_current_tasks(): void {
        $sth = $this->mysqliSingleton->prepare("select * from " . $this->task_table . " where dueDate<now() and dateStarted is null and deleted<>'1' order by dueDate");
        $sth->execute();
        $r = $sth->get_result();
        if ($r->num_rows != 0) {
            while ($row = $r->fetch_assoc()) {
                $this->update_task($row['id'], ['dateStarted' => date('Y-m-d H:i:s'), 'running' => 1]);
                $t = task_dispatch($row, $this->log);
                $this->update_task($row['id'], ['dateCompleted' => date('Y-m-d H:i:s'), 'running' => 0, 'error' => (($t != 1) ? $t : null)]);
            }
        }

    }

    public function update_task(int $id, array $vars): void {
        $sql = '';
        $this->log->info("Updating Task: " . $id);
        $keys = array_keys($vars);
        $values = array_values($vars);
        $bindParam = new BindParam();
        $sql = 'update ' . $this->task_table . ' set ' . implode('=?, ', $keys) . '=? where id=?';
        $sth = $this->mysqliSingleton->prepare($sql);
        for ($i = 0, $iMax = count($values); $i < $iMax; $i++) {
            $bindParam->add('s', $values[$i]);
        }
        $bindParam->add('i', $id);
        $param = $bindParam->get();
        $type = array_shift($param);
        $sth->bind_param($type, ...$param);
        $sth->execute();
        if (isset($sth->error) && $sth->error !== '') {
            $this->log->error("Updating Task: ERROR - " . $sth->error);
            echo "<div class='alert alert-danger'>" . $sth->error . "</div>";
        }
    }

    public function delete_task(int $id): void {
        $this->log->info("Deleting Task: " . $id);
        $sth = $this->mysqliSingleton->prepare("update " . $this->task_table . " set deleted='1' where id=?");
        $sth->bind_param('i', $id);
        $sth->execute();
        if (isset($sth->error) && $sth->error !== '') {
            $this->log->error("Deleting Task: ERROR - " . $sth->error);
        }
    }

    public function reset_unfinished_tasks():void {
        $sth = $this->mysqliSingleton->prepare("update " . $this->task_table . " set dateStarted = null where running <> '0' and dateStarted is not null and dateCompleted is null and error is null and deleted<>'1'");
        $sth->execute();
    }

    public function clone_task(int $id, string $due_date = null): int {
        $this->log->info("Cloning Task: " . $id);
        if ($due_date == null) {
            $due_date = date('Y-m-d H:i:s');
        }
        $my_task = $this->get_task(['id' => $id]);
        if (isset($my_task)) {
            if (isset($my_task['id'])) {
                unset($my_task['id']);
            }
            if (isset($my_task['dueDate'])) {
                unset($my_task['dueDate']);
            }
            if (isset($my_task['dateStarted'])) {
                unset($my_task['dateStarted']);
            }
            if (isset($my_task['dateCompleted'])) {
                unset($my_task['dateCompleted']);
            }
            if (isset($my_task['deleted'])) {
                unset($my_task['deleted']);
            }
            if (isset($my_task['dateAdded'])) {
                unset($my_task['dateAdded']);
            }
            if (isset($my_task['running'])) {
                unset($my_task['running']);
            }
            if (isset($my_task['error'])) {
                unset($my_task['error']);
            }
            $my_task['dueDate'] = $due_date;
            return $this->add_task($my_task);
        } else {
            return -1;
        }
    }

    public function get_task(array $vars): ?array {
        $sql = 'select * from ' . $this->task_table . ' where ';
        $keys = array_keys($vars);
        $values = array_values($vars);
        $bindParam = new BindParam();
        $qmarks = [];
        for ($i = 0, $iMax = count($keys); $i < $iMax; $i++) {
            if (strpos($values[$i], '%') !== false || strpos($values[$i], '_') !== false || strpos($values[$i], '[^') !== false || strpos($values[$i], '[!') !== false) {
                $sql .= $keys[$i] . " like ? and ";
            } else {
                $sql .= $keys[$i] . " = ? and ";
            }
        }
        $sql = substr($sql, 0, -5);
        $sth = $this->mysqliSingleton->prepare($sql);
        for ($i = 0, $iMax = count($values); $i < $iMax; $i++) {
            $bindParam->add('s', $values[$i]);
        }
        $param = $bindParam->get();
        $type = array_shift($param);
        $sth->bind_param($type, ...$param);
        $sth->execute();
        $r = $sth->get_result();
        if ($r->num_rows) {
            return $r->fetch_assoc();
        } else {
            $this->log->error("Get Task: ERROR - " . $sth->error);
            return null;
        }
    }

    public function add_task(array $vars): int{
        $this->log->info("Adding Task: " . print_r($vars,true));
        $sql = '';
        $keys = array_keys($vars);
        $values = array_values($vars);
        $bindParam = new BindParam();
        $qmarks = [];
        for ($i = 0, $iMax = count($keys); $i < $iMax; $i++) {
            $qMarks[] = '?';
        }
        $sql = 'insert into ' . $this->task_table . ' (' . implode(', ', $keys) . ') values (' . implode(', ', $qMarks) . ')';
        $sth = $this->mysqliSingleton->prepare($sql);
        for ($i = 0, $iMax = count($values); $i < $iMax; $i++) {
            $bindParam->add('s', $values[$i]);
        }
        $param = $bindParam->get();
        $type = array_shift($param);
        $sth->bind_param($type, ...$param);
        if ($sth->execute()) {
            return $this->mysqliSingleton->insert_id;
        } else {
            $this->log->error("Adding Task: ERROR - " . $sth->error);
            return -1;
        }
    }

    public function run_task(int $id):void {
        $this->log->info("Running Task: " . $id);
        $sth = $this->mysqliSingleton->prepare("select * from " . $this->task_table . " where id=? and deleted<>'1'");
        $sth->bind_param('i', $id);
        $sth->execute();
        $r = $sth->get_result();
        if ($r->num_rows != 0) {
            $row = $r->fetch_assoc();
            $this->update_task($row['id'], ['dateStarted' => date('Y-m-d H:i:s'), 'running' => 1]);
            task_dispatch($row, $this->log);
            $this->update_task($row['id'], ['dateCompleted' => date('Y-m-d H:i:s'), 'running' => 0, 'error' => (($t != 1) ? $t : null)]);
        }
    }

}
