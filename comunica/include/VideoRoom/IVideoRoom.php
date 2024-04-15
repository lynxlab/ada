<?php

use Lynxlab\ADA\Comunica\VideoRoom\VideoRoom;

use Lynxlab\ADA\Comunica\VideoRoom\IVideoRoom;

// Trigger: ClassWithNameSpace. The class IVideoRoom was declared with namespace Lynxlab\ADA\Comunica\VideoRoom. //

namespace Lynxlab\ADA\Comunica\VideoRoom;

interface IVideoRoom
{
    public function addRoom($name = 'service', $sess_id_course_instance = null, $sess_id_user = null, $comment = 'Inserimento automatico via ADA', $num_user = 25, $course_title = 'service', $selected_provider = ADA_PUBLIC_TESTER);
    public function serverLogin();
    public function roomAccess($username, $nome, $cognome, $user_email, $sess_id_user, $id_profile, $selected_provider);
    public function getRoom($id_room);
}
