<?php

/**
 * Course file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Course;

/**
 * Description of Course
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class Course extends AbstractCourse
{
    public const MEDIA_PATH_DEFAULT = ROOT_DIR . MEDIA_PATH_DEFAULT . 'courses/';
    public $publicCourse;
    public $autoSubscription;

    public function __construct($courseId)
    {
        parent::__construct($courseId);
        if (isset($_SESSION['service_level_info'][$this->getServiceLevel()]['isPublic'])) {
            $this->publicCourse = $_SESSION['service_level_info'][$this->getServiceLevel()]['isPublic'] == 1;
        } elseif ($this->id == PUBLIC_COURSE_ID_FOR_NEWS) {
            $this->publicCourse = true;
        } else {
            $this->publicCourse = false;
        }
        $this-> autoSubscription = array_key_exists('autosubscribeServiceTypes', $GLOBALS) && in_array($this->getServiceLevel(), $GLOBALS['autosubscribeServiceTypes']);
    }
    public function getId()
    {
        return parent::getId();
    }
    public function getAuthorId()
    {
        return $this->id_autore;
    }
    public function getLayoutId()
    {
        return $this->id_layout;
    }
    public function getCode()
    {
        return $this->nome;
    }
    public function getTitle()
    {
        return $this->titolo;
    }
    public function getCreationDate()
    {
        return $this->d_create;
    }
    public function getPublicationDate()
    {
        return $this->d_publish;
    }
    public function getDescription()
    {
        return $this->descr;
    }
    public function getRootNodeId()
    {
        return $this->id_nodo_iniziale;
    }
    public function getTableOfContentsNodeId()
    {
        return $this->id_nodo_toc;
    }
    public function getMediaPath()
    {
        return $this->media_path;
    }
    public function getLanguageId()
    {
        return $this->id_lingua;
    }
    public function getStaticMode()
    {
        return $this->static_mode;
    }
    public function getTemplateFamily()
    {
        return $this->template_family;
    }
    public function getCredits()
    {
        return $this->crediti;
    }
    public function isFull()
    {
        return $this->full == true;
    }
    public function getIsPublic()
    {
        return $this->publicCourse;
    }
    public function getDurationHours()
    {
        return $this->duration_hours;
    }
    public function getServiceLevel()
    {
        return $this->service_level;
    }
    public function getAutoSubscription()
    {
        return $this->autoSubscription;
    }
}
