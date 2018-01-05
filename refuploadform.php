
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
 * Lesson page without answers
 *
 * @package mod_lesson
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Include formslib if it has not already been included
 */
require_once ($CFG->libdir.'/formslib.php');


// require_once ($CFG->dirroot.'/course/moodleform_mod.php');
/**
 * Lesson page without answers
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class refuploadform extends moodleform {

    public function definition() {
        global $OUTPUT;

        global $CFG, $DB;

        $courseId = $this->_customdata['courseid'];
        $workshopIdArray = $DB->get_records("workshop",array("course"=>$courseId),"id");
        $option = array();
        foreach($workshopIdArray as $workdshopId){
            $option = $option + array("$workdshopId->id" =>"$workdshopId->name");
        }


        $mform = $this->_form;
        
        $mform->addElement('filemanager', 'mydraft', "参考文献", "label things",
                            array('subdirs' => 0, 'maxbytes' => 0, 'areamaxbytes' => 0 , 'maxfiles' => 50,
                                'accepted_types' => array('.txt','.pdf'), 'return_types'=> 1|2));
        $mform->addElement('select', 'workshopId', "选择需要分析的互评", $option);
        $mform->setDefault('workshopId', 0);                       

        $attributes=array('size'=>'20');
        $mform->addElement('text', 'plagThrehold', '抄袭阈值', null);
        $mform->setDefault('plagThrehold',0.7);
        // $mform->setType('plagThrehold',PARAM_INT);
        $mform->addElement('text', 'minUnit', '最小检测单位', null);
        $mform->setDefault('minUnit',6);

        $mform->addElement('text', 'meanlessWords', '助词列表', null);
        $mform->setDefault('meanlessWords','的，了，呢');
        
        $this->add_action_buttons(1,"开始分析");

        $mform->addElement('hidden', 'contextid', '');
        if(isset($this->_customdata['contextid']))
            $mform->setDefault('contextid',$this->_customdata['contextid']);
    }

    // function validation($data, $files) {
    //     $errors = parent::validation($data, $files);

    //     if (empty($data['plagThrehold']))
    //     {
    //         $errors['plagThrehold'] = "抄袭阈值不能为空！" ;
    //     }
    //     return $errors;
    // }
}
