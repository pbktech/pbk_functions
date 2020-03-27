<?php
function pbrf_depositCalculator(){
  $ret.="
<script type=\"text/javascript\">
  function updateTotal(){
    var totalCash=document.getElementById('totalCash').value;
    var payouts=document.getElementById('payouts').value;
    document.getElementById('expectedDeposit').innerHTML ='$' + (totalCash - payouts);
  }
</script>
<form>
  <table>
    <tr><td>Total Cash Payments</td><td> + <input type='text' size='5' id='totalCash' onblur='updateTotal()'  /></td></tr>
    <tr><td>Payouts</td><td> -  <input type='text' size='5' id='payouts' onblur='updateTotal()'  /></td></tr>
    <tr><td colspan='2'><input type='button' value='Calculate' onclick='updateTotal()' /></td></tr>
    <tr style='border-top: solid 1px black;''><td>EXPECTED DEPOSIT</td><td><div id='expectedDeposit'>$0.00</div></td></tr>
  </table>
</form>

";
return $ret;
}
