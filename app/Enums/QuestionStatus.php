<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Incomplete = 'incomplete';
    case Pending = 'pending';
    case ForRoleUserAction = 'for_role_user_action';
    case RoleUserActionsAccepted = 'role_user_actions_accepted';
    case RoleUserActionsNotAccepted = 'role_user_actions_not_accepted';
    case Done = 'done';
}
