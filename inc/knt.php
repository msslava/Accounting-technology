<?php
session_start();
if (validate_user($_SESSION['dilema_user_id'], $_SESSION['us_code'])) {
include("header.php");
include("menus.php");
 ?>
 <div class="container-fluid">
   <div class="page-header" style="margin-top: -15px;">
   <div class="row">
            <div class="col-md-6"> <h3><i class="fa fa-server"></i>&nbsp;<?=get_lang('Menu_knt');?></h3>
            </div>
   </div>
    </div>
 <div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-info-circle"></i>&nbsp;<?=get_lang('Knt_title');?>

    </div>
    <div class="panel-body">
      <table id="table_knt" class="table table-striped table-bordered nowrap" cellspacing="0" width="100%">
          <thead>
            <tr>
              <th class="center_header"><?=get_lang('Active')?></th>
              <th class="center_header"><?=get_lang('Id')?></th>
              <th class="center_header"><?=get_lang('Name')?></th>
              <th class="center_header"><?=get_lang('Inn')?></th>
              <th class="center_header"><?=get_lang('Kpp')?></th>
              <th class="center_header"><?=get_lang('Comment')?></th>
            </tr>
          </thead>
      </table>
    </div>
 </div>
 </div>
 <div class="col-md-8">
 <div class="panel panel-default">
 <div class="panel-heading">
   <i class="fa fa-file"></i>&nbsp;<?=get_lang('Files_title');?>
 </div>
 <div class="panel-body">
   <input type="file" id="file_knt">
   <table id="table_knt_files" class="table table-striped table-bordered nowrap" cellspacing="0" width="100%">
       <thead>
         <tr>
           <th class="center_header"><?=get_lang('Id')?></th>
           <th class="center_header"><?=get_lang('Name_file')?></th>
         </tr>
       </thead>
   </table>
   <input type="hidden" id="file_size" value="<?=$CONF['file_size']?>">
 </div>
</div>
 </div>
</div>
</div>
 </div>
 </div>
<?php
include("footer.php");
?>
<script>
var file_types = ['<?=str_replace(",", "','", get_conf_param('file_types'))?>'];
var permit_users_knt = ['<?=str_replace(",", "','", get_conf_param('permit_users_knt'))?>'];
</script>
<?php
}
else {
    include 'auth.php';
}
 ?>
