<?php


final class PBKCheck
{
    private $mysqli;
    private $payment;
    private $tabName;
    private $guid;
    private $checkID;
    private $orderID;
    public $today;

    public function __construct($mysql){
        if (!isset($mysql)) {
            $this->reportError( "PBKCheck class failed to construct. Missing MySQLi object.");
            exit;
        }
        $this->setMysqli($mysql);
        $this->setToday(date(TODAY_FORMAT));
    }

    public function createCheckHeader(array $h): ?int{
        $stmt = $this->mysqli->prepare("INSERT INTO pbc_minibar_order_check (mbOrderID, mbUserID, tabName, subtotal, tax, smsConsent) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",
            $h['orderHeaderID'],
            $h['mbUserID'],
            $h['name'],
            $h['subtotal'],
            $h['tax'],
            $h['smsConsent']
        );
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error != '') {
            $this->reportError( "Failed to insert check header. <br><br> The server said: " . $stmt->error);
            return false;
        }
        $this->setCheckID($stmt->insert_id);
        $stmt = $this->mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'guid' FROM pbc_minibar_order_check WHERE checkID=?");
        $stmt->bind_param("s",$stmt->insert_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $this->setGUID($row->guid);
        return $stmt->insert_id;
    }

    public function verifyGUID(string $guid): ?int{
        $stmt = $this->mysqli->prepare("SELECT checkID FROM pbc_minibar_order_check WHERE publicUnique=UuidToBin(?)");
        $stmt->bind_param("s",$guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if(isset($row->checkID)) {
            $this->setGUID($guid);
            $this->setCheckID($row->checkID);
            return $row->checkID;
        }
        return false;
    }

    public function clearOrderItems(): void{
        $stmt = $this->mysqli->prepare("DELETE FROM pbc_minibar_order_mods WHERE itemID IN (SELECT itemID FROM pbc_minibar_order_items WHERE checkID = ?)");
        $stmt->bind_param("s",$this->checkID);
        $stmt->execute();
        $stmt = $this->mysqli->prepare("DELETE FROM pbc_minibar_order_items WHERE checkID = ?");
        $stmt->bind_param("s",$this->checkID);
        $stmt->execute();
    }

    public function addItems(array $items): void{
        $insertItem = $this->mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_items(checkID, itemName, itemPrice, itemGUID, quantity) VALUES (?,?,?,?,?)");

        foreach ($items as $item) {
            $insertItem->bind_param('sssss',
                $this->checkID,
                $item->name,
                $item->price,
                $item->guid,
                $item->quantity
            );
            $insertItem->execute();

            if (isset($insertItem->error) && $insertItem->error != '') {
                $this->reportError( "Failed to insert item record. <br>Check ID: ".$this->checkID."<br><br> The server said: " . $insertItem->error . "<br><br> Item Object:" . print_r($item,true));
                exit;
            }

            $itemID = $insertItem->insert_id;
            if(isset($item->forName) && $item->forName!=''){
                $this->insertSpecialMod("FOR",0.00,$itemID,$item->forName);
            }
            if (!empty($item->mods)) {
                $this->insertItemMods($item->mods,$itemID);
            }
            if(isset($item->specialRequest) && $item->specialRequest!=''){
                $this->insertSpecialMod("SPECIAL_REQUEST",0.00,$itemID,$item->specialRequest);
            }
        }
    }

    private function insertSpecialMod(string $guid,float $price,int $itemID,string $request): void{
        $insertMod = $this->mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_mods(itemID, modName, modPrice, modGUID) VALUES (?,?,?,?)");
        $insertMod->bind_param('ssss',
            $itemID,
            $request,
            $price,
            $guid
        );
        $insertMod->execute();
    }

    private function insertItemMods(array $mods, int $itemID): void{
        $insertMod = $this->mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_mods(itemID, modName, modPrice, modGUID) VALUES (?,?,?,?)");
        foreach ($mods as $mod) {
            $insertMod->bind_param('ssss',
                $itemID,
                $mod->modifier,
                $mod->price,
                $mod->guid
            );
            $insertMod->execute();

            if (isset($insertMod->error) && $insertMod->error != '') {
                $this->reportError( "Failed to insert mod record. <br>Check ID: ".$this->checkID."<br>ItemID: ".$itemID."<br><br> The server said: " . $insertMod->error . "<br><br> Mod Object:" . print_r($mod,true));
                exit;
            }
        }
    }

    public function addDiscounts(array $discounts,string $promoCode): void{
        $insertDiscount = $this->mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_discount (checkID, discountName, discountGUID, discountAmount, promoCode, discountType) VALUES (?,?,?,?,?,'promo')");
        foreach($discounts as $d) {
            $insertDiscount->bind_param('sssss',
                $this->checkID,
                $d->name,
                $d->discount->guid,
                $d->discountAmount,
                $promoCode
            );
            $insertDiscount->execute();
            if (isset($insertDiscount->error) && $insertDiscount->error != '') {
                $this->reportError( "Failed to insert discount record. <br>Check ID: ".$this->checkID."<br><br> The server said: " . $insertDiscount->error . "<br><br> Discounts Object:" . print_r($discounts,true));
                exit;
            }
        }
    }

    private function setCheckID(int $checkID): void{
        $this->checkID = $checkID;
    }

    private function setGUID(string $guid): void{
        $this->guid = $guid;
    }

    public function getGUID(): string{
        return $this->guid;
    }

    public function setOrderID(int $orderID): void{
        $this->orderID = $orderID;
    }

    public function setTabName(string $contactInfo): void{
        $this->tabName = $contactInfo;
    }

    public function setPayment(object $paymentInfo): void{
        $this->payment = $paymentInfo;
    }

    private function setToday(string $date): void{
        $this->today = $date;
    }

    public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }

    private function reportError(string $m): void{
        $report = new ToastReport;
        $report->reportEmail("errors@theproteinbar.com", $m, "User error");
    }

}