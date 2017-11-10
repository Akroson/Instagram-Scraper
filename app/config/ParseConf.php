<?php 

namespace App\Config;

class ParseConf
{
	const PARAM =  ['https://hidemy.name' => ['URL' => 'https://hidemy.name/en/proxy-list/?type=s&anon=34#list',
                                    'method' => 'App\Components\ParserXml::parseHidemyName',
                                    'patternSearch' => '//*[@id="content-section"]/section[1]/div/table/tbody/tr',
                                    'morePage' => ['step' => 64, 
                                                   'requestPattern' => 'https://hidemy.name/en/proxy-list/?type=s&anon=34&start={{count}}#list']
			                                    ],
			        'https://free-proxy-list.net' => ['URL' => 'https://free-proxy-list.net/',
			                                        'method' => 'App\Components\ParserXml::parseFreeProxyList',
			                                        'patternSearch' => '//*[@id="proxylisttable"]/tbody/tr',
			                                        'morePage' => false
			                                        ],
			        'http://www.httptunnel.ge' => ['URL' => 'http://www.httptunnel.ge/ProxyListForFree.aspx',
			                                    'method' => 'App\Components\ParserXml::parseHttptunnel',
			                                    'patternSearch' => '//*[@id="ctl00_ContentPlaceHolder1_GridViewNEW"]',
			                                    'morePage' => false
			                                    ]
				];
}