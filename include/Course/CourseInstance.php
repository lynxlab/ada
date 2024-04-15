<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\Course\CourseInstance;

use Lynxlab\ADA\Main\Course\Course;

use Lynxlab\ADA\Main\Course\AbstractCourseInstance;

// Trigger: ClassWithNameSpace. The class CourseInstance was declared with namespace Lynxlab\ADA\Main\Course. //

/**
 * CourseInstance file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Course;

use function Lynxlab\ADA\Main\Utilities\ts2dFN;

/**
 * Description of CourseInstance
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class CourseInstance extends AbstractCourseInstance
{
    public function __construct($courseInstanceId)
    {
        parent::__construct($courseInstanceId);
    }
    public function getId()
    {
        return $this->id;
    }
    public function getCourseId()
    {
        return $this->id_corso;
    }
    public function getStartDate()
    {
        if ($this->data_inizio > 0) {
            return ts2dFN($this->data_inizio);
        }
        return '';
    }
    public function getDuration()
    {
        return $this->durata;
    }
    public function getScheduledStartDate()
    {
        if ($this->data_inizio_previsto > 0) {
            return ts2dFN($this->data_inizio_previsto);
        }
        return '';
    }
    public function getLayoutId()
    {
        return $this->id_layout;
    }
    public function getEndDate()
    {
        if ($this->data_fine > 0) {
            return ts2dFN($this->data_fine);
        }
        return '';
    }

    public function isFull()
    {
        return $this->full == true;
    }

    public function isStarted()
    {
        return $this->data_inizio > 0;
    }

    public function getSelfInstruction()
    {
        return $this->self_instruction;
    }

    public function getSelfRegistration()
    {
        return $this->self_registration;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDurationSubscription()
    {
        return $this->duration_subscription;
    }

    public function getStartLevelStudent()
    {
        return $this->start_level_student;
    }

    public function getOpenSubscription()
    {
        return $this->open_subscription;
    }
    public function getDurationHours()
    {
        return $this->duration_hours;
    }
    public function getServiceLevel()
    {
        return $this->service_level;
    }
    public function isTutorCommunity()
    {
        return (int) $this->getServiceLevel() === ADA_SERVICE_TUTORCOMMUNITY;
    }
}
