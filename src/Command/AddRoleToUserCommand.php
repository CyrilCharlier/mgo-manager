<?php
// src/Command/AddRoleToUserCommand.php

namespace App\Command;

use App\Entity\User; // Assurez-vous d'importer votre classe User
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddRoleToUserCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:add-role-to-user')
            ->setDescription('Adds a role to a user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
            ->addArgument('role', InputArgument::REQUIRED, 'The role to add.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $username */
        $username = (string)$input->getArgument('username');
        /** @var string $role */
        $role = (string)$input->getArgument('role');

        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var \App\Entity\User|null $user */
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $output->writeln('User not found.');
            return Command::FAILURE;
        }
        /** @var list<string> $roles */
        $roles = $user->getRoles();
        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
            $user->setRoles($roles);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln(sprintf('Role "%s" added to user "%s".', $role, $username));
        } else {
            $output->writeln(sprintf('User "%s" already has the role "%s".', $username, $role));
        }

        return Command::SUCCESS;
    }
}
