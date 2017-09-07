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

Restos::using('resources.resources.searchengine');
Restos::using('resources.resources.engines.solr.solr_boa_indexer');
Restos::using('resources.resources.engines.solr.solr_querybuilder');
/**
 * Class to manage the Solr search engine
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class SearchEngine_solr extends SearchEngine {

    public function queryExecute ($query = null, $number = null, $start_on = null, $groups = null) {
        $queryBuilder = new Solr_querybuilder($this->_parameters);
        $queryBuilder->setFilters($groups);
        $queryBuilder->setPagination($start_on, $number);
        return $queryBuilder->buildAndExecute($query);
    }

    public function cron (RestosCron $cron) {
        $cron->addLog(RestosLang::get('searchengine.solr.searchcatalogues', 'boa'));
        $catalogs = $this->_driver->getCataloguesList();

        if (is_array($catalogs) && count($catalogs) > 0) {
            $cron->addLog(RestosLang::get('searchengine.solr.indexcatalogues', 'boa', count($catalogs)));
            $indexer = new Solr_boa_indexer($this->_parameters, $cron);
            foreach ($catalogs as $catalog) {
                if ($catalog->type != 'dco') continue; //Skip non Digital content objects catalogs

                $path = $catalog->path;

                $indexer->indexCatalog($catalog);
            }
        }
        else {
            $cron->addLog(RestosLang::get('searchengine.solr.notcatalogues', 'boa'));
        }
    }
}