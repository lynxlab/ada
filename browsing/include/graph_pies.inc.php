<?php

/**
 *  Root dir relative path
 */

require_once realpath(dirname(__FILE__)) . '/../../config/config_jsgraph.inc.php';
require_once realpath(dirname(__FILE__)) . '/graph/jpgraph.php';
require_once realpath(dirname(__FILE__)) . '/graph/jpgraph_pie.php';

extract($_GET, EXTR_OVERWRITE);
extract($_POST, EXTR_OVERWRITE);

if (isset($nodes_percent)) {
    $nodes_percent_decode = urldecode($nodes_percent);

    // Array dei dati
    $data = [$nodes_percent_decode, 100 - $nodes_percent_decode];

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
