<?php
require_once 'pages/device/cucm/widgets/functions.inc.php';

$labels = array('InitializationState', 'Replicate_State');
if (component_exists($CUCM_BASIC,$labels)) {

?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <a href="device/device=<?=$device['device_id']?>/tab=cucm/metric=status/"><img src='images/icons/overview.png'> <strong>Component Status</strong></a>
        </div>
        <div class="panel-body">
    <?php
    foreach ($CUCM_BASIC as $ID => $ARRAY) {
        if ($ARRAY['status'] == 0) {
            $style = 'background-color: #ffaaaa;';
        }
        else {
            $style = 'background-color: #cffccc;';
        }

        switch ($ARRAY['label']) {
            case 'InitializationState':
                echo "            <div style=\"".$style."\" title='A value less than 100 indicates that not all services have started.'><strong>".$ARRAY['label']." - ".$ARRAY['state']."</strong></div>";
                break;
            case 'Replicate_State':
                echo "            <div style=\"".$style."\" title='Status of DB Replication. 0=Not Started, 1=Starting, 2=Completed'>".$ARRAY['label']." - ".$ARRAY['state']."</div>";
                break;
        }
    }
    ?>
        </div>
    </div>
<?php
}
