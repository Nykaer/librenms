<?php

require_once "../includes/component.php";
$COMPONENT = new component();
$options = array();
$options['filter']['ignore'] = array('=',0);
$graph_array['device'] = $device['device_id'];
$options['type'] = 'UCOS-SERVICES';
$SERVICES = $COMPONENT->getComponents($device['device_id'],$options);

?>
    <table id='table' class='table table-condensed table-responsive table-striped'>
        <thead>
        <tr>
            <th data-column-id='name'>Service Name</th>
            <th data-column-id='status'>Status</th>
            <th data-column-id='uptime'>Uptime</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($SERVICES as $VALUE) {
            if ($VALUE['status'] == 1) {
                $STATUS = "Ok";
                $CLASS = "";
            }
            else {
                $STATUS = "Alert";
                $CLASS = 'bg-danger';
                $STYLE = 'style="color: #FF1C00;"';
            }
            ?>
            <tr>
                <td class="<?=$CLASS?>"><?=$VALUE['label']?></td>
                <td class="<?=$CLASS?>"><?=$STATUS?></td>
                <td class="<?=$CLASS?>"><?=(floor($VALUE['uptime']/86400))." days, ".(gmdate("H:i:s", $VALUE['uptime']));?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
