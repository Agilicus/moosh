<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle27\File;

use Moosh\MooshCommand;

class FileHashDelete extends MooshCommand{
    
    public function __construct() 
    {
        parent::__construct('hash-delete', 'file');
        
        $this->addArgument('hash');
    }
    
    public function execute()
    {
        global $DB;
        
        $hash = $this->arguments[0];
        
        $results = $DB->get_records('files', array('contenthash' => $hash));

        if ($results === false){
            cli_error("no file was found");
        }
        
        foreach ($results as $result) {
            $filesByItemIndex = $DB->get_records('files', array(
                    'itemid'    => $result->itemid,
                    'contextid' => $result->contextid,
                    'component' => $result->component,
                    'filearea'  => $result->filearea,
                ));

            if (count($filesByItemIndex) <= 2) {
                if (count($filesByItemIndex) === 1){
                    // there is just one file, delete it.
                    $this->deleteFiles($filesByItemIndex);
                }

                if (count($filesByItemIndex) === 2) {
                    // there are 2 files. Check if one of them is '.'
                    $safeDelete = false;

                    foreach ($filesByItemIndex as $file){
                        if ($file->filename == ".") {
                            $safeDelete = true;
                        }
                    }

                    $this->deleteFiles($filesByItemIndex, $safeDelete);
                }
            }else{
                // give user info that there is too many files for safe deletion
                $this->tooManyFiles($filesByItemIndex);
            }
        }
        
    }

    private function deleteFiles(array $files, $safeDelete = false) {
        global $DB;
        
        $fileIds = [];
        
        foreach ($files as $file) {
            $fileIds[] = $file->id;
        }
        
        $where = "id IN (" . implode(',', $fileIds) . ")";

        if($safeDelete === true) {
            $DB->delete_records_select('files', $where);
            echo "Successfully deleted files." . PHP_EOL;
            foreach ($files as $file) {
                echo "File ID: {$file->id}, contenthash: {$file->contenthash}, "
                    . "itemid: {$file->itemid}, component {$file->component}, "
                    . "filearea {$file->filearea}, filename {$file->filename}" . PHP_EOL;
            }
        } else {
            echo "safeDelete is not set. Refuse to remove any records in DB.";
        }
}
    
    private function tooManyFiles($files) {
        echo "There are too many files for safe delete.".PHP_EOL;
        echo "All files count: " . count($files) . PHP_EOL;
    }
}
