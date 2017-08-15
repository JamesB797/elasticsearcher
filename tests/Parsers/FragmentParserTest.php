<?php

use ElasticSearcher\Parsers\FragmentParser;
use ElasticSearcher\Fragments\Queries\TermQuery;
use ElasticSearcher\Fragments\Analyzers\StandardAnalyzer;
use ElasticSearcher\Dummy\Fragments\Filters\IDFilter;

class FragmentParserTest extends ElasticSearcherTestCase
{
    public function testParsingRootLevel()
    {
        $parser = new FragmentParser();

        $body = [
            'query' => new TermQuery('name', 'John'),
        ];
        $expectedBody = [
            'query' => [
                'term' => [
                    'name' => 'John',
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }

    public function testParsingChildLevel()
    {
        $parser = new FragmentParser();

        $body = [
            'query' => [
                'bool' => [
                    'and' => [
                        new TermQuery('name', 'John'),
                        new TermQuery('category', 'authors'),
                    ]
                ]
            ]
        ];
        $expectedBody = [
            'query' => [
                'bool' => [
                    'and' => [
                        [
                            'term' => [
                                'name' => 'John'
                            ]
                        ],
                        [
                            'term' => [
                                'category' => 'authors'
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }

    public function testParsingNestedFragments()
    {
        $parser = new FragmentParser();

        $body = [
            'query' => [
                'bool' => [
                    'and' => [
                        new TermQuery('name', new TermQuery('category', 'authors')),
                    ]
                ]
            ]
        ];
        $expectedBody = [
            'query' => [
                'bool' => [
                    'and' => [
                        [
                            'term' => [
                                'name' => [
                                    'term' => [
                                        'category' => 'authors'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }

    public function testParsingAndMergingWithParent()
    {
        $parser = new FragmentParser();

        $body = [
            'settings' => [
                new StandardAnalyzer('myAnalyzer')
            ]
        ];
        $expectedBody = [
            'settings' => [
                'myAnalyzer' => [
                    'type' => 'standard'
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }

    public function testParsingAndMergingMultipleFragmentsWithParent()
    {
        $parser = new FragmentParser();

        $body = [
            'settings' => [
                new StandardAnalyzer('myAnalyzer1'),
                new StandardAnalyzer('myAnalyzer2'),
                new StandardAnalyzer('myAnalyzer3'),
            ]
        ];
        $expectedBody = [
            'settings' => [
                'myAnalyzer1' => [
                    'type' => 'standard'
                ],
                'myAnalyzer2' => [
                    'type' => 'standard'
                ],
                'myAnalyzer3' => [
                    'type' => 'standard'
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }

    public function testParsingCustomFragments()
    {
        $parser = new FragmentParser();

        $body = [
            'query' => [
                new IDFilter(123),
            ]
        ];
        $expectedBody = [
            'query' => [
                [
                    'term' => [
                        'id' => 123
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedBody, $parser->parse($body));
    }
}
