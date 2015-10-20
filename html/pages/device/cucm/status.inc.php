<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$COMPONENTS = $COMPONENT->getComponents($device['device_id'],array('filter'=>array('type'=>array('LIKE','CUCM')),'ignore'=>0));

if (count($COMPONENTS) > 0) {
    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <img src='images/icons/overview.png'> <strong>Component Status</strong>
        </div>
        <div class="panel-body">
            <table class="table table-hover table-condensed table-striped">
<?php
    foreach ($COMPONENTS as $ID => $ARRAY) {
        if ($ARRAY['status'] == 0) {
            $style = 'background-color: #ffaaaa;';
        }
        else {
            $style = 'background-color: #cffccc;';
        }
        echo "            <tr><td style=\"".$style."\"><strong>".$ARRAY['label']."</strong></td></tr>";
    }
    ?>
            </table>
        </div>
    </div>
<?php
}
