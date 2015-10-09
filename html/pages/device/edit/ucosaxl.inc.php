<?php

$attribs = get_dev_attribs($device);
$update_message = false;
$updated        = false;

if ($_POST['editing']) {
    if ($_SESSION['userlevel'] > '7') {
        $attribs['ucosaxl_host'] = mres($_POST['axl_host']);
        $attribs['ucosaxl_user'] = mres($_POST['axl_user']);
        $attribs['ucosaxl_pass'] = mres($_POST['axl_pass']);

        if ($attribs['ucosaxl_host'] != '') {
            set_dev_attrib($device, 'ucosaxl_host', $attribs['ucosaxl_host']);
        }
        else {
            del_dev_attrib($device, 'ucosaxl_host');
        }

        if ($attribs['ucosaxl_user'] != '') {
            set_dev_attrib($device, 'ucosaxl_user', $attribs['ucosaxl_user']);
        }
        else {
            del_dev_attrib($device, 'ucosaxl_user');
        }

        if ($attribs['ucosaxl_pass'] != '') {
            set_dev_attrib($device, 'ucosaxl_pass', $attribs['ucosaxl_pass']);
        }
        else {
            del_dev_attrib($device, 'ucosaxl_pass');
        }

        $update_message = 'AXL Credentials Updated.';
        $updated        = 1;
    }
    else {
        include 'includes/error-no-perm.inc.php';
    }//end if
}//end if

if ($updated && $update_message) {
    print_message($update_message);
}
else if ($update_message) {
    print_error($update_message);
}

?>

<h3>Cisco UC AXL Configuration</h3>

<form id="edit" name="edit" method="post" action="" role="form" class="form-horizontal">
<input type="hidden" name="editing" value="yes">
  <div class="form-group">
    <label for="axl_host" class="col-sm-2 control-label">Hostname</label>
    <div class="col-sm-6">
      <input id="axl_host" name="axl_host" class="form-control" value="<?php echo $attribs['ucosaxl_host']; ?>" />
    </div>
  </div>
  <div class="form-group">
    <label for="axl_user" class="col-sm-2 control-label">AXL Username</label>
    <div class="col-sm-6">
      <input id="axl_user" name="axl_user" class="form-control" value="<?php echo $attribs['ucosaxl_user']; ?>" />
    </div>
  </div>
  <div class="form-group">
    <label for="axl_pass" class="col-sm-2 control-label">AXL Password</label>
    <div class="col-sm-6">
      <input id="axl_pass" name="axl_pass" type="password" class="form-control" value="<?php echo $attribs['ucosaxl_pass']; ?>" />
    </div>
  </div>
  <button class="btn btn-default btn-sm" type="submit" name="Submit">Save</button>
</form>
