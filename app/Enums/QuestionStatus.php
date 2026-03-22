<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Incomplete = 'incomplete';
    case Pending = 'pending';
    case ForRoleUserAction = 'for_role_user_action';
    case NeedPeopleValidateResponse = 'need_people_validate_response';
    case ForRoleUserSecondAction = 'for_role_user_second_action';
    case RoleUserActionsAccepted = 'role_user_actions_accepted';
    case RoleUserActionsNotAccepted = 'role_user_actions_not_accepted';
    case Done = 'done';
}
