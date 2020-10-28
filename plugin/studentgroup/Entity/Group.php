<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\StudentGroup;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Group.
 *
 * @package Chamilo\PluginBundle\Entity\StudentGroup
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_student_group")
 */
class Group
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="text")
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="group_size", type="integer")
     */
    private $group_size;

    /**
     * @var string
     *
     * @ORM\Column(name="group_name", type="text")
     */
    private $group_name;


    /**
     * @var int
     *
     * @ORM\Column(name="course_id", type="integer")
     */
    private $course_id;

    /**
     * @var int
     *
     * @ORM\Column(name="teacher_id", type="integer")
     */
    private $teacher;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Group
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->group_name;
    }

    /**
     * @param $name
     *
     * @return Group
     */
    public function setGroupName($name)
    {
        $this->group_name = $name;

        return $this;
    }

     /**
     * @return int
     */
    public function getGroupSize()
    {
        return $this->group_size;
    }

    /**
     * @param $size
     *
     * @return Group
     */
    public function setGroupSize($size)
    {
        $this->group_size = $size;

        return $this;
    }

    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->course_id;
    }

    /**
     * @return Group
     */
    public function setCourseId($course_id)
    {
        $this->course_id = $course_id;

        return $this;
    }

     /**
     * @return int
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * @return Group
     */
    public function setTeacher($teacher)
    {
        $this->teacher = $teacher;

        return $this;
    }

}
 
