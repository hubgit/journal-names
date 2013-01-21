<?php

$output = gzopen('data/nlm-catalog.csv.gz', 'w');
 
$params = array(
	'db' => 'nlmcatalog',
	'term' => 'ncbijournals[filter]',
	'usehistory' => 'y',
	'retmax' => 0,
	'retmode' => 'xml',
);
 
$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?' . http_build_query($params);
print "$url\n";
 
$dom = new DOMDocument;
$dom->load($url);
$xpath = new DOMXpath($dom);

$history = array(
	'count' => get($xpath, 'Count', null),
	'webenv' => get($xpath, 'WebEnv', null),
	'querykey' => get($xpath, 'QueryKey', null),
);

print_r($history);

$limit = 100;

for ($i = 0; $i <= $history['count']; $i += $limit) {
	fetch($output, $history, $i, $limit);
}

function fetch($output, $history, $start = 0, $limit = 10) {
	$params = array(
		'db' => 'nlmcatalog',
		'query_key' => $history['querykey'],
		'webenv' => $history['webenv'],
		'retstart' => $start,
		'retmax' => $limit,
		'retmode' => 'xml',
	);
	 
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?' . http_build_query($params);
	print "$url\n";
	 
	$dom = new DOMDocument;
	$dom->load($url);

	$xpath = new DOMXpath($dom);
	 
	foreach ($xpath->query('NLMCatalogRecord') as $journal) {
		$data = array(
			'issn' => get($xpath, 'ISSN[@IssnType="Print"]', $journal),
			'eissn' => get($xpath, 'ISSN[@IssnType="Electronic"]', $journal),
			'issnl' => get($xpath, 'ISSNLinking', $journal),
			'ncbi' => get($xpath, 'TitleMain/Title', $journal),
			'ncbi-other' => get($xpath, 'TitleOther[@Owner="NCBI"][@TitleType="OtherTA"]/TitleAlternate', $journal),
			'nlm' => get($xpath, 'TitleOther[@Owner="NLM"][@TitleType="Other"]/TitleAlternate', $journal),
			'medline' => get($xpath, 'MedlineTA', $journal),
		);

		fputcsv($output, $data);
	}
}

function get($xpath, $query, $node) {
	$nodes = $xpath->query($query, $node);

	return $nodes->length ? $nodes->item(0)->textContent : null;
}
