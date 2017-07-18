<?php

namespace ixTheo\Autocomplete;

class Solr extends \VuFind\Autocomplete\Solr
{
    /**
     * Try to turn an array of record drivers into an array of suggestions.
     *
     * @param array  $searchResults An array of record drivers
     * @param string $query         User search query
     * @param bool   $exact         Ignore non-exact matches?
     *
     * @return array
     */
    protected function getSuggestionsFromSearch($searchResults, $query, $exact)
    {
        $results = [];
        foreach ($searchResults as $object) {
            $current = $object->getRawData();
            foreach ($this->displayField as $field) {
                if (isset($current[$field])) {
                    $bestMatch = $this->pickBestMatch(
                        $current[$field], $query, $exact
                    );
                    if ($bestMatch) {
                        $results[] = $this->mungeQuery($bestMatch);
                        break;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Process the user query to make it suitable for a Solr query.
     *
     * @param string $query Incoming user query
     *
     * @return string       Processed query
     */
    protected function mungeQuery($query)
    {
        // Modify the query so it makes a nice, truncated autocomplete query:
        $forbidden = [':', '(', ')', '*', '+', '"', '–' /* a hyphen, not a minus sign */];
        $query = str_replace($forbidden, " ", $query);
        if (substr($query, -1) != " ") {
            $query .= "*";
        }
        return $query;
    }
}
