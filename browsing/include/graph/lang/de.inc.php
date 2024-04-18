<?php
/*=======================================================================
// File:        DE.INC.PHP
// Description: German language file for error messages
// Created:     2006-03-06
// Author:      Timo Leopold (timo@leopold-hh.de)
//              Johan Persson (ljp@localhost.nil)
// Ver:         $Id: de.inc.php 1886 2009-10-01 23:30:16Z ljp $
//
// Copyright (c)
//========================================================================
*/

// Notiz: Das Format fuer jede Fehlermeldung ist array(<Fehlermeldung>,<Anzahl der Argumente>)
$_jpg_messages = [

/*
** Headers wurden bereits gesendet - Fehler. Dies wird als HTML formatiert, weil es direkt als text zurueckgesendet wird
*/
10  => ['<table border="1"><tr><td style="color:darkred;font-size:1.2em;"><b>JpGraph Fehler:</b>
HTTP header wurden bereits gesendet.<br>Fehler in der Datei <b>%s</b> in der Zeile <b>%d</b>.</td></tr><tr><td><b>Erklärung:</b><br>HTTP header wurden bereits zum Browser gesendet, wobei die Daten als Text gekennzeichnet wurden, bevor die Bibliothek die Chance hatte, seinen Bild-HTTP-Header zum Browser zu schicken. Dies verhindert, dass die Bibliothek Bilddaten zum Browser schicken kann (weil sie vom Browser als Text interpretiert würden und daher nur Mist dargestellt würde).<p>Wahrscheinlich steht Text im Skript bevor <i>Graph::Stroke()</i> aufgerufen wird. Wenn dieser Text zum Browser gesendet wird, nimmt dieser an, dass die gesamten Daten aus Text bestehen. Such nach irgendwelchem Text, auch nach Leerzeichen und Zeilenumbrüchen, die eventuell bereits zum Browser gesendet wurden. <p>Zum Beispiel ist ein oft auftretender Fehler, eine Leerzeile am Anfang der Datei oder vor <i>Graph::Stroke()</i> zu lassen."<b>&lt;?php</b>".</td></tr></table>',2],

/*
** Setup Fehler
*/
11 => ['Es wurde kein Pfad für CACHE_DIR angegeben. Bitte gib einen Pfad CACHE_DIR in der Datei jpg-config.inc an.',0],
12 => ['Es wurde kein Pfad für TTF_DIR angegeben und der Pfad kann nicht automatisch ermittelt werden. Bitte gib den Pfad in der Datei jpg-config.inc an.',0],
13 => ['The installed PHP version (%s) is not compatible with this release of the library. The library requires at least PHP version %s',2],

/*
**  jpgraph_bar
*/

2001 => ['Die Anzahl der Farben ist nicht gleich der Anzahl der Vorlagen in BarPlot::SetPattern().',0],
2002 => ['Unbekannte Vorlage im Aufruf von BarPlot::SetPattern().',0],
2003 => ['Anzahl der X- und Y-Koordinaten sind nicht identisch. Anzahl der X-Koordinaten: %d; Anzahl der Y-Koordinaten: %d.',2],
2004 => ['Alle Werte für ein Balkendiagramm (barplot) müssen numerisch sein. Du hast den Wert nr [%d] == %s angegeben.',2],
2005 => ['Du hast einen leeren Vektor für die Schattierungsfarben im Balkendiagramm (barplot) angegeben.',0],
2006 => ['Unbekannte Position für die Werte der Balken: %s.',1],
2007 => ['Kann GroupBarPlot nicht aus einem leeren Vektor erzeugen.',0],
2008 => ['GroupBarPlot Element nbr %d wurde nicht definiert oder ist leer.',0],
2009 => ['Eins der Objekte, das an GroupBar weitergegeben wurde ist kein Balkendiagramm (BarPlot). Versichere Dich, dass Du den GroupBarPlot aus einem Vektor von Balkendiagrammen (barplot) oder AccBarPlot-Objekten erzeugst. (Class = %s)',1],
2010 => ['Kann AccBarPlot nicht aus einem leeren Vektor erzeugen.',0],
2011 => ['AccBarPlot-Element nbr %d wurde nicht definiert oder ist leer.',1],
2012 => ['Eins der Objekte, das an AccBar weitergegeben wurde ist kein Balkendiagramm (barplot). Versichere Dich, dass Du den AccBar-Plot aus einem Vektor von Balkendiagrammen (barplot) erzeugst. (Class=%s)',1],
2013 => ['Du hast einen leeren Vektor für die Schattierungsfarben im Balkendiagramm (barplot) angegeben.',0],
2014 => ['Die Anzahl der Datenpunkte jeder Datenreihe in AccBarPlot muss gleich sein.',0],
2015 => ['Individual bar plots in an AccBarPlot or GroupBarPlot can not have specified X-coordinates',0],


/*
**  jpgraph_date
*/

3001 => ['Es ist nur möglich, entweder SetDateAlign() oder SetTimeAlign() zu benutzen, nicht beides!',0],

/*
**  jpgraph_error
*/

4002 => ['Fehler bei den Eingabedaten von LineErrorPlot. Die Anzahl der Datenpunkte mus ein Mehrfaches von drei sein!',0],

/*
**  jpgraph_flags
*/

5001 => ['Unbekannte Flaggen-Größe (%d).',1],
5002 => ['Der Flaggen-Index %s existiert nicht.',1],
5003 => ['Es wurde eine ungültige Ordnungszahl (%d) für den Flaggen-Index angegeben.',1],
5004 => ['Der Landesname %s hat kein korrespondierendes Flaggenbild. Die Flagge mag existieren, abr eventuell unter einem anderen Namen, z.B. versuche "united states" statt "usa".',1],


/*
**  jpgraph_gantt
*/

6001 => ['Interner Fehler. Die Höhe für ActivityTitles ist < 0.',0],
6002 => ['Es dürfen keine negativen Werte für die Gantt-Diagramm-Dimensionen angegeben werden. Verwende 0, wenn die Dimensionen automatisch ermittelt werden sollen.',0],
6003 => ['Ungültiges Format für den Bedingungs-Parameter bei Index=%d in CreateSimple(). Der Parameter muss bei index 0 starten und Vektoren in der Form (Row,Constrain-To,Constrain-Type) enthalten.',1],
6004 => ['Ungültiges Format für den Fortschritts-Parameter bei Index=%d in CreateSimple(). Der Parameter muss bei Index 0 starten und Vektoren in der Form (Row,Progress) enthalten.',1],
6005 => ['SetScale() ist nicht sinnvoll bei Gantt-Diagrammen.',0],
6006 => ['Das Gantt-Diagramm kann nicht automatisch skaliert werden. Es existieren keine Aktivitäten mit Termin. [GetBarMinMax() start >= n]',0],
6007 => ['Plausibiltätsprüfung für die automatische Gantt-Diagramm-Größe schlug fehl. Entweder die Breite (=%d) oder die Höhe (=%d) ist größer als MAX_GANTTIMG_SIZE. Dies kann möglicherweise durch einen falschen Wert bei einer Aktivität hervorgerufen worden sein.',2],
6008 => ['Du hast eine Bedingung angegeben von Reihe=%d bis Reihe=%d, die keine Aktivität hat.',2],
6009 => ['Unbekannter Bedingungstyp von Reihe=%d bis Reihe=%d',2],
6010 => ['Ungültiger Icon-Index für das eingebaute Gantt-Icon [%d]',1],
6011 => ['Argument für IconImage muss entweder ein String oder ein Integer sein.',0],
6012 => ['Unbekannter Typ bei der Gantt-Objekt-Title-Definition.',0],
6015 => ['Ungültige vertikale Position %d',1],
6016 => ['Der eingegebene Datums-String (%s) für eine Gantt-Aktivität kann nicht interpretiert werden. Versichere Dich, dass es ein gültiger Datumsstring ist, z.B. 2005-04-23 13:30',1],
6017 => ['Unbekannter Datumstyp in GanttScale (%s).',1],
6018 => ['Intervall für Minuten muss ein gerader Teiler einer Stunde sein, z.B. 1,5,10,12,15,20,30, etc. Du hast ein Intervall von %d Minuten angegeben.',1],
6019 => ['Die vorhandene Breite (%d) für die Minuten ist zu klein, um angezeigt zu werden. Bitte benutze die automatische Größenermittlung oder vergrößere die Breite des Diagramms.',1],
6020 => ['Das Intervall für die Stunden muss ein gerader Teiler eines Tages sein, z.B. 0:30, 1:00, 1:30, 4:00, etc. Du hast ein Intervall von %d eingegeben.',1],
6021 => ['Unbekanntes Format für die Woche.',0],
6022 => ['Die Gantt-Skala wurde nicht eingegeben.',0],
6023 => ['Wenn Du sowohl Stunden als auch Minuten anzeigen lassen willst, muss das Stunden-Interval gleich 1 sein (anderenfalls ist es nicht sinnvoll, Minuten anzeigen zu lassen).',0],
6024 => ['Das CSIM-Ziel muss als String angegeben werden. Der Start des Ziels ist: %d',1],
6025 => ['Der CSIM-Alt-Text muss als String angegeben werden. Der Beginn des Alt-Textes ist: %d',1],
6027 => ['Der Fortschrittswert muss im Bereich [0, 1] liegen.',0],
6028 => ['Die eingegebene Höhe (%d) für GanttBar ist nicht im zulässigen Bereich.',1],
6029 => ['Der Offset für die vertikale Linie muss im Bereich [0,1] sein.',0],
6030 => ['Unbekannte Pfeilrichtung für eine Verbindung.',0],
6031 => ['Unbekannter Pfeiltyp für eine Verbindung.',0],
6032 => ['Interner Fehler: Unbekannter Pfadtyp (=%d) für eine Verbindung.',1],
6033 => ['Array of fonts must contain arrays with 3 elements, i.e. (Family, Style, Size)',0],

/*
**  jpgraph_gradient
*/

7001 => ['Unbekannter Gradiententyp (=%d).',1],

/*
**  jpgraph_iconplot
*/

8001 => ['Der Mix-Wert für das Icon muss zwischen 0 und 100 sein.',0],
8002 => ['Die Ankerposition für Icons muss entweder "top", "bottom", "left", "right" oder "center" sein.',0],
8003 => ['Es ist nicht möglich, gleichzeitig ein Bild und eine Landesflagge für dasselbe Icon zu definieren',0],
8004 => ['Wenn Du Landesflaggen benutzen willst, musst Du die Datei "jpgraph_flags.php" hinzufügen (per include).',0],

/*
**  jpgraph_imgtrans
*/

9001 => ['Der Wert für die Bildtransformation ist außerhalb des zulässigen Bereichs. Der verschwindende Punkt am Horizont muss als Wert zwischen 0 und 1 angegeben werden.',0],

/*
**  jpgraph_lineplot
*/

10001 => ['Die Methode LinePlot::SetFilled() sollte nicht mehr benutzt werden. Benutze lieber SetFillColor()',0],
10002 => ['Der Plot ist zu kompliziert für FastLineStroke. Benutze lieber den StandardStroke()',0],
10003 => ['Each plot in an accumulated lineplot must have the same number of data points.',0],
/*
**  jpgraph_log
*/

11001 => ['Deine Daten enthalten nicht-numerische Werte.',0],
11002 => ['Negative Werte können nicht für logarithmische Achsen verwendet werden.',0],
11003 => ['Deine Daten enthalten nicht-numerische Werte.',0],
11004 => ['Skalierungsfehler für die logarithmische Achse. Es gibt ein Problem mit den Daten der Achse. Der größte Wert muss größer sein als Null. Es ist mathematisch nicht möglich, einen Wert gleich Null in der Skala zu haben.',0],
11005 => ['Das Tick-Intervall für die logarithmische Achse ist nicht definiert. Lösche jeden Aufruf von SetTextLabelStart() oder SetTextTickInterval() bei der logarithmischen Achse.',0],

/*
**  jpgraph_mgraph
*/

12001 => ["Du benutzt GD 2.x und versuchst ein Nicht-Truecolor-Bild als Hintergrundbild zu benutzen. Um Hintergrundbilder mit GD 2.x zu benutzen, ist es notwendig Truecolor zu aktivieren, indem die USE_TRUECOLOR-Konstante auf TRUE gesetzt wird. Wegen eines Bugs in GD 2.0.1 ist die Qualität der Truetype-Schriften sehr schlecht, wenn man Truetype-Schriften mit Truecolor-Bildern verwendet.",0],
12002 => ['Ungültiger Dateiname für MGraph::SetBackgroundImage() : %s. Die Datei muss eine gültige Dateierweiterung haben (jpg,gif,png), wenn die automatische Typerkennung verwendet wird.',1],
12003 => ['Unbekannte Dateierweiterung (%s) in MGraph::SetBackgroundImage() für Dateiname: %s',2],
12004 => ['Das Bildformat des Hintergrundbildes (%s) wird von Deiner System-Konfiguration nicht unterstützt. ',1],
12005 => ['Das Hintergrundbild kann nicht gelesen werden: %s',1],
12006 => ['Es wurden ungültige Größen für Breite oder Höhe beim Erstellen des Bildes angegeben, (Breite=%d, Höhe=%d)',2],
12007 => ['Das Argument für MGraph::Add() ist nicht gültig für GD.',0],
12008 => ['Deine PHP- (und GD-lib-) Installation scheint keine bekannten Bildformate zu unterstützen.',0],
12009 => ['Deine PHP-Installation unterstützt das gewählte Bildformat nicht: %s',1],
12010 => ['Es konnte kein Bild als Datei %s erzeugt werden. Überprüfe, ob Du die entsprechenden Schreibrechte im aktuellen Verzeichnis hast.',1],
12011 => ['Es konnte kein Truecolor-Bild erzeugt werden. Überprüfe, ob Du wirklich die GD2-Bibliothek installiert hast.',0],
12012 => ['Es konnte kein Bild erzeugt werden. Überprüfe, ob Du wirklich die GD2-Bibliothek installiert hast.',0],

/*
**  jpgraph_pie3d
*/

14001 => ['Pie3D::ShowBorder(). Missbilligte Funktion. Benutze Pie3D::SetEdge(), um die Ecken der Tortenstücke zu kontrollieren.',0],
14002 => ['PiePlot3D::SetAngle() 3D-Torten-Projektionswinkel muss zwischen 5 und 85 Grad sein.',0],
14003 => ['Interne Festlegung schlug fehl. Pie3D::Pie3DSlice',0],
14004 => ['Tortenstück-Startwinkel muss zwischen 0 und 360 Grad sein.',0],
14005 => ['Pie3D Interner Fehler: Versuch, zweimal zu umhüllen bei der Suche nach dem Startindex.',0,],
14006 => ['Pie3D Interner Fehler: Z-Sortier-Algorithmus für 3D-Tortendiagramme funktioniert nicht einwandfrei (2). Versuch, zweimal zu umhüllen beim Erstellen des Bildes.',0],
14007 => ['Die Breite für das 3D-Tortendiagramm ist 0. Gib eine Breite > 0 an.',0],

/*
**  jpgraph_pie
*/

15001 => ['PiePLot::SetTheme() Unbekannter Stil: %s',1],
15002 => ['Argument für PiePlot::ExplodeSlice() muss ein Integer-Wert sein',0],
15003 => ['Argument für PiePlot::Explode() muss ein Vektor mit Integer-Werten sein.',0],
15004 => ['Tortenstück-Startwinkel muss zwischen 0 und 360 Grad sein.',0],
15005 => ['PiePlot::SetFont() sollte nicht mehr verwendet werden. Benutze stattdessen PiePlot->value->SetFont().',0],
15006 => ['PiePlot::SetSize() Radius für Tortendiagramm muss entweder als Bruch [0, 0.5] der Bildgröße oder als Absoluwert in Pixel im Bereich [10, 1000] angegeben werden.',0],
15007 => ['PiePlot::SetFontColor() sollte nicht mehr verwendet werden. Benutze stattdessen PiePlot->value->SetColor()..',0],
15008 => ['PiePlot::SetLabelType() der Typ für Tortendiagramme muss entweder 0 or 1 sein (nicht %d).',1],
15009 => ['Ungültiges Tortendiagramm. Die Summe aller Daten ist Null.',0],
15010 => ['Die Summe aller Daten ist Null.',0],
15011 => ['Um Bildtransformationen benutzen zu können, muss die Datei jpgraph_imgtrans.php eingefügt werden (per include).',0], // @todo translate into German
15012 => ['PiePlot::SetTheme() is no longer recommended. Use PieGraph::SetTheme()',0],

/*
**  jpgraph_plotband
*/

16001 => ['Die Dichte für das Pattern muss zwischen 1 und 100 sein. (Du hast %f eingegeben)',1],
16002 => ['Es wurde keine Position für das Pattern angegeben.',0],
16003 => ['Unbekannte Pattern-Definition (%d)',0],
16004 => ['Der Mindeswert für das PlotBand ist größer als der Maximalwert. Bitte korrigiere dies!',0],


/*
**  jpgraph_polar
*/

17001 => ['PolarPlots müssen eine gerade Anzahl von Datenpunkten haben. Jeder Datenpunkt ist ein Tupel (Winkel, Radius).',0],
17002 => ['Unbekannte Ausrichtung für X-Achsen-Titel. (%s)',1],
//17003 => array('Set90AndMargin() wird für PolarGraph nicht unterstützt.',0),
17004 => ['Unbekannter Achsentyp für PolarGraph. Er muss entweder \'lin\' oder \'log\' sein.',0],

/*
**  jpgraph_radar
*/

18001 => ['ClientSideImageMaps werden für RadarPlots nicht unterstützt.',0],
18002 => ['RadarGraph::SupressTickMarks() sollte nicht mehr verwendet werden. Benutze stattdessen HideTickMarks().',0],
18003 => ['Ungültiger Achsentyp für RadarPlot (%s). Er muss entweder \'lin\' oder \'log\' sein.',1],
18004 => ['Die RadarPlot-Größe muss zwischen 0.1 und 1 sein. (Dein Wert=%f)',1],
18005 => ['RadarPlot: nicht unterstützte Tick-Dichte: %d',1],
18006 => ['Minimum Daten %f (RadarPlots sollten nur verwendet werden, wenn alle Datenpunkte einen Wert > 0 haben).',1],
18007 => ['Die Anzahl der Titel entspricht nicht der Anzahl der Datenpunkte.',0],
18008 => ['Jeder RadarPlot muss die gleiche Anzahl von Datenpunkten haben.',0],

/*
**  jpgraph_regstat
*/

19001 => ['Spline: Anzahl der X- und Y-Koordinaten muss gleich sein.',0],
19002 => ['Ungültige Dateneingabe für Spline. Zwei oder mehr aufeinanderfolgende X-Werte sind identisch. Jeder eigegebene X-Wert muss unterschiedlich sein, weil vom mathematischen Standpunkt ein Eins-zu-Eins-Mapping vorliegen muss, d.h. jeder X-Wert korrespondiert mit exakt einem Y-Wert.',0],
19003 => ['Bezier: Anzahl der X- und Y-Koordinaten muss gleich sein.',0],

/*
**  jpgraph_scatter
*/

20001 => ['Fieldplots müssen die gleiche Anzahl von X und Y Datenpunkten haben.',0],
20002 => ['Bei Fieldplots muss ein Winkel für jeden X und Y Datenpunkt angegeben werden.',0],
20003 => ['Scatterplots müssen die gleiche Anzahl von X- und Y-Datenpunkten haben.',0],

/*
**  jpgraph_stock
*/

21001 => ['Die Anzahl der Datenwerte für Stock-Charts müssen ein Mehrfaches von %d Datenpunkten sein.',1],

/*
**  jpgraph_plotmark
*/

23001 => ['Der Marker "%s" existiert nicht in der Farbe: %d',2],
23002 => ['Der Farb-Index ist zu hoch für den Marker "%s"',1],
23003 => ['Ein Dateiname muss angegeben werden, wenn Du den Marker-Typ auf MARK_IMG setzt.',0],

/*
**  jpgraph_utils
*/

24001 => ['FuncGenerator : Keine Funktion definiert. ',0],
24002 => ['FuncGenerator : Syntax-Fehler in der Funktionsdefinition ',0],
24003 => ['DateScaleUtils: Unknown tick type specified in call to GetTicks()',0],
24004 => ['ReadCSV2: Die anzahl der spalten fehler in %s reihe %d',2],
/*
**  jpgraph
*/

25001 => ['Diese PHP-Installation ist nicht mit der GD-Bibliothek kompiliert. Bitte kompiliere PHP mit GD-Unterstützung neu, damit JpGraph funktioniert. (Weder die Funktion imagetypes() noch imagecreatefromstring() existiert!)',0],
25002 => ['Diese PHP-Installation scheint nicht die benötigte GD-Bibliothek zu unterstützen. Bitte schau in der PHP-Dokumentation nach, wie man die GD-Bibliothek installiert und aktiviert.',0],
25003 => ['Genereller PHP Fehler : Bei %s:%d : %s',3],
25004 => ['Genereller PHP Fehler : %s ',1],
25005 => ['PHP_SELF, die PHP-Global-Variable kann nicht ermittelt werden. PHP kann nicht von der Kommandozeile gestartet werden, wenn der Cache oder die Bilddateien automatisch benannt werden sollen.',0],
25006 => ['Die Benutzung der FF_CHINESE (FF_BIG5) Schriftfamilie benötigt die iconv() Funktion in Deiner PHP-Konfiguration. Dies wird nicht defaultmäßig in PHP kompiliert (benötigt "--width-iconv" bei der Konfiguration).',0],
25007 => ['Du versuchst das lokale (%s) zu verwenden, was von Deiner PHP-Installation nicht unterstützt wird. Hinweis: Benutze \'\', um das defaultmäßige Lokale für diese geographische Region festzulegen.',1],
25008 => ['Die Bild-Breite und Höhe in Graph::Graph() müssen numerisch sein',0],
25009 => ['Die Skalierung der Achsen muss angegeben werden mit Graph::SetScale()',0],

25010 => ['Graph::Add() Du hast versucht, einen leeren Plot zum Graph hinzuzufügen.',0],
25011 => ['Graph::AddY2() Du hast versucht, einen leeren Plot zum Graph hinzuzufügen.',0],
25012 => ['Graph::AddYN() Du hast versucht, einen leeren Plot zum Graph hinzuzufügen.',0],
25013 => ['Es können nur Standard-Plots zu multiplen Y-Achsen hinzugefügt werden',0],
25014 => ['Graph::AddText() Du hast versucht, einen leeren Text zum Graph hinzuzufügen.',0],
25015 => ['Graph::AddLine() Du hast versucht, eine leere Linie zum Graph hinzuzufügen.',0],
25016 => ['Graph::AddBand() Du hast versucht, ein leeres Band zum Graph hinzuzufügen.',0],
25017 => ['Du benutzt GD 2.x und versuchst, ein Hintergrundbild in einem Truecolor-Bild zu verwenden. Um Hintergrundbilder mit GD 2.x zu verwenden, ist es notwendig, Truecolor zu aktivieren, indem die USE_TRUECOLOR-Konstante auf TRUE gesetzt wird. Wegen eines Bugs in GD 2.0.1 ist die Qualität der Schrift sehr schlecht, wenn Truetype-Schrift in Truecolor-Bildern verwendet werden.',0],
25018 => ['Falscher Dateiname für Graph::SetBackgroundImage() : "%s" muss eine gültige Dateinamenerweiterung (jpg,gif,png) haben, wenn die automatische Dateityperkennung verwenndet werden soll.',1],
25019 => ['Unbekannte Dateinamenerweiterung (%s) in Graph::SetBackgroundImage() für Dateiname: "%s"',2],

25020 => ['Graph::SetScale(): Dar Maximalwert muss größer sein als der Mindestwert.',0],
25021 => ['Unbekannte Achsendefinition für die Y-Achse. (%s)',1],
25022 => ['Unbekannte Achsendefinition für die X-Achse. (%s)',1],
25023 => ['Nicht unterstützter Y2-Achsentyp: "%s" muss einer von (lin,log,int) sein.',1],
25024 => ['Nicht unterstützter X-Achsentyp: "%s" muss einer von (lin,log,int) sein.',1],
25025 => ['Nicht unterstützte Tick-Dichte: %d',1],
25026 => ['Nicht unterstützter Typ der nicht angegebenen Y-Achse. Du hast entweder: 1. einen Y-Achsentyp für automatisches Skalieren definiert, aber keine Plots angegeben. 2. eine Achse direkt definiert, aber vergessen, die Tick-Dichte zu festzulegen.',0],
25027 => ['Kann cached CSIM "%s" zum Lesen nicht öffnen.',1],
25028 => ['Apache/PHP hat keine Schreibrechte, in das CSIM-Cache-Verzeichnis (%s) zu schreiben. Überprüfe die Rechte.',1],
25029 => ['Kann nicht in das CSIM "%s" schreiben. Überprüfe die Schreibrechte und den freien Speicherplatz.',1],

25030 => ['Fehlender Skriptname für StrokeCSIM(). Der Name des aktuellen Skriptes muss als erster Parameter von StrokeCSIM() angegeben werden.',0],
25031 => ['Der Achsentyp muss mittels Graph::SetScale() angegeben werden.',0],
25032 => ['Es existieren keine Plots für die Y-Achse nbr:%d',1],
25033 => ['',0],
25034 => ['Undefinierte X-Achse kann nicht gezeichnet werden. Es wurden keine Plots definiert.',0],
25035 => ['Du hast Clipping aktiviert. Clipping wird nur für Diagramme mit 0 oder 90 Grad Rotation unterstützt. Bitte verändere Deinen Rotationswinkel (=%d Grad) dementsprechend oder deaktiviere Clipping.',1],
25036 => ['Unbekannter Achsentyp AxisStyle() : %s',1],
25037 => ['Das Bildformat Deines Hintergrundbildes (%s) wird von Deiner System-Konfiguration nicht unterstützt. ',1],
25038 => ['Das Hintergrundbild scheint von einem anderen Typ (unterschiedliche Dateierweiterung) zu sein als der angegebene Typ. Angegebenen: %s; Datei: %s',2],
25039 => ['Hintergrundbild kann nicht gelesen werden: "%s"',1],

25040 => ['Es ist nicht möglich, sowohl ein Hintergrundbild als auch eine Hintergrund-Landesflagge anzugeben.',0],
25041 => ['Um Landesflaggen als Hintergrund benutzen zu können, muss die Datei "jpgraph_flags.php" eingefügt werden (per include).',0],
25042 => ['Unbekanntes Hintergrundbild-Layout',0],
25043 => ['Unbekannter Titelhintergrund-Stil.',0],
25044 => ['Automatisches Skalieren kann nicht verwendet werden, weil es unmöglich ist, einen gültigen min/max Wert für die Y-Achse zu ermitteln (nur Null-Werte).',0],
25045 => ['Die Schriftfamilien FF_HANDWRT und FF_BOOK sind wegen Copyright-Problemen nicht mehr verfügbar. Diese Schriften können nicht mehr mit JpGraph verteilt werden. Bitte lade Dir Schriften von http://corefonts.sourceforge.net/ herunter.',0],
25046 => ['Angegebene TTF-Schriftfamilie (id=%d) ist unbekannt oder existiert nicht. Bitte merke Dir, dass TTF-Schriften wegen Copyright-Problemen nicht mit JpGraph mitgeliefert werden. Du findest MS-TTF-Internetschriften (arial, courier, etc.) zum Herunterladen unter http://corefonts.sourceforge.net/',1],
25047 => ['Stil %s ist nicht verfügbar für Schriftfamilie %s',2],
25048 => ['Unbekannte Schriftstildefinition [%s].',1],
25049 => ['Schriftdatei "%s" ist nicht lesbar oder existiert nicht.',1],

25050 => ['Erstes Argument für Text::Text() muss ein String sein.',0],
25051 => ['Ungültige Richtung angegeben für Text.',0],
25052 => ['PANIK: Interner Fehler in SuperScript::Stroke(). Unbekannte vertikale Ausrichtung für Text.',0],
25053 => ['PANIK: Interner Fehler in SuperScript::Stroke(). Unbekannte horizontale Ausrichtung für Text.',0],
25054 => ['Interner Fehler: Unbekannte Grid-Achse %s',1],
25055 => ['Axis::SetTickDirection() sollte nicht mehr verwendet werden. Benutze stattdessen Axis::SetTickSide().',0],
25056 => ['SetTickLabelMargin() sollte nicht mehr verwendet werden. Benutze stattdessen Axis::SetLabelMargin().',0],
25057 => ['SetTextTicks() sollte nicht mehr verwendet werden. Benutze stattdessen SetTextTickInterval().',0],
25058 => ['TextLabelIntevall >= 1 muss angegeben werden.',0],
25059 => ['SetLabelPos() sollte nicht mehr verwendet werden. Benutze stattdessen Axis::SetLabelSide().',0],

25060 => ['Unbekannte Ausrichtung angegeben für X-Achsentitel (%s).',1],
25061 => ['Unbekannte Ausrichtung angegeben für Y-Achsentitel (%s).',1],
25062 => ['Label unter einem Winkel werden für die Y-Achse nicht unterstützt.',0],
25063 => ['Ticks::SetPrecision() sollte nicht mehr verwendet werden. Benutze stattdessen Ticks::SetLabelFormat() (oder Ticks::SetFormatCallback()).',0],
25064 => ['Kleinere oder größere Schrittgröße ist 0. Überprüfe, ob Du fälschlicherweise SetTextTicks(0) in Deinem Skript hast. Wenn dies nicht der Fall ist, bist Du eventuell über einen Bug in JpGraph gestolpert. Bitte sende einen Report und füge den Code an, der den Fehler verursacht hat.',0],
25065 => ['Tick-Positionen müssen als array() angegeben werden',0],
25066 => ['Wenn die Tick-Positionen und -Label von Hand eingegeben werden, muss die Anzahl der Ticks und der Label gleich sein.',0],
25067 => ['Deine von Hand eingegebene Achse und Ticks sind nicht korrekt. Die Skala scheint zu klein zu sein für den Tickabstand.',0],
25068 => ['Ein Plot hat eine ungültige Achse. Dies kann beispielsweise der Fall sein, wenn Du automatisches Text-Skalieren verwendest, um ein Liniendiagramm zu zeichnen mit nur einem Datenpunkt, oder wenn die Bildfläche zu klein ist. Es kann auch der Fall sein, dass kein Datenpunkt einen numerischen Wert hat (vielleicht nur \'-\' oder \'x\').',0],
25069 => ['Grace muss größer sein als 0',0],

25070 => ['Deine Daten enthalten nicht-numerische Werte.',0],
25071 => ['Du hast mit SetAutoMin() einen Mindestwert angegeben, der größer ist als der Maximalwert für die Achse. Dies ist nicht möglich.',0],
25072 => ['Du hast mit SetAutoMax() einen Maximalwert angegeben, der kleiner ist als der Minimalwert der Achse. Dies ist nicht möglich.',0],
25073 => ['Interner Fehler. Der Integer-Skalierungs-Algorithmus-Vergleich ist außerhalb der Grenzen  (r=%f).',1],
25074 => ['Interner Fehler. Der Skalierungsbereich ist negativ (%f) [für %s Achse]. Dieses Problem könnte verursacht werden durch den Versuch, \'ungültige\' Werte in die Daten-Vektoren einzugeben (z.B. nur String- oder NULL-Werte), was beim automatischen Skalieren einen Fehler erzeugt.',2],
25075 => ['Die automatischen Ticks können nicht gesetzt werden, weil min==max.',0],
25077 => ['Einstellfaktor für die Farbe muss größer sein als 0',0],
25078 => ['Unbekannte Farbe: %s',1],
25079 => ['Unbekannte Farbdefinition: %s, Größe=%d',2],

25080 => ['Der Alpha-Parameter für Farben muss zwischen 0.0 und 1.0 liegen.',0],
25081 => ['Das ausgewählte Grafikformat wird entweder nicht unterstützt oder ist unbekannt [%s]',1],
25082 => ['Es wurden ungültige Größen für Breite und Höhe beim Erstellen des Bildes definiert (Breite=%d, Höhe=%d).',2],
25083 => ['Es wurde eine ungültige Größe beim Kopieren des Bildes angegeben. Die Größe für das kopierte Bild wurde auf 1 Pixel oder weniger gesetzt.',0],
25084 => ['Fehler beim Erstellen eines temporären GD-Canvas. Möglicherweise liegt ein Arbeitsspeicherproblem vor.',0],
25085 => ['Ein Bild kann nicht aus dem angegebenen String erzeugt werden. Er ist entweder in einem nicht unterstützen Format oder er represäntiert ein kaputtes Bild.',0],
25086 => ['Du scheinst nur GD 1.x installiert zu haben. Um Alphablending zu aktivieren, ist GD 2.x oder höher notwendig. Bitte installiere GD 2.x oder versichere Dich, dass die Konstante USE_GD2 richtig gesetzt ist. Standardmäßig wird die installierte GD-Version automatisch erkannt. Ganz selten wird GD2 erkannt, obwohl nur GD1 installiert ist. Die Konstante USE_GD2 muss dann zu "false" gesetzt werden.',0],
25087 => ['Diese PHP-Version wurde ohne TTF-Unterstützung konfiguriert. PHP muss mit TTF-Unterstützung neu kompiliert und installiert werden.',0],
25088 => ['Die GD-Schriftunterstützung wurde falsch konfiguriert. Der Aufruf von imagefontwidth() ist fehlerhaft.',0],
25089 => ['Die GD-Schriftunterstützung wurde falsch konfiguriert. Der Aufruf von imagefontheight() ist fehlerhaft.',0],

25090 => ['Unbekannte Richtung angegeben im Aufruf von StrokeBoxedText() [%s].',1],
25091 => ['Die interne Schrift untestützt das Schreiben von Text in einem beliebigen Winkel nicht. Benutze stattdessen TTF-Schriften.',0],
25092 => ['Es liegt entweder ein Konfigurationsproblem mit TrueType oder ein Problem beim Lesen der Schriftdatei "%s" vor. Versichere Dich, dass die Datei existiert und Leserechte und -pfad vergeben sind. (wenn \'basedir\' restriction in PHP aktiviert ist, muss die Schriftdatei im Dokumentwurzelverzeichnis abgelegt werden). Möglicherweise ist die FreeType-Bibliothek falsch installiert. Versuche, mindestens zur FreeType-Version 2.1.13 zu aktualisieren und kompiliere GD mit einem korrekten Setup neu, damit die FreeType-Bibliothek gefunden werden kann.',1],
25093 => ['Die Schriftdatei "%s" kann nicht gelesen werden beim Aufruf von Image::GetBBoxTTF. Bitte versichere Dich, dass die Schrift gesetzt wurde, bevor diese Methode aufgerufen wird, und dass die Schrift im TTF-Verzeichnis installiert ist.',1],
25094 => ['Die Textrichtung muss in einem Winkel zwischen 0 und 90 engegeben werden.',0],
25095 => ['Unbekannte Schriftfamilien-Definition. ',0],
25096 => ['Der Farbpalette können keine weiteren Farben zugewiesen werden. Dem Bild wurde bereits die größtmögliche Anzahl von Farben (%d) zugewiesen und die Palette ist voll. Verwende stattdessen ein TrueColor-Bild',0],
25097 => ['Eine Farbe wurde als leerer String im Aufruf von PushColor() angegegeben.',0],
25098 => ['Negativer Farbindex. Unpassender Aufruf von PopColor().',0],
25099 => ['Die Parameter für Helligkeit und Kontrast sind außerhalb des zulässigen Bereichs [-1,1]',0],

25100 => ['Es liegt ein Problem mit der Farbpalette und dem GD-Setup vor. Bitte deaktiviere anti-aliasing oder verwende GD2 mit TrueColor. Wenn die GD2-Bibliothek installiert ist, versichere Dich, dass die Konstante USE_GD2 auf "true" gesetzt und TrueColor aktiviert ist.',0],
25101 => ['Ungültiges numerisches Argument für SetLineStyle(): (%d)',1],
25102 => ['Ungültiges String-Argument für SetLineStyle(): %s',1],
25103 => ['Ungültiges Argument für SetLineStyle %s',1],
25104 => ['Unbekannter Linientyp: %s',1],
25105 => ['Es wurden NULL-Daten für ein gefülltes Polygon angegeben. Sorge dafür, dass keine NULL-Daten angegeben werden.',0],
25106 => ['Image::FillToBorder : es können keine weiteren Farben zugewiesen werden.',0],
25107 => ['In Datei "%s" kann nicht geschrieben werden. Überprüfe die aktuellen Schreibrechte.',1],
25108 => ['Das Bild kann nicht gestreamt werden. Möglicherweise liegt ein Fehler im PHP/GD-Setup vor. Kompiliere PHP neu und verwende die eingebaute GD-Bibliothek, die mit PHP angeboten wird.',0],
25109 => ['Deine PHP- (und GD-lib-) Installation scheint keine bekannten Grafikformate zu unterstützen. Sorge zunächst dafür, dass GD als PHP-Modul kompiliert ist. Wenn Du außerdem JPEG-Bilder verwenden willst, musst Du die JPEG-Bibliothek installieren. Weitere Details sind in der PHP-Dokumentation zu finden.',0],

25110 => ['Dein PHP-Installation unterstützt das gewählte Grafikformat nicht: %s',1],
25111 => ['Das gecachete Bild %s kann nicht gelöscht werden. Problem mit den Rechten?',1],
25112 => ['Das Datum der gecacheten Datei (%s) liegt in der Zukunft.',1],
25113 => ['Das gecachete Bild %s kann nicht gelöscht werden. Problem mit den Rechten?',1],
25114 => ['PHP hat nicht die erforderlichen Rechte, um in die Cache-Datei %s zu schreiben. Bitte versichere Dich, dass der Benutzer, der PHP anwendet, die entsprechenden Schreibrechte für die Datei hat, wenn Du das Cache-System in JPGraph verwenden willst.',1],
25115 => ['Berechtigung für gecachetes Bild %s kann nicht gesetzt werden. Problem mit den Rechten?',1],
25116 => ['Datei kann nicht aus dem Cache %s geöffnet werden',1],
25117 => ['Gecachetes Bild %s kann nicht zum Lesen geöffnet werden.',1],
25118 => ['Verzeichnis %s kann nicht angelegt werden. Versichere Dich, dass PHP die Schreibrechte in diesem Verzeichnis hat.',1],
25119 => ['Rechte für Datei %s können nicht gesetzt werden. Problem mit den Rechten?',1],

25120 => ['Die Position für die Legende muss als Prozentwert im Bereich 0-1 angegeben werden.',0],
25121 => ['Eine leerer Datenvektor wurde für den Plot eingegeben. Es muss wenigstens ein Datenpunkt vorliegen.',0],
25122 => ['Stroke() muss als Subklasse der Klasse Plot definiert sein.',0],
25123 => ['Du kannst keine Text-X-Achse mit X-Koordinaten verwenden. Benutze stattdessen eine "int" oder "lin" Achse.',0],
25124 => ['Der Eingabedatenvektor mus aufeinanderfolgende Werte von 0 aufwärts beinhalten. Der angegebene Y-Vektor beginnt mit leeren Werten (NULL).',0],
25125 => ['Ungültige Richtung für statische Linie.',0],
25126 => ['Es kann kein TrueColor-Bild erzeugt werden. Überprüfe, ob die GD2-Bibliothek und PHP korrekt aufgesetzt wurden.',0],
25127 => ['The library has been configured for automatic encoding conversion of Japanese fonts. This requires that PHP has the mb_convert_encoding() function. Your PHP installation lacks this function (PHP needs the "--enable-mbstring" when compiled).',0],
25128 => ['The function imageantialias() is not available in your PHP installation. Use the GD version that comes with PHP and not the standalone version.',0],
25129 => ['Anti-alias can not be used with dashed lines. Please disable anti-alias or use solid lines.',0],
25130 => ['Too small plot area. (%d x %d). With the given image size and margins there is to little space left for the plot. Increase the plot size or reduce the margins.',2],

25131 => ['StrokeBoxedText2() only supports TTF fonts and not built-in bitmap fonts.',0],
25132 => ['Undefined property %s.',1], // @todo translate
25133 => ['Use Graph::SetTheme() after Graph::SetScale().',0], // @todo translate

/*
**  jpgraph_led
*/

25500 => ['Multibyte strings must be enabled in the PHP installation in order to run the LED module so that the function mb_strlen() is available. See PHP documentation for more information.',0],


/*
**---------------------------------------------------------------------------------------------
** Pro-version strings
**---------------------------------------------------------------------------------------------
*/

/*
**  jpgraph_table
*/

27001 => ['GTextTable: Ungültiges Argument für Set(). Das Array-Argument muss 2-- dimensional sein.',0],
27002 => ['GTextTable: Ungültiges Argument für Set()',0],
27003 => ['GTextTable: Falsche Anzahl von Argumenten für GTextTable::SetColor()',0],
27004 => ['GTextTable: Angegebener Zellenbereich, der verschmolzen werden soll, ist ungültig.',0],
27005 => ['GTextTable: Bereits verschmolzene Zellen im Bereich (%d,%d) bis (%d,%d) können nicht ein weiteres Mal verschmolzen werden.',4],
27006 => ['GTextTable: Spalten-Argument = %d liegt außerhalb der festgelegten Tabellengröße.',1],
27007 => ['GTextTable: Zeilen-Argument = %d liegt außerhalb der festgelegten Tabellengröße.',1],
27008 => ['GTextTable: Spalten- und Zeilengröße müssen zu den Dimensionen der Tabelle passen.',0],
27009 => ['GTextTable: Die Anzahl der Tabellenspalten oder -zeilen ist 0. Versichere Dich, dass die Methoden Init() oder Set() aufgerufen werden.',0],
27010 => ['GTextTable: Es wurde keine Ausrichtung beim Aufruf von SetAlign() angegeben.',0],
27011 => ['GTextTable: Es wurde eine unbekannte Ausrichtung beim Aufruf von SetAlign() abgegeben. Horizontal=%s, Vertikal=%s',2],
27012 => ['GTextTable: Interner Fehler. Es wurde ein ungültiges Argument festgeleget %s',1],
27013 => ['GTextTable: Das Argument für FormatNumber() muss ein String sein.',0],
27014 => ['GTextTable: Die Tabelle wurde weder mit einem Aufruf von Set() noch von Init() initialisiert.',0],
27015 => ['GTextTable: Der Zellenbildbedingungstyp muss entweder TIMG_WIDTH oder TIMG_HEIGHT sein.',0],

/*
**  jpgraph_windrose
*/

22001 => ['Die Gesamtsumme der prozentualen Anteile aller Windrosenarme darf 100%% nicht überschreiten!\n(Aktuell max: %d)',1],
22002 => ['Das Bild ist zu klein für eine Skala. Bitte vergrößere das Bild.',0],
22004 => ['Die Etikettendefinition für Windrosenrichtungen müssen 16 Werte haben (eine für jede Kompassrichtung).',0],
22005 => ['Der Linientyp für radiale Linien muss einer von ("solid","dotted","dashed","longdashed") sein.',0],
22006 => ['Es wurde ein ungültiger Windrosentyp angegeben.',0],
22007 => ['Es wurden zu wenig Werte für die Bereichslegende angegeben.',0],
22008 => ['Interner Fehler: Versuch, eine freie Windrose zu plotten, obwohl der Typ keine freie Windrose ist.',0],
22009 => ['Du hast die gleiche Richtung zweimal angegeben, einmal mit einem Winkel und einmal mit einer Kompassrichtung (%f Grad).',0],
22010 => ['Die Richtung muss entweder ein numerischer Wert sein oder eine der 16 Kompassrichtungen',0],
22011 => ['Der Windrosenindex muss ein numerischer oder Richtungswert sein. Du hast angegeben Index=%d',1],
22012 => ['Die radiale Achsendefinition für die Windrose enthält eine nicht aktivierte Richtung.',0],
22013 => ['Du hast dasselbe Look&Feel für die gleiche Kompassrichtung zweimal engegeben, einmal mit Text und einmal mit einem Index (Index=%d)',1],
22014 => ['Der Index für eine Kompassrichtung muss zwischen 0 und 15 sein.',0],
22015 => ['Du hast einen unbekannten Windrosenplottyp angegeben.',0],
22016 => ['Der Windrosenarmindex muss ein numerischer oder ein Richtungswert sein.',0],
22017 => ['Die Windrosendaten enthalten eine Richtung, die nicht aktiviert ist. Bitte berichtige, welche Label angezeigt werden sollen.',0],
22018 => ['Du hast für dieselbe Kompassrichtung zweimal Daten angegeben, einmal mit Text und einmal mit einem Index (Index=%d)',1],
22019 => ['Der Index für eine Richtung muss zwischen 0 und 15 sein. Winkel dürfen nicht für einen regelmäßigen Windplot angegeben werden, sondern entweder ein Index oder eine Kompassrichtung.',0],
22020 => ['Der Windrosenplot ist zu groß für die angegebene Bildgröße. Benutze entweder WindrosePlot::SetSize(), um den Plot kleiner zu machen oder vergrößere das Bild im ursprünglichen Aufruf von WindroseGraph().',0],
22021 => ['It is only possible to add Text, IconPlot or WindrosePlot to a Windrose Graph',0],

/*
**  jpgraph_odometer
*/

13001 => ['Unbekannter Nadeltypstil (%d).',1],
13002 => ['Ein Wert für das Odometer (%f) ist außerhalb des angegebenen Bereichs [%f,%f]',3],

/*
**  jpgraph_barcode
*/

1001 => ['Unbekannte Kodier-Specifikation: %s',1],
1002 => ['datenvalidierung schlug fehl. [%s] kann nicht mittels der Kodierung "%s" kodiert werden',2],
1003 => ['Interner Kodierfehler. Kodieren von %s ist nicht möglich in Code 128',1],
1004 => ['Interner barcode Fehler. Unbekannter UPC-E Kodiertyp: %s',1],
1005 => ['Interner Fehler. Das Textzeichen-Tupel (%s, %s) kann nicht im Code-128 Zeichensatz C kodiert werden.',2],
1006 => ['Interner Kodierfehler für CODE 128. Es wurde versucht, CTRL in CHARSET != A zu kodieren.',0],
1007 => ['Interner Kodierfehler für CODE 128. Es wurde versucht, DEL in CHARSET != B zu kodieren.',0],
1008 => ['Interner Kodierfehler für CODE 128. Es wurde versucht, kleine Buchstaben in CHARSET != B zu kodieren.',0],
1009 => ['Kodieren mittels CODE 93 wird noch nicht unterstützt.',0],
1010 => ['Kodieren mittels POSTNET wird noch nicht unterstützt.',0],
1011 => ['Nicht untrstütztes Barcode-Backend für den Typ %s',1],

/*
** PDF417
*/

26000 => ['PDF417: The PDF417 module requires that the PHP installation must support the function bcmod(). This is normally enabled at compile time. See documentation for more information.',0],
26001 => ['PDF417: Die Anzahl der Spalten muss zwischen 1 und 30 sein.',0],
26002 => ['PDF417: Der Fehler-Level muss zwischen 0 und 8 sein.',0],
26003 => ['PDF417: Ungültiges Format für Eingabedaten, um sie mit PDF417 zu kodieren.',0],
26004 => ['PDF417: die eigebenen Daten können nicht mit Fehler-Level %d und %d spalten kodiert werden, weil daraus zu viele Symbole oder mehr als 90 Zeilen resultieren.',2],
26005 => ['PDF417: Die Datei "%s" kann nicht zum Schreiben geöffnet werden.',1],
26006 => ['PDF417: Interner Fehler. Die Eingabedatendatei für PDF417-Cluster %d ist fehlerhaft.',1],
26007 => ['PDF417: Interner Fehler. GetPattern: Ungültiger Code-Wert %d (Zeile %d)',2],
26008 => ['PDF417: Interner Fehler. Modus wurde nicht in der Modusliste!! Modus %d',1],
26009 => ['PDF417: Kodierfehler: Ungültiges Zeichen. Zeichen kann nicht mit ASCII-Code %d kodiert werden.',1],
26010 => ['PDF417: Interner Fehler: Keine Eingabedaten beim Dekodieren.',0],
26011 => ['PDF417: Kodierfehler. Numerisches Kodieren bei nicht-numerischen Daten nicht möglich.',0],
26012 => ['PDF417: Interner Fehler. Es wurden für den Binary-Kompressor keine Daten zum Dekodieren eingegeben.',0],
26013 => ['PDF417: Interner Fehler. Checksum Fehler. Koeffiziententabellen sind fehlerhaft.',0],
26014 => ['PDF417: Interner Fehler. Es wurden keine Daten zum Berechnen von Kodewörtern eingegeben.',0],
26015 => ['PDF417: Interner Fehler. Ein Eintrag 0 in die Statusübertragungstabellen ist nicht NULL. Eintrag 1 = (%s)',1],
26016 => ['PDF417: Interner Fehler: Nichtregistrierter Statusübertragungsmodus beim Dekodieren.',0],


/*
** jpgraph_contour
*/

28001 => ['Dritten parameter fur Contour muss ein vector der fargen sind.',0],
28002 => ['Die anzahlen der farges jeder isobar linien muss gleich sein.',0],
28003 => ['ContourPlot Interner Fehler: isobarHCrossing: Spalten index ist zu hoch (%d)',1],
28004 => ['ContourPlot Interner Fehler: isobarHCrossing: Reihe index ist zu hoch (%d)',1],
28005 => ['ContourPlot Interner Fehler: isobarVCrossing: Reihe index ist zu hoch (%d)',1],
28006 => ['ContourPlot Interner Fehler: isobarVCrossing: Spalten index ist zu hoch (%d)',1],
28007 => ['ContourPlot. Interpolation faktor ist zu hoch (>5)',0],


/*
 * jpgraph_matrix and colormap
*/
29201 => ['Min range value must be less or equal to max range value for colormaps',0],
29202 => ['The distance between min and max value is too small for numerical precision',0],
29203 => ['Number of color quantification level must be at least %d',1],
29204 => ['Number of colors (%d) is invalid for this colormap. It must be a number that can be written as: %d + k*%d',3],
29205 => ['Colormap specification out of range. Must be an integer in range [0,%d]',1],
29206 => ['Invalid object added to MatrixGraph',0],
29207 => ['Empty input data specified for MatrixPlot',0],
29208 => ['Unknown side specifiction for matrix labels "%s"',1],
29209 => ['CSIM Target matrix must be the same size as the data matrix (csim=%d x %d, data=%d x %d)',4],
29210 => ['CSIM Target for matrix labels does not match the number of labels (csim=%d, labels=%d)',2],


/*
* jpgraph_theme
*/
30001 => ["Theme::%s() is not defined. \nPlease make %s(\$graph) function in your theme classs.",2],


];
