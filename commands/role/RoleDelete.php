<?php
/**
 *
 */
class RoleDelete extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('delete', 'role');

        $this->addOption('i|id', 'use numerical id instead of short name');
        $this->addRequiredArgument('role');
    }

    public function execute()
    {
        global $CFG, $DB;

        require_once($CFG->libdir . DIRECTORY_SEPARATOR . "accesslib.php");

        $options = $this->expandedOptions;
        $arguments = $this->arguments;

        //execute method
        //delete by id?
        if ($options['id']) {
            $role = $DB->get_record('role', array('id' => $arguments[0]));
            if (!$role) {
                echo "Role with id '" . $arguments[0] . "' does not exist\n";
                exit(0);
            }
            delete_role($arguments[0]);
        } else {
            $role = $DB->get_record('role', array('shortname' => $arguments[0]));
            if (!$role) {
                echo "Role '" . $arguments[0] . "' does not exist.\n";
                exit(0);
            }
            delete_role($role->id);
        }

        echo "\n";
    }
}
