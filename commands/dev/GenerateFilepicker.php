<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class GenerateFilepicker extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('filepicker', 'generate');
    }


    public function execute()
    {
        $loader = new Twig_Loader_Filesystem($this->mooshDir.'/templates');
        $twig = new Twig_Environment($loader,array('debug' => true));
        $twig->addExtension(new Twig_Extension_Debug());

        foreach(array('filepicker/form-handler.twig','filepicker/display.twig','filepicker/lib.twig') as $template) {
            echo $twig->render($template, array('id' =>  $this->pluginInfo['type'] .'_'. $this->pluginInfo['name']));
        }
    }
}