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

//Include global dependences
Restos::using('resources.boacomplexobject');
Restos::using('resources.counters.counter');

/**
 * Class to manage the comment actions
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2018 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Comment extends BoAComplexObject {

    public function __construct($id = 0) {

        parent::__construct('comments', $id);

    }

    /**
     *
     * Persist current object data. If object exist this is updated else it is created
     *
     * @param object $data Optional, data to persist
     * @return bool True if object is saved, false in other case
     */
    public function save($data = null) {
        if (parent::save($data)) {
            $comments = $this->_driver->countEntityRecords($this->_entity, array('resource' => $this->resource));

            // Save average like a counter.
            $counter = new Counter();
            try {
                $counter->loadByFilter(array('resource' => $this->resource, 'type' => 'comments'));
            }
            catch(Exception $e) {
                $counter->resource = $this->resource;
                $counter->type = 'comments';
            }

            $counter->value = $comments;
            $counter->updated_at = time();

            try {
                $counter->save();
            }
            catch(Exception $e) {
                // Nothing to do.
            }


            return true;
        }

        return false;
    }
}
