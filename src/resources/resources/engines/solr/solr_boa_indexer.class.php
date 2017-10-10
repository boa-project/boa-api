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

Restos::using('classes.curl');
Restos::using('resources.resources.engines.solr.solr_client');

if (!function_exists('glob_recursive')){
    // Does not support flag GLOB_BRACE
    function glob_recursive($pattern, $flags = 0){
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir){
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}


class Solr_boa_indexer {
    /**
     *
     * Unique id for an indexation task process
     * @var string
     */
    private $_execution_id;
    /**
     *
     * Unique id for an indexation task process
     * @var string
     */
    private $_catalog_id;
    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    private $_properties;
    /**
     *
     * Cron log
     * @var object
     */
    private $_cron;
    /**
     *
     *
     * @param object $properties
     */

    /**
     *
     * Solr_client Client
     * @var Solr_client
     */
    private $_client;

    public function __construct ($properties, $cron) {
        if(!is_object($properties)) {
            throw new Exception('Properties object is required.');
        }

        $this->_properties = $properties;
        $this->_cron = $cron;
        $this->_client = new Solr_client($properties->URI);
    }

    /**
     * 
     * Visit all indexable objects on the $path catalog
     * @param $catalog Object that has the root path for the BoA catalog to index and the catalog alias
     */
    public function indexCatalog($catalog) {
        if (strpos($catalog->path, "APP_") !== false) return;
        $path = $catalog->path;
        $this->_catalog_id = $catalog->alias;

        $entries = glob($path."/*/{.}manifest", GLOB_NOSORT|GLOB_BRACE);

        $this->_execution_id = uniqid();
        $docUpdates = array("p" => array(), "f" => array());
        $CHUNK_SIZE = $this->_properties->CHUNK_SIZE;
        $client = $this->_client;

        if ($this->_properties->Rebuild == "All"){
            if (!$client->deleteDocs(array("catalog_id" => $this->_catalog_id, "-execution_id" => $this->_execution_id))){ //Remove all docs
                $this->addErrorLog("indexCatalog", "deleting all docs", $client->errorMessage());
                return false;
            } 
        }

        if (preg_match('/^(All|Schema)$/', $this->_properties->Rebuild) && !$this->refreshSchema()) {
            return false;
        } 

        foreach(array_chunk($entries, $CHUNK_SIZE) as $chunk){
            //get doc ids for this chunk
            $ids = array_map(array($this, "getIdFromManifestPath"), $chunk);
            //get index info for this chunck from lucen
            $solr_info = $client->getDocuments($ids);
            if ($solr_info === false){
                $this->addErrorLog("indexCatalog", "getting documents", $client->errorMessage());
                return false;
            }
            $chunk_size = count($chunk);
            foreach ($chunk as $idx => $entry) {
                $this->visitRootObject($entry, (isset($solr_info[$ids[$idx]]) ? $solr_info[$ids[$idx]] : NULL), $docUpdates);

                //If we reached the chunck size then attempt to update the index
                $count = count($docUpdates["p"]);
                if ($count >= $CHUNK_SIZE || ($count > 0 && $idx == $chunk_size - 1)){
                    $payload = "[\n" . implode(",\n", $docUpdates["p"]) . "\n]";
                    if (!$client->updateDocs($payload, true)){
                        $this->addErrorLog("indexCatalog", "partially updating docs", $client->errorMessage());
                    }
                    $docUpdates["p"] = array(); //Start a new request batch
                }

                $count = count($docUpdates["f"]);
                if ($count >= $CHUNK_SIZE || ($count > 0 && $idx == $chunk_size - 1)){
                    $payload = "[\n" . implode(",\n", $docUpdates["f"]) . "\n]";
                    if (!$client->updateDocs($payload, false)){
                        $this->addErrorLog("indexCatalog", "updating docs", $client->errorMessage());
                    }
                    $docUpdates["f"] = array(); //Start a new request batch
                }
            }
        }

        //Delete all objects that were not visited, it means they were deleted
        if (!$client->deleteDocs(array("catalog_id" => $this->_catalog_id, "-execution_id" => $this->_execution_id))){
            $this->addErrorLog("indexCatalog", "deleting not visited documents", $client->errorMessage());
        }

        if (!$client->commit()){
            $this->addErrorLog("indexCatalog", "doing commit", $client->errorMessage());
        }
    }

    private function getIdFromManifestPath($manifestPath){
        $dir = dirname($manifestPath);
        return basename($dir);
    }

    private function visitRootObject($manifestPath, $solr_info, &$docUpdates){
        $run_id = $this->_execution_id;
        $dir = dirname($manifestPath);
        $dirname = basename($dir);

        if (preg_match("/recycle_bin/", $dirname) === 1) return; //Do not index recycle bin folders:

        $last_update = $solr_info ? $solr_info["last_update"] : null;
        //DateTime::createFromFormat("y-m-d H:i:s", "70-01-01 00:00:00", new DateTimeZone("UTC"));
        $hasChanged = $this->fileHasChanged($manifestPath, $last_update);
        $manifest = file_get_contents($manifestPath);
        $json = json_decode($manifest);
        $id = isset($json->id) ? $json->id : $dirname;
        unset($json->id);
        $manifest = json_encode($json);

        $metadata = "{}";
        if (file_exists($dir."/.metadata")) {
            $hasChanged |= $this->fileHasChanged($dir."/.metadata", $last_update);
            if ($hasChanged){
                $metadata = file_get_contents($dir."/.metadata");        
            }; //Do not process unchanged files unless it is first time
        }

        if ($hasChanged){
            $doc = json_encode("{\"manifest\":$manifest,\"metadata\":$metadata}");
            //var_dump(json_encode("{\"manifest\":$manifest,\"metadata\":$metadata}"));
            $docUpdates["f"][] = "{\"id\":\"$id\",\"catalog_id\":\"{$this->_catalog_id}\",\"execution_id\":\"$run_id\",\"manifest\":$manifest,\"metadata\":$metadata,\"rawdoc\":$doc}";
        }
        else {
            $docUpdates["p"][] = "{\"id\":\"$id\",\"execution_id\":{\"set\":\"$run_id\"}}";
        }
        $this->visitObjectContent($id, $dir, $docUpdates);
    }

    private function visitObjectContent($id, $dir, &$docUpdates){
        $children = glob_recursive($dir."/content/{.}*.metadata", GLOB_NOSORT|GLOB_BRACE);
        $client = $this->_client;
        $children_info = $client->getDocumentChildren($id);
        if ($children_info === false){
            $this->addErrorLog("visitObjectContent", "getting children for $id", $client->errorMessage());
            return false;
        }
        
        foreach($children as $idx => $child){
            $path = str_replace(dirname($dir)."/", "", $child);
            $info = isset($children_info[$path]) ? $children_info[$path] : null;
            $this->visitChildObject($path, $child, $docUpdates, ($info ? $info["last_update"] : null));
        }
    }

    private function visitChildObject($path, $metadataPath, &$docUpdates, $last_update){
        $run_id = $this->_execution_id;
        $hasChanged = $this->fileHasChanged($metadataPath, $last_update);
        $id = dirname($path) . "/" . substr(str_replace('.metadata', '', basename($path)), 1);
        if (!$hasChanged){
            $docUpdates["p"][] = "{\"id\":\"$id\",\"execution_id\":{\"set\":\"$run_id\"}}";
            return;
        }
        $metadata = file_get_contents($metadataPath);
        $manifestJ = new \stdClass();
        $manifest = json_encode($manifestJ);
        $doc = json_encode("{\"manifest\":$manifest,\"metadata\":$metadata}");

        $docUpdates["f"][] = "{\"id\":\"$id\",\"catalog_id\":\"{$this->_catalog_id}\",\"execution_id\":\"$run_id\",\"manifest\":$manifest,\"metadata\":$metadata,\"rawdoc\":$doc}";
    }

    private function fileHasChanged($path, $last_update){
        return $last_update == null || ($last_update->getTimestamp() < filemtime($path));
    }

    private function refreshSchema(){
        $client = $this->_client;

        $fields = $client->getFields();
        if ($fields === false){
            $this->addErrorLog("refreshSchema", "getting fields", $client->errorMessage());
            return false;
        }

        $copyFields = $client->getCopyFields();
        if ($copyFields === false){
            $this->addErrorLog("refreshSchema", "getting copy fields", $client->errorMessage());
            return false;
        }

        $schemaCommands = array("delete-copy-field" => array(), "delete-field" => array(), "add-field" => array(), "add-copy-field" => array());

        //Remove copy fields
        foreach($copyFields as $field){
            if (preg_match("/^(metadata|manifest)\./", $field->source)) {
                $schemaCommands["delete-copy-field"][] = array("source" => $field->source, "dest" => $field->dest);
            }
            if ($field->source == "*" && $field->dest == "_text_"){
                $schemaCommands["delete-copy-field"][] = array("source" => $field->source, "dest" => $field->dest);
            }
        }
        //Remove schema fields if they already exists
        foreach($fields as $field){
            if (preg_match("/^(catalog_id|execution_id|updated_at|rawdoc)$|^(metadata|manifest)\.\S+$/", $field->name)) {
                $schemaCommands["delete-field"][] = array("name" => $field->name);
            }
        }

        $commands = @simplexml_load_file(realpath(dirname(__FILE__)) . "/default_schema_commands.xml");

        foreach ($commands->add_field as $add_field) {
            $schemaCommands["add-field"][] = $this->simpleXMLElementToArray($add_field);
        }
        
        foreach ($commands->add_copy_field as $add_copy_field) {
            $schemaCommands["add-copy-field"][] = $this->simpleXMLElementToArray($add_copy_field);
        }

        foreach ($schemaCommands as $command => $data) {
            if (!$client->runSchemaCommand($command, json_encode($data))){
                $this->addErrorLog("refreshSchema", "running command $command", $client->errorMessage());
                return false;
            }
        }

        return true;
    }

    private function simpleXMLElementToArray($simpleXml){
        foreach (get_object_vars($simpleXml) as $key => $value) {
            return $value;
        }
    }

    /*
     * This method will return solr last_update information for all the ids in the $ids parameter
     */
    private function addErrorLog($method, $context, $error){
        $this->_cron->addLog("ERROR: On method '$method', while $context. $error");
    }
}
