<?php

/**
 *  Root dir relative path
 */
if (!defined('ROOT_DIR')) {
  define('ROOT_DIR', realpath(dirname(__FILE__)) . '/../../');
}
require_once ROOT_DIR . '/config/config_main.inc.php';
require_once ROOT_DIR . '/config/config_jsgraph.inc.php';

include 'graph/jpgraph.php';
include 'graph/jpgraph_pie.php';

extract($_GET, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);
extract($_POST, EXTR_OVERWRITE, ADA_GP_VARIABLES_PREFIX);

$nodes_percent_decode = urldecode($nodes_percent);

// Array dei dati
$data = array($nodes_percent_decode, 100 - $nodes_percent_decode);

// Crea un grafico a torta
$graph = new PieGraph(300, 200);
$graph->SetShadow();

// Set titolo
$graph->title->Set('Nodi visitati');
$graph->title->SetFont(FF_FONT1, FS_BOLD);

// Crea il grafico
$p1 = new PiePlot($data);
$p1->SetLegends(array('visitati'));
$graph->Add($p1);

// output
$graph->Stroke();
