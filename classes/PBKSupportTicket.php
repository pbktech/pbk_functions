<?php


class PBKSupportTicket {

    private int $id;
    private string $guid;
    private int $restaurantID;
    private int $itemID;
    private int $area;
    private string $personName;
    private string $description;
    private string $openedTime;
    private string $status;
    private array $files;
    private array $mms;
    private float $cost;

    public function __construct(string $id) {
        if ($id === "_NEW") {
            $this->setTicketID(0);
            $this->status = "new";
        } else {
            if ($ticket = $this->checkTicketValidity($id)) {
                $this->setTicketID($ticket);
                $this->status = "update";
                $this->loadTicketDetails();
            }
        }
    }

    private function setTicketID(int $id) {
        $this->id = $id;
    }

    public function getTicketID(): ?int {
        if (!empty($this->id)) {
            return $this->id;
        }
        return null;
    }

    private function loadTicketDetails(): void {
        global $wpdb;
        $d = $wpdb->get_row("SELECT * FROM pbc_support_ticket pst, pbc_support_items psi WHERE psi.itemID = pst.areaID AND pst.ticketID = " . $this->id);
        $this->area = $d->itemID;
        $this->openedTime = $d->openedTime;
        $this->restaurantID = $d->restaurantID;
        $this->personName = $d->userName;
        $this->status = $d->ticketStatus;
        if ($mms = json_decode($d->equipmentInfo, true)) {
            $this->mms = $mms;
        } else {
            $this->mms = [];
        }
    }

    private function checkTicketValidity(string $id): ?int {
        global $wpdb;
        $var = $wpdb->get_var("SELECT ticketID FROM pbc_support_ticket WHERE publicUnique = UuidToBin('" . $id . "')");
        if ($var) {
            $this->guid = $id;
            return $var;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function addNewTicket(int $uid): ?array {
        global $wpdb;
        $wpdb->insert(
            "pbc_support_ticket",
            [
                "userID" => $uid,
                "ticketStatus" => "Open",
                "userName" => $this->personName,
                "openedTime" => date("Y-m-d G:i:s"),
                "restaurantID" => $this->restaurantID,
                "areaID" => $this->area,
                "itemID" => $this->itemID,
                "equipmentInfo" => json_encode($this->mms)
            ],
            ["%d", "%s", "%s", "%s", "%d", "%d", "%d", "%s"]
        );
        $this->setTicketID($wpdb->insert_id);
        $this->guid = $wpdb->get_var("SELECT UuidFromBin(publicUnique) FROM pbc_support_ticket WHERE ticketID = " . $this->id);
        if (!empty($this->guid)) {
            return $this->recordResponse($uid);
        }
        return null;
    }

    private function recordCost(int $response, string $type): void {
        global $wpdb;
        $wpdb->insert(
            "pbc_support_ticket_response_costs",
            [
                "responseID" => $response,
                "costType" => $type,
                "costAmount" => $this->cost
            ],
            ["%d", "%s", "%s"]
        );
    }

    public function updateStatus(string $status): void {
        global $wpdb;
        $wpdb->update(
            "pbc_support_ticket",
            ["ticketStatus" => $status],
            ["ticketID" => $this->getTicketID()]
        );
    }

    public function recordResponse(int $uid): ?array {
        global $wpdb;
        global $wp;
        $wpdb->insert(
            "pbc_support_ticket_responses",
            [
                "userID" => $uid,
                "ticketID" => $this->id,
                "responseName" => $this->personName,
                "responseText" => $this->description,
                "responseFiles" => json_encode($this->files)
            ],
            ["%d", "%d", "%s", "%s", "%s"]
        );
        $updateID = $wpdb->insert_id;
        $this->recordCost($updateID, "Cost");
        $restaurant = $wpdb->get_var("SELECT restaurantName FROM pbc_pbrestaurants WHERE restaurantID = " . $this->restaurantID);
        $emails = $this->getEmails();

        $userEmail = $wpdb->get_var("SELECT user_email FROM pbc_users WHERE ID = " . $uid);

        if (!in_array($userEmail, $emails)) {
            $emails[] = $userEmail;
        }

        $notify = new PBKNotify();
        $subject = "[PBK Ticket] ";
        $subject .= $this->status === "new" ? "New Ticket for " : "Ticket Updated for ";
        $subject .= $restaurant;
        if (!$issue = $wpdb->get_var("SELECT issueTitle FROM pbc_support_common psc, pbc_support_ticket pst WHERE psc.issueID = pst.itemID AND ticketID = " . $this->id)) {
            $issue = " --- ";
        }
        $notify->setMethod("sendEmail");
        $notify->setRecipients($emails);
        $notify->setSubject($subject);
        $notify->setTemplate("ticket.html");
        $notify->setTemplateOptions([
            "name" => $this->personName,
            "restaurant" => $restaurant,
            "device" => $wpdb->get_var("SELECT itemName FROM pbc_support_items psi, pbc_support_ticket pst WHERE psi.itemID = pst.areaID AND ticketID = " . $this->id),
            "issue" => $issue,
            "opened" => $wpdb->get_var("SELECT DATE_FORMAT(openedTime, '%m/%d/%Y %r') FROM pbc_support_ticket WHERE ticketID = " . $this->id),
            "updated" => $wpdb->get_var("SELECT DATE_FORMAT(responseTime, '%m/%d/%Y %r') FROM pbc_support_ticket_responses WHERE responseID = " . $updateID),
            "description" => $this->description,
            "link" => add_query_arg(["id" => $this->guid], home_url($path = 'support', $scheme = 'https'))
        ]);
        return $notify->sendMessage();
    }


    private function getEmails(): array {
        global $wpdb;
        $returnEmails = [];
        $emails = $wpdb->get_results("SELECT user_email FROM pbc_users WHERE ID IN (
    SELECT userID FROM pbc_support_contact psc, pbc_support_items psi WHERE psc.department = psi.department AND psi.itemID = '" . $this->area . "')");
        if ($emails) {
            foreach ($emails as $email) {
                $returnEmails[] = $email->user_email;
            }
        }

        $emails = $wpdb->get_results("SELECT user_email FROM pbc_users WHERE ID IN (
    SELECT managerID FROM pbc_pbr_managers ppm WHERE ppm.restaurantID = '" . $this->restaurantID . "')");
        if ($emails) {
            foreach ($emails as $email) {
                $returnEmails[] = $email->user_email;
            }
        }
        if ($_SERVER['HTTP_HOST'] === 'localhost') {
            return ["jon@theproteinbar.com"];
        } else {
            return $returnEmails;
        }
    }

    public function setRestaurantID(int $id): void {
        $this->restaurantID = $id;
    }

    public function setPersonName(string $name): void {
        $this->personName = $name;
    }

    public function getPersonName(): string {
        return $this->personName;
    }

    public function getRestaurantID(): int {
        return $this->restaurantID;
    }

    public function setAreaID(int $id): void {
        $this->area = $id;
    }

    public function setItemId(int $id = 0): void {
        $this->itemID = $id;
    }

    public function getAreaID(): int {
        return $this->area;
    }

    public function getItemId(): int {
        if (empty($this->itemID)) {
            return 0;
        } else {
            return $this->itemID;
        }
    }

    public function setFiles(array $files): void {
        $this->files = $files;
    }

    public function getOpenTime(): string {
        return $this->openedTime;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getMms(): array {
        return $this->mms;
    }

    /**
     * @param array $mms
     */
    public function setMms(array $mms): void {
        $this->mms = $mms;
    }

    /**
     * @return float
     */
    public function getCost(): float {
        return $this->cost;
    }

    /**
     * @param float $cost
     */
    public function setCost(float $cost): void {
        $this->cost = $cost;
    }

    /**
     * @return string
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void {
        $this->status = $status;
    }
}