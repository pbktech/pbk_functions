<?php

final class GrubHub extends PBKDelivery {

    public function __construct() {
        parent::__construct("GrubHub");
    }

    public function buildRequest(string $grubHubID): void {
        $this->setHeaders([
            "Authorization: " . $grubHubID,
            "Content-Type: application/json"
        ]);
        $request = (object)[
            "delivery_quote" => (object)[
                "restaurant_id" => 1239545395,
                "currency" => "USD",
                "desired_dropoff_at" => $this->getExpected(),
                "dropoff_address" => $this->getAddress(),
                "diner" => $this->getGuest()
            ],
            "cart" => $this->parseItems(),
            "cart_total" => round($this->getSubTotal() * 100, 0),
            "driver_tip" => round($this->getTip() * 100, 0),
        ];

        $this->setRequest($request);
    }

    private function parseItems(): array {
        global $wpdb;
        if (!isset($wpdb)) {
            define('SHORTINIT', true);
            require_once('/var/www/html/c2.theproteinbar.com/wp-load.php');
        }
        $items = [];
        if (!empty($this->getOrderID())) {
            $checkItems = $wpdb->get_results("SELECT * FROM pbc_minibar_order_items pmoi  WHERE checkID IN 
(SELECT checkID FROM pbc_minibar_order_check pmoc WHERE mbOrderID = " . $this->getOrderID() . ")");
            if ($checkItems) {
                foreach ($checkItems as $item) {
                    if ($item->item['guid'] !== parent::textIdentifier) {
                        $mods = $wpdb->get_results("SELECT * FROM pbc_minibar_order_mods WHERE itemID = " . $item->itemID);
                        $items[] = (object)[
                            "name" => $item->itemName,
                            "quantity" => $item->quantity,
                            "price" => round($item->itemPrice * 100, 0),
                            "options" => $this->parseOptions($mods, $item->quantity)
                        ];
                    }
                }
            }
        }
        return $items;
    }

    private function parseOptions(array $mods, int $quantity): array {
        $options = [];
        foreach ($mods as $mod) {
            $options[] = (object)[
                "name" => $mod->modName,
                "quantity" => $quantity,
                "price" => round($mod->modPrice * 100, 0)
            ];
        }
        return $options;
    }
}