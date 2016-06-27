<?php

namespace ixTheo\Search\Factory;

use VuFind\Search\Factory\SolrDefaultBackendFactory;
use ixTheo\Search\Backend\Solr\IxTheoQueryBuilder;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\HandlerMap;
use VuFindSearch\Backend\Solr\LuceneSyntaxHelper;

class IxTheoSolrDefaultBackendFactory extends SolrDefaultBackendFactory
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create the query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        $specs   = $this->loadSpecs();
        $config = $this->config->get('config');
        $defaultDismax = isset($config->Index->default_dismax_handler)
                       ? $config->Index->default_dismax_handler : 'dismax';
        $builder = new IxTheoQueryBuilder($specs, $defaultDismax);

        // Configure builder:
        $search = $this->config->get($this->searchConfig);
        $caseSensitiveBooleans
            = isset($search->General->case_sensitive_bools)
            ? $search->General->case_sensitive_bools : true;
        $caseSensitiveRanges
            = isset($search->General->case_sensitive_ranges)
            ? $search->General->case_sensitive_ranges : true;
        $helper = new LuceneSyntaxHelper(
            $caseSensitiveBooleans, $caseSensitiveRanges
        );
        $builder->setLuceneHelper($helper);
        return $builder;
    }

    /**
     * Create the SOLR connector 
     * Set the language code
     *
     * @return Connector
     */

 protected function createConnector()
    {
        $config = $this->config->get('config');
        
        $current_lang = $this->serviceLocator->get('Vufind\Translator')->getLocale();
        
        $handlers = [
            'select' => [
                'fallback' => true,
                'defaults' => ['fl' => '*,score', 'lang' => $current_lang],
                'appends'  => ['fq' => []],
            ],
            'term' => [
                'functions' => ['terms'],
            ],
        ];

        foreach ($this->getHiddenFilters() as $filter) {
            array_push($handlers['select']['appends']['fq'], $filter);
        }

        $connector = new Connector(
            $this->getSolrUrl(), new HandlerMap($handlers), $this->uniqueKey
        );
        $connector->setTimeout(
            isset($config->Index->timeout) ? $config->Index->timeout : 30
        );

        if ($this->logger) {
            $connector->setLogger($this->logger);
        }
        if ($this->serviceLocator->has('VuFind\Http')) {
            $connector->setProxy($this->serviceLocator->get('VuFind\Http'));
        }
        return $connector;
    }



}
