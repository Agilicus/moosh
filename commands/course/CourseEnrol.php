<?php
/**
 * Enrol user(s) in a course. Uses manual enrollment plugin.
 * moosh course-enrol
 *      -i --id
 *      -r --role
 *      courseid username1 [<username2> ...]
 */
class CourseEnrol extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('enrol', 'course');
        $this->addOption('i|id', 'use numeric IDs instead of user name(s)');
        $this->addOption('r|role:', 'role short name');

        //possible other options
        //duration
        //startdate
        //recovergrades

        $this->addRequiredArgument('courseid');
        $this->addRequiredArgument('username');
        $this->maxArguments = 255;
    }

    public function execute()
    {
        global $CFG, $DB, $PAGE;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        $options = $this->expandedOptions;
        $arguments = $this->arguments;

        //find role id for given role
        $role = $DB->get_record('role', array('shortname' => $options['role']), '*', MUST_EXIST);

        $course = $DB->get_record('course', array('id' => $arguments[0]), '*', MUST_EXIST);
        $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
        $manager = new course_enrolment_manager($PAGE, $course);

        $instances = $manager->get_enrolment_instances();
        //find the manual one
        foreach ($instances as $instance) {
            if ($instance->enrol == 'manual') {
                break;
            }
        }

        if ($instance->enrol != 'manual') {
            die("No manual enrolment instance for the course\n");
        }

        $plugins = $manager->get_enrolment_plugins();

        //only one manual enrolment in a course
        if (!isset($plugins['manual'])) {
            die("No manual enrolment plugin for the course\n");
        }
        $plugin = $plugins['manual'];

        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

        array_shift($arguments);
        foreach ($arguments as $argument) {
            if ($options['id']) {
                $user = $DB->get_record('user', array('id' => $argument), '*', MUST_EXIST);
            } else {
                $user = $DB->get_record('user', array('username' => $argument), '*', MUST_EXIST);
            }
            $plugin->enrol_user($instance, $user->id, $role->id, $today, 0);
        }
    }

}
