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
            } else if ($this->config->dashboardcharttype == 'category') {
                return $this->make_category_course_table();
            } else if ($this->config->dashboardcharttype == 'login') {
                return $this->make_login_table();
            } else if ($this->config->dashboardcharttype == 'grades') {
                return $this->make_course_grade_table();
            } else if ($this->config->dashboardcharttype == 'active_courses') {
                return $this->make_most_active_courses_table();
            } else if ($this->config->dashboardcharttype == 'learner_teacher') {
                return $this->make_learnervTeacher_table();
            } else {
                return $this->make_enrollment_table();
            }
        } else {
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
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) or $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }
        return $this->display_graph($series, $labels, "Student Enrolled by contries", "Country name");
    }

    public function make_course_grade_table()
    {
        global $DB;
        $sql = "SELECT 
        u.username, 
        c.shortname AS 'Course',
        ROUND(gg.finalgrade,2) AS 'Grade',
        DATE_FORMAT(FROM_UNIXTIME(gg.timemodified), '%Y-%m-%d') AS 'Date'
        
        FROM {course} AS c
        JOIN {context} AS ctx ON c.id = ctx.instanceid
        JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
        JOIN {user} AS u ON u.id = ra.userid
        JOIN {grade_grades} AS gg ON gg.userid = u.id
        JOIN {grade_items} AS gi ON gi.id = gg.itemid
        WHERE gi.courseid = c.id 
        AND gi.itemtype = 'course'
        # students only role id is 5
        AND ra.roleid = 5
        ORDER BY u.username, c.shortname";

        $datas = $DB->get_records_sql($sql);

        //die(var_dump($datas));
    }

    public function make_most_active_courses_table()
    {
        global $DB;
        $sql = "SELECT count(l.userid) AS Views
            FROM {logstore_standard_log} l, {user} u, {role_assignments} r
            WHERE l.courseid=0
            AND l.userid = u.id
            AND r.contextid= (
                SELECT id
                FROM mdl_context
                WHERE contextlevel=50 AND instanceid=l.courseid
            )
            AND r.roleid=5
            AND r.userid = u.id";

        $datas = $DB->get_records_sql($sql);
        //die(var_dump($datas));
    }

    public function make_learnervTeacher_table()
    {
        global $DB;
        $sql = "SELECT COUNT(DISTINCT lra.userid) AS learners, COUNT(DISTINCT tra.userid) as teachers
            FROM {course} AS c #, {course_categories} AS cats
            LEFT JOIN {context} AS ctx ON c.id = ctx.instanceid
            JOIN {role_assignments}  AS lra ON lra.contextid = ctx.id
            JOIN {role_assignments}  AS tra ON tra.contextid = ctx.id JOIN {course_categories} AS cats ON c.category = cats.id
            WHERE c.category = cats.id
            AND lra.roleid=5
            AND tra.roleid=3";

        $datas = $DB->get_records_sql($sql);
        //die(var_dump($datas));
    }

    public function make_login_table()
    {
        global $DB;
        $sql = "SELECT
        COUNT( l.userid) AS 'DistinctUserLogins',
        DATE_FORMAT(FROM_UNIXTIME(l.timecreated), '%M') AS 'Month'
        FROM {logstore_standard_log} l
        WHERE l.action = 'loggedin' 
        GROUP BY MONTH(FROM_UNIXTIME(l.timecreated))";

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->distinctuserlogins;
            $labels[] = $data->month;
        }

        return $this->display_graph($series, $labels, "user log ins in past months", "Month name");
    }

    public function make_category_course_table()
    {
        global $DB;
        $sql = "SELECT {course_categories}.name, {course_categories}.coursecount
        FROM {course_categories}";
        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->coursecount;
            $labels[] = $data->name;
        }

        return $this->display_graph($series, $labels, "No of courses", "Category name");
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

        return $this->display_graph($series, $labels, "Student per course", "Course name");
    }

    public function display_graph($series, $labels, $title, $labelx)
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
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_min(0);
        $chart->get_xaxis(0, true)->set_label($labelx);
        return $OUTPUT->render($chart);
    }
}
