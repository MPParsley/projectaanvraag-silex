<?php

namespace CultuurNet\ProjectAanvraag\User;

use CultuurNet\ProjectAanvraag\JsonAssertionTrait;

class UserRoleStorageTest extends \PHPUnit_Framework_TestCase
{
    use JsonAssertionTrait;

    /**
     * Test UserRoleStorage
     */
    public function testUserRoleStorage()
    {
        $userRoleStorage = new UserRoleStorage(__DIR__ . '/data/config/user_roles.yml');
        $roles = $userRoleStorage->getRoles();

        $this->assertEquals($roles, ['administrator'], 'It correctly returns the available user roles');
        $this->assertEquals($userRoleStorage->getRolesByUserId('948cf2a5-65c5-470e-ab55-97ee4b05f576'), ['administrator'], 'It correctly returns the roles for a given user id');

        // Test roles and ids
        $userRoleStorageIds = new UserRoleStorage(__DIR__ . '/data/config/user_roles.yml');
        $rolesAndUserIds = $userRoleStorageIds->getRolesAndUserIds();

        $expected = [
            'administrator' => [
                '948cf2a5-65c5-470e-ab55-97ee4b05f576',
            ],
        ];

        $this->assertEquals($rolesAndUserIds, $expected, 'It correctly returns the available user roles and user ids');
    }
}
