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
    if ($nodes_percent_decode <= 0) {
        $data = [100];
        $legends = ['non visitati'];
    } else if ($nodes_percent_decode >= 100) {
        $data = [100];
        $legends = ['visitati'];
    } else {
        $data = [100 - $nodes_percent_decode, $nodes_percent_decode];
        $legends = ['non visitati', 'visitati'];
    }

    MtJpGraph::load(['pie', 'pie3d'], true);
    // Crea un grafico a torta
    // @phpstan-ignore-next-line
    $graph = new PieGraph(500, 400);
    $graph->SetShadow();

    // Set titolo
    $graph->title->Set('Nodi visitati');
    $graph->title->SetFont(FF_DV_SANSSERIF, FS_BOLD, 18);

    // Crea il grafico
    // @phpstan-ignore-next-line
    $p1 = new PiePlot3D($data);
    $p1->SetStartAngle(0);
    $p1->ExplodeAll(10);
    $p1->SetSize(150);
    $p1->SetLegends($legends);
    $graph->Add($p1);

    // output
    $graph->Stroke();
}
