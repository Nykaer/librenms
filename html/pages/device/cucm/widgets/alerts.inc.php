<?php
require_once 'pages/device/cucm/widgets/functions.inc.php';
$options = array();
$options['filter']['type'] = array('LIKE','CUCM-');
$options['filter']['status'] = array('=',0);
$ALERTS1 = $COMPONENT->getComponents($device['device_id'],$options);
$ALERTS1 = $ALERTS1[$device['device_id']];
$options['filter']['type'] = array('LIKE','UCOS-');
$ALERTS2 = $COMPONENT->getComponents($device['device_id'],$options);
$ALERTS2 = $ALERTS2[$device['device_id']];

// Merge CUCM Alerts with UCOS Alerts.
$ALERTS = array_merge($ALERTS1, $ALERTS2);
if (count($ALERTS) > 0) {
    ?>
    <div class="panel panel-default panel-condensed">
        <div class="panel-heading">
            <img src='images/icons/overview.png'> <strong>Alerting Components</strong>
        </div>
        <div class="panel-body">
            <ol>
                <?php
                foreach ($ALERTS as $ID => $ARRAY) {
                    switch ($ARRAY['label']) {
                        case 'InitializationState':
                            echo "            <li title='A value less than 100 indicates that not all services have started.'>".$ARRAY['type']." - ".$ARRAY['label']." - ".$ARRAY['state']."</li>";
                            break;
                        case 'Replicate_State':
                            echo "            <li title='Status of DB Replication. 0=Not Started, 1=Starting, 2=Completed'>".$ARRAY['type']." - ".$ARRAY['label']." - ".$ARRAY['state']."</li>";
                            break;
                        default:
                            echo "            <li>".$ARRAY['type']." - ".$ARRAY['label']."</li>";
                            break;
                    }
                }
                ?>
            </ol>
        </div>
    </div>
<?php
}
