<?php

namespace App\Command;

use App\Entity\SiteSettings;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:bootstrap', description: 'Создаёт базовые настройки и первого администратора без удаления данных')]
final class BootstrapCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Логин администратора')
            ->addArgument('password', InputArgument::OPTIONAL, 'Пароль администратора')
            ->addOption('reset-password', null, InputOption::VALUE_NONE, 'Обновить пароль существующего администратора');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->em->getRepository(SiteSettings::class)->findOneBy(['code' => 'main'])) {
            $this->em->persist(new SiteSettings());
        }

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        if ($username && $password) {
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
            if (!$user) {
                $user = (new User())->setUsername($username)->setRoles(['ROLE_ADMIN']);
                $this->em->persist($user);
            }
            if (!$user->getPassword() || $input->getOption('reset-password')) {
                $user->setPassword($this->hasher->hashPassword($user, $password));
            }
        }

        $this->em->flush();
        $output->writeln('<info>Базовые данные готовы.</info>');

        return Command::SUCCESS;
    }
}
