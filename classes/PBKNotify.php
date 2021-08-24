<?php

class PBKNotify {

    private string $method;
    private array $recipients;
    private string $subject;
    private array $templateOptions;
    private string $sendTime;
    private string $template;
    private array $attachments;

    private const methods = ["sendEmail", "sendText"];
    private const emailHeader = __DIR__ . "/notifyTemplates/emailHeader.html";
    private const emailFooter = __DIR__ . "/notifyTemplates/emailFooter.html";


    /**
     * @throws Exception
     */
    public function __construct() {
        $this->setSendTime(date("Y-m-d G:I:s"));
        $this->setTemplate("generic.html");
    }

    public function setSendTime(string $date): void {
        $this->sendTime = $date;
    }

    public function setSubject(string $subject): PBKNotify {
        $this->subject = $subject;
        return $this;
    }

    public function setTemplateOptions(array $options): PBKNotify {
        $this->templateOptions = $options;
        return $this;
    }

    public function getSubject(): string {
        return $this->subject;
    }

    public function getSendTime(): string {
        return $this->sendTime;
    }

    public function sendMessage(): array {
        $answer = ["status" => 200, "message" => ""];
        $m = $this->buildMessage();
        if ($this->method === "sendEmail") {
            $header = file_get_contents(self::emailHeader);
            $footer = file_get_contents(self::emailFooter);
            $m = $header . $m . $footer;
        }
        /*
        $report = new ToastReport();
        $report->reportEmail("jon@theproteinbar.com", $m, $this->subject);
        */

        global $wpdb;
        if(!isset($wpdb)){
            define( 'SHORTINIT', true );
            require_once( '/var/www/html/c2.theproteinbar.com/wp-load.php' );
        }
        $wpdb->insert(
            "pbc_tasks",
            array(
                'what' => $this->method,
                'target' => implode(",", $this->recipients),
                'text' => $m,
                'subject' => $this->subject,
                'dueDate' => $this->sendTime,
                'files' => json_encode($this->attachments)
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );
        if (!empty($wpdb->last_error)) {
            $answer = ["status" => 400, "message" => $wpdb->last_error];
        }
        return [$answer];
    }

    /**
     * @throws Exception - If the method is not available, throw an error.
     */
    public function setMethod(string $method): PBKNotify {
        if (in_array($method, self::methods, true)) {
            $this->method = $method;
        } else {
            throw new Exception('Method not found');
        }
        return $this;
    }

    public function setRecipients(array $recipients): PBKNotify {
        $this->recipients = $recipients;
        return $this;
    }

    private function buildMessage(): string {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/notifyTemplates');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($this->template);
        return $template->render($this->templateOptions);
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getRecipients(): array {
        return $this->recipients;
    }

    /**
     * @return string
     */
    public function getTemplate(): string {
        return $this->template;
    }

    /**
     * @param string $template
     * @return PBKNotify
     * @throws Exception
     */
    public function setTemplate(string $template): PBKNotify {
        if (file_exists(__DIR__ . "/notifyTemplates/" . $template)) {
            $this->template = $template;
        } else {
            throw new Exception('Template not found');
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getAttachments(): array {
        return $this->attachments;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments(array $attachments): void {
        $this->attachments = $attachments;
    }

}