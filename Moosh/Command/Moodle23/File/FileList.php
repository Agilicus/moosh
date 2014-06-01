<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\File;
use Moosh\MooshCommand;

class FileList extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('list', 'file');

        $this->addOption('i|id', 'display IDs only - used for piping into other file-related commands');
        $this->addOption('a|all', 'display all possible information');

        /*
contextid
component
filearea
itemid
filepath
filename
userid
filesize
mimetype
status
timecreated
timemodified
*/

        $this->addArgument('expression');
  //      $this->maxArguments = 3;
    }

    protected function getArgumentsHelp()
    {
        $help = parent::getArgumentsHelp();
        $help .= "\n\n";
        $help .= "To get all files from course N use 'course=N' as an argument.";

        return $help;
    }


    public function execute()
    {
        global $CFG, $DB;

        $fs = get_file_storage();

        $query = trim($this->arguments[0]);

        //check if asking for course files: course=NNN
        $match = NULL;
        if(preg_match('/course=(\d+)/',$query,$match) !== false) {
            //get all context IDs
            $courseid = $match[1];

            //get context path for course
            $context = \context_course::instance($courseid);
            $contexts = array($context->get_course_context()->id);
            $results = $DB->get_records_sql("SELECT * FROM {context} WHERE path LIKE '" . $context->get_course_context()->path . "/%'");
            foreach($results as $result) {
                $contexts[] = $result->id;
            }
            list($sql, $params) = $DB->get_in_or_equal($contexts);

            $rs = $DB->get_recordset_sql("SELECT id FROM {files} WHERE filename <> '.' AND contextid $sql", $params);
        } else {
            $rs = $DB->get_recordset_sql("SELECT id FROM {files} WHERE ". $query);

        }

        foreach($rs as $file) {
            if($this->expandedOptions['id']) {
                echo $file->id . "\n";
                continue;
            }
            $fileobject = $fs->get_file_by_id($file->id);

            echo $fileobject->get_id() . "\t";

            echo $fileobject->get_status();
            if ($fileobject->is_directory()) {
                echo 'd';
            } else {
                echo '.';
            }

            if ($fileobject->is_external_file()) {
                echo 'e';
            } else {
                echo '.';
            }

            if ($fileobject->is_valid_image()) {
                echo 'i';
            } else {
                echo '.';
            }

            if($fileobject->get_timecreated() != $fileobject->get_timemodified()) {
                echo 'm';
            } else {
                echo '.';
            }

            echo "\t" . $fileobject->get_contenthash();
            echo "\t" . userdate($fileobject->get_timecreated());

            echo "\t" . $fileobject->get_contextid() . ':' . $fileobject->get_component() . ':'. $fileobject->get_filearea() . ':' . $fileobject->get_itemid();

            if($this->expandedOptions['all']) {
                echo "\t" . $fileobject->get_mimetype();
                echo "\t" . $fileobject->get_filesize();
                echo "\t" . $fileobject->get_userid();
            }
            echo "\t\t" . substr($fileobject->get_filepath(),1) . $fileobject->get_filename();

            echo "\n\r";
            echo chr(27) . "[0G";
            flush();
        }
        $rs->close();

    }
}


