<?php

namespace App\Controller;

use App\Dto\CreateUserRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(UserRepository $users): JsonResponse
    {
        return $this->json(array_map(fn (User $u) => $u->toArray(), $users->findBy([], ['id' => 'ASC'])));
    }

    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateUserRequest $dto,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($users->findOneBy(['email' => mb_strtolower($dto->email)])) {
            return $this->json(['error' => 'E-mail já cadastrado.'], Response::HTTP_CONFLICT);
        }

        $user = new User($dto->email, $dto->name, $dto->admin ? User::ROLE_ADMIN : User::ROLE_USER);
        $user->setPassword($hasher->hashPassword($user, $dto->password));
        $em->persist($user);
        $em->flush();

        return $this->json($user->toArray(), Response::HTTP_CREATED, ['Location' => '/api/users/' . $user->getId()]);
    }
}