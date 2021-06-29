<?php


class PBKSupportTicket {

    private int $id;

    public function __construct(string $id) {
        if($id === "_NEW"){
            $this->setTicketID(0);
        }else{
            if($ticket = $this->checkTicketValidity($id)){
                $this->setTicketID($ticket);
            }
        }
    }

    private function setTicketID(string $id){
        $this->id = $id;
    }
    public function getTicketID(): ?int{
        if(!empty($this->id)) {
            return $this->id;
        }
        return null;
    }
    private function checkTicketValidity(string $id): ?string{
        global $wpdb;
        $var = $wpdb->get_var("SELECT ticketID FROM pbc_support_ticket WHERE publicUnique = UuidToBin($id)");
        if($var){
            return $var;
        }
        return false;
    }
}