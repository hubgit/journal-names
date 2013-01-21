<?php

ini_set('memory_limit', '2G');

$data = array();

/* Portico */

print "Portico\n";

$input = fopen('data/portico.csv', 'r');
fgetcsv($input);

while (($row = fgetcsv($input)) !== false) {
	list($name, $pissn, $eissn) = $row;

	$issns = array_unique(array_filter(array($pissn, $eissn)));

	if ($name) {
		foreach ($issns as $issn) {
			$issn = str_replace('-', '', $issn);
			$data[$issn]['portico'] = $name;
		}
	}
}

fclose($input);

/* NLM Catalog */

print "NLM Catalog\n";

$input = fopen('data/nlm-catalog.csv', 'r');
fgetcsv($input);

while (($row = fgetcsv($input)) !== false) {
	list($pissn, $eissn, $lissn, $nlm, $ncbi, $nlmOther, $medline) = $row;

	$issns = array_unique(array_filter(array($pissn, $eissn, $lissn)));

	$names = array(
		'nlm' => $nlm,
		'ncbi' => $ncbi,
		'nlm-other' => $nlmOther,
		'medline' => $medline,
	);

	foreach ($issns as $issn) {
		$issn = str_replace('-', '', $issn);

		foreach ($names as $nameType => $name) {
			if ($name) {
				$data[$issn][$nameType] = $name;
			}
		}
	}
}

fclose($input);

/* CUFTS */

print "CUFTS\n";

$input = fopen('data/cufts.tsv', 'r');
fgetcsv($input, null, "\t");

while (($row = fgetcsv($input, null, "\t")) !== false) {
	list($file, $title, $issn, $eissn) = $row;

	if ($issn) {
		$data[$issn][$file] = $title;
	}

	if ($eissn) {
		$data[$eissn][$file] = $title;
	}
}

fclose($input);

/* Collapse */

print "Collapse\n";

krsort($data);

$output = fopen('data/collapsed.csv', 'w');
foreach ($data as $issn => $names) {
	$matched = preg_match('/^(\d{4})(\d{3}(?:\d|X))$/', $issn, $matches);

	if (!$matched) {
		continue;
	}

	$issn = $matches[1] . '-' . $matches[2];

	$names = array_unique($names); // TODO: save keys, too?
	array_unshift($names, $issn);
	fputcsv($output, $names);
}