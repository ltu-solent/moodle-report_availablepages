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

/**
 * Display report_availablepages report
 *
 * @package    report_availablepages
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_availablepages\helper;

require('../../config.php');

require_login(null, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/report/availablepages/index.php'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_availablepages'));

$availablepages = helper::get_available_pages();
if (count($availablepages) == 0) {
    echo get_string('nopages', 'report_availablepages');
    echo $OUTPUT->footer();
    die();
}

$categories = core_course_category::make_categories_list();
$catlist = [];

foreach ($availablepages as $page) {
    $courseid = $page[0]->courseid;
    $enrolurl = new moodle_url('/enrol/index.php', ['id' => $courseid]);
    $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
    $pagetitle = format_string($page[0]->course_fullname);
    $catid = $page[0]->category_id;

    if (!isset($catlist[$categories[$catid]])) {
        $catlist[$categories[$catid]] = [];
        $catlist[$categories[$catid]]['catgroup'] = $categories[$catid];
        $catlist[$categories[$catid]]['courses'] = [];
    }
    // If there's one method and it's a guest, link to the course.
    // If there's more than one method, primary link the enrol, with subsequent guest link, if there's one.
    $guestlink = '';
    $pagelink = html_writer::link($enrolurl, $pagetitle);
    $enrolcount = count($page);
    if ($enrolcount == 1 && $page[0]->enrol == 'guest') {
        $pagelink = html_writer::link($courseurl, $pagetitle);
    }
    if (count($page) > 1) {
        foreach ($page as $instance) {
            if ($instance->enrol == 'guest') {
                $guestlink = ' - ' . html_writer::link($courseurl, get_string('guestlink', 'report_availablepages'));
            }
        }
    }
    $catlist[$categories[$catid]]['courses'][] = $pagelink . $guestlink;
}

echo get_string('intro', 'report_availablepages');
core_collator::ksort($catlist);
foreach ($catlist as $cat) {
    echo html_writer::tag('h4', $cat['catgroup']);
    echo html_writer::alist($cat['courses']);
}

echo $OUTPUT->footer();
