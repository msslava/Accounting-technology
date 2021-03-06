<?php
session_start();
if (validate_user($_SESSION['dilema_user_id'], $_SESSION['us_code'])) {
include("header.php");
include("menus.php");
  if ((in_array('3-2', explode(",",validate_menu($_SESSION['dilema_user_id'])))) || (validate_priv($_SESSION['dilema_user_id']) == 1)){
 ?>
 <div class="container-fluid">
   <div class="page-header" style="margin-top: -15px;">
   <div class="row">
            <div class="col-md-6"> <h3><i class="fa fa-list-alt" aria-hidden="true"></i>&nbsp;<?=get_lang('Menu_eqlist');?></h3>
            </div>
   </div>
    </div>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-info-circle" aria-hidden="true"></i>&nbsp;<?=get_lang('Eq_list_title');?>

    </div>
    <div class="panel-body">
      <table id="eq_list" class="table table-striped table-bordered nowrap" cellspacing="0" width="100%">
          <thead>
            <tr>
              <th class="center_header"><?=get_lang('Id')?></th>
              <th class="center_header"><?=get_lang('Places')?></th>
              <th class="center_header"><?=get_lang('Namenome')?></th>
              <th class="center_header"><?=get_lang('Group')?></th>
              <th class="center_header"><?=get_lang('Sernum')?></th>
              <th class="center_header"><?=get_lang('Shtrih')?></th>
              <th class="center_header"><?=get_lang('Orgname')?></th>
              <th class="center_header"><?=get_lang('Matname')?></th>
              <th class="center_header"><?=get_lang('Spisan')?></th>
            </tr>
          </thead>
      </table>
    </div>
  </div>
    <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-random" aria-hidden="true"></i>&nbsp;<?=get_lang('Equipment_move');?>

    </div>
  <div class="panel-body">
    <table id="equipment_move_show" class="table table-striped table-bordered nowrap" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th rowspan="2" class="center_header"><?=get_lang('Id')?></th>
            <th rowspan="2" class="center_header"><?=get_lang('Date')?></th>
            <th colspan="5" class="center_header"><?=get_lang('From')?></th>
            <th colspan="3" class="center_header"><?=get_lang('To')?></th>
            <th rowspan="2" class="center_header"><?=get_lang('Comment')?></th>
          </tr>
          <tr>
            <th class="center_header"><?=get_lang('Orgname')?></th>
            <th class="center_header"><?=get_lang('Places')?></th>
            <th class="center_header"><?=get_lang('Matname')?></th>
            <th class="center_header"><?=get_lang('Kntname')?></th>
            <th class="center_header"><?=get_lang('Invoice')?></th>
            <th class="center_header"><?=get_lang('Orgname')?></th>
            <th class="center_header"><?=get_lang('Places')?></th>
            <th class="center_header"><?=get_lang('Matname')?></th>
          </tr>
        </thead>
    </table>
  </div>
</div>
    </div>
</div>
</div>
</div>
<?php
}
 else{
?>
<div class="row">
  <div class="col-md-12">
    <center>
    <font size="20"><?=get_lang('Access_denied')?></font>
  </center>
  </div>
</div>
<br>
<?php
}
include("footer.php");
}
else {
    include 'auth.php';
}
 ?>
