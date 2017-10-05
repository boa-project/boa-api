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
 * Class to manage the Solr api requests
 *
 * @author Jesus Otero <jesusoterove@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */

class Solr_client {
    const ERRCODE_UNABLE_TO_PARSE_RESPONSE = 1000;
    const ERRCODE_API_RESPONSE_ERROR = 1001;
    /**
     *
     * Solr service URI
     * @var string
     */
    private $_rootURI;
    /**
     *
     * Solr service URI including core
     * @var string
     */
    private $_coreURI;
    /**
     *
     * Solr core this client is bound to
     * @var string
     */
    private $_core;
    /**
     *
     * Curl Object
     * @var object
     */
    private $_ch;

    /**
     *
     * UTC Timezone
     * @var string
     */
    private $_UTCTZ;

    /**
     *
     * Error number after request
     * @var _errno
     */
    private $_errno;

    /**
     *
     * Error message after request
     * @var _error
     */
    private $_error;

    /**
     *
     * Parsed response
     * @var _result
     */
    private $_result;

    public function __construct ($baseURI){
        //Initialize required private variables
        $uriparts = explode('/', $baseURI);
        $this->_core = array_pop($uriparts);
        $this->_rootURI = implode('/', $uriparts);
        $this->_coreURI = $baseURI;
        $this->_UTCTZ = new DateTimeZone("UTC");
        $this->_errno = 0;

        //Initialize Curl Object
        $ch = new Curl();
        $ch->setHeader(['Content-Type: application/json']);
        $ch->setopt(array("BINARYTRANSFER" => TRUE));
        $this->_ch = $ch;
    }

    /**
     * 
     * Issue a Solr delete documents request
     * @param $restrictions Restrictions that will restrict the delete query
     * @param $operator Optional operator to join the restrictions, default to AND
     */
    public function deleteDocs($restrictions, $operator = 'AND'){
        if (is_array($restrictions)){
            $mapper = function ($key, $val){ return "{$key}:{$val}";};
            $query = implode (" $operator ", array_map($mapper, array_keys($restrictions), $restrictions));
        }
        else {
            $query = $restrictions;
        }
        $payload = "{\"delete\":{\"query\":\"$query\"},\"commit\":{}}";
        $url = $this->_coreURI . "/update";
        $response = $this->_ch->post($url, $payload);

        $this->handleResponse($response);
        
        if ($this->hasError()){
            return false;
        }
        return true;
    }

    /**
     * 
     * Issue a Solr update documents request
     * @param $payload JSon string with the documents to create/update
     * @param $partial Where this is a partial or full update. Defaults to false, if updating an existing document this and not passing all fields, this should be set to true
     */
    public function updateDocs($payload, $partial = false){
        $url = $this->_coreURI . "/update".($partial ? "" : "/json/docs");
        $this->_ch->cleanopt();
        $response = $this->_ch->post($url, $payload);
        var_dump($response);

        $this->handleResponse($response);

        if ($this->hasError()){
            return false;
        }
        return true;
    }

    /**
     * 
     * Issue a Solr get documents request
     * @param $ids The id or ids of documents to retrieve
     */
    public function getDocuments($ids, $transform = true){
        if (is_array($ids)) $ids = implode(',', $ids);        
        
        $url = $this->_coreURI . "/get?fl=id,updated_at&ids=" . $ids;
        return $this->getDocs($url, $transform);
    }

    /**
     * 
     * Issue a Solr get documents request
     * @param $ids The id or ids of documents to retrieve
     */
    public function getDocumentsByQuery($query, $transform = true){
        $url = $this->_coreURI . "/select?$query";
        return $this->getDocs($url, $transform);
    }

    /**
     * 
     * Issue a Solr get documents request based on a parent_id
     * @param $parend_id Id of the document root for which you are requesting children
     */
    public function getDocumentChildren($parent_id){
        return getDocumentsByQuery("fl=id,updated_at&q=id:$parent_id/*&wt=json", true);
    }

    /**
     * 
     * Issue a Solr get schema fields request
     */
    public function getFields(){
        $url = $this->_coreURI . "/schema/fields";
        $response = $this->_ch->get($url);

        $this->handleResponse($response);
        if ($this->hasError()){
            return false;
        }
        return $this->_result->fields;
    }

    /**
     * 
     * Issue a Solr get schema copy fields request
     */
    public function getCopyFields(){
        $url = $this->_coreURI . "/schema/copyfields";
        $response = $this->_ch->get($url);

        $this->handleResponse($response);
        if ($this->hasError()){
            return false;
        }
        return $this->_result->copyFields;
    }

    /**
     * 
     * Issue a Solr schema update request
     * @param $command Any of add-field, delete-field, add-copy-field, delete-copy-field
     * @param $operator Optional operator to join the restrictions, default to AND
     */
    public function runSchemaCommand($command, $data){
        $url = $this->_coreURI . "/schema";
        $payload = "{\"$command\":$data }";
        $response = $this->_ch->post($url, $payload);
        $this->handleResponse($response);
        
        if ($this->hasError()){
            return false;
        }

        // Issue a core reload.
        $core = $this->_core;
        $url = $this->_rootURI . "/admin/cores?action=RELOAD&core=$core";
        $response = $this->_ch->get($url);
        $this->handleResponse($response);
        
        if ($this->hasError()){
            return false;
        }
        return true;
    }

    /**
     * 
     * Returns the error message of the last request if any
     */
    public function errorMessage(){
        return $this->_error;
    }
    /**
     * 
     * Issue a Solr get documents request
     * @param $url Get documents url including query and other restriction parameters
     */
    private function getDocs($url, $parse = true){
        $response = $this->_ch->get($url);

        $this->handleResponse($response);
        if ($this->hasError()){
            return false;
        }

        $info = $this->_result;
        
        if (is_object($info) && property_exists($info, 'response')) {
            return $parse ? 
                array_reduce($info->response->docs, array($this, 'parseDocBaseInfo'), array()) :
                $info->response->docs;
        }
        return $info;
    }

    /**
     * 
     * Array reduce callback to assing last_update field on local time
     * @param $output Carry out variable
     * @param $item Current 'reduce' item
     */
    private function parseDocBaseInfo($output, $item){ 
        $output[$item->id] = array(
            "last_update" => DateTime::createFromFormat('Y-m-d\TH:i:s.u+', $item->updated_at, $this->_UTCTZ)
            );
        return $output; 
    }

    /**
     * 
     * Returns true if the las request results in an error
     */
    private function hasError(){ 
        return $this->_errno != 0; 
    }

    /**
     *
     * Process a solr request response to handle any error occurred during the call
     * @param $response Solr api raw response
     */
    private function handleResponse($response){
        $this->_result = NULL;
        $this->_errno = 0;
        $this->_error = "";

        if ($this->_ch->errno != 0){
            $this->_errno = $this->_ch->errno;
            $this->_error = $this->_ch->error;
            return false;
        }
        
        $result = json_decode($response);
        if ($result == NULL){
            $this->_errno = Solr_client::ERRCODE_UNABLE_TO_PARSE_RESPONSE;
            $this->_error = "Unable to parse response: '$response'";
            return false;
        }

        if (property_exists($result, 'errors')){
            $all = array();
            if (is_array($result->errors)) {
                foreach($result->errors as $error) {
                    $all[] = implode(',', $error->errorMessages);
                }
            }
            else {
                $all[] = "Unable to find error message from response: '$response'";
            }
            $this->_errno = Solr_client::ERRCODE_API_RESPONSE_ERROR;
            $this->_error = implode('\n', $all);
            return false;
        }
        
        if (property_exists($result, 'error')) {
            $this->_errno = Solr_client::ERRCODE_API_RESPONSE_ERROR;
            $this->_error = $result->error->msg;
            return false;
        }

        $this->_result = $result;
        return true;
    }
}



