<?php

if (!$graph_array['period']) {
    $graph_array['period']  = 'day';
}

$graph_array['from']        = $config['time'][$graph_array['period']];
$graph_array['to']          = $config['time']['now'];
unset($graph_array['period']);

$graph_array_zoom           = $graph_array;
$graph_array_zoom['height'] = '150';
$graph_array_zoom['width']  = '400';

$link_array         = $graph_array;
$link_array['page'] = 'graphs';
unset($link_array['height'], $link_array['width']);
$link = generate_url($link_array);

if ($return_data === true) {
    $minigraph = overlib_link($link, generate_lazy_graph_tag($graph_array), generate_graph_tag($graph_array_zoom), null);
} else {
//    generate_lazy_graph_tag($graph_array);
    overlib_link($link, generate_lazy_graph_tag($graph_array), generate_graph_tag($graph_array_zoom), null);
}
unset($graph_array);
