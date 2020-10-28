<?php
/* For licensing terms, see /license.txt */

/**
 * Class StudentGroupPlugin
 * Plugin to add student groups to particular courses and have them unique codes
 */

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\CoreBundle\Entity\Course; 
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\StudentGroup\Group;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class StudentGroupPlugin.
 */
class StudentGroupPlugin extends Plugin
{
    const SETTING_ENABLED = 'tool_enabled';
    const TBL_STUDENT_GROUP = 'plugin_student_group';

    public $isCoursePlugin = true;

    /**
     * StudentGroupPlugin constructor.
     */
    protected function __construct()
    {

        parent::__construct(
            '0.1',
            'Ville Nyman',
            [
                self::SETTING_ENABLED => 'boolean',
            ]
        );
    }

     /**
     * @return StudentGroupPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $entityPath = $this->getEntityPath();

        if (!is_dir($entityPath)) {
            if (!is_writable(dirname($entityPath))) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ErrorCreatingDir').' '.$entityPath,
                        'error'
                    )
                );

                return false;
            }

            mkdir($entityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $entityPath, null, ['override']);

        $this->createPluginTables();

        $this->install_course_fields_in_all_courses();
    }

    public function uninstall()
    {
        
        // TOTAL UNINSTALL WITH DELETING DATABASE TABLES IS UNCOMMENTED
        // NOW TABLES CONTINUE TO EXIST IF PLUGIN IS DISABLED BY ACCIDENT

       /*  $entityPath = $this->getEntityPath();
        $fs = new Filesystem();

        if ($fs->exists($entityPath)) {
            $fs->remove($entityPath);
        }

        Database::query('DROP TABLE IF EXISTS '.self::TBL_STUDENT_GROUP); */

        $this->deleteCourseToolLinks();
    }

    /**
     * @return StudentGroupPlugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get(self::SETTING_ENABLED)) {
            $courses = $em->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')->getResult();

            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->get_title(), $course['id']);
            }
        }

        return $this;
    }

    /**
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloPluginBundle:StudentGroup\Group e WHERE e.course = :course')
            ->execute(['course' => (int) $courseId]);
    }

    /**
     * @param int $sessionId
     */
    public function doWhenDeletingSession($sessionId)
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloPluginBundle:StudentGroup\Group e WHERE e.session = :session')
            ->execute(['session' => (int) $sessionId]);
    }

    private function createPluginTables()
    {
        $connection = Database::getManager()->getConnection();

        if ($connection->getSchemaManager()->tablesExist(self::TBL_STUDENT_GROUP)) {
            return;
        }

       $query =
            'CREATE TABLE plugin_student_group (
                id VARCHAR(255), 
                course_id INT NOT NULL, 
                teacher_id INT NOT NULL, 
                group_name VARCHAR(255) NOT NULL, 
                group_size INT NOT NULL, 
                PRIMARY KEY (id),
                FOREIGN KEY (course_id) REFERENCES course(id),
                FOREIGN KEY (teacher_id) REFERENCES user(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB';

        Database::query($query);
    }

    /**
     * @return string
     */
    private function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'studentgroup/start.php%']);
    }
}