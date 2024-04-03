<?php

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
