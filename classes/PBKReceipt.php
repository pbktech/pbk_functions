<?php


final class PBKReceipt {

    private $config;
    private $mysqli;

    function __construct() {
        $this->setConfig();
        $this->connectDB();
    }

    public function setConfig() {
        if (!defined('ABSPATH')) {
            if (file_exists('/var/www/html/c2.theproteinbar.com')) {
                define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
            } else {
                define('ABSPATH', '/var/www/html/c2dev.theproteinbar.com/');
            }
        }
        $default = dirname(ABSPATH) . '/config.json';
        $this->config = json_decode(file_get_contents($default));
    }

    function connectDB() {
        $this->mysqli = new mysqli($this->config->host, $this->config->username, $this->config->password, $this->config->dBase);
        $this->mysqli->set_charset('utf8mb4');
    }

    public function getReceiptItems(string $guid): array {
        $stmt = $this->mysqli->prepare("SELECT * FROM pbc2.pbc_minibar_order_header WHERE publicUnique = UuidToBin(?)");
        $stmt->bind_param("s", $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $orderHeaderID = $row;
        $orderCheckID = [];
        $orderPayment = [];

        if (empty($orderHeaderID)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_check,pbc_minibar_order_header, pbc_minibar pm WHERE mbOrderID = headerID AND pbc_minibar_order_check.publicUnique = UuidToBin(?) AND miniBarID=pm.idpbc_minibar");
            $stmt->bind_param("s", $guid);
        } else {
            if (!empty($row->defaultPayment)) {
                $q = $this->mysqli->prepare("SELECT paymentID, mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, authorization, fdsToken, cardNum FROM pbc_minibar_order_payment WHERE paymentID = ?");
                $q->bind_param('s', $row->defaultPayment);
                $q->execute();
                $pResult = $q->get_result();
                while ($pr = $pResult->fetch_object()) {
                    $orderPayment[] = $pr;
                }
            }
            $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_check,pbc_minibar_order_header, pbc_minibar pm WHERE mbOrderID = headerID AND mbOrderID = ? AND miniBarID=pm.idpbc_minibar ");
            $stmt->bind_param("s", $orderHeaderID->headerID);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_object()) {
            $orderCheckID[] = $row;
        }

        $orderChecks = array();
        foreach ($orderCheckID as $check) {
            $checkItems = array();
            $payments = array();
            $discounts = array();
            $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_items WHERE checkID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $modItems = array();
                $stmt1 = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_mods WHERE itemID=?");
                $stmt1->bind_param("s", $row->itemID);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
                $modPrice = 0;
                while ($row1 = $result1->fetch_object()) {
                    $modPrice += $row1->modPrice;
                    $modItems[] = array("name" => $row1->modName, "price" => $row1->modPrice, "guid" => $row1->modGUID);
                }

                $checkItems[] = array("name" => $row->itemName, "price" => ($row->itemPrice + $modPrice) * $row->quantity, "quantity" => $row->quantity, "guid" => $row->itemGUID, "mods" => $modItems);
            }

            $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_discount WHERE checkID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $discounts[] = $row;
            }

            $stmt = $this->mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'guid', paymentType, paymentDate, paymentAmount, authorization, cardNum, tipAmount FROM pbc_minibar_order_payment WHERE mbCheckID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $payments[] = $row;
            }

            $orderChecks[] = ["tab" => $check->tabName, "ordered" => date("m/d/y g:i:s a", strtotime($check->checkAdded)), "items" => $checkItems, "discounts" => $discounts, "payments" => $payments, "totals" => array("subtotal" => $check->subtotal, "tax" => $check->tax)];
        }

        return array("checks" => $orderChecks, "payment" => $orderPayment, "minibar" => $orderCheckID[0]->company, "delivery" => date("m/d/y g:i:s a", strtotime($orderCheckID[0]->dateDue)));
    }

    public function buildPBKReceipt(array $d): string {
        $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
        $receiptBody = "";
        $receiptHeader = "
<style>
.receipt {
    padding: 8px 10px;
    max-width: 500px;
}
.receipt-header {
    height: 4px;
    max-width: 500px;
}
.receipt-body {
    padding: 10px;
    background-color: #FFFFFF;
    max-width: 500px;
    margin: auto;    
}
.receipt-footer {
    height: 4px;
    max-width: 500px;
}
.receipt-body hr {
    margin: 5px 0;
}
.receipt table td {
    border:none;
    padding:0;
    max-width: 500px;
}
</style>
<div class='container' style='text-align: center; padding-top: 1em; font-family: Lora; padding-bottom: 2em; overflow-y: auto; overflow-x: hidden;'>
    <h2>Thank you for your order!</h2>
    <div class='receipt' style='textAlign: center; padding-top: 1em; padding-bottom: 1em; margin: auto;'>
        <div class='row' style='padding-bottom: 1em;'>
            <div class='col'>
                <div><img src='https://www.pbkgrouporder.com/assets/images/receipt-logo_1519923720_400.png' alt='Protein Bar & Kitchen' /></div>
                <div style=' padding: 1em;'>Due on " . $d['delivery'] . "<br /><strong>" . $d['minibar'] . "</strong></div>
            </div>
        </div>";
    if(!empty($d['checks'])){
        $grandTotal = 0;
        foreach($d['checks'] as $check){
            $discounts = "";
            $discountTotal = 0;
            $payments = "";
            $paymentTotal = 0;
            $tipTotal = 0;
            if(!empty($check['discounts'])){
                foreach($check['discounts'] as $discount) {
                    $discountTotal+= $discount->discountAmount;
                    $discounts .= "
                        <tr style='color: #dc3545; font-style: italic;'>
                            <td style='text-align: right; width: 80%;'>" . $discount->discountName . " (" . $discount->promoCode . ")</td>
                            <td style='text-align: right;'>" . $fmt->formatCurrency($discount->discountAmount, "USD") . "</td>
                        </tr>";
                }
            }
            if(!empty($check['payments'])){
                foreach ($check['payments'] as $payment){
                    $paymentTotal += $payment->paymentAmount;
                    $payments .= "
                        <tr class='row' style=''>
                            <td style='text-align: right; width: 80%;'>" . $payment->paymentType . " - " . substr($payment->cardNum, -4) . "</td>
                            <td style='text-align: right;'>" . $fmt->formatCurrency($payment->paymentAmount, "USD") . "</td>
                        </tr>";
                    if(!empty($payment->tipAmount) && is_numeric($payment->tipAmount)){
                        $tipTotal+=$payment->tipAmount;
                    }
                    if($payment->paymentType === "Prepay"){
                        $grandTotal += $payment->paymentAmount;
                    }
                }
            }
            $receiptBody.="
            <div class='container-fluid'>
                <div class='receipt-header' >&nbsp;</div>
                <div class='receipt-body'>
                    <div style='text-align: left; font-weight: bold; width: 500px;margin: auto;'>" . $check['tab'] ." : " . $check['ordered'] ."<hr /></div>
                </div>
                <div class='receipt-body'>
                    <table style='width: 500px;margin: auto;'>";
            foreach ($check['items'] as $item){
                $modLines = "";
                $modPrice = 0;
                $checkTotal = 0;
                if(!empty($item['mods'])){
                    $modLines.= "<tr><td colspan='2'><ul style='list-style-type: none; font-size: 75%; font-style: italic; color: #9d9d9d; text-align: left;'>";
                    foreach($item['mods'] as $mod){
                        $modPrice += $mod['price'];
                        $modLines.="<li>" . $mod['name'] . "</li>";
                    }
                    $modLines.= "</ul></td> </tr>";
                }
                $linePrice = round($item['price'] + $modPrice, 2);
                $checkTotal+=$linePrice;
                $receiptBody.="
                    <tr>
                        <td style='text-align: left; width: 80%;'>" . $item['quantity'] . " <span style='color: #F36C21; font-weight: bold;'>" . $item['name'] . "</span></td>
                        <td style='text-align: right;'>".$fmt->formatCurrency($linePrice,"USD")."</td>
                    </tr>
                " . $modLines;
            }
            $receiptBody.="
                    </table>                
                </div>
                <div class='receipt-body' style='font-size: 85%; text-align: right;'>
                        
                    <table style='width: 500px;margin: auto;'>
                        <tr><td colspan='2'><hr /></td></tr>
                        <tr>
                            <td style='text-align: right; width: 80%;'>Subtotal:</td>
                            <td style='text-align: right;'>".$fmt->formatCurrency($checkTotal,"USD")."</td>
                        </tr>" . $discounts;
            if($tipTotal > 0){
                $receiptBody.="
                <tr>
                            <td style='text-align: right; width: 80%;'>Tip:</td>
                            <td style='text-align: right;'>".$fmt->formatCurrency($tipTotal,"USD")."</td>
                        </tr>
                ";
            }
            $receiptBody.=$payments . "
                    </table>                
                </div>
            </div>
            ";

        }
        if($grandTotal !==0 && !empty($d['payment'])){
                $receiptBody .= "
            <div class='row' style='padding-top: 1em;'>
                <div class='col'>Amount applied to " . $d['payment'][0]->paymentType . " ending in " . $d['payment'][0]->cardNum . ": ".$fmt->formatCurrency($grandTotal,"USD")."</div>
</div>
            ";
        }
    }
$receiptFooter = "        
    </div>
</div>";
    return $receiptHeader . $receiptBody . $receiptFooter;
    }

}