<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle27\UserProfile;

use Moosh\MooshCommand;

class UserProfileExport extends MooshCommand
{
    public function __construct() {
        parent::__construct('export', 'userprofile');

        $this->addArgument('userid');

        $this->addOption('p|path', 'path to save exported file.', 'exported_users.csv');
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/csvlib.class.php');      
        require_once($CFG->libdir . '/moodlelib.php');

        $filename = $this->expandedOptions['path'];
        if ($filename[0] != '/') {
            $filename = $this->cwd . DIRECTORY_SEPARATOR . $filename;
        }
        $userid = $this->arguments['0'];
        $user = $DB->get_record('user', array('id' => $userid));
        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');

        $data = array();
        foreach ($categories as $category) {

            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    $field->categoryname        = $category->name;
                    $field->categorysortorder   = $category->sortorder;
                    $data[]                     = $field;

                }
            }
        } // End of $categories foreach.

        $header = array(
            'id',
            'shortname',
            'name',
            'datatype',
            'description',
            'descriptionformat',
            'categoryid',
            'sortorder',
            'required',
            'locked',
            'visible',
            'forceunique',
            'signup',
            'defaultdata',
            'defaultdataformat',
            'param1',
            'param2',
            'param3',
            'param4',
            'param5',
            'categoryname',
            'categorysortorder'
        );

        $csvexport = new \csv_export_writer();

        $csvexport->add_data($header);

        foreach ($data as $row) {
            $arrayrow = (array)$row;
            $csvexport->add_data($arrayrow);
        }
        try {
            file_put_contents($filename, $csvexport->print_csv_data(true));
            echo "Userfields exported to: " . $filename;
        }
        catch (Exception $e) {
            cli_error("Unable to save file. Check if file $filename is writable");
        }

    }
}