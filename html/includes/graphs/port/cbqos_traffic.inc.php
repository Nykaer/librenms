<?php

/*
 * process $classes to get the RRD's and labels for each class-map
 */

$rrd_filename = $config['rrd_dir'].'/'.$device['hostname'].'/port-'.safename($port['ifIndex'].'-cbqos-'.$CBQOS['sp-id'].'-'.$CBQOS['sp-obj'].'.rrd');

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = 'Downstream';
$rrd_list[0]['ds']       = 'AturChanCurrTxRate';

$rrd_list[1]['filename'] = $rrd_filename;
$rrd_list[1]['descr']    = 'Upstream';
$rrd_list[1]['ds']       = 'AtucChanCurrTxRate';

$unit_text = 'Bits/sec';

$units       = '';
$total_units = '';
$colours     = 'mixed';

$scale_min = '0';

$nototal = 1;

if ($rrd_list) {
    include 'includes/graphs/generic_multi_line.inc.php';
}
