<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin User
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@agrinest.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->userPasswordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        $admin->setIsActive(true);
        $admin->setCreatedAt(new \DateTime());
        $manager->persist($admin);

        // Staff User
        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@agrinest.com');
        $staff->setRoles(['ROLE_STAFF']);
        $hashedPassword = $this->userPasswordHasher->hashPassword($staff, '123456');
        $staff->setPassword($hashedPassword);
        $staff->setIsActive(true);
        $staff->setCreatedAt(new \DateTime());
        $manager->persist($staff);

        // // Regular User
        // $user = new User();
        // $user->setUsername('user');
        // $user->setEmail('user@agrinest.com');
        // $user->setRoles(['ROLE_USER']);
        // $hashedPassword = $this->userPasswordHasher->hashPassword($user, '123456');
        // $user->setPassword($hashedPassword);
        // $user->setIsActive(true);
        // $user->setCreatedAt(new \DateTime());
        // $manager->persist($user);

        $manager->flush();
    }
}

