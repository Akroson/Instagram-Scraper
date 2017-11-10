<?php 

namespace App\Components;

use App\Config\ConstName;

class ParserXml
{
    public static function parseFreeProxyList($html, $patterSearch)
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query('//*[@id="proxylisttable"]/tbody/tr');
        $resultParse = [];

        $i = 0;
        foreach ($nodes as $node) {
            $nodeValue = [];
            $cell = $node->firstChild;

            while ($cell) {
                $nodeValue[] = $cell->nodeValue;
                $cell = $cell->nextSibling;
            }

            if ($nodeValue[4] == 'transparent' || $nodeValue[6] != 'yes') continue;

            $resultParse[$i] = $nodeValue[0] . ':' . $nodeValue[1];
            $i++;
        }

        $resultParse[ConstName::REPEAT] = false;

        return $resultParse;
    }

    public static function parseHidemyName($html, $patterSearch)
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query($patterSearch);
        $resultParse = [];
        
        if (!$nodes['length']){
        	$resultParse[ConstName::REPEAT] = false;
        	return $resultParse;
        }

        foreach ($nodes as $node) {
            $nodeValue = [];
            $cell = $node->firstChild;

            while ($cell) {
                $nodeValue[] = $cell->nodeValue;
                $cell = $cell->nextSibling;
            }

            $resultParse[] = $nodeValue[0] . ':' . $nodeValue[1];
        }

        $resultParse[ConstName::REPEAT] = true;

        return $resultParse;
    }

    public static function parseHttptunnel($html, $patterSearch)
    {
		$doc = new \DOMDocument();
		$doc->loadHTML($html);
		$xpath = new \DOMXPath($doc);
		$nodes = $xpath->query($patterSearch);

		// $td = $doc->getElementsByTagName('td');
		$td = preg_split('/\s+/', $nodes->item(0)->nodeValue);
		$td = array_slice($td, 2);
		$td = array_chunk($td, 3);

		foreach ($td as $item) {
		   if (strpos($item[1], 'T') === false) $resultParse[] = $item[0];
		}

		array_pop($resultParse);

		$resultParse[ConstName::REPEAT] = false;

        return $resultParse;
    }
}