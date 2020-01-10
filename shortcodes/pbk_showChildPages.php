<?php
function pbr_showChildPages(){
  $r=new Restaurant;
  $pages=$r->pbk_get_children();
  if(count($pages)>0){
    return $r->pbk_array_nav($pages);
  }else {
    return "<div class='alert  alert-primary'>There are not any pages available for you.</div>";
  }
}
