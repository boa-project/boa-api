<?php
// This file is part of BoA - https://github.com/boa-project
//
// BoA is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// BoA is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with BoA.  If not, see <http://www.gnu.org/licenses/>.
//
// The latest code can be found at <https://github.com/boa-project/>.

/**
 * Class to manage the Solr search engine
 *
 * @author Jesus Otero <jesusoterove@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */

Restos::using('third_party.parensparser.parensparser');
Restos::using('resources.resources.engines.solr.solr_client');

class Solr_querybuilder {
    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    private $_properties;
    /**
     *
     * Current query
     * @var string
     */
    private $_query;

    /**
     *
     *
     * @param object $properties
     */
    public function __construct ($properties) {
        $this->_properties = $properties;
        $this->_query = array(
            'q' => "*:*",
            'fq' => array(),
            'sort' => null,
            'start' => null,
            'rows' => null,
            'fl' => '',
            'wt' => 'json'
        );
        $this->setMode();

        if (!class_exists('SolrUtils')) {
            Restos::throwException(null, RestosLang::get('searchengine.solr.notphpextension', 'boa'), 500);
        }
    }
    /*
      . id
  . title
  . description
  . url
  . preview url (icono de previsualización).
  . object url play (should be able to handle themes)
*/
    /**
     *
     *
     * @param object $filters
     */
    public function setFilters($filters){
        if (!$filters) return;

        $this->_query["fq"] = array();
        if ($filters->specification){
            $this->_query["fq"][] = "manifest.type:".$filters->specification;
        }

        if ($filters->extensions && count($filters->extensions) > 0){
            $this->_query["fq"][] = "file.extension:(" . implode(' OR ', $filters->extensions) . ")";
        }
        if ($filters->user){
            $this->_query["fq"][] = "manifest.author:" . $filters->user;
        }
        if ($filters->connection){
            $this->_query["fq"][] = "manifest.conexion_type:" . $filters->connection;
        }
        if ($filters->metas && is_array($filters->metas) && count($filters->metas) > 0) {
            $metas = array();
            foreach ($filters->metas as $meta => $value) {
                if (is_array($value)) {
                    $optional = array();
                    foreach ($value as $text) {
                        if (is_string($text)) {
                            $optional[] = $meta . ':' . $text;
                        }
                    }
                    if (count($optional) > 0) {
                        $metas[] = '(' . implode(' OR ', $optional) . ')';
                    }
                }
                else {
                    $metas[] = $meta . ':' . $value;
                }
            }
            $this->_query["fq"][] = implode(' AND ', $metas);
        }
        if ($filters->catalog){
            if (is_array($filters->catalog)) {
                $catalogues = array();
                foreach($filters->catalog as $catalog) {
                    $catalogues[] = "catalog_id:" . $catalog;
                }

                $this->_query["fq"][] = implode(' OR ', $catalogues);
            }
            else {
                $this->_query["fq"][] = "catalog_id:" . $filters->catalog;
            }
        }
        //$this->_query["wt"] = "json";
        //var_dump($filters);
    }
    /**
     *
     *
     * @param int $start First record to retrieve
     * @param int $take Number of records to retrieve
     */
    public function setMode($mode = null){
        if (isset($mode) && strtolower($mode) == 'full'){
            $this->_query['fl'] = '';
            return;
        }
        $this->_query['fl'] = empty($this->_properties->BasicFields) ? 'id,catalog_id' : $this->_properties->BasicFields;
    }

    /**
     *
     *
     * @param int $start First record to retrieve
     * @param int $take Number of records to retrieve
     */
    public function setPagination($start, $take){
        $this->_query['start'] = $start;
        $this->_query['rows'] = $take;
    }

    /**
     *
     *
     * @param string $sort Sort full sentence
     */
    public function setSort($sort){
        $this->_query['sort'] = $sort;
    }

    /**
     *
     *
     * @param
     */
    public function buildAndExecute($query){
        $this->_query['q'] = $query;

        $fields = $this->_query['fl'];
        unset($this->_query['fl']); //Do not set the fields options on the api query
        $queryString = $this->getQueryString();

        $client = new Solr_client($this->_properties->URI);
        $client->setOutputFields($fields);
        $docs = $client->getDocumentsByQuery($queryString, false); //Do not transform response

        if ($docs === false){

            // To fix query errors on search.
            $this->_query['q'] = SolrUtils::escapeQueryChars($query);
            $queryString = $this->getQueryString();
            $docs = $client->getDocumentsByQuery($queryString, false); //Do not transform response

            if ($docs === false){
                Restos::throwException(null, $client->errorMessage(), $client->errorNumber());
            }
        }
        return $docs;
    }

    /**
     *
     *
     * @param
     */
    private function getQueryString(){
        return implode('&', array_filter(array_map(array($this, 'parseQueryItem'), array_keys($this->_query))));
    }

    /**
     *
     *
     * @param
     */
    private function parseQueryItem($key){
        if (!$this->_query[$key]) return false;

        if (is_array($this->_query[$key])){
            $return = array();
            foreach ($this->_query[$key] as $l) {
                $return[] = $key . "=" . urlencode($l);
            }
            return implode('&', $return);
            //return $key . "=" . urlencode(implode(' ', $this->_query[$key]));
        }
        return $key . "=" . urlencode($this->_query[$key]);
    }
}
