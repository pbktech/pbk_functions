<?php


class PBKSupport {

    public function __construct() {

    }

    final public function supportRouter(): string{
        if(!empty($_REQUEST['id'])){
            $ticket = new PBKSupportTicket($_REQUEST['id']);
            if(empty($ticket->getTicketID())){
                return "<div class='alert alert-danger'>Ticket not found</div>";
            }else{
                return "";
            }
        }else{
            return $this->showTicketPage("ticketStarter.php") . $this->showTicketPage("ticketList.php");
        }
    }

    final private function showTicketPage($page){
        if(!empty($page)) {
            require_once __DIR__ . "/support_mods/" . $page;
        }
    }
}