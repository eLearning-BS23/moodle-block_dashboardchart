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

    public function init()
    {
        $this->title = get_string('pluginname', 'block_dashboardchart');
    }

    public function get_content()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = $this->make_custom_content();
        return $this->content;
    }

    /**
     * Update title
     *
     * @return void
     */
    public function specialization()
    {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = $this->config->msg;
            } else {
                $this->title = $this->config->msg;
            }

            if (empty($this->config->msg)) {
                $this->config->msg = get_string('dashboardchart', 'block_customleaderboard');
            }
        }
    }

    /**
     * Allow the block to have a multiple instance
     *
     * @return boolean
     */
    public function instance_allow_multiple()
    {
        return true;
    }

    /**
     * Make custom content for block_dashboardchart block
     *
     * @return String
     */
    public function make_custom_content()
    {
        if (isset($this->config->datalimit))
            $datalimit = $this->config->datalimit;
        else  $datalimit = '';
        $datalimit = $this->return_data_limit($datalimit);
        if (isset($this->config->dashboardcharttype)) {
            if ($this->config->dashboardcharttype == 'coursewiseenrollment') {
                return $this->make_course_student_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'category') {
                return $this->make_category_course_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'login') {
                return $this->make_login_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'grades') {
                return $this->make_course_grade_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'active_courses') {
                return $this->make_most_active_courses_table($datalimit);
            } else if ($this->config->dashboardcharttype == 'learner_teacher') {
                return $this->make_learnervTeacher_table($datalimit);
            } else {
                return $this->make_enrollment_table($datalimit);
            }
        } else {
            return $this->make_enrollment_table($datalimit);
        }
    }

    /**
     * Make HTML table for enrollment leaderboard
     *
     * @return String
     */
    public function make_enrollment_table($datalimit)
    {
        global $DB;
        $sql = "SELECT country, COUNT(country) as newusers FROM {user} where country <>'' GROUP BY country ORDER BY count(country) desc " . $datalimit;
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

    public function make_most_active_courses_table($datalimit)
    {
        global $DB;
        $sql = "SELECT c.fullname,count(l.userid) AS Views
        FROM {logstore_standard_log} l, {user} u, {role_assignments} r, {course} c, {context} ct
        WHERE l.courseid=6
        AND l.userid = u.id
        AND r.roleid=5
        AND r.userid = u.id
        AND ct.contextlevel=50
        and l.courseid=ct.instanceid
        group by c.fullname
        order by count(l.userid) desc" . $datalimit;

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->views;
            $labels[] = $data->fullname;
        }
        return $this->display_graph($series, $labels, "Most active course", "Course name");
    }

    public function make_learnervTeacher_table($datalimit)
    {
        global $DB;
        $sql = "SELECT COUNT(DISTINCT lra.userid) AS learners, COUNT(DISTINCT tra.userid) as teachers
            FROM {course} AS c #, {course_categories} AS cats
            LEFT JOIN {context} AS ctx ON c.id = ctx.instanceid
            JOIN {role_assignments}  AS lra ON lra.contextid = ctx.id
            JOIN {role_assignments}  AS tra ON tra.contextid = ctx.id JOIN {course_categories} AS cats ON c.category = cats.id
            WHERE c.category = cats.id
            AND lra.roleid=5
            AND tra.roleid=3" . $datalimit;

        $datas = $DB->get_records_sql($sql);
        //die(var_dump($datas));
    }

    public function make_login_table($datalimit)
    {
        global $DB;
        $sql = "SELECT date(from_unixtime(lg.timecreated)) as date,count(distinct lg.userid) as logins FROM {logstore_standard_log} as lg
        group by date(from_unixtime(lg.timecreated)) 
        order by date(from_unixtime(lg.timecreated)) desc
        " . $datalimit;

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->logins;
            $labels[] = $data->date;
        }

        return $this->display_graph($series, $labels, "users log ins", "Date");
    }

    public function make_category_course_table($datalimit)
    {
        global $DB;
        $sql = "SELECT {course_categories}.name, {course_categories}.coursecount
        FROM {course_categories}" . $datalimit;
        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->coursecount;
            $labels[] = $data->name;
        }

        return $this->display_graph($series, $labels, "No of courses", "Category name");
    }


    public function make_course_student_table($datalimit)
    {
        global $DB;
        $sql = "SELECT c.fullname AS 'course',COUNT(u.username) AS 'users'
        FROM {role_assignments} AS r
        JOIN {user} AS u on r.userid = u.id
        JOIN {role} AS rn on r.roleid = rn.id
        JOIN {context} AS ctx on r.contextid = ctx.id
        JOIN {course} AS c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        GROUP BY c.fullname, rn.shortname
        ORDER BY COUNT(u.username) desc " . $datalimit;

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->users;
            $labels[] = $data->course;
        }

        return $this->display_graph($series, $labels, "Student per course", "Course name");
    }

    public function make_course_grade_table($datalimit)
    {
        global $DB, $USER, $OUTPUT, $CFG;

        $courseid = $this->config->courseid;
        if ($courseid == null) {
            return "select a course to show grade data";
        }
        $userid = $USER->id;
        $sql = " SELECT round(gg.rawgrade,2) as grade, gi.itemname from {grade_grades} as gg
        join {grade_items} as gi on gi.courseid={$courseid}
        where gg.itemid=gi.id and
        gg.userid={$userid} and (gg.rawgrade) is not null " . $datalimit;

        $datas = $DB->get_records_sql($sql);

        $series = [];
        $labels = [];

        foreach ($datas as $data) {
            $series[] = $data->grade;
        }

        $sql = "SELECT itemname FROM {grade_items} 
        where courseid={$courseid} and itemname is not null
                    " . $datalimit;

        $datas = $DB->get_records_sql($sql);



        foreach ($datas as $data) {
            $labels[] = $data->itemname;
        }
        while (count($series) < count($labels)) {
            $series[] = 0;
        }

        $sql = "SELECT fullname FROM {course} where id={$courseid}";
        $coursename = $DB->get_record_sql($sql);
        return $this->display_graph($series, $labels, "Earned grades", $coursename->fullname);
    }

    public function display_graph($seriesvalue, $labels, $title, $labelx)
    {
        global $OUTPUT, $CFG;

        $chart = new \core\chart_bar();
        $series = new \core\chart_series($title, $seriesvalue);

        if (isset($this->config->graphtype)) {
            if ($this->config->graphtype == "horizontal") {
                $chart->set_horizontal(true);
                $CFG->chart_colorset = ['#0f6cbf'];
            } else if ($this->config->graphtype == "pie") {
                $chart = new \core\chart_pie();
            } else if ($this->config->graphtype == "line") {
                $CFG->chart_colorset = ['#0f6cbf'];
                $chart = new \core\chart_line();
                $chart->set_smooth(true);
            } else {
                $CFG->chart_colorset = ['#0f6cbf'];
            }
        } else {
            $CFG->chart_colorset = ['#0f6cbf'];
        }

        //$CFG->chart_colorset = ['#001f3f'];
        $chart->set_labels($labels);
        $chart->add_series($series);
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_min(0);
        $chart->get_xaxis(0, true)->set_label($labelx);
        return $OUTPUT->render($chart);
    }

    public function return_data_limit($datalimit)
    {
        $limit = (int) $datalimit;
        switch ($limit) {
            case 1:
                return "";
            case 5:
                return " LIMIT 5 ";
            case 10:
                return " LIMIT 10 ";
            case 20:
                return " LIMIT 20 ";
            default:
                return " LIMIT 10 ";
        }
    }
}
