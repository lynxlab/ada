<?php

use Lynxlab\ADA\Services\Exercise\StandardExerciseCorrection;

use Lynxlab\ADA\Services\Exercise\OpenUploadExerciseCorrection;

use Lynxlab\ADA\Services\Exercise\OpenManualExerciseCorrection;

use Lynxlab\ADA\Services\Exercise\OpenAutomaticExerciseCorrection;

use Lynxlab\ADA\Services\Exercise\ExerciseCorrectionFactory;

use Lynxlab\ADA\Services\Exercise\ClozeExerciseCorrection;

// Trigger: ClassWithNameSpace. The class ExerciseCorrectionFactory was declared with namespace Lynxlab\ADA\Services\Exercise. //

namespace Lynxlab\ADA\Services\Exercise;

class ExerciseCorrectionFactory
{
    public static function create($exercise_type)
    {
        switch ($exercise_type) {
            case ADA_STANDARD_EXERCISE_TYPE:
            default:
                return new StandardExerciseCorrection();

            case ADA_OPEN_MANUAL_EXERCISE_TYPE:
                return new OpenManualExerciseCorrection();

            case ADA_OPEN_AUTOMATIC_EXERCISE_TYPE:
                return new OpenAutomaticExerciseCorrection();

            case ADA_CLOZE_EXERCISE_TYPE:
                return new ClozeExerciseCorrection();

            case ADA_OPEN_UPLOAD_EXERCISE_TYPE:
                return new OpenUploadExerciseCorrection();
        }
    }
}
