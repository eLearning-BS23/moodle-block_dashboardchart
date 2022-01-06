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
 * Form for editing HTML block instances.
 *
 * @package   block_dashboardchart
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_dashboardchart extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_dashboardchart');
    }

    function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = $this->make_custom_content();
        return $this->content;
    }

    /**
     * Make custom content for block_dashboardchart block
     *
     * @return String
     */
    public function make_custom_content() {

        if (isset($this->config->dashboardcharttype)) {
            if ($this->config->dashboardcharttype == 'coursewiseenrollment') {
                return $this->make_enrollment_table();
            }
// else if ($this->config->leaderboardtype == 'coursetotal') {
//                return $this->make_course_leaderboard_table();
//            } else if ($this->config->leaderboardtype == 'discussionpost') {
//                return $this->make_discussion_post_table();
//            } else if ($this->config->leaderboardtype == 'quizleaderboard') {
//                return $this->make_quiz_table();
//            } else {
//                return get_string('leaderboardtypewarning', 'block_dashboardchart');
//            }
        } else {
            return get_string('leaderboardtypewarning', 'block_dashboardchart');
        }
    }

    /**
     * Make HTML table for enrollment leaderboard
     *
     * @return String
     */
    public function make_enrollment_table() {
//        $datalimit = $this->config->datalimit;
//        $datamanager = new block_customleaderboard\external\datamanager();
//        $enrolldata = $datamanager->get_enrollment_data($datalimit);
//        $coursenamelabel = get_string('tblenrollment:coursename', 'block_customleaderboard');
//        $enrolleduserlabel = get_string('tblenrollment:enrollment', 'block_customleaderboard');
//        $headings = array($coursenamelabel, $enrolleduserlabel);
//        $align = array('left');
//
//        $table = new \html_table();
//        $table->head = $headings;
//        $table->align = $align;
//        $rowcount = count($enrolldata);
////        $this->add_custom_css($table, $rowcount);
//        foreach ($enrolldata as $row) {
//            $table->data[] = array ($row->fullname, $row->enroled_count);
//        }
//        return html_writer::table($table);

        global $DB;
        $sql = "SELECT country, COUNT(country) as newusers FROM {user} GROUP BY country ORDER BY country";
        $rows = $DB->get_records_sql($sql);
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) or $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }
        $title = 'chart';
        $series = new \core\chart_series($title, $series);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $chart->to_html();
//        return $this->output->render($chart);
    }

}
