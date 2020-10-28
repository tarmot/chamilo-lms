<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once 'StudentGroupPlugin.php';

use Chamilo\PluginBundle\Entity\StudentGroup\Group;

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = StudentGroupPlugin::create();

if ('false' === $plugin->get(StudentGroupPlugin::SETTING_ENABLED)) {
    api_not_allowed(true);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

$em = Database::getManager();
$studentGroupRepo = $em->getRepository('ChamiloPluginBundle:StudentGroup\Group');

$session = api_get_session_entity(api_get_session_id());

$course = isset($_REQUEST['cidReq']) ? $_REQUEST['cidReq'] : null;
$course_id = api_get_course_int_id($course);

$teacher = api_get_user_id();

$actions = [];

function createStudentGroup($groupId, $groupSize, $groupName, $course_id, $teacher) {

    $groupId = Database::escape_string($groupId);
    $groupSize = intval($groupSize);
    $groupName = Database::escape_string($groupName);
    $course_id = intval($course_id);
    $teacher = intval($teacher);

    $group = new Group();
    $group
        ->setId($groupId)
        ->setGroupSize($groupSize)
        ->setGroupName($groupName)
        ->setCourseId($course_id)
        ->setTeacher($teacher);
    
    return $group;
}

function getGroupData() {
    
    global $course_id, $teacher, $studentGroupRepo;

    $allGroups = [];
    
    // Get all study groups in this course and with this teacher
    $allGroupEntities = $studentGroupRepo->findBy(['course_id'=>$course_id, 'teacher'=>$teacher]);

    foreach ($allGroupEntities as $entity) {
        // Don't show deleted groups that still exist in database
        if (strpos($entity->getGroupName(), '_DELETED_') === false) {
            $allgroups[] = [$entity->getId(), $entity->getGroupName(), $entity->getGroupSize(), $entity->getId(), $entity->getId()];
        }
    }
    return $allgroups;
}

// Create delete icon button inside action column
function modifyFilter($value) {
    global $plugin;
    $result = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete&id='.$value.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))."'".')) return false;">'
    .Display::return_icon('delete.png', $plugin->get_lang('deleteGroup'), [], ICON_SIZE_SMALL).
    '</a>';
    return '<div style="width:205px">'.$result.'</div>';
}

switch ($action) {

    // Add study group form
    // 1. Prepare adding form
    // 2. If form was sent and validates -> create new study group to database
    // 3. Continue to presenting form
    case 'add':
        $formAdd = new FormValidator('formAdd', 'POST', api_get_self().'?'.api_get_cidreq().'&sent=1');
        $formAdd->addHeader('Add study group to your course');
        $formAdd->addText('groupName', 'Name of the study group', false);
        $formAdd->addNumeric('groupSize', 'Number of students', ['min' => 0], true);
        $formAdd->addRule('groupSize', get_lang('ThisFieldIsRequired'), 'required');
        $formAdd->addRule('groupSize','', 'regex', '/[1-9]/');
        $formAdd->addButtonSave('Save', 'saveGroupButton');
        $formAdd->addHidden('action', 'add');
        
        $template =$formAdd->returnForm();

        $actions = '<a href="start.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('Plugin frontpage'), '', ICON_SIZE_MEDIUM)
            .'</a>';

        $groupName = isset($_POST['groupName']) ? $_POST['groupName'] : null;
        $groupName = trim($groupName);
        $groupSize = isset($_POST['groupSize']) ? $_POST['groupSize'] : null;

        // If form validates, then try to add study group to database
        if ($formAdd->validate() && $groupName && $groupSize){

            // Check if group name is already in use for this course and this teacher
            if($studentGroupRepo->findBy(['group_name'=>$groupName, 'course_id'=>$course_id, 'teacher'=>$teacher])) {
                Display::addFlash(Display::return_message($plugin->get_lang('groupNameUsed'), 'error', false));
                break;
            }

            // Check if groupId is already in use, if it is then make another random id, if not then save group to database
            $check = false;
            while(!$check) {
                $pinA = random_int(100,999);
                $pinB = random_int(100,999);
                $groupId = $pinA.'-'.$pinB;
                if(!($studentGroupRepo->find($groupId))){
                    $em->persist(createStudentGroup($groupId, $groupSize, $groupName, $course_id, $teacher));
                    $em->flush();
                    $check = true;
                }
            }

            // Remove possible html tags for display message where filtering is turned false due to custom html
            $groupId = api_remove_tags_with_space($groupId);
            $groupName = api_remove_tags_with_space($groupName);

            $message = $plugin->get_lang('newStudyGroupCreated').' '
                .$plugin->get_lang('YouHaveBeenSentConfirmation').'<br><br>'
                .$plugin->get_lang('groupName').$groupName.'<br><br>'
                .$plugin->get_lang('pinCode').$groupId;
            
            Display::addFlash(Display::return_message($message, 'confirm', false));

            // Send confirmation email
            $teacherInfo = api_get_user_info($teacher);
            
            $recipientEmail = $teacherInfo['email'];

            if ($recipientEmail) {

                $recipientName = $teacherInfo['firstname'].' '.$teacherInfo['lastname'];
                $subject = $plugin->get_lang('newStudyGroup').$groupName;

                $emailBody = $plugin->get_lang('hi')
                    .api_get_user_info($teacher)['firstname']
                    .','."<br>"
                    .$plugin->get_lang('newStudyGroupCreated')
                    ."<br>"
                    .$plugin->get_lang('groupName')
                    .$groupName
                    ."<br>"
                    .$plugin->get_lang('pinCode')
                    .$groupId
                    ."<br>";
                
                api_mail_html($recipientName, $recipientEmail, $subject, $emailBody);

            }
           
        } 
        // If form is filled and it does not validate, then show error
        else {
            $sent = isset($_REQUEST['sent']) ? $_REQUEST['sent'] : null;
            if ($sent == 1) {
                Display::addFlash(Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error', false));
            }
        }
        break;

    // Delete study group
    // 1. Delete selected group if user has originally added this group
    // 2. Continue to default case
    case 'delete':
        if (is_array($_REQUEST['id'])) {
            foreach ($_REQUEST['id'] as $groupId) {
                // If there's a student group with this teacher in database
                $groupToRemove = $studentGroupRepo->findOneBy(['id'=>$groupId, 'teacher'=>$teacher]);

                if ($groupId && $groupToRemove) {
                    $deleted = $groupToRemove->getGroupName().'_DELETED_';
                    $groupToRemove->setGroupName($deleted);
                    $em->persist($groupToRemove);
                    $em->flush();

                    Display::addFlash(Display::return_message($plugin->get_lang('groupDeleted'), 'confirm', false));

                } else {
                    api_not_allowed();
                }
            }
        } else {
            $groupId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null; 

            // If there's a student group with this teacher in database
            $groupToRemove = $studentGroupRepo->findOneBy(['id'=>$groupId, 'teacher'=>$teacher]);

            if ($groupId && $groupToRemove) {
                $deleted = $groupToRemove->getGroupName().'_DELETED_';
                $groupToRemove->setGroupName($deleted);
                $em->persist($groupToRemove);
                $em->flush();

                Display::addFlash(Display::return_message($plugin->get_lang('groupDeleted'), 'confirm', false));

            } else {
                api_not_allowed();
            }
        }     

    // "Main" view of plugin
    default:

        $table = new SortableTable(
            'groups',
            null,
            'getGroupData',
            2,
            20,
            'ASC',
            null,
            ['style' => 'font-size: 1.4rem;', 'class' => 'table table-hover table-striped table-bordered table-condensed']
        );
        $table->set_header(0, '&nbsp;', false);
        $table->set_header(1, $plugin->get_lang('name'), false);
        $table->set_header(2, $plugin->get_lang('size'), false);
        $table->set_header(3, $plugin->get_lang('pinCode'));
        $table->set_header(4, get_lang('Action'), false);
        $table->set_column_filter(4, 'modifyFilter');
        $actionsList = [];
        $actionsList['delete'] = $plugin->get_lang('deleteGroup');
        $table->set_form_actions($actionsList);

        $actions = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add'.'">'
        .Display::return_icon('new_user.png', $plugin->get_lang('addNewGroup'), '', ICON_SIZE_MEDIUM).'</a>';

        $template=$table->return_table();
        break;
}

Display::display_header($plugin->get_title());
echo $toolbar = Display::toolbarAction('toolbar-plugin', [$actions]);
echo($template);
Display::display_footer();