<?php


class ToastOrder extends Toast {

    private int $orderID;
    private int $userID;
    private object $orderHeader;

    public function __construct($guid){
        parent::__construct($guid);
    }

    final public function returnOrderChecks(): object{
        $check = new PBKCheck($this->mysqli);
        $check->setOrderID($this->orderHeader->headerID);
        $grandTotal = 0;
        $orderChecks = $check->returnChecks();
        $checks = array();
        foreach ($orderChecks as $orderCheck) {
            $check->setCheckID($orderCheck->checkID);
            $items = $check->buildCheckItems();
            $selections = array();

            foreach ($items as $item) {
                $modifiers = array();
                foreach ($item->mods as $mod) {
                    if (!empty($mod)) {
                        $modifiers[] = $this->buildMods($mod, $item->quantity);
                    }
                }
                $itemGUIDS = explode("/", $item->guid);
                $selections[] = (object)[
                    "entityType" => "MenuItemSelection",
                    "itemGroup" => array("guid" => $itemGUIDS[0], "entityType" => "MenuGroup"),
                    "item" => array("guid" => $itemGUIDS[1], "entityType" => "MenuItem"),
                    "quantity" => $item->quantity,
                    "modifiers" => $modifiers
                ];
            }
            $p = $this->buildPayments($check->returnPayments());
            $grandTotal += $p->grandTotal;
            $checks[] = (object)[
                "entityType" => "Check",
                "selections" => $selections,
                "customer" => $this->returnCustomerObject(),
                "appliedDiscounts" => $this->buildDiscounts($check->returnDiscounts()),
                "payments" => $p->payments,
                "tabName" => $orderCheck->tabName
            ];
        }
        return (object)["order" => $checks, "grandTotal" => $grandTotal];
    }
    private function buildMods(object $mod, int $quantity): object{
        if ($mod->guid === "SPECIAL_REQUEST" || $mod->guid === "FOR") {
            $pretext = "SR";
            if ($mod->guid === "FOR") {
                    $pretext = "FOR";
            }
            return (object)[
                "displayName" => " --- " . $pretext . ": " . $mod->modName,
                "selectionType" => "SPECIAL_REQUEST"
            ];
        } else {
            $modGUIDS = explode("/", $mod->guid);
            return (object)
            [
                "entityType" => "MenuItemSelection",
                "optionGroup" => (object)["guid" => $modGUIDS[0]],
                "item" => (object)[
                    "entityType" => "MenuItem",
                    "guid" => $modGUIDS[1]
                ],
                "quantity" => $quantity
            ];
        }
    }
    private function buildDiscounts(array $discounts): array{
        $appliedDiscounts = array();
        if ($discounts) {
            foreach ($discounts as $d) {
                if($d->discountType === 'system') {
                    $appliedDiscounts[] = (object)[
                        "discount" => (object)["guid" => $d->discountGUID],
                        "discountAmount" => $d->discountAmount
                    ];
                }else{
                    $appliedDiscounts[] = (object)[
                        "discount" => (object)["guid" => $d->discountGUID]
                    ];

                }
            }
        }
        return $appliedDiscounts;
    }
    private function buildPayments(array $payments): object{
        $appliedPayments = array();
        $grandTotal = 0;
        $amount = 0;
        if ($payments) {
            foreach ($payments as $p) {
                if ($p->paymentStatus === 'approved') {
                    $amount += $p->paymentAmount;
                    if (in_array($p->paymentType, array('Visa', 'Mastercard', 'American Express', 'Diners Club', 'Discover', 'JCB'))) {
                        $txn = json_decode($p->transactionID);
                        $payeezy = new Payeezy($this->mysqli);
                        $payeezy->setPaymentID($p->paymentID);
                        $payeezy->setBillAmount($p->paymentAmount);
                        $payeezy->setTransactionID($txn->transaction_id);
                        $payeezy->setTransactionTag($txn->transaction_tag);
                        $payeezy->captureCard();
                    }

                    if ($p->paymentType === 'Prepay') {
                        $grandTotal += $p->paymentAmount;
                    }
                }
            }

            $appliedPayments[] = (object)[
                "paidDate" => $this->toastDate(date(TODAY_FORMAT)),
                "type" => "OTHER",
                "amount" => $amount,
                "otherPayment" => (object)["guid" => self::PAYMENT],
                "tipAmount" => 0.00,
                "amountTendered" => $amount
            ];
        }
        return (object)["payments" => $appliedPayments,"grandTotal"=>$grandTotal];
    }
    final public function returnCustomerObject(): object{
        $q = "SELECT * FROM pbc_minibar_user WHERE id = ?";
        $stmt = $this->mysqli->prepare($q);
        $stmt->bind_param('s',$this->userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $name = explode(" ", $row->real_name1);

        return (object)["entityType" => "Customer", "firstName" => $name[0], "lastName" => $name[1], "phone" => $this->parsePhoneToast($row->phone_number), "email" => $row->email_address];
    }
    private function parsePhoneToast(string $phone): string{
        preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $phone,  $matches );
        return $matches[1] . '-' .$matches[2] . '-' . $matches[3];
    }
    final public function returnOrderHeader(): string {
        if(!empty($this->orderHeader->outpostIdentifier) && !empty($this->orderHeader->restaurantID)) {
            $q = "SELECT * FROM pbc2.pbc_ToastGUIDOptions WHERE optionName = '" . $this->orderHeader->outpostIdentifier . "' AND restaurantID = '" . $this->orderHeader->restaurantID . "'";
            $stmt = $this->mysqli->prepare($q);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_object()) {
                return $row->GUID;
            }
        }
        return self::DINING_OPTION;
    }

    private function orderHeader(): object{
        $q="SELECT * FROM pbc_minibar_order_header WHERE headerID = ?";
        $stmt = $this->mysqli->prepare($q);
        $stmt->bind_param('s',$this->orderID);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_object();
    }
    final public function setOrderID(int $orderID): void{
        $this->orderID = $orderID;
        $this->orderHeader = $this->orderHeader();
    }
    final public function setUserID(int $userID): void{
        $this->userID = $userID;
    }
}