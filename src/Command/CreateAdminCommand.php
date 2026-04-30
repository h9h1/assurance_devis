<?php



namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Crée un compte administrateur')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email',    InputArgument::REQUIRED, 'Email')
            ->addArgument('name',     InputArgument::REQUIRED, 'Nom complet')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $user = new AdminUser();
        $user->setEmail($input->getArgument('email'));
        $user->setName($input->getArgument('name'));
        $user->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));
        $this->em->persist($user);
        $this->em->flush();
        $io->success(sprintf('Admin "%s" (%s) créé avec succès.', $user->getName(), $user->getEmail()));
        return Command::SUCCESS;
    }
}
