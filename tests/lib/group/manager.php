<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Group;

use OC\User\User;

class Manager extends \PHPUnit_Framework_TestCase {
	public function testGet() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->get('group1');
		$this->assertNotNull($group);
		$this->assertEquals('group1', $group->getGID());
	}

	public function testGetNoBackend() {
		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);

		$this->assertNull($manager->get('group1'));
	}

	public function testGetNotExists() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(false));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$this->assertNull($manager->get('group1'));
	}

	public function testGetDeleted() {
		$backend = new \OC_Group_Dummy();
		$backend->createGroup('group1');

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->get('group1');
		$group->delete();
		$this->assertNull($manager->get('group1'));
	}

	public function testGetMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(false));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$group = $manager->get('group1');
		$this->assertNotNull($group);
		$this->assertEquals('group1', $group->getGID());
	}

	public function testCreate() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(false));
		$backend->expects($this->once())
			->method('implementsActions')
			->will($this->returnValue(true));
		$backend->expects($this->once())
			->method('createGroup');

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->createGroup('group1');
		$this->assertEquals('group1', $group->getGID());
	}

	public function testCreateExists() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->any())
			->method('groupExists')
			->with('group1')
			->will($this->returnValue(true));
		$backend->expects($this->never())
			->method('createGroup');

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$group = $manager->createGroup('group1');
		$this->assertEquals('group1', $group->getGID());
	}

	public function testSearch() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group1')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$groups = $manager->search('1');
		$this->assertEquals(1, count($groups));
		$group1 = $groups[0];
		$this->assertEquals('group1', $group1->getGID());
	}

	public function testSearchMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group1')));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getGroups')
			->with('1')
			->will($this->returnValue(array('group12', 'group1')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->search('1');
		$this->assertEquals(2, count($groups));
		$group1 = $groups[0];
		$group12 = $groups[1];
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group12', $group12->getGID());
	}

	public function testSearchMultipleBackendsLimitAndOffset() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getGroups')
			->with('1', 2, 1)
			->will($this->returnValue(array('group1')));

		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getGroups')
			->with('1', 1, 0)
			->will($this->returnValue(array('group12')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->search('1', 2, 1);
		$this->assertEquals(2, count($groups));
		$group1 = $groups[0];
		$group12 = $groups[1];
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group12', $group12->getGID());
	}

	public function testGetUserGroups() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend
		 */
		$backend = $this->getMock('\OC_Group_Database');
		$backend->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend);

		$groups = $manager->getUserGroups(new User('user1', null));
		$this->assertEquals(1, count($groups));
		$group1 = $groups[0];
		$this->assertEquals('group1', $group1->getGID());
	}

	public function testGetUserGroupsMultipleBackends() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend1
		 */
		$backend1 = $this->getMock('\OC_Group_Database');
		$backend1->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1')));
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC_Group_Backend $backend2
		 */
		$backend2 = $this->getMock('\OC_Group_Database');
		$backend2->expects($this->once())
			->method('getUserGroups')
			->with('user1')
			->will($this->returnValue(array('group1', 'group2')));

		/**
		 * @var \OC\User\Manager $userManager
		 */
		$userManager = $this->getMock('\OC\User\Manager');
		$manager = new \OC\Group\Manager($userManager);
		$manager->addBackend($backend1);
		$manager->addBackend($backend2);

		$groups = $manager->getUserGroups(new User('user1', null));
		$this->assertEquals(2, count($groups));
		$group1 = $groups[0];
		$group2 = $groups[1];
		$this->assertEquals('group1', $group1->getGID());
		$this->assertEquals('group2', $group2->getGID());
	}
}
