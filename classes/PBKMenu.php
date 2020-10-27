<?php


final class PBKMenu
{
    private $mysqli;
    public $today;
    private $restaurantGUID;
    private $restaurantID;
    private $menu;

    public function __construct($mysql){
        if (!isset($mysql)) {
            $report = new ToastReport;
            $m = "Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            exit;
        }
        $this->setMysqli($mysql);
        $this->setToday(date(TODAY_FORMAT));
    }

    public function buildSortOrder(): array
    {
        $menuGroupOrder=array();
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_ref_menuSortOrder WHERE sortOrder  is not null");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_object()) {
            $menuGroupOrder[$row->itemName] = $row->sortOrder;
        }
        return $menuGroupOrder;
    }

    public function getNutrtitional(string $guid): string{
        $stmt=$this->mysqli->prepare("SELECT itemInfo FROM pbc_public_nutritional WHERE toastGUID=?");
        $stmt->bind_param("s", $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        return $row->itemInfo;
    }

    public function buildMenu($menuGUID): array{
        $this->menu = $menuGUID;
        $allModGroups=$this->buildModGroups();
        $menuGroupOrder=$this->buildSortOrder();
        $groups=$this->getMenuGroups();
        $menuGroups=array();
        foreach ($groups as $group){
            $items=$this->getMenuItems($group->menuGroupGUID);
            $menuItems=array();
            foreach ($items as $item){
                $modGroups=array();
                $mods=$this->getMenuModGroups($group->menuGroupGUID,$item->guid);
                if(count($mods) > 1 ){
                    foreach ($mods as $mod) {
                        $modGroups[] = $allModGroups[$mod->modifierGroupGUID];
                    }
                }
                $nutritionalShort="";
                $description=explode("[",$item->description);
                if(isset($description[1]) && $description[1]!=''){$nutritionalShort="[" . $description[1];}
                $menuItems[]=array(
                    "name"=>$item->name,
                    "guid"=>$item->guid,
                    "price"=>$item->price,
                    "image"=>$item->image,
                    "description"=>$description[0],
                    "sort"=>$menuGroupOrder[$item->name],
                    "modGroups"=>$modGroups,
                    "nutritionalShort"=>$nutritionalShort,
                    "nutritional"=>$this->getNutrtitional($item->guid)
                );
            }
            $menuGroups[]=array(
                "name"=>$group->name,
                "guid"=>$group->menuGroupGUID,
                "description"=>$group->name,
                "availability"=>$group->availability,
                "sort"=>$menuGroupOrder[$group->name],
                "menuItems"=>$menuItems
            );
        }
        $name=$this->getMenuInfo("name");
        return array("menuName"=>$$name,"sort"=>$menuGroupOrder[$name],"menuGroups"=>$menuGroups);
    }

    public function getMenuInfo($field){
        $stmt=$this->mysqli->prepare("SELECT $field FROM pbc_ToastMenus WHERE isActive=1 AND guid=? ");
        $stmt->bind_param("s", $this->menu);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        return $row->$field;

    }

    public function buildModGroups(): array{
        $modGroups=array();
        $menuGroupOrder=$this->buildSortOrder();
        $stmt = $this->mysqli->prepare("SELECT modifierGroupGUID,modifierGUID,price,minSelections,maxSelections,isMultiSelect,ptmmg.name as 'modGroup',ptmmgi.name as 'modifier',isDefault,allowsDuplicates
from pbc_ToastMenuModifierGroups ptmmg, pbc_ToastMenuModifierGroupItems ptmmgi, pbc_ref_menuModifierGroups prmmg
where ptmmg.guid = prmmg.modifierGroupGUID AND ptmmgi.guid = prmmg.modifierGUID");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_object()) {
            $modGroups[$row->modifierGroupGUID]["modifierGroupGUID"]=$row->modifierGroupGUID;
            $modGroups[$row->modifierGroupGUID]["modGroup"]=$row->modGroup;
            $modGroups[$row->modifierGroupGUID]["minSelections"]=$row->minSelections;
            $modGroups[$row->modifierGroupGUID]["maxSelections"]=$row->maxSelections;
            $modGroups[$row->modifierGroupGUID]["isMultiSelect"]=$row->isMultiSelect;
            $modGroups[$row->modifierGroupGUID]["sort"]=$menuGroupOrder[$row->modGroup];
            $modGroups[$row->modifierGroupGUID]["mods"][$row->modifierGUID]["modifierGUID"]=$row->modifierGroupGUID."/".$row->modifierGUID;
            $modGroups[$row->modifierGroupGUID]["mods"][$row->modifierGUID]["modifier"]=$row->modifier;
            $modGroups[$row->modifierGroupGUID]["mods"][$row->modifierGUID]["isDefault"]=$row->isDefault;
            $modGroups[$row->modifierGroupGUID]["mods"][$row->modifierGUID]["price"]=$row->price;
            $modGroups[$row->modifierGroupGUID]["mods"][$row->modifierGUID]["allowsDuplicates"]=$row->allowsDuplicates;
        }
        return $modGroups;
    }

    public function getMenuGroups(): array{
        $stmt=$this->mysqli->prepare("SELECT availability,menuGroupGUID,ptmg.name as 'name' FROM pbc_ToastMenus ptm, pbc_ToastMenuGroups ptmg, pbc_ref_menuGroups prmg WHERE  ptm.isActive=1 AND ptmg.isActive=1 AND prmg.menuGUID = ptm.guid AND prmg.menuGroupGUID = ptmg.guid AND restaurantGUID=? AND prmg.menuGUID = ?");
        $stmt->bind_param("ss", $this->restaurantGUID, $this->menu);
        $stmt->execute();
        $result = $stmt->get_result();
        $menuGroups=array();
        while ($row = $result->fetch_object()) {
            $menuGroups[] = $row;
        }
        return $menuGroups;
    }

    public function getMenuItems($menuGroupGUID): array{
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_ToastMenuItems ptmi, pbc_ref_menuItems prmi WHERE isActive=1 AND prmi.menuItemGUID = ptmi .guid AND restaurantGUID=? AND prmi.menuGroupGUID = ?");
        $stmt->bind_param("ss", $this->restaurantGUID, $menuGroupGUID);
        $stmt->execute();
        $result = $stmt->get_result();
        $menuItems=array();
        while ($row = $result->fetch_object()) {
            $menuItems[] = $row;
        }
        return $menuItems;
    }

    public function getMenuModGroups($menuGroupGUID,$menuItemGUID): array{
        $stmt=$this->mysqli->prepare("SELECT modifierGroupGUID FROM pbc_ref_menuItemModifiers WHERE restaurantGUID=? AND menuItemGUID=? AND menuGroupGUID=?");
        $stmt->bind_param("sss", $this->restaurantGUID, $menuItemGUID, $menuGroupGUID);
        $stmt->execute();
        $result = $stmt->get_result();
        $modGroups=array();
        while ($row = $result->fetch_object()) {
            $modGroups[] = $row;
        }
        return $modGroups;
    }

    public function setrestaurantID(int $restaurantID): void{
        $this->restaurantID = $restaurantID;
    }

    public function setrestaurantGUID(string $restaurantGUID): void{
        $this->restaurantGUID = $restaurantGUID;
    }

    public function setMenu(string $menu): void{
        $this->menu = $menu;
    }

    public function getMenuGroupOrder(): array{
        return $this->menuGroupOrder;
    }

    private function setMenuGroupOrder(array $menuGroupOrder): void{
        $this->menuGroupOrder = $menuGroupOrder;
    }

    private function setToday(string $date): void{
        $this->today = $date;
    }

    public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }

}