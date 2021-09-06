<?php


class PBKSupport {

    private array $adminPages = [
        "Tickets" => "ticketStarter.php",
        "Department" => "adminDepartment.php",
        "Equipment" => "adminEquipment.php",
        "Common" => "adminCommon.php",
        "Vendors" => "adminVendors.php"
    ];

    private array $departments = ["Repair", "Technology", "Marketing"];

    public function __construct() {

    }

    final public function supportRouter(): ?string{
        if(!empty($_REQUEST['id'])){
            $ticket = new PBKSupportTicket($_REQUEST['id']);
            if(empty($ticket->getTicketID())){
                return "<div class='alert alert-warning'>Ticket not found</div>";
            }else{
                $this->showTicketPage("ticketUpdate.php");
            }
        }else{
            return $this->showTicketPage("ticketStarter.php") . $this->showTicketPage("ticketList.php");
        }
         return null;
    }

    final public function getNextSupportItemID(): ?int{
        global $wpdb;
        return $wpdb->get_var("SELECT MAX(itemID)+1 as 'nextItem' FROM pbc_support_items psi WHERE itemID !=2147483646 AND itemID !=2147483647");
    }

    final private function showTicketPage($page){
        if(!empty($page)) {
            require_once __DIR__ . "/support_mods/" . $page;
        }
    }

    /**
     * @return array
     */
    public function getAdminPages(): array {
        return $this->adminPages;
    }

    /**
     * @param array $adminPages
     */
    public function setAdminPages(array $adminPages): void {
        $this->adminPages = $adminPages;
    }

    /**
     * @return array|string[]
     */
    public function getDepartments(): array {
        return $this->departments;
    }
}