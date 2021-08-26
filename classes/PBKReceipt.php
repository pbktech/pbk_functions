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

    private function paymentLine(string $name, string $amount, string $style = ""): string {
        return "
            <tr style='".$style."'>
                <td style='text-align: right; width: 80%;'>" . $name . "</td>
                <td style='text-align: right;'>" . $amount . "</td>
            </tr>
        ";
    }

    public function buildPBKReceipt(array $d): string {
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $receipt = "";
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/notifyTemplates');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load("receipt.html");
        if (!empty($d['checks'])) {
            $grandTotal = 0;
            foreach ($d['checks'] as $check) {
                $discounts = "";
                $discountTotal = 0;
                $payments = "";
                $paymentTotal = 0;
                $tipTotal = 0;
                if (!empty($check['discounts'])) {
                    foreach ($check['discounts'] as $discount) {
                        $discountTotal += $discount->discountAmount;
                        $discounts .= $this->paymentLine($discount->discountName . " (" . $discount->promoCode . ")",$fmt->formatCurrency($discount->discountAmount, "USD"),"color: #dc3545; font-style: italic;");
                    }
                }
                if (!empty($check['payments'])) {
                    foreach ($check['payments'] as $payment) {
                        $paymentTotal += $payment->paymentAmount;
                        $payments .= $this->paymentLine( $payment->paymentType . " - " . substr($payment->cardNum, -4), $fmt->formatCurrency($payment->paymentAmount, "USD"));
                        if (!empty($payment->tipAmount) && is_numeric($payment->tipAmount)) {
                            $tipTotal += $payment->tipAmount;
                        }
                        if ($payment->paymentType === "Prepay") {
                            $grandTotal += $payment->paymentAmount;
                        }
                    }
                }
                $receiptBody= "";
                $checkTotal = 0;
                foreach ($check['items'] as $item) {
                    $modLines = "";
                    $modPrice = 0;
                    if (!empty($item['mods'])) {
                        $modLines .= "<tr><td colspan='2'><ul style='list-style-type: none; font-size: 75%; font-style: italic; color: #9d9d9d; text-align: left;'>";
                        foreach ($item['mods'] as $mod) {
                            $modPrice += $mod['price'];
                            $modLines .= "<li>" . $mod['name'] . "</li>";
                        }
                        $modLines .= "</ul></td> </tr>";
                    }
                    $linePrice = round($item['price'] + $modPrice, 2);
                    $checkTotal += $linePrice;
                    $receiptBody .= "
                    <tr>
                        <td style='text-align: left; width: 80%;'>" . $item['quantity'] . " <span style='color: #F36C21; font-weight: bold;'>" . $item['name'] . "</span></td>
                        <td style='text-align: right;'>" . $fmt->formatCurrency($linePrice, "USD") . "</td>
                    </tr>
                " . $modLines;
                }
                $subtotal = $this->paymentLine("Subtotal:", $fmt->formatCurrency($checkTotal, "USD"));
                $tax = $this->paymentLine("Tax:", $fmt->formatCurrency($check['totals']['tax'], "USD"));
                $tip = $tipTotal > 0 ?  $this->paymentLine("tip:", $fmt->formatCurrency($tipTotal, "USD")) : "";
                $receipt.= $template->render([
                    "image" => "https://www.pbkgrouporder.com/assets/images/receipt-logo_1519923720_400.png",
                    "delivery" => $d['delivery'],
                    "minibar" => $d['minibar'],
                    "tab" => $check['tab'],
                    "ordered" => $check['ordered'],
                    "receiptItems" => $receiptBody,
                    "subtotal" => $subtotal,
                    "discounts" => $discounts,
                    "tax" => $tax,
                    "tips" => $tip,
                    "payment" => $payments
                ]);

            }
            if ($grandTotal !== 0 && !empty($d['payment'])) {
                $receipt .= "
            <div class='row' style='padding-top: 1em; text-align: center;'>
                <div class='col'>Amount applied to " . $d['payment'][0]->paymentType . " ending in " . $d['payment'][0]->cardNum . ": " . $fmt->formatCurrency($grandTotal, "USD") . "</div>
</div>
            ";
            }
        }
        return $receipt;
    }

}