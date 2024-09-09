<?php

/**
 *  Root dir relative path
 */

use mitoteam\jpgraph\MtJpGraph;

/**
 *  Root dir relative path
 */
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__) . '/../../');
}
require_once ROOT_DIR . '/config/config_main.inc.php';
require_once ROOT_DIR . '/config/config_install.inc.php';
require_once ROOT_DIR . '/config/config_jsgraph.inc.php';
require_once ROOT_DIR . '/vendor/mitoteam/jpgraph/src/MtJpGraph.php';

extract($_GET, EXTR_OVERWRITE);
extract($_POST, EXTR_OVERWRITE);

if (isset($nodes_percent)) {
    $nodes_percent_decode = (int) urldecode($nodes_percent);

    // Array dei dati
    $data = [100 - $nodes_percent_decode, $nodes_percent_decode];

    MtJpGraph::load('pie');
    // Crea un grafico a torta
    // @phpstan-ignore-next-line
    $graph = new PieGraph(300, 200);
    $graph->SetShadow();

    // Set titolo
    $graph->title->Set('Nodi visitati');
    $graph->title->SetFont(FF_FONT1, FS_BOLD);

    // Crea il grafico
    // @phpstan-ignore-next-line
    $p1 = new PiePlot($data);
    $p1->SetLegends(['visitati']);
    $graph->Add($p1);

    // output
    $graph->Stroke();
}
