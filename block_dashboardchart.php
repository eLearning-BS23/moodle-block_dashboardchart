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

class block_dashboardchart extends block_base
{

    function init()
    {
        $this->title = get_string('pluginname', 'block_dashboardchart');
    }

    function get_content()
    {

        if (isset($this->config->dashboardcharttype)) {
            $this->title = $this->config->msg;
        }

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
    public function make_custom_content()
    {

        if (isset($this->config->dashboardcharttype)) {
            if ($this->config->dashboardcharttype == 'coursewiseenrollment') {
                return $this->make_course_student_table();
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
            else {
                return $this->make_enrollment_table();
            }
        } else {
            //return get_string('leaderboardtypewarning', 'block_dashboardchart');
            return $this->make_enrollment_table();
        }
    }

    /**
     * Make HTML table for enrollment leaderboard
     *
     * @return String
     */
    public function make_enrollment_table()
    {
        global $DB;
        $sql = "SELECT country, COUNT(country) as newusers FROM {user} GROUP BY country ORDER BY country";
        $rows = $DB->get_records_sql($sql);
        $chart = new \core\chart_bar();
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) or $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }
        return $this->display_graph($series, $labels, "Student Enrolled by contries");
    }


    public function make_course_student_table()
    {
        global $DB;
        $sql = "SELECT c.fullname AS 'course',COUNT(u.username) AS 'users'
        FROM {role_assignments} AS r
        JOIN {user} AS u on r.userid = u.id
        JOIN {role} AS rn on r.roleid = rn.id
        JOIN {context} AS ctx on r.contextid = ctx.id
        JOIN {course} AS c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        GROUP BY c.fullname, rn.shortname";

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->users;
            $labels[] = $data->course;
        }

        return $this->display_graph($series, $labels, "Student per course");
    }

    public function display_graph($series, $labels, $title)
    {
        global $OUTPUT, $CFG;

        $chart = new \core\chart_bar();
        $series = new \core\chart_series($title, $series);

        if (isset($this->config->graphtype)) {
            if ($this->config->graphtype == "horizontal") {
                $chart->set_horizontal(true);
            } else if ($this->config->graphtype == "pie") {
                $chart = new \core\chart_pie();
            } else if ($this->config->graphtype == "line") {
                $chart = new \core\chart_line();
                $chart->set_smooth(true);
            }
        }

        //$CFG->chart_colorset = ['#001f3f'];

        $chart->add_series($series);
        $chart->set_labels($labels);
        return $OUTPUT->render($chart);
    }
}
