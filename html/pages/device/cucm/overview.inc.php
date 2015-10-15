<?php

if ($services['down']) {
    $services_colour = $warn_colour_a;
}
else {
    $services_colour = $list_colour_a;
}
if ($ports['down']) {
    $ports_colour = $warn_colour_a;
}
else {
    $ports_colour = $list_colour_a;
}

echo '
<div class="container-fluid">
  <div class="row">
    <div class="col-md-12"></div>
  </div>
  <div class="row">
    <div class="col-md-6">
';
// Left Pane
require 'includes/dev-overview-data.inc.php';
Plugins::call('device_overview_container',array($device));

require 'overview/ports.inc.php';
echo '
    </div>
    <div class="col-md-6">
';

// Right Pane
require 'overview/processors.inc.php';
require 'overview/mempools.inc.php';
require 'overview/storage.inc.php';

if(is_array($entity_state['group']['c6kxbar'])) {
    require 'overview/c6kxbar.inc.php';
}

echo '
    </div>
</div>
</div>';
