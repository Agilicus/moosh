<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\Data;
use Moosh\MooshCommand;

class DataStats extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('stats', 'data');

        $this->addOption('j|json', 'generate output using json format');
    }

    public function execute() 
    {
        global $CFG, $DB;

        $options = $this->expandedOptions;

        $dataroot = run_external_command("du -s $CFG->dataroot", "Couldn't find dataroot directory");
        $pattern = '/\d*/';
        preg_match($pattern, $dataroot[0], $matches);

        $filedir = run_external_command("du -s $CFG->dataroot/filedir", "Couldn't find filedir directory");
        preg_match($pattern, $filedir[0], $dir_matches);

        $sql_query = "SELECT SUM(filesize) AS total FROM {files}";
        $all_files = $DB->get_record_sql($sql_query);

        $sql_query = "SELECT DISTINCT contenthash, SUM(filesize) AS total FROM {files}";
        $distinct_contenthash = $DB->get_record_sql($sql_query);
        
        $filesbycourse = array();
        if ($courses = get_all_courses()) {
            foreach ($courses as $course) {
                $subcontexts = get_sub_context_ids($course->ctxpath);
                $filesbycourse[$course->id] = array('unique' => 0, 'all' => 0);
                foreach($subcontexts as $subcontexts) {
                    if ($files = get_files($subcontexts->id)) {
                        foreach ($files as $file) {
                            $filesbycourse[$course->id]['unique'] += file_is_unique($file->contenthash, $subcontexts->id) ? $file->filesize : 0;
                            $filesbycourse[$course->id]['all'] += $file->filesize;
                        }
                    }
                }
            }
        }
        $sortarray = higher_size($filesbycourse);

        $data = array('dataroot' => $matches[0],
            'filedir' => $dir_matches[0],
            'files total' => $all_files->total,
            'distinct files total' => $distinct_contenthash->total);
        foreach ($sortarray as $courseid => $values) {
            $data["Course $courseid files total"] = strval($values['all']);
            $data["Course $courseid files unique"] = strval($values['unique']);
        }

        if ($options['json']) {
            echo json_encode($data);
        } else {
            foreach ($data as $k => $v) {
                echo "$k: " . display_size($v) ."\n";
            }
        }
    }
}
