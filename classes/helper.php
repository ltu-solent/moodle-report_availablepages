<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_availablepages;

/**
 * Class helper
 *
 * @package    report_availablepages
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Get a list of all the pages that have self-enrolment and are available.
     *
     * @return array List of courses with available self-enrolment instances
     */
    public static function get_available_pages(): array {
        global $DB;
        $config = get_config('report_availablepages');
        $pages = [];
        // Customint6 - Allow new enrolments.
        $sql = "SELECT * FROM (
            SELECT e.*, c.fullname course_fullname, c.shortname course_shortname, cat.name category_name, cat.id category_id
            FROM {enrol} e
            JOIN {course} c ON c.id = e.courseid AND c.visible = 1 AND (c.enddate = 0 OR c.enddate > :now1)
            JOIN {course_categories} cat ON cat.id = c.category AND cat.visible = 1
            WHERE e.enrol = 'self'
                AND e.status = :enrolstatus
                AND e.customint6 = 1
                AND (e.enrolenddate = 0 OR e.enrolenddate > :now2)
            UNION
            SELECT e.*, c.fullname course_fullname, c.shortname course_shortname, cat.name category_name, cat.id category_id
            FROM {enrol} e
            JOIN {course} c ON c.id = e.courseid AND c.visible = 1 AND (c.enddate = 0 OR c.enddate > :nowguest)
            JOIN {course_categories} cat ON cat.id = c.category AND cat.visible = 1
            WHERE e.enrol = 'guest'
                AND e.status = :enrolstatusguest) a
            ORDER BY a.course_fullname";
        $instances = $DB->get_records_sql($sql, [
            'now1' => time(),
            'now2' => time(),
            'nowguest' => time(),
            'enrolstatus' => ENROL_INSTANCE_ENABLED,
            'enrolstatusguest' => ENROL_INSTANCE_ENABLED,
        ]);

        foreach ($instances as $instanceid => $instance) {
            // Does a proper check if the enrolment is available.
            if (!self::is_self_enrol_available($instance)) {
                unset($instances[$instanceid]);
                continue;
            }
            $pages[$instance->courseid][] = $instance;
        }
        return $pages;
    }

    /**
     * Check if the specified instance is available.
     *
     * @param stdClass $instance
     * @return bool
     */
    private static function is_self_enrol_available($instance): bool {
        global $CFG, $DB, $USER;
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return false;
        }

        if ($instance->enrol == 'guest') {
            return true;
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return false;
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return false;
        }

        if (!$instance->customint6) {
            // New enrols not allowed.
            return false;
        }

        if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            return false;
        }

        if ($instance->customint3 > 0) {
            // Max enrol limit specified.
            $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id]);
            if ($count >= $instance->customint3) {
                // Bad luck, no more self enrolments here.
                return false;
            }
        }

        if ($instance->customint5) {
            require_once("$CFG->dirroot/cohort/lib.php");
            if (!cohort_is_member($instance->customint5, $USER->id)) {
                $cohort = $DB->get_record('cohort', ['id' => $instance->customint5]);
                if (!$cohort) {
                    return false;
                }
                return false;
            }
        }
        return true;
    }
}
