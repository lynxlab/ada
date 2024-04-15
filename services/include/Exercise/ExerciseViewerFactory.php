<?php

use Lynxlab\ADA\Services\Exercise\StandardExerciseViewer;

use Lynxlab\ADA\Services\Exercise\OpenUploadExerciseViewer;

use Lynxlab\ADA\Services\Exercise\OpenManualExerciseViewer;

use Lynxlab\ADA\Services\Exercise\OpenAutomaticExerciseViewer;

use Lynxlab\ADA\Services\Exercise\ExerciseViewerFactory;

use Lynxlab\ADA\Services\Exercise\ClozeExerciseViewer;

// Trigger: ClassWithNameSpace. The class ExerciseViewerFactory was declared with namespace Lynxlab\ADA\Services\Exercise. //

namespace Lynxlab\ADA\Services\Exercise;

class ExerciseViewerFactory
{
    public static function create($exercise_type)
    {
        switch ($exercise_type) {
            case ADA_STANDARD_EXERCISE_TYPE:
            default:
                return new StandardExerciseViewer();

            case ADA_OPEN_MANUAL_EXERCISE_TYPE:
                return new OpenManualExerciseViewer();

            case ADA_OPEN_AUTOMATIC_EXERCISE_TYPE:
                return new OpenAutomaticExerciseViewer();

            case ADA_CLOZE_EXERCISE_TYPE:
                return new ClozeExerciseViewer();

            case ADA_OPEN_UPLOAD_EXERCISE_TYPE:
                return new OpenUploadExerciseViewer();
        }
    }
}
