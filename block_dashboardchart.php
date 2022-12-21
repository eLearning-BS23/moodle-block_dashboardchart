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
 * @package    block_dashboardchart
 * @copyright  2021 Brain Station 23
 * @author     Brainstation23
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dashboardchart extends block_base {

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }


    /**
     * init function for plugin name
     * @return void
     * @throws coding_exception
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_dashboardchart');
    }

    /**
     * get content function
     * @return stdClass|stdObject|null
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        } else {
            isset($this->config->dashboardcharttype) ? $this->title = $this->config->msg : '';
        }

        $this->content = new stdClass();
        $this->content->text = $this->make_custom_content();

        return $this->content;
    }

    /**
     * Update title.
     *
     * @return void
     */
    public function specialization() {
        if (isset($this->config)) {
            if (!empty($this->config->msg)) {
                $this->title = $this->config->msg;
            } else {
                $this->config->msg = get_string('dashboardchart', 'block_dashboardchart');
            }
        }
    }

    /**
     * Allow the block to have a multiple instance.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Make custom content for block_dashboardchart block.
     *
     * @return string
     */
    public function make_custom_content() {
        $datalimit = $this->config->datalimit ?? 5;

        if (isset($this->config->dashboardcharttype)) {
            $dashboardtype = '';
            if ($this->config->dashboardcharttype == 'coursewiseenrollment') {
                $dashboardtype = $this->make_course_student_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'category') {
                $dashboardtype = $this->make_category_course_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'login') {
                $dashboardtype = $this->make_login_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'grades') {
                $dashboardtype = $this->make_course_grade_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'active_courses') {
                $dashboardtype = $this->make_most_active_courses_table($datalimit);
            } else {
                $dashboardtype = $this->make_enrollment_table($datalimit);
            }
            return $dashboardtype;
        } else {
            return $this->make_enrollment_table($datalimit);
        }
    }

    /**
     * Make  table for enrollment leaderboard.
     * @param int $datalimit
     * @return string
     */
    public function make_enrollment_table($datalimit) {
        global $DB;
        $sql = "SELECT country, COUNT(country) as newusers
                FROM {user} where country <>''
                GROUP BY country ORDER BY count(country) desc";

        $rows = $DB->get_records_sql($sql, null, 0, $datalimit);
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) || $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }

        return $this->display_graph($series, $labels, 'Student Enrolled by contries', 'Country name');
    }

    /**
     * Getting course records from the database
     * @param int $datalimit
     * @return mixed
     * @throws dml_exception
     */
    public function make_most_active_courses_table($datalimit) {
        global $DB;
        $sql = 'SELECT c.fullname,count(l.userid) AS Views
                FROM {logstore_standard_log} l, {user} u, {role_assignments} r, {course} c, {context} ct
                WHERE l.courseid=6 AND l.userid = u.id
                AND r.roleid=5 AND r.userid = u.id
                AND ct.contextlevel=50 AND l.courseid=ct.instanceid
                GROUP BY c.fullname
                ORDER BY count(l.userid) desc';

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        $series = [];
        $labels = [];

        foreach ($records as $data) {
            $series[] = $data->views;
            $labels[] = $data->fullname;
        }

        return $this->display_graph($series, $labels, 'Most active course', 'Course name');
    }

    /**
     * Login Table function
     * @param int $datalimit
     * @return mixed
     * @throws dml_exception
     */
    public function make_login_table($datalimit) {
        global $DB;
        $sql = 'SELECT date(from_unixtime(lg.timecreated)) date, count(distinct lg.userid) logins
                FROM {logstore_standard_log} lg
                GROUP BY date(from_unixtime(lg.timecreated))
                ORDER BY date(from_unixtime(lg.timecreated)) desc';

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        $series = [];
        $labels = [];

        foreach ($records as $data) {
            $series[] = $data->logins;
            $labels[] = $data->date;
        }

        return $this->display_graph($series, $labels, 'users log ins', 'Date');
    }

    /**
     *  Getting category wise course table
     * @param int $datalimit
     * @return mixed
     * @throws dml_exception
     */
    public function make_category_course_table($datalimit) {
        global $DB;

        $sql = 'SELECT {course_categories}.name, {course_categories}.coursecount
                FROM {course_categories}';

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        $series = [];
        $labels = [];

        foreach ($records as $data) {
            $series[] = $data->coursecount;
            $labels[] = $data->name;
        }

        return $this->display_graph($series, $labels, 'No of courses', 'Category name');
    }

    /**
     * Getting data about course and users
     * @param int $datalimit
     * @return mixed
     * @throws dml_exception
     */
    public function make_course_student_table($datalimit) {
        global $DB;

        $sql = "SELECT c.fullname 'course', COUNT(u.username) 'users'
                FROM {role_assignments} r
                JOIN {user} u on r.userid = u.id
                JOIN {role} rn on r.roleid = rn.id
                JOIN {context} ctx on r.contextid = ctx.id
                JOIN {course} c on ctx.instanceid = c.id
                WHERE rn.shortname = 'student'
                GROUP BY c.fullname, rn.shortname
                ORDER BY COUNT(u.username) desc";

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        $series = [];
        $labels = [];

        foreach ($records as $data) {
            $series[] = $data->users;
            $labels[] = $data->course;
        }

        return $this->display_graph($series, $labels, 'Student per course', 'Course name');
    }

    /**
     * Getting data within a limit
     * @param int $datalimit
     * @return string
     * @throws dml_exception
     */
    public function make_course_grade_table($datalimit) {
        global $DB, $USER;

        $courseid = $this->config->courseid;
        if ($courseid == null) {
            return 'select a course to show grade data';
        }
        $userid = $USER->id;
        $sql = "SELECT round(gg.rawgrade,2) as grade, gi.itemname
                FROM {grade_grades} as gg
                JOIN {grade_items} as gi on gi.courseid={$courseid}
                WHERE gg.itemid=gi.id AND gg.userid={$userid} AND (gg.rawgrade) is not null";

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        $series = [];
        $labels = [];

        foreach ($records as $data) {
            $series[] = $data->grade;
        }

        $sql = "SELECT itemname FROM {grade_items} where courseid={$courseid} and itemname is not null";

        $records = $DB->get_records_sql($sql, null, 0, $datalimit);

        foreach ($records as $data) {
            $labels[] = $data->itemname;
        }
        while (count($series) < count($labels)) {
            $series[] = 0;
        }

        $sql = "SELECT fullname FROM {course} where id={$courseid}";
        $coursename = $DB->get_record_sql($sql, null, 0, $datalimit);

        return $this->display_graph($series, $labels, 'Earned grades', $coursename->fullname);
    }

    /**
     * Display graph.
     * @param  float[] $seriesvalue
     * @param string $labels
     * @param string $title
     * @param string $labelx
     * @return mixed
     */
    public function display_graph($seriesvalue, $labels, $title, $labelx) {
        global $OUTPUT, $CFG;

        $config = get_config('block_dashboardchart');

        $chart = new \core\chart_bar();
        $series = new \core\chart_series($title, $seriesvalue);

        $chartcolour = $config->barcolor;
        if (isset($this->config->graphtype)) {
            if ($this->config->graphtype == 'horizontal') {
                $chart->set_horizontal(true);
                $CFG->chart_colorset = [$chartcolour];
            } else if ($this->config->graphtype == 'pie') {
                $chart = new \core\chart_pie();
            } else if ($this->config->graphtype == 'line') {
                $CFG->chart_colorset = [$chartcolour];
                $chart = new \core\chart_line();
                $chart->set_smooth(true);
            } else {
                $CFG->chart_colorset = [$chartcolour];
            }
        } else {
            $CFG->chart_colorset = [$chartcolour];
        }
        $chart->set_labels($labels);
        $chart->add_series($series);
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_min(0);
        $chart->get_xaxis(0, true)->set_label($labelx);

        return $OUTPUT->render($chart);
    }
}
