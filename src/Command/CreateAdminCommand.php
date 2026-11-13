<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Cria o administrador inicial (idempotente).')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED)
             ->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = mb_strtolower($input->getArgument('email'));

        if ($this->users->findOneBy(['email' => $email])) {
            $output->writeln('Administrador já existe.');
            return Command::SUCCESS;
        }

        $admin = new User($email, 'Administrador', User::ROLE_ADMIN);
        $admin->setPassword($this->hasher->hashPassword($admin, $input->getArgument('password')));
        $this->em->persist($admin);
        $this->em->flush();

        $output->writeln("Administrador {$email} criado.");
        return Command::SUCCESS;
    }
}