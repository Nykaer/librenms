<?php

function generate_widget_part($graph_array,$device,$title=null) {
    $graph = generate_lazy_graph_tag($graph_array);
    $link_array         = $graph_array;
    $link_array['page'] = 'graphs';
    unset($link_array['height'], $link_array['width']);
    $link = generate_url($link_array);

    $graph_array['width'] = '210';
    if (!is_null($title)) {
        echo "                <strong>".$title."</strong><br>\n";
        $text = $device['hostname'].' - '.$title;
    }
    else {
        $text = $device['hostname'];
    }
    $overlib_content      = generate_overlib_content($graph_array, $text);
    echo "                ".overlib_link($link, $graph, $overlib_content, null)."\n";
}

function component_exists($ARRAY,$labels=array()) {
    // Search $ARRAY for any components containing $label
    foreach ($labels as $label) {
        foreach ($ARRAY as $VALUE) {
            if ($VALUE['label'] == $label) {
                return true;
            }
        }
    }
    return false;
}
