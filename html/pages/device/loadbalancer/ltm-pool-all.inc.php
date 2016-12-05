<?php
/*
 * LibreNMS module to Display data from F5 BigIP LTM Devices
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

?>
<table id='grid' data-toggle='bootgrid' class='table table-condensed table-responsive table-striped'>
    <thead>
    <tr>
        <th data-column-id="poolid" data-type="numeric" data-visible="false">poolid</th>
        <th data-column-id="name">Name</th>
        <th data-column-id="currentup">Members</th>
        <th data-column-id="status">Status</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($components as $pool_id => $array) {
        if ($array['type'] != 'f5-ltm-pool') {
            continue;
        }
        if ($array['status'] == 2) {
            $status = $array['error'];
            $error = 'class="danger"';
        } else {
            $status = 'Ok';
            $error = '';
        }
        ?>
        <tr <?php echo $error; ?>>
            <td><?php echo $pool_id; ?></td>
            <td><?php echo $array['label']; ?></td>
            <td><?php echo $array['currentup']; ?></td>
            <td><?php echo $status; ?></td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
<script type="text/javascript">
    $("#grid").bootgrid({}).on("click.rs.jquery.bootgrid", function (e, columns, row) {
        var link = '<?php echo generate_url($vars, array('type' => 'ltm-pool', 'subtype' => 'ltm-pool-details')); ?>poolid='+row['poolid'];
        window.location.href = link;
    });
</script>
