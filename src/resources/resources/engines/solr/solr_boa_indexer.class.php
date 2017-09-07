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
     * UTC Timezone
     * @var string
     */
    private $_UTCTZ;


    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    private $_properties;

    /**
     *
     *
     * @param object $properties
     */
    public function __construct ($properties) {
        if(!is_object($properties)) {
            throw new Exception('Properties object is required.');
        }

        $this->_properties = $properties;
        $this->_UTCTZ = new DateTimeZone("UTC");
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
        $ch = $this->initSolrCurlRequest();
        $docUpdates = array("p" => array(), "f" => array());
        $CHUNK_SIZE = $this->_properties->CHUNK_SIZE;

        if ($this->_properties->Rebuild == "All"){
            $this->performSolrDeleteRequest($ch, true);
        }
        
        if (preg_match('/^(All|Schema)$/', $this->_properties->Rebuild) && !$this->refreshSchema($ch)) { //Ensure the schema has at least a few required fields
            echo 'Unable to index catalog. Failed to refresh solr schema!';
            return;
        } 

        foreach(array_chunk($entries, $CHUNK_SIZE) as $chunk){
            //get doc ids for this chunk
            $ids = array_map(array($this, "getIdFromManifestPath"), $chunk);
            //get index info for this chunck from lucen
            $solr_info = $this->getDocsInfoFromSolr($ch, $ids);
            $chunk_size = count($chunk);
            foreach ($chunk as $idx => $entry) {
                $this->visitRootObject($ch, $entry, isset($solr_info[$ids[$idx]])?$solr_info[$ids[$idx]]:NULL, $docUpdates);

                //If we reached the chunck size then attempt to update the index
                $count = count($docUpdates["p"]);
                if ($count >= $CHUNK_SIZE || ($count > 0 && $idx == $chunk_size - 1)){
                    $payload = "[\n" . implode(",\n", $docUpdates["p"]) . "\n]";
                    $this->performSolrUpdateRequest($payload, $ch, true);
                    $docUpdates["p"] = array(); //Start a new request batch
                }

                $count = count($docUpdates["f"]);
                if ($count >= $CHUNK_SIZE || ($count > 0 && $idx == $chunk_size - 1)){
                    $payload = "[\n" . implode(",\n", $docUpdates["f"]) . "\n]";
                    $this->performSolrUpdateRequest($payload, $ch, false);
                    $docUpdates["f"] = array(); //Start a new request batch
                }
            }
        }

        //Delete all objects that were not visited, it means they were deleted
        $this->performSolrDeleteRequest($ch);
        curl_close($ch);
    }

    private function getIdFromManifestPath($manifestPath){
        $dir = dirname($manifestPath);
        return basename($dir);
    }

    private function visitRootObject($ch, $manifestPath, $solr_info, &$docUpdates){
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
        //echo $id . PHP_EOL;
        $metadata = "{}";
        $visited = [];
        if (file_exists($dir."/.metadata")) {
            $hasChanged |= $this->fileHasChanged($dir."/.metadata", $last_update);
            if ($hasChanged){
                $metadata = file_get_contents($dir."/.metadata");        
            }; //Do not process unchanged files unless it is first time
        }

        if ($hasChanged){
            $docUpdates["f"][] = "{\"id\":\"$id\",\"catalog_id\":\"{$this->_catalog_id}\",\"execution_id\":\"$run_id\",\"manifest\":$manifest,\"metadata\":$metadata}";
        }
        else {
            $docUpdates["p"][] = "{\"id\":\"$id\",\"execution_id\":{\"set\":\"$run_id\"}}";
        }
        $this->visitObjectContent($id, $dir, $ch, $docUpdates);
    }

    private function visitObjectContent($id, $dir, $ch, &$docUpdates){
        $children = glob_recursive($dir."/content/{.}*.metadata", GLOB_NOSORT|GLOB_BRACE);
        $children_info = $this->getDocChildrenInfoFromSolr($ch, $id);
        foreach($children as $idx => $child){
            $path = str_replace(dirname($dir)."/", "", $child);
            $info = isset($children_info[$path])?$children_info[$path]:null;
            $this->visitChildObject($path, $child, $docUpdates, $info?$info["last_update"]:null);
        }
    }

    private function visitChildObject($path, $metadataPath, &$docUpdates, $last_update){
        $run_id = $this->_execution_id;
        $hasChanged = $this->fileHasChanged($metadataPath, $last_update);
        if (!$hasChanged){
            $docUpdates["p"][] = "{\"id\":\"$path\",\"execution_id\":{\"set\":\"$run_id\"}}";
            return;
        }
        $metadata = file_get_contents($metadataPath);
        $manifestJ = new \stdClass();
        $id = $path;
        $manifest = json_encode($manifestJ);
        //echo $id . PHP_EOL;
        $docUpdates["f"][] = "{\"id\":\"$id\",\"catalog_id\":\"{$this->_catalog_id}\",\"execution_id\":\"$run_id\",\"manifest\":$manifest,\"metadata\":$metadata}";
    }

    private function fileHasChanged($path, $last_update){
        return $last_update == null || ($last_update->getTimestamp() < filemtime($path));
    }

    private function initSolrCurlRequest(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // -H
        return $ch;
    }

    private function performSolrUpdateRequest($payload, $ch, $partial = false){
        curl_setopt($ch, CURLOPT_URL, $this->_properties->URI . "/update".($partial?"":"/json/docs"));
        curl_setopt($ch, CURLOPT_POST, TRUE); // -b
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            print curl_error($ch) . PHP_EOL;
        }
    }

    private function performSolrDeleteRequest($ch, $all = false){
        $run_id = $this->_execution_id;
        $query = $all ? "*:*" : "catalog_id:{$this->_catalog_id} AND -execution_id:$run_id";
        $payload = "{\"delete\":{\"query\":\"$query\"},\"commit\":{}}";
        curl_setopt($ch, CURLOPT_URL, $this->_properties->URI . "/update");
        curl_setopt($ch, CURLOPT_POST, TRUE); // -b
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            print curl_error($ch) . PHP_EOL;
        }
    }

    private function refreshSchema($ch){
        curl_setopt($ch, CURLOPT_URL, $this->_properties->URI . "/schema/fields");
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE); // -b
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $fields = json_decode($result)->fields;
        curl_setopt($ch, CURLOPT_URL, $this->_properties->URI . "/schema/copyfields");
        $result = curl_exec($ch);
        $copyFields = json_decode($result)->copyFields;
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
            if (preg_match("/^(catalog_id|execution_id|updated_at)$|^(metadata|manifest)\.\S+$/", $field->name)) {
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


        curl_setopt($ch, CURLOPT_URL, $this->_properties->URI . "/schema");
        curl_setopt($ch, CURLOPT_POST, TRUE); // -b
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary
        $uriparts = explode('/', $this->_properties->URI);
        $solrcore = array_pop($uriparts);
        $adminUri = implode('/', $uriparts) . "/admin/cores?action=RELOAD&core=$solrcore";
        
        foreach ($schemaCommands as $command => $data) {
            $jsonData = json_encode($data);
            $jsonPayload = "{\"$command\":$jsonData";        
            if (!$this->performSchemaUpdate($ch, $jsonPayload, $adminUri)){
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

    private function performSchemaUpdate($ch, $payload, $adminUri){
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            print curl_error($ch) . PHP_EOL;
            return false;
        }
        
        $result = json_decode($result);
        if ($result && isset($result->errors)){
            var_dump($result->errors);
            return false;
        }

        //issue a core reload.
        curl_setopt($ch, CURLOPT_URL, $adminUri);
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            print curl_error($ch) . PHP_EOL;
            return false;
        }
        return true;
    }
    /*
     * This method will return solr last_update information for all the ids in the $ids parameter
     */

    private function getDocsInfoFromSolr($ch, $ids){
        $url = $this->_properties->URI . "/get?fl=id,updated_at&ids=" . implode(",", $ids);
        curl_setopt($ch, CURLOPT_URL, $url);
        return $this->performSolrGetRequest($ch);
    }

    private function getDocChildrenInfoFromSolr($ch, $parent_id){
        $url = $this->_properties->URI . "/select?fl=id,updated_at&q=id:$parent_id/*&wt=json";
        curl_setopt($ch, CURLOPT_URL, $url);
        return $this->performSolrGetRequest($ch);
    }

    private function performSolrGetRequest($ch){
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE); // -b
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $info = json_decode($result);

        if ($info && is_object($info) && property_exists($info, 'response')) {
            $info = array_reduce($info->response->docs, array($this, 'parseDocBaseInfo'), array());
        }

        return $info;
    }

    private function parseDocBaseInfo($output, $item){ 
        $output[$item->id] = array(
            "last_update" => DateTime::createFromFormat('Y-m-d\TH:i:s.u+', $item->updated_at, $this->_UTCTZ)
            );
        return $output; 
    }
}
